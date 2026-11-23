<?php

namespace Tests\Feature;

use App\Mail\EntityUpdateSummary;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Group;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The "send update summary" action on an entity page, which runs through
 * App\Services\EntityUpdateSummarySender (#2407). The opt-out and the
 * public-events-only rule have their own tests.
 */
class EntityUpdateSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $superAdmin;

    private Entity $entity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        Mail::fake();

        $this->superAdmin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->superAdmin->groups()->attach(Group::firstOrCreate(['name' => 'super_admin'])->id);
        $this->entity = Entity::factory()->create();
    }

    public function test_the_summary_goes_to_the_entitys_contact(): void
    {
        $contact = Contact::create(['name' => 'Booking', 'email' => 'zz-booker@example.com']);
        $this->entity->contacts()->attach($contact->id);

        $this->actingAs($this->superAdmin)->get("/entities/{$this->entity->id}/send-update-summary")
            ->assertRedirect()
            ->assertSessionHas('flash_message.message', 'Update summary sent to zz-booker@example.com');

        Mail::assertSent(EntityUpdateSummary::class, fn (EntityUpdateSummary $mail) => $mail->hasTo('zz-booker@example.com'));
    }

    public function test_an_entity_without_a_contact_email_gets_no_summary(): void
    {
        $this->actingAs($this->superAdmin)->get("/entities/{$this->entity->id}/send-update-summary")
            ->assertRedirect()
            ->assertSessionHas('flash_message.message', 'This entity has no contact email address on file.');

        Mail::assertNothingSent();
    }

    public function test_only_a_super_admin_may_send_the_summary(): void
    {
        $contact = Contact::create(['name' => 'Booking', 'email' => 'zz-booker@example.com']);
        $this->entity->contacts()->attach($contact->id);
        $member = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($member)->get("/entities/{$this->entity->id}/send-update-summary")
            ->assertSessionHas('flash_message.message', 'You are not authorized to perform this action.');

        Mail::assertNothingSent();
    }
}
