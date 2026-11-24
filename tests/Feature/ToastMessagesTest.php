<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Toast messages (#2397): the layout has the live regions, and a flash()
 * message reaches the page as data for the toast script.
 */
class ToastMessagesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_every_page_has_the_two_live_regions(): void
    {
        $html = $this->get('/tags')->assertOk()->getContent();

        $this->assertSame(1, preg_match_all('/<div id="toast-alerts" role="alert"/', $html));
        $this->assertSame(1, preg_match_all('/<div id="toast-status" role="status"/', $html));
        $this->assertStringNotContainsString('id="flash-data"', $html);
        // the old placeholders
        $this->assertStringNotContainsString('id="loading"', $html);
        $this->assertStringNotContainsString('id="flash"', $html);
    }

    public function test_a_flash_message_is_passed_to_the_page_as_json(): void
    {
        $html = $this->withSession(['flash_message' => [
            'title' => 'Error',
            'message' => 'No such "thread" <b>',
            'level' => 'error',
        ]])->get('/tags')->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<script type="application\/json" id="flash-data">(.*?)<\/script>/s', $html, $match));
        $this->assertSame(
            ['title' => 'Error', 'message' => 'No such "thread" <b>', 'level' => 'error'],
            json_decode($match[1], true)
        );
        // no popup that takes focus and closes itself
        $this->assertStringNotContainsString('Swal.fire', $match[0]);
        $this->assertStringNotContainsString('timer: 2500', $html);
    }

    public function test_a_plain_string_flash_is_shown_as_an_info_message(): void
    {
        $html = $this->withSession(['flash_message' => 'Saved'])->get('/tags')->assertOk()->getContent();

        preg_match('/id="flash-data">(.*?)<\/script>/s', $html, $match);

        $this->assertSame(['title' => '', 'message' => 'Saved', 'level' => 'info'], json_decode($match[1], true));
    }

    public function test_the_failed_request_handlers_report_to_the_visitor(): void
    {
        $script = file_get_contents(public_path('js/custom.js'));

        // one deliberate silent failure: an embed that doesn't load
        $this->assertSame(1, preg_match_all('/\.fail\(function \(\) \{/', $script));
        $this->assertSame(4, preg_match_all('/\.fail\(function \(xhr\) \{\s+showRequestFailure\(xhr,/', $script));
        // SweetAlert 11 has no "type" option
        $this->assertStringNotContainsString('type: "success"', $script);
    }
}
