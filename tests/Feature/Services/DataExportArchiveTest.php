<?php

namespace Tests\Feature\Services;

use App\Models\Comment;
use App\Models\Entity;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Follow;
use App\Models\Series;
use App\Models\SurveyCampaign;
use App\Models\SurveyResponse;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use App\Services\DataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;
use ZipArchive;

/**
 * The personal data export as the user receives it: the zip, what is in it,
 * where it is kept and when it is removed (#2404).
 */
class DataExportArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /** A folder of this test's own: storage/ is shared by the parallel test processes. */
    private string $root;

    private DataExportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir().'/zz-export-test-'.uniqid('', true);
        $root = $this->root;

        $this->service = new class($root) extends DataExportService {
            public function __construct(private string $root)
            {
            }

            protected function privateExportRoots(): array
            {
                return [$this->root.'/private'];
            }

            protected function exportDirectories(): array
            {
                return [$this->root.'/private', $this->root.'/public'];
            }
        };
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    private function user(): User
    {
        /** @var User $user */
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $user->profile()->create(['bio' => 'Zz export bio']);

        return $user->fresh();
    }

    /**
     * Run the export and return the decoded JSON files of its zip, by name.
     *
     * @return array<string, mixed>
     */
    private function export(User $user): array
    {
        $result = $this->service->generateExport($user);

        $this->assertSame($this->root.'/private/'.$result['filename'], $result['zipPath']);
        $this->assertFileExists($result['zipPath']);
        $this->assertSame($result['filename'], basename($result['zipPath']));

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($result['zipPath']));
        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            $files[$name] = json_decode((string) $zip->getFromIndex($i), true, 512, JSON_THROW_ON_ERROR);
        }
        $zip->close();

        return $files;
    }

    #[Test]
    public function the_zip_holds_one_json_file_per_kind_of_data(): void
    {
        $files = $this->export($this->user());

        $expected = [
            'blogs.json', 'comments.json', 'constants.json', 'entities.json', 'event_responses.json', 'events.json',
            'follows.json', 'photos_metadata.json', 'posts.json', 'profile.json', 'series.json', 'survey_responses.json',
        ];
        $names = array_keys($files);
        sort($names);

        $this->assertSame($expected, $names);
    }

    #[Test]
    public function the_export_has_what_the_user_made_followed_and_answered(): void
    {
        $user = $this->user();
        $event = Event::factory()->create(['name' => 'Zz Exported Event', 'created_by' => $user->id]);
        Comment::factory()->on($event)->create(['message' => 'Zz exported comment', 'created_by' => $user->id]);

        $entity = Entity::factory()->create(['name' => 'Zz Followed Entity']);
        $tag = Tag::factory()->create();
        $series = Series::factory()->create(['name' => 'Zz Followed Series']);
        $thread = Thread::factory()->create(['name' => 'Zz Followed Thread']);
        foreach ([$entity, $tag, $series, $thread] as $followed) {
            Follow::factory()->of($followed)->create(['user_id' => $user->id]);
        }

        $attended = Event::factory()->create(['name' => 'Zz Attended Event']);
        EventResponse::factory()->create(['event_id' => $attended->id, 'user_id' => $user->id]);

        SurveyResponse::factory()->create([
            'survey_campaign_id' => SurveyCampaign::bySlug(SurveyCampaign::POST_EVENT_ATTENDEE)->id,
            'user_id' => $user->id,
        ]);

        // someone else's data stays out
        $other = $this->user();
        Event::factory()->create(['name' => 'Zz Other Event', 'created_by' => $other->id]);
        Follow::factory()->of(Entity::factory()->create(['name' => 'Zz Other Entity']))->create(['user_id' => $other->id]);

        $files = $this->export($user);

        $this->assertSame(['Zz Exported Event'], array_column($files['events.json'], 'name'));
        $this->assertSame(['Zz exported comment'], array_column($files['comments.json'], 'message'));
        $this->assertSame(['Zz Followed Entity'], array_column($files['follows.json']['entities'], 'name'));
        $this->assertSame([$tag->slug], array_column($files['follows.json']['tags'], 'slug'));
        $this->assertSame(['Zz Followed Series'], array_column($files['follows.json']['series'], 'name'));
        $this->assertSame(['Zz Followed Thread'], array_column($files['follows.json']['threads'], 'name'));
        $this->assertSame(['Zz Attended Event'], array_column($files['event_responses.json'], 'event_name'));
        $this->assertSame(['Attending'], array_column($files['event_responses.json'], 'response_type'));
        $this->assertCount(1, $files['survey_responses.json']);
        $this->assertSame($user->email, $files['profile.json']['email']);
        $this->assertSame('Zz export bio', $files['profile.json']['profile']['bio']);
        $this->assertNotEmpty($files['constants.json']['response_types']);
    }

    #[Test]
    public function the_working_folder_is_removed_whether_or_not_the_zip_is_made(): void
    {
        $this->export($this->user());
        $this->assertSame([], glob($this->root.'/private/tmp/*') ?: []);

        // a failure part-way must not leave the data lying unzipped
        $failing = new class($this->root) extends DataExportService {
            public function __construct(private string $root)
            {
            }

            protected function privateExportRoots(): array
            {
                return [$this->root.'/private'];
            }

            protected function createZipArchive(string $sourceDir, string $zipPath): void
            {
                throw new \RuntimeException('Cannot create ZIP file');
            }
        };

        try {
            $failing->generateExport($this->user());
            $this->fail('the export should have thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('Cannot create ZIP file', $e->getMessage());
        }

        $this->assertSame([], glob($this->root.'/private/tmp/*') ?: []);
    }

    #[Test]
    public function the_zip_is_only_handed_out_through_its_signed_link(): void
    {
        $this->withExceptionHandling();
        $this->app->instance(DataExportService::class, $this->service);

        $result = $this->service->generateExport($this->user());

        // nothing is copied to public storage, where the address alone would do
        $this->assertFileDoesNotExist(storage_path('app/public/exports/'.$result['filename']));
        $this->assertSame($result['zipPath'], $this->service->findExportPath($result['filename']));

        $link = $this->service->getDownloadUrl($result['filename']);
        $this->assertStringContainsString('/exports/download/'.$result['filename'], $link);

        // no sign-in needed: the link is opened from an email
        $download = $this->get($link)->assertOk();
        $file = $download->baseResponse;
        $this->assertInstanceOf(BinaryFileResponse::class, $file);
        $this->assertSame($result['zipPath'], $file->getFile()->getPathname());
        $this->assertStringContainsString($result['filename'], (string) $download->headers->get('content-disposition'));

        // without the signature, or with another file's name under it, there is nothing
        $this->get('/exports/download/'.$result['filename'])->assertForbidden();
        $this->get(str_replace($result['filename'], 'user_data_export_other.zip', $link))->assertForbidden();

        // a link outlives neither its seven days nor its file
        $this->travel(DataExportService::KEEP_DAYS)->days();
        $this->travel(1)->minutes();
        $this->get($link)->assertForbidden();
        $this->travelBack();

        unlink($result['zipPath']);
        $this->get($link)->assertNotFound();
    }

    #[Test]
    public function old_exports_are_removed_from_every_folder_that_holds_one(): void
    {
        $result = $this->service->generateExport($this->user());
        // a copy left in public storage by an earlier version
        File::ensureDirectoryExists($this->root.'/public');
        $public = $this->root.'/public/'.$result['filename'];
        copy($result['zipPath'], $public);

        $this->service->cleanupOldExports(7);
        $this->assertFileExists($result['zipPath']);
        $this->assertFileExists($public);

        touch($result['zipPath'], time() - 60 * 60 * 24 * 8);
        touch($public, time() - 60 * 60 * 24 * 8);
        $this->service->cleanupOldExports(7);

        $this->assertFileDoesNotExist($result['zipPath']);
        $this->assertFileDoesNotExist($public);
    }

    #[Test]
    public function the_clean_up_covers_the_folder_exports_are_written_to(): void
    {
        // The zip goes to the first writable private folder, storage/framework/exports,
        // which the clean-up never looked at: exports stayed there for good (#2404).
        $service = new DataExportService();
        $directories = (new ReflectionMethod($service, 'exportDirectories'))->invoke($service);
        $roots = (new ReflectionMethod($service, 'privateExportRoots'))->invoke($service);

        $this->assertSame(storage_path('framework/exports'), $roots[0]);
        $this->assertSame([], array_diff($roots, $directories));
        $this->assertContains(storage_path('app/public/exports'), $directories);
    }
}
