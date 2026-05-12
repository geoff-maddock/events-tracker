<?php

namespace Tests\Feature\Console;

use App\Mail\AdminMailer;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminTestCommandTest extends TestCase
{
    public function test_command_sends_admin_mailer_to_configured_admin(): void
    {
        Mail::fake();

        config()->set('app.admin', 'admin@example.com');

        $this->artisan('adminTest')->assertExitCode(0);

        Mail::assertSent(AdminMailer::class, function ($mail) {
            return $mail->hasTo('admin@example.com');
        });
    }
}
