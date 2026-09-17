<?php

namespace App\Http\Controllers;

use App\Mail\EntityClaimDecided;
use App\Mail\EntityClaimSubmitted;
use App\Mail\EntityOwnershipTransferred;
use App\Models\Action;
use App\Models\Activity;
use App\Models\Entity;
use App\Models\EntityClaim;
use App\Models\User;
use App\Services\BestEffortMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Self-serve entity claims (#2148): a user asks to take over an entity page,
 * an admin approves or denies, and approval fully transfers ownership.
 */
class EntityClaimsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('verified', ['only' => ['create', 'store']]);
        $this->middleware('can:grant_entity_ownership', ['only' => ['index', 'approve', 'deny']]);

        parent::__construct();
    }

    /**
     * Admin review queue: pending claims first, then the most recent decisions.
     */
    public function index(): View
    {
        $pending = EntityClaim::pending()
            ->with(['entity.owners', 'user'])
            ->oldest()
            ->get();

        $recent = EntityClaim::where('status', '!=', EntityClaim::STATUS_PENDING)
            ->with(['entity', 'user', 'reviewer'])
            ->latest('updated_at')
            ->limit(25)
            ->get();

        return view('entity-claims.index-tw', compact('pending', 'recent'));
    }

    public function create(Request $request, Entity $entity): View|RedirectResponse
    {
        $user = $request->user();

        if ($entity->isOwnedBy($user)) {
            flash()->success('Already yours', 'You already own this page.');

            return redirect()->route('entities.show', $entity);
        }

        $pendingClaim = EntityClaim::pending()
            ->where('entity_id', $entity->id)
            ->where('user_id', $user->id)
            ->first();

        return view('entity-claims.create-tw', compact('entity', 'pendingClaim'));
    }

    public function store(Request $request, Entity $entity, BestEffortMailer $mailer): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:20', 'max:2000'],
            'evidence_url' => ['nullable', 'url', 'max:500'],
        ]);

        if ($entity->isOwnedBy($user)) {
            flash()->success('Already yours', 'You already own this page.');

            return redirect()->route('entities.show', $entity);
        }

        $alreadyPending = EntityClaim::pending()
            ->where('entity_id', $entity->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyPending) {
            flash()->error('Already requested', 'You already have a pending claim for this page.');

            return redirect()->route('entities.claim.create', $entity);
        }

        $claim = EntityClaim::create($validated + [
            'entity_id' => $entity->id,
            'user_id' => $user->id,
        ]);

        Activity::log($entity, $user, Action::UPDATE, 'Requested ownership (claim #'.$claim->id.')');

        foreach ($this->reviewerEmails() as $email) {
            $mailer->send($email, new EntityClaimSubmitted($claim), ['entity_claim_id' => $claim->id]);
        }

        flash()->success('Claim sent', 'An admin will review your claim and email you when it is decided.');

        return redirect()->route('entities.show', $entity);
    }

    /**
     * The claimant can take back a claim that has not been decided yet.
     */
    public function withdraw(Request $request, EntityClaim $entityClaim): RedirectResponse
    {
        abort_unless((int) $entityClaim->user_id === $request->user()->id, 403);

        if ($entityClaim->isPending()) {
            $entityClaim->status = EntityClaim::STATUS_WITHDRAWN;
            $entityClaim->save();
            flash()->success('Claim withdrawn', 'Your claim has been withdrawn.');
        }

        return redirect()->route('entities.show', $entityClaim->entity);
    }

    /**
     * Approve a claim: the claimant becomes the sole owner, every previous
     * owner loses control, and any other pending claims on the entity are denied.
     */
    public function approve(Request $request, EntityClaim $entityClaim, BestEffortMailer $mailer): RedirectResponse
    {
        $request->validate(['review_note' => ['nullable', 'string', 'max:1000']]);

        if (!$entityClaim->isPending()) {
            flash()->error('Already decided', 'That claim has already been '.$entityClaim->status.'.');

            return redirect()->route('entity-claims.index');
        }

        $reviewer = $request->user();
        $entity = $entityClaim->entity;

        /** @var Collection<int, User> $previousOwners */
        $previousOwners = collect();
        /** @var Collection<int, EntityClaim> $competing */
        $competing = collect();

        DB::transaction(function () use ($entityClaim, $entity, $reviewer, $request, &$previousOwners, &$competing) {
            // lock the entity so two approvals for the same page cannot both win
            Entity::whereKey($entity->id)->lockForUpdate()->first();

            $previousOwners = $entity->owners()->get()
                ->reject(fn (User $owner) => $owner->id === (int) $entityClaim->user_id);

            $entity->syncOwners([$entityClaim->user_id], $reviewer);

            $this->decide($entityClaim, EntityClaim::STATUS_APPROVED, $reviewer, $request->input('review_note'));

            $competing = EntityClaim::pending()
                ->where('entity_id', $entity->id)
                ->whereKeyNot($entityClaim->id)
                ->with('user')
                ->get();
            foreach ($competing as $other) {
                $this->decide($other, EntityClaim::STATUS_DENIED, $reviewer, 'Another claim for this page was approved.');
            }

            Activity::log(
                $entity,
                $reviewer,
                Action::UPDATE,
                'Approved claim #'.$entityClaim->id.': ownership transferred to user '.$entityClaim->user_id
                    .' from ['.$previousOwners->pluck('id')->implode(', ').']'
            );
        });

        $mailer->send($entityClaim->user->email, new EntityClaimDecided($entityClaim), ['entity_claim_id' => $entityClaim->id]);
        foreach ($previousOwners as $owner) {
            /** @var User $owner */
            $mailer->send($owner->email, new EntityOwnershipTransferred($entity, $owner), ['entity_id' => $entity->id, 'user_id' => $owner->id]);
        }
        foreach ($competing as $other) {
            $mailer->send($other->user->email, new EntityClaimDecided($other), ['entity_claim_id' => $other->id]);
        }

        flash()->success('Claim approved', $entityClaim->user->name.' now owns '.$entity->name.'.');

        return redirect()->route('entity-claims.index');
    }

    public function deny(Request $request, EntityClaim $entityClaim, BestEffortMailer $mailer): RedirectResponse
    {
        $request->validate(['review_note' => ['nullable', 'string', 'max:1000']]);

        if (!$entityClaim->isPending()) {
            flash()->error('Already decided', 'That claim has already been '.$entityClaim->status.'.');

            return redirect()->route('entity-claims.index');
        }

        $this->decide($entityClaim, EntityClaim::STATUS_DENIED, $request->user(), $request->input('review_note'));

        Activity::log($entityClaim->entity, $request->user(), Action::UPDATE, 'Denied claim #'.$entityClaim->id);

        $mailer->send($entityClaim->user->email, new EntityClaimDecided($entityClaim), ['entity_claim_id' => $entityClaim->id]);

        flash()->success('Claim denied', 'The claimant has been emailed.');

        return redirect()->route('entity-claims.index');
    }

    protected function decide(EntityClaim $claim, string $status, User $reviewer, ?string $note): void
    {
        $claim->status = $status;
        $claim->reviewed_by = $reviewer->id;
        $claim->reviewed_at = Carbon::now();
        $claim->review_note = $note;
        $claim->save();
    }

    /**
     * Claims are reviewed by admins, so they go to the site admin address.
     *
     * @return array<int, string>
     */
    protected function reviewerEmails(): array
    {
        return array_values(array_filter([config('app.admin')]));
    }
}
