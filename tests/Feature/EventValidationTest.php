<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Requests\EventRequest;
use Illuminate\Support\Facades\Validator;

class EventValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /**
     * Test that end_at cannot be before start_at
     */
    public function test_end_at_cannot_be_before_start_at()
    {
        $request = new EventRequest();
        
        $data = [
            'name' => 'Test Event',
            'slug' => 'test-event',
            'start_at' => '2024-12-01 20:00:00',
            'end_at' => '2024-12-01 19:00:00', // Before start_at
            'event_type_id' => 1,
            'visibility_id' => 1,
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());
        
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('end_at'));
        $this->assertStringContainsString('must be after or equal to the start time', $validator->errors()->first('end_at'));
    }

    /**
     * Test that end_at can be equal to start_at
     */
    public function test_end_at_can_be_equal_to_start_at()
    {
        $request = new EventRequest();
        
        $data = [
            'name' => 'Test Event',
            'slug' => 'test-event',
            'start_at' => '2024-12-01 20:00:00',
            'end_at' => '2024-12-01 20:00:00', // Equal to start_at
            'event_type_id' => 1,
            'visibility_id' => 1,
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());
        
        $this->assertFalse($validator->errors()->has('end_at'));
    }

    /**
     * Test that end_at can be after start_at
     */
    public function test_end_at_can_be_after_start_at()
    {
        $request = new EventRequest();
        
        $data = [
            'name' => 'Test Event',
            'slug' => 'test-event',
            'start_at' => '2024-12-01 20:00:00',
            'end_at' => '2024-12-01 22:00:00', // After start_at
            'event_type_id' => 1,
            'visibility_id' => 1,
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());
        
        $this->assertFalse($validator->errors()->has('end_at'));
    }

    /**
     * Test that door_at cannot be after start_at
     */
    public function test_door_at_cannot_be_after_start_at()
    {
        $request = new EventRequest();
        
        $data = [
            'name' => 'Test Event',
            'slug' => 'test-event',
            'start_at' => '2024-12-01 20:00:00',
            'door_at' => '2024-12-01 21:00:00', // After start_at
            'event_type_id' => 1,
            'visibility_id' => 1,
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());
        
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('door_at'));
        $this->assertStringContainsString('must be before or equal to the start time', $validator->errors()->first('door_at'));
    }

    /**
     * Test that door_at can be equal to start_at
     */
    public function test_door_at_can_be_equal_to_start_at()
    {
        $request = new EventRequest();
        
        $data = [
            'name' => 'Test Event',
            'slug' => 'test-event',
            'start_at' => '2024-12-01 20:00:00',
            'door_at' => '2024-12-01 20:00:00', // Equal to start_at
            'event_type_id' => 1,
            'visibility_id' => 1,
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());
        
        $this->assertFalse($validator->errors()->has('door_at'));
    }

    /**
     * Test that door_at can be before start_at
     */
    public function test_door_at_can_be_before_start_at()
    {
        $request = new EventRequest();
        
        $data = [
            'name' => 'Test Event',
            'slug' => 'test-event',
            'start_at' => '2024-12-01 20:00:00',
            'door_at' => '2024-12-01 19:00:00', // Before start_at
            'event_type_id' => 1,
            'visibility_id' => 1,
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());
        
        $this->assertFalse($validator->errors()->has('door_at'));
    }

    /**
     * Test that all fields are optional except required ones
     */
    public function test_door_at_and_end_at_are_optional()
    {
        $request = new EventRequest();
        
        $data = [
            'name' => 'Test Event',
            'slug' => 'test-event',
            'start_at' => '2024-12-01 20:00:00',
            // No door_at or end_at provided
            'event_type_id' => 1,
            'visibility_id' => 1,
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());
        
        $this->assertFalse($validator->errors()->has('door_at'));
        $this->assertFalse($validator->errors()->has('end_at'));
    }

    /**
     * Test valid event with all time fields
     */
    public function test_valid_event_with_all_time_fields()
    {
        $request = new EventRequest();
        
        $data = [
            'name' => 'Test Event',
            'slug' => 'test-event',
            'start_at' => '2024-12-01 20:00:00',
            'door_at' => '2024-12-01 19:30:00', // Before start_at
            'end_at' => '2024-12-01 23:00:00', // After start_at
            'event_type_id' => 1,
            'visibility_id' => 1,
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());
        
        $this->assertFalse($validator->fails(), 'Valid event data should pass validation. Errors: ' . $validator->errors()->toJson());
    }
}