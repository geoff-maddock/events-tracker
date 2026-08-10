<?php

namespace Tests;

use App\Exceptions\Handler;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp():void
    {
        parent::setUp();

        $this->withoutVite();

        // Prevent tests from depending on external S3/Spaces configuration.
        Storage::fake('external');

        // No test may deliver mail. phpunit.xml sets MAIL_DRIVER=array too,
        // but that is only honoured when the suite is run through that file —
        // this holds even when it is not, and survives a test that rewrites
        // mail config itself.
        //
        // ArrayTransport rather than Mail::fake(): the message is still fully
        // built and rendered, so a broken Blade template in a Mailable still
        // fails the test that exercises it. It just never reaches a transport.
        //
        // config/mail.php is the pre-6.x flat format, so MailManager resolves
        // the transport from mail.driver (see its createSymfonyTransport BC
        // branch). mail.default is set as well in case that file is ever
        // modernised.
        config(['mail.driver' => 'array', 'mail.default' => 'array']);

        // replaces disableExceptionHandling
        // disabled due to Token mismatch on post - not sure when this is needed
        $this->withoutExceptionHandling();
    }

    protected function signIn($user = null)
    {
        $user = $user ?: User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user);

        return $this;
    }
}
