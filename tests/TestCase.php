<?php

namespace Tests;

use App\Exceptions\Handler;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp():void
    {
        parent::setUp();

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
