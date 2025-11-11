<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Entity;
use App\Models\EventType;
use App\Models\User;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstagramFormatTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /**
     * Test that Event getInstagramFormat includes additional handles from config.
     *
     * @return void
     */
    public function test_event_instagram_format_includes_additional_handles()
    {
        // Set the config value
        config(['app.instagram_additional_handles' => '@pgh.events,@another.handle']);

        $user = User::factory()->create();
        $eventType = EventType::first();
        $visibility = Visibility::first();

        $event = Event::factory()->create([
            'name' => 'Test Event',
            'slug' => 'test-event',
            'short' => 'A test event',
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'event_type_id' => $eventType->id,
            'visibility_id' => $visibility->id,
        ]);

        $format = $event->getInstagramFormat();

        $this->assertStringContainsString('@pgh.events', $format);
        $this->assertStringContainsString('@another.handle', $format);
    }

    /**
     * Test that Event getInstagramFormat works without additional handles.
     *
     * @return void
     */
    public function test_event_instagram_format_works_without_additional_handles()
    {
        // Set the config value to empty
        config(['app.instagram_additional_handles' => '']);

        $user = User::factory()->create();
        $eventType = EventType::first();
        $visibility = Visibility::first();

        $event = Event::factory()->create([
            'name' => 'Test Event',
            'slug' => 'test-event',
            'short' => 'A test event',
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'event_type_id' => $eventType->id,
            'visibility_id' => $visibility->id,
        ]);

        $format = $event->getInstagramFormat();

        // Should still generate a valid format
        $this->assertNotEmpty($format);
        $this->assertStringContainsString('Test Event', $format);
    }

    /**
     * Test that Event getInstagramFormat handles whitespace in config.
     *
     * @return void
     */
    public function test_event_instagram_format_handles_whitespace()
    {
        // Set the config value with extra whitespace
        config(['app.instagram_additional_handles' => ' @pgh.events , @another.handle ']);

        $user = User::factory()->create();
        $eventType = EventType::first();
        $visibility = Visibility::first();

        $event = Event::factory()->create([
            'name' => 'Test Event',
            'slug' => 'test-event',
            'short' => 'A test event',
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'event_type_id' => $eventType->id,
            'visibility_id' => $visibility->id,
        ]);

        $format = $event->getInstagramFormat();

        $this->assertStringContainsString('@pgh.events', $format);
        $this->assertStringContainsString('@another.handle', $format);
    }

    /**
     * Test that Entity getInstagramFormat includes additional handles from config.
     *
     * @return void
     */
    public function test_entity_instagram_format_includes_additional_handles()
    {
        // Set the config value
        config(['app.instagram_additional_handles' => '@pgh.events,#pittsburgh']);

        $user = User::factory()->create();

        $entity = Entity::factory()->create([
            'name' => 'Test Venue',
            'slug' => 'test-venue',
            'description' => 'A test venue',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $format = $entity->getInstagramFormat();

        $this->assertStringContainsString('@pgh.events', $format);
        $this->assertStringContainsString('#pittsburgh', $format);
    }

    /**
     * Test that Entity getInstagramFormat works without additional handles.
     *
     * @return void
     */
    public function test_entity_instagram_format_works_without_additional_handles()
    {
        // Set the config value to empty
        config(['app.instagram_additional_handles' => '']);

        $user = User::factory()->create();

        $entity = Entity::factory()->create([
            'name' => 'Test Venue',
            'slug' => 'test-venue',
            'description' => 'A test venue',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $format = $entity->getInstagramFormat();

        // Should still generate a valid format
        $this->assertNotEmpty($format);
        $this->assertStringContainsString('Test Venue', $format);
    }

    /**
     * Test that additional handles with hashtags are properly included.
     *
     * @return void
     */
    public function test_instagram_format_supports_hashtags()
    {
        // Set the config value with both handles and hashtags
        config(['app.instagram_additional_handles' => '@pgh.events,#pittsburghrocks,#events']);

        $user = User::factory()->create();
        $eventType = EventType::first();
        $visibility = Visibility::first();

        $event = Event::factory()->create([
            'name' => 'Test Event',
            'slug' => 'test-event',
            'short' => 'A test event',
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'event_type_id' => $eventType->id,
            'visibility_id' => $visibility->id,
        ]);

        $format = $event->getInstagramFormat();

        $this->assertStringContainsString('@pgh.events', $format);
        $this->assertStringContainsString('#pittsburghrocks', $format);
        $this->assertStringContainsString('#events', $format);
    }
}
