<?php

namespace Tests\Feature;

use App\Mail\EntityClaimDecided;
use App\Mail\EntityClaimSubmitted;
use App\Mail\EntityOwnershipTransferred;
use App\Models\Entity;
use App\Models\EntityClaim;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Self-serve entity claims with admin approval and full ownership transfer (#2148).
 */
class EntityClaimTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        Mail::fake();
    }

    private function makeUser(?string $group = null, bool $verified = true): User
    {
        $user = User::factory()->create([
            'user_status_id' => UserStatus::ACTIVE,
            'email_verified_at' => $verified ? now() : null,
        ]);
        if ($group) {
            $user->assignGroup($group);
        }

        return $user;
    }

    private function claim(Entity $entity, User $claimant): EntityClaim
    {
        return EntityClaim::create([
            'entity_id' => $entity->id,
            'user_id' => $claimant->id,
            'message' => 'I book every show at this venue and run its Instagram.',
        ]);
    }

    // Filing

    public function test_guest_is_sent_to_login(): void
    {
        $entity = Entity::factory()->create();

        $this->get(route('entities.claim.create', $entity))->assertRedirect('/login');
    }

    public function test_unverified_user_cannot_file_a_claim(): void
    {
        $entity = Entity::factory()->create();

        $this->actingAs($this->makeUser(verified: false))
            ->post(route('entities.claim.store', $entity), ['message' => 'I am the artist and this is my page.'])
            ->assertRedirect();

        $this->assertDatabaseCount('entity_claims', 0);
    }

    public function test_user_can_file_a_claim_and_admins_are_emailed(): void
    {
        $entity = Entity::factory()->create(['created_by' => $this->makeUser()->id]);
        $claimant = $this->makeUser();

        $this->actingAs($claimant)->get(route('entities.claim.create', $entity))->assertOk();
        $this->actingAs($claimant)
            ->post(route('entities.claim.store', $entity), [
                'message' => 'I am the artist and this is my page.',
                'evidence_url' => 'https://example.bandcamp.com',
            ])
            ->assertRedirect(route('entities.show', $entity));

        $this->assertDatabaseHas('entity_claims', [
            'entity_id' => $entity->id,
            'user_id' => $claimant->id,
            'status' => EntityClaim::STATUS_PENDING,
            'evidence_url' => 'https://example.bandcamp.com',
        ]);
        Mail::assertSent(EntityClaimSubmitted::class, fn ($mail) => $mail->hasTo(config('app.admin')));

        // filing a claim grants nothing until it is approved
        $this->assertFalse($entity->fresh()->isOwnedBy($claimant));
    }

    public function test_a_second_pending_claim_from_the_same_user_is_rejected(): void
    {
        $entity = Entity::factory()->create();
        $claimant = $this->makeUser();
        $this->claim($entity, $claimant);

        $this->actingAs($claimant)
            ->post(route('entities.claim.store', $entity), ['message' => 'I am the artist and this is my page.'])
            ->assertRedirect(route('entities.claim.create', $entity));

        $this->assertSame(1, EntityClaim::where('user_id', $claimant->id)->count());
    }

    public function test_an_owner_cannot_claim_their_own_page(): void
    {
        $owner = $this->makeUser();
        $entity = Entity::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($owner)
            ->post(route('entities.claim.store', $entity), ['message' => 'I am the artist and this is my page.'])
            ->assertRedirect(route('entities.show', $entity));

        $this->assertDatabaseCount('entity_claims', 0);
    }

    public function test_claim_message_is_required(): void
    {
        $entity = Entity::factory()->create();

        $this->actingAs($this->makeUser())
            ->post(route('entities.claim.store', $entity), ['message' => 'mine'])
            ->assertSessionHasErrors('message');

        $this->assertDatabaseCount('entity_claims', 0);
    }

    public function test_claimant_can_withdraw_but_others_cannot(): void
    {
        $entity = Entity::factory()->create();
        $claimant = $this->makeUser();
        $claim = $this->claim($entity, $claimant);

        $this->actingAs($this->makeUser())->post(route('entity-claims.withdraw', $claim))->assertForbidden();
        $this->assertTrue($claim->fresh()->isPending());

        $this->actingAs($claimant)->post(route('entity-claims.withdraw', $claim))->assertRedirect();
        $this->assertSame(EntityClaim::STATUS_WITHDRAWN, $claim->fresh()->status);
    }

    // Reviewing

    public function test_non_admin_cannot_see_the_queue_or_decide(): void
    {
        $entity = Entity::factory()->create();
        $claimant = $this->makeUser();
        $claim = $this->claim($entity, $claimant);

        $this->actingAs($claimant)->get(route('entity-claims.index'))->assertForbidden();
        $this->actingAs($claimant)->post(route('entity-claims.approve', $claim))->assertForbidden();
        $this->actingAs($claimant)->post(route('entity-claims.deny', $claim))->assertForbidden();

        $this->assertTrue($claim->fresh()->isPending());
        $this->assertFalse($entity->fresh()->isOwnedBy($claimant));
    }

    public function test_approval_transfers_ownership_and_locks_out_the_previous_owner(): void
    {
        $creator = $this->makeUser();
        $entity = Entity::factory()->create(['created_by' => $creator->id, 'name' => 'Original Name']);
        $claimant = $this->makeUser();
        $rival = $this->makeUser();
        $claim = $this->claim($entity, $claimant);
        $rivalClaim = $this->claim($entity, $rival);
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get(route('entity-claims.index'))->assertOk()->assertSee($claimant->name);

        $this->actingAs($admin)
            ->post(route('entity-claims.approve', $claim), ['review_note' => 'Confirmed via Instagram DM.'])
            ->assertRedirect(route('entity-claims.index'));

        $entity = $entity->fresh();
        $this->assertTrue($entity->isOwnedBy($claimant));
        $this->assertFalse($entity->isOwnedBy($creator));
        $this->assertSame($creator->id, (int) $entity->created_by);

        $claim = $claim->fresh();
        $this->assertSame(EntityClaim::STATUS_APPROVED, $claim->status);
        $this->assertSame($admin->id, (int) $claim->reviewed_by);
        $this->assertNotNull($claim->reviewed_at);
        $this->assertSame(EntityClaim::STATUS_DENIED, $rivalClaim->fresh()->status);

        Mail::assertSent(EntityClaimDecided::class, fn ($mail) => $mail->hasTo($claimant->email));
        Mail::assertSent(EntityClaimDecided::class, fn ($mail) => $mail->hasTo($rival->email));
        Mail::assertSent(EntityOwnershipTransferred::class, fn ($mail) => $mail->hasTo($creator->email));

        // the previous owner can no longer edit or delete; the new owner can
        $this->actingAs($creator)->get(route('entities.edit', $entity))->assertRedirect(route('entities.show', $entity));
        $this->actingAs($creator)->delete(route('entities.destroy', $entity))->assertForbidden();
        $this->actingAs($claimant)->get(route('entities.edit', $entity))->assertOk();
    }

    public function test_deny_keeps_ownership_and_emails_the_claimant(): void
    {
        $owner = $this->makeUser();
        $entity = Entity::factory()->create(['created_by' => $owner->id]);
        $claimant = $this->makeUser();
        $claim = $this->claim($entity, $claimant);

        $this->actingAs($this->makeUser('admin'))
            ->post(route('entity-claims.deny', $claim), ['review_note' => 'Could not confirm.'])
            ->assertRedirect(route('entity-claims.index'));

        $this->assertSame(EntityClaim::STATUS_DENIED, $claim->fresh()->status);
        $this->assertSame('Could not confirm.', $claim->fresh()->review_note);
        $this->assertTrue($entity->fresh()->isOwnedBy($owner));
        $this->assertFalse($entity->fresh()->isOwnedBy($claimant));
        Mail::assertSent(EntityClaimDecided::class, fn ($mail) => $mail->hasTo($claimant->email));
        Mail::assertNotSent(EntityOwnershipTransferred::class);
    }

    public function test_a_decided_claim_cannot_be_approved_again(): void
    {
        $owner = $this->makeUser();
        $entity = Entity::factory()->create(['created_by' => $owner->id]);
        $claim = $this->claim($entity, $this->makeUser());
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->post(route('entity-claims.deny', $claim))->assertRedirect();
        $this->actingAs($admin)->post(route('entity-claims.approve', $claim))->assertRedirect();

        $this->assertSame(EntityClaim::STATUS_DENIED, $claim->fresh()->status);
        $this->assertTrue($entity->fresh()->isOwnedBy($owner));
    }

    public function test_entity_page_offers_claim_to_non_owners_only(): void
    {
        $owner = $this->makeUser();
        $entity = Entity::factory()->create(['created_by' => $owner->id]);

        $this->get(route('entities.show', $entity))->assertSee('Claim this page');
        $this->actingAs($this->makeUser())->get(route('entities.show', $entity))->assertSee('Claim this page');
        $this->actingAs($owner)->get(route('entities.show', $entity))->assertDontSee('Claim this page');
    }

    public function test_claim_emails_and_admin_modules_badge_render(): void
    {
        $owner = $this->makeUser();
        $entity = Entity::factory()->create(['created_by' => $owner->id]);
        $claim = $this->claim($entity, $this->makeUser());
        $claim->forceFill(['evidence_url' => 'https://example.com/proof', 'review_note' => 'Confirmed.'])->save();

        $this->assertStringContainsString('I book every show', (new EntityClaimSubmitted($claim))->render());
        $claim->status = EntityClaim::STATUS_APPROVED;
        $this->assertStringContainsString('approved', (new EntityClaimDecided($claim))->render());
        $claim->status = EntityClaim::STATUS_DENIED;
        $this->assertStringContainsString('not approved', (new EntityClaimDecided($claim))->render());
        $this->assertStringContainsString($entity->name, (new EntityOwnershipTransferred($entity, $owner))->render());

        $this->actingAs($this->makeUser('admin'))
            ->get(route('pages.allModules'))
            ->assertOk()
            ->assertSee('Entity Claims');
    }
}
