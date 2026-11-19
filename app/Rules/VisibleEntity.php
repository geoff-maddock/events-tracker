<?php

namespace App\Rules;

use App\Models\Entity;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The value is the id of an entity the user can see, or one the record being
 * edited already refers to (so an unlisted venue kept by the form still passes).
 *
 * Replaces `exists:entities,id`, which looks at the table directly and so
 * accepts ids of unlisted entities from anyone.
 */
class VisibleEntity implements ValidationRule
{
    /** @var array<int, int> */
    private array $alreadyReferenced;

    /**
     * @param array<int, int|string|null> $alreadyReferenced ids the record already refers to
     */
    public function __construct(array $alreadyReferenced = [])
    {
        $this->alreadyReferenced = array_map('intval', array_filter($alreadyReferenced));
    }

    /**
     * The rule for a request editing the given event or series (or creating one).
     */
    public static function for(mixed $record): self
    {
        return new self(is_object($record) ? [$record->venue_id ?? null, $record->promoter_id ?? null] : []);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_numeric($value)) {
            $fail('The selected :attribute is invalid.');

            return;
        }

        if (in_array((int) $value, $this->alreadyReferenced, true)) {
            return;
        }

        if (!Entity::whereKey((int) $value)->exists()) {
            $fail('The selected :attribute is invalid.');
        }
    }
}
