<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An address that opted out of one mailing list without being a user.
 *
 * Users opt out through their profile flags; this is for recipients who have
 * no profile, currently entity contact addresses (NotifyEntities and the
 * entity update summary).
 *
 * @property int    $id
 * @property string $email
 * @property string $list
 */
class EmailOptOut extends Model
{
    /** Entity contact mail: the NotifyEntities reminder and the update summary. */
    public const LIST_ENTITY_CONTACT = 'entity_contact';

    protected $fillable = ['email', 'list'];

    public static function isOptedOut(string $email, string $list): bool
    {
        return static::query()
            ->where('email', EmailSuppression::normalize($email))
            ->where('list', $list)
            ->exists();
    }

    public static function optOut(string $email, string $list): self
    {
        return static::query()->firstOrCreate([
            'email' => EmailSuppression::normalize($email),
            'list' => $list,
        ]);
    }
}
