<?php

namespace App\Mail\Concerns;

use App\Models\User;
use App\Services\EmailPreferences;
use Illuminate\Mail\Mailables\Headers;

/**
 * List-Unsubscribe headers and the footer unsubscribe link for bulk mail
 * (#2103). Transactional mail (verification, password reset, claims) does not
 * use this.
 *
 * The header carries RFC 8058 one-click: Gmail and Yahoo POST
 * "List-Unsubscribe=One-Click" to the URL, which turns off just this list.
 * The footer links to the preference page instead, where the recipient can
 * pick which lists to keep.
 *
 * The mailable's build() must call withUnsubscribeLink() so the footer gets
 * the link; headers() is picked up by Mailable on its own.
 */
trait HasUnsubscribeLink
{
    /**
     * The URLs for this message, or null when there is nobody to unsubscribe
     * (e.g. a digest built without a user).
     *
     * @return array{oneClick: string, page: string}|null
     */
    abstract protected function unsubscribeUrls(): ?array;

    public function headers(): Headers
    {
        $urls = $this->unsubscribeUrls();
        if ($urls === null) {
            return new Headers();
        }

        return new Headers(text: [
            'List-Unsubscribe' => '<'.$urls['oneClick'].'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    /**
     * Pass the footer link to the view as $unsubscribeUrl.
     *
     * @return $this
     */
    protected function withUnsubscribeLink(): static
    {
        return $this->with('unsubscribeUrl', $this->unsubscribeUrls()['page'] ?? null);
    }

    /**
     * URLs for a user list.
     *
     * @return array{oneClick: string, page: string}|null
     */
    protected function userUnsubscribeUrls(?User $user, string $list): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'oneClick' => EmailPreferences::unsubscribeUrl($user, $list),
            'page' => EmailPreferences::preferencesUrl($user),
        ];
    }

    /**
     * URLs for mail to an entity contact, keyed by the address the message is
     * actually going to. Under notifyEntities --test-run that is the test
     * address, so a test click never opts out the real contact.
     *
     * @return array{oneClick: string, page: string}|null
     */
    protected function contactUnsubscribeUrls(): ?array
    {
        $email = $this->to[0]['address'] ?? null;
        if (!$email) {
            return null;
        }

        $url = EmailPreferences::contactUnsubscribeUrl($email);

        return ['oneClick' => $url, 'page' => $url];
    }
}
