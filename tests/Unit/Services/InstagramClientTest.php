<?php

namespace Tests\Unit\Services;

use App\Services\Integrations\Instagram;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * The Graph API client, with the HTTP call replaced by canned responses (#2404).
 */
class InstagramClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.facebook_system_user_access_token' => 'user-token',
            'app.facebook_system_page_access_token' => 'page-token',
        ]);
        Sleep::fake();
    }

    /**
     * A client whose API calls return the given responses in order.
     *
     * @param array<int, mixed> $responses decoded JSON bodies
     */
    private function client(array $responses): FakeGraphInstagram
    {
        return new FakeGraphInstagram($responses);
    }

    private function base(): string
    {
        return 'https://graph.facebook.com/'.Instagram::API_VERSION.'/';
    }

    public function test_it_reads_the_tokens_from_config(): void
    {
        $client = $this->client([]);

        $this->assertSame('page-token', $client->getPageAccessToken());
        $this->assertGreaterThan(0, $client->getIgUserId());
        $this->assertNull($client->getLastError());
    }

    public function test_upload_photo_creates_an_image_container(): void
    {
        $client = $this->client([['id' => 111]]);

        $this->assertSame(111, $client->uploadPhoto('https://img.test/a.jpg', 'caption'));

        $call = $client->calls[0];
        $this->assertSame('POST', $call['type']);
        $this->assertSame(
            $this->base().$client->getIgUserId().'/media?media_type=IMAGE&image_url=https://img.test/a.jpg&caption=caption&access_token=page-token',
            $call['endpoint']
        );
    }

    public function test_upload_photo_reports_the_graph_error(): void
    {
        $client = $this->client([['error' => [
            'message' => 'Invalid OAuth access token.',
            'code' => 190,
            'error_subcode' => 460,
            'error_user_msg' => 'Log in again.',
        ]]]);

        $this->expectExceptionMessage('photo upload. Invalid OAuth access token.; code=190; subcode=460; Log in again.');

        $client->uploadPhoto('https://img.test/a.jpg', 'caption');
    }

    public function test_an_empty_response_is_reported_as_such(): void
    {
        $client = $this->client([null]);

        $this->expectExceptionMessage('Empty response from Instagram API.');

        $client->uploadCarouselPhoto('https://img.test/a.jpg');
    }

    public function test_an_unexpected_response_is_quoted_in_the_error(): void
    {
        $client = $this->client([['unexpected' => 'shape']]);

        $this->expectExceptionMessage('Unexpected Instagram API response: {"unexpected":"shape"}');

        $client->uploadStoryPhoto('https://img.test/a.jpg', 'caption');
    }

    public function test_an_error_without_details_still_has_a_message(): void
    {
        $client = $this->client([['error' => []]]);

        $this->expectExceptionMessage('Unknown Instagram API error.');

        $client->createCarousel([1], 'caption');
    }

    public function test_upload_carousel_photo_marks_the_item_as_a_carousel_child(): void
    {
        $client = $this->client([['id' => 222]]);

        $this->assertSame(222, $client->uploadCarouselPhoto('https://img.test/a.jpg'));
        $this->assertStringContainsString('&is_carousel_item=true&', $client->calls[0]['endpoint']);
    }

    public function test_upload_story_photo_encodes_the_link(): void
    {
        $client = $this->client([['id' => 333], ['id' => 334]]);

        $this->assertSame(333, $client->uploadStoryPhoto('https://img.test/a.jpg', 'caption', 'https://site.test/events/a b?x=1'));
        $this->assertStringContainsString('media_type=STORIES', $client->calls[0]['endpoint']);
        $this->assertStringEndsWith('&link_url='.urlencode('https://site.test/events/a b?x=1'), $client->calls[0]['endpoint']);

        $client->uploadStoryPhoto('https://img.test/a.jpg', 'caption');
        $this->assertStringNotContainsString('link_url', $client->calls[1]['endpoint']);
    }

    public function test_create_carousel_joins_the_children_and_caps_the_caption(): void
    {
        $client = $this->client([['id' => 444]]);

        $this->assertSame(444, $client->createCarousel([1, 2, 3], str_repeat('é', 2300)));

        $endpoint = $client->calls[0]['endpoint'];
        $this->assertStringContainsString('media_type=CAROUSEL&children='.urlencode('1,2,3').'&', $endpoint);
        // 2200 characters of caption, counted before encoding
        $this->assertStringContainsString('&caption='.urlencode(str_repeat('é', 2200)).'&access_token=', $endpoint);
    }

    public function test_check_status_is_true_once_the_container_has_finished(): void
    {
        $client = $this->client([['status_code' => 'IN_PROGRESS'], ['status_code' => 'FINISHED']]);

        $this->assertTrue($client->checkStatus(555));
        $this->assertCount(2, $client->calls);
        $this->assertSame('GET', $client->calls[0]['type']);
        $this->assertSame($this->base().'555?fields=status_code,status&access_token=page-token', $client->calls[0]['endpoint']);
    }

    public function test_check_status_stops_on_an_error_status(): void
    {
        $client = $this->client([['status_code' => 'ERROR', 'status' => 'Error: media could not be fetched']]);

        $this->assertFalse($client->checkStatus(555));
        $this->assertSame('Container 555 returned ERROR status: Error: media could not be fetched', $client->getLastError());
    }

    public function test_check_status_stops_when_the_api_returns_an_error_object(): void
    {
        $client = $this->client([['error' => ['message' => 'Unsupported get request.', 'code' => 100]]]);

        $this->assertFalse($client->checkStatus(555));
        $this->assertSame('Container 555 status check failed: Unsupported get request.; code=100', $client->getLastError());
        $this->assertCount(1, $client->calls);
    }

    public function test_check_status_gives_up_on_a_container_that_never_finishes(): void
    {
        $client = $this->client(array_fill(0, 20, ['status_code' => 'IN_PROGRESS', 'status' => 'In progress']));

        $this->assertFalse($client->checkStatus(555));
        $this->assertCount(16, $client->calls);
        $this->assertStringStartsWith('Container 555 did not finish after 15 polls (last status_code=IN_PROGRESS', $client->getLastError());
    }

    public function test_check_batch_status_with_nothing_to_check_is_true(): void
    {
        $client = $this->client([]);

        $this->assertTrue($client->checkBatchStatus([]));
        $this->assertSame([], $client->calls);
    }

    public function test_check_batch_status_polls_until_every_container_has_finished(): void
    {
        $client = $this->client([
            // first pass stops at the first unfinished container
            ['status_code' => 'FINISHED'], ['status_code' => 'IN_PROGRESS'],
            ['status_code' => 'FINISHED'], ['status_code' => 'FINISHED'],
        ]);

        $this->assertTrue($client->checkBatchStatus([1, 2]));
        $this->assertCount(4, $client->calls);
        Sleep::assertSleptTimes(1);
    }

    public function test_check_batch_status_stops_on_an_error_status(): void
    {
        $client = $this->client([['status_code' => 'FINISHED'], ['status_code' => 'ERROR', 'status' => 'bad image']]);

        $this->assertFalse($client->checkBatchStatus([1, 2]));
        $this->assertSame('Container 2 returned ERROR status: bad image', $client->getLastError());
    }

    public function test_check_batch_status_stops_when_the_api_returns_an_error_object(): void
    {
        $client = $this->client([['error' => ['message' => 'Rate limited', 'code' => 4]]]);

        $this->assertFalse($client->checkBatchStatus([1, 2]));
        $this->assertSame('Container 1 status check failed: Rate limited; code=4', $client->getLastError());
    }

    public function test_check_batch_status_gives_up_after_five_attempts(): void
    {
        $client = $this->client(array_fill(0, 10, ['status_code' => 'IN_PROGRESS']));

        $this->assertFalse($client->checkBatchStatus([1, 2]));
        $this->assertCount(5, $client->calls);
        $this->assertStringStartsWith('Batch did not finish after 5 attempts (last container=1, status_code=IN_PROGRESS', $client->getLastError());
        Sleep::assertSleptTimes(4);
    }

    public function test_publish_media_returns_the_media_id(): void
    {
        $client = $this->client([['id' => 777]]);

        $this->assertSame(777, $client->publishMedia(444));
        $this->assertSame('POST', $client->calls[0]['type']);
        $this->assertSame(
            $this->base().$client->getIgUserId().'/media_publish?creation_id=444&access_token=page-token',
            $client->calls[0]['endpoint']
        );
    }

    public function test_publish_media_returns_false_and_keeps_the_error(): void
    {
        $client = $this->client([['error' => ['message' => 'Media ID is not available', 'code' => 9007]]]);

        $this->assertFalse($client->publishMedia(444));
        $this->assertSame('publishMedia(container 444): Media ID is not available; code=9007', $client->getLastError());
    }

    public function test_publish_story_media_returns_the_media_id_or_false(): void
    {
        $client = $this->client([['id' => 888], null]);

        $this->assertSame(888, $client->publishStoryMedia(444));
        $this->assertFalse($client->publishStoryMedia(445));
        $this->assertSame('publishStoryMedia(container 445): Empty response from Instagram API.', $client->getLastError());
    }
}

/**
 * The client with the HTTP call replaced: records each call and answers with
 * the next canned response.
 */
class FakeGraphInstagram extends Instagram
{
    /** @var array<int, array{type: string, endpoint: string}> */
    public array $calls = [];

    /**
     * @param array<int, mixed> $responses decoded JSON bodies
     */
    public function __construct(private array $responses)
    {
        parent::__construct();
    }

    protected function makeApiCall(string $endpoint, string $type, array $params): array
    {
        $this->calls[] = ['type' => $type, 'endpoint' => $endpoint];

        return [
            'type' => $type,
            'endpoint' => $endpoint,
            'params' => $params,
            'api_endpoint' => $endpoint,
            'data' => array_shift($this->responses),
        ];
    }
}
