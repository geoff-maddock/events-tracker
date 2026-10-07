<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\EmailPreferences;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;

/**
 * Logged-out unsubscribe and email preferences (#2103).
 *
 * Every route here is behind the `signed` middleware and nothing else: the
 * signature in the link from the email is the credential. Links don't expire,
 * so an unsubscribe link in an old digest still works.
 *
 * GET on an unsubscribe link acts immediately, because that is what the
 * recipient clicked it for. The confirmation page links back to the
 * preference page, so a mistaken click (or a link scanner) is one click to undo.
 */
class EmailPreferencesController extends Controller
{
    public function edit(int $id): View
    {
        $user = User::with('profile')->findOrFail($id);

        return view('email-preferences.edit-tw', [
            'recipient' => $user,
            'lists' => EmailPreferences::LISTS,
            'updateUrl' => EmailPreferences::preferencesUrl($user),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = User::with('profile')->findOrFail($id);
        $profile = $user->profile;

        if ($profile) {
            $keep = $request->boolean('unsubscribe_all') ? [] : (array) $request->input('lists', []);

            foreach (EmailPreferences::LISTS as $list => $definition) {
                $profile->{$definition['setting']} = in_array($list, $keep, true) ? 1 : 0;
            }
            $profile->save();
        }

        flash()->success('Saved', 'Your email preferences were updated.');

        return redirect()->to(EmailPreferences::preferencesUrl($user));
    }

    /** The link a recipient clicks: unsubscribe from one list, then confirm. */
    public function unsubscribe(int $id, string $list): View
    {
        $user = User::with('profile')->findOrFail($id);
        abort_unless(EmailPreferences::isList($list), 404);

        EmailPreferences::unsubscribe($user, $list);

        return view('email-preferences.unsubscribed-tw', [
            'listLabel' => EmailPreferences::LISTS[$list]['label'],
            'preferencesUrl' => EmailPreferences::preferencesUrl($user),
        ]);
    }

    /**
     * RFC 8058 one-click: the mail client POSTs "List-Unsubscribe=One-Click"
     * to the List-Unsubscribe URL with no cookies or CSRF token, so this path
     * is CSRF-exempt and answers with no page.
     */
    public function oneClick(int $id, string $list): Response
    {
        $user = User::with('profile')->findOrFail($id);
        abort_unless(EmailPreferences::isList($list), 404);

        EmailPreferences::unsubscribe($user, $list);

        return response()->noContent();
    }

    /** An entity contact address (not a user) opting out of entity mail. */
    public function unsubscribeContact(Request $request): View
    {
        EmailPreferences::unsubscribeContact($this->contactEmail($request));

        return view('email-preferences.contact-unsubscribed-tw');
    }

    public function oneClickContact(Request $request): Response
    {
        EmailPreferences::unsubscribeContact($this->contactEmail($request));

        return response()->noContent();
    }

    private function contactEmail(Request $request): string
    {
        $email = $request->query('email');
        abort_unless(is_string($email) && $email !== '', 404);

        return $email;
    }
}
