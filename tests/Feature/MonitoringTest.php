<?php

namespace Tests\Feature;

use App\Exceptions\MonitoringAlert;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['queue.default' => 'database', 'app.facebook_system_page_access_token' => null]);
    }

    public function test_health_route_answers_without_starting_a_session(): void
    {
        $response = $this->get('/up');

        $response->assertOk()->assertSee('OK');
        $this->assertSame([], $response->headers->getCookies());
    }

    public function test_health_route_reports_an_unreachable_database(): void
    {
        Exceptions::fake();
        DB::shouldReceive('select')->once()->andThrow(new \RuntimeException('gone away'));

        $this->get('/up')->assertStatus(503);
    }

    public function test_a_job_waiting_too_long_is_reported_once(): void
    {
        Exceptions::fake();
        $this->queueJob(now()->subMinutes(50)->getTimestamp());

        $this->artisan('monitor:check')->assertSuccessful();
        $this->artisan('monitor:check')->assertSuccessful();

        Exceptions::assertReportedCount(1);
        Exceptions::assertReported(fn (MonitoringAlert $e) => str_contains($e->getMessage(), '1 job(s) waiting')
            && str_contains($e->getMessage(), '50 minutes'));
    }

    public function test_a_recent_or_delayed_job_is_not_reported(): void
    {
        Exceptions::fake();
        $this->queueJob(now()->subMinutes(5)->getTimestamp());
        $this->queueJob(now()->addHours(3)->getTimestamp());

        $this->artisan('monitor:check')->assertSuccessful();

        Exceptions::assertNothingReported();
    }

    public function test_a_job_being_worked_is_not_reported(): void
    {
        Exceptions::fake();
        $this->queueJob(now()->subMinutes(50)->getTimestamp(), now()->subMinutes(2)->getTimestamp());

        $this->artisan('monitor:check')->assertSuccessful();

        Exceptions::assertNothingReported();
    }

    public function test_a_job_left_reserved_by_a_dead_worker_is_reported(): void
    {
        Exceptions::fake();
        // retry_after is 21 minutes: offered again 69 minutes ago
        $this->queueJob(now()->subMinutes(95)->getTimestamp(), now()->subMinutes(90)->getTimestamp());

        $this->artisan('monitor:check')->assertSuccessful();

        Exceptions::assertReported(fn (MonitoringAlert $e) => str_contains($e->getMessage(), '1 job(s) waiting')
            && str_contains($e->getMessage(), '69 minutes'));
    }

    public function test_the_queue_check_is_skipped_for_other_drivers(): void
    {
        Exceptions::fake();
        config(['queue.default' => 'sync']);
        $this->queueJob(now()->subMinutes(50)->getTimestamp());

        $this->artisan('monitor:check')->assertSuccessful();

        Exceptions::assertNothingReported();
    }

    public function test_a_sync_queue_in_production_is_reported(): void
    {
        Exceptions::fake();
        config(['queue.default' => 'sync']);
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('monitor:check')->assertSuccessful();

        Exceptions::assertReported(fn (MonitoringAlert $e) => str_contains($e->getMessage(), 'QUEUE_DRIVER=database'));
    }

    public function test_a_token_about_to_expire_is_reported_without_the_token(): void
    {
        Exceptions::fake();
        config(['app.facebook_system_page_access_token' => 'secret-token-value']);
        Http::fake(['graph.facebook.com/*' => Http::response(['data' => [
            'is_valid' => true,
            'expires_at' => now()->addDays(5)->addHour()->getTimestamp(),
            'data_access_expires_at' => now()->addDays(60)->getTimestamp(),
        ]])]);

        $this->artisan('monitor:check')->assertSuccessful();
        // asked once a day
        $this->artisan('monitor:check')->assertSuccessful();

        Http::assertSentCount(1);
        Exceptions::assertReportedCount(1);
        Exceptions::assertReported(fn (MonitoringAlert $e) => str_contains($e->getMessage(), '(5 days)')
            && !str_contains($e->getMessage(), 'secret-token-value'));
    }

    public function test_an_invalid_token_is_reported(): void
    {
        Exceptions::fake();
        config(['app.facebook_system_page_access_token' => 'secret-token-value']);
        Http::fake(['graph.facebook.com/*' => Http::response(['data' => ['is_valid' => false]])]);

        $this->artisan('monitor:check')->assertSuccessful();

        Exceptions::assertReported(fn (MonitoringAlert $e) => str_contains($e->getMessage(), 'no longer valid'));
    }

    /**
     * A dead token can't look itself up, so the Graph API answers with an
     * error and no data.
     */
    public function test_a_token_the_graph_api_rejects_is_reported(): void
    {
        Exceptions::fake();
        config(['app.facebook_system_page_access_token' => 'secret-token-value']);
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['type' => 'OAuthException', 'code' => 190]], 400)]);

        $this->artisan('monitor:check')->assertSuccessful();
        $this->artisan('monitor:check')->assertSuccessful();

        Http::assertSentCount(1);
        Exceptions::assertReportedCount(1);
        Exceptions::assertReported(fn (MonitoringAlert $e) => str_contains($e->getMessage(), 'no longer valid'));
    }

    public function test_the_token_is_not_sent_in_the_url(): void
    {
        config(['app.facebook_system_page_access_token' => 'secret-token-value']);
        Http::fake(['graph.facebook.com/*' => Http::response(['data' => ['is_valid' => true, 'expires_at' => 0]])]);

        $this->artisan('monitor:check')->assertSuccessful();

        Http::assertSent(fn ($request) => !str_contains($request->url(), 'secret-token-value')
            && $request['input_token'] === 'secret-token-value'
            && $request['method'] === 'GET');
    }

    public function test_a_valid_token_is_not_reported(): void
    {
        Exceptions::fake();
        config(['app.facebook_system_page_access_token' => 'secret-token-value']);
        Http::fakeSequence('graph.facebook.com/*')
            ->push(['data' => ['is_valid' => true, 'expires_at' => 0, 'data_access_expires_at' => 0]])
            ->push(['data' => ['is_valid' => true, 'expires_at' => now()->addDays(40)->getTimestamp()]]);

        $this->artisan('monitor:check')->assertSuccessful();
        $this->travel(2)->days();
        $this->artisan('monitor:check')->assertSuccessful();

        Http::assertSentCount(2);
        Exceptions::assertNothingReported();
    }

    public function test_an_unanswered_token_check_is_retried_and_not_reported(): void
    {
        Exceptions::fake();
        config(['app.facebook_system_page_access_token' => 'secret-token-value']);
        Http::fake(['graph.facebook.com/*' => Http::response('Bad gateway', 502)]);

        $this->artisan('monitor:check')->assertSuccessful();
        $this->artisan('monitor:check')->assertSuccessful();

        Http::assertSentCount(2);
        Exceptions::assertNothingReported();
    }

    public function test_the_check_is_scheduled_and_failed_commands_are_reported(): void
    {
        Exceptions::fake();
        $events = collect($this->scheduledEvents());

        $check = $events->first(fn (Event $e) => str_contains($e->command ?? '', 'monitor:check'));
        $this->assertNotNull($check);
        $this->assertSame('*/15 * * * *', $check->expression);

        $notify = $events->first(fn (Event $e) => str_ends_with($e->command ?? '', " notify"));
        $this->assertNotNull($notify);

        $notify->exitCode = 0;
        $notify->callAfterCallbacks($this->app);
        Exceptions::assertNothingReported();

        $notify->exitCode = 2;
        $notify->callAfterCallbacks($this->app);
        Exceptions::assertReported(fn (MonitoringAlert $e) => str_contains($e->getMessage(), 'exit code 2')
            && str_contains($e->getMessage(), 'notify'));
    }

    /**
     * @return Event[]
     */
    private function scheduledEvents(): array
    {
        $this->app->make(Kernel::class);

        return $this->app->make(Schedule::class)->events();
    }

    private function queueJob(int $availableAt, ?int $reservedAt = null): void
    {
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => $reservedAt === null ? 0 : 1,
            'reserved_at' => $reservedAt,
            'available_at' => $availableAt,
            'created_at' => $availableAt,
        ]);
    }
}
