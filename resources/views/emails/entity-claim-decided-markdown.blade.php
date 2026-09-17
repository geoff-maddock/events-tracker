@component('mail::message')
Hello {{ $claim->user->name }},

@if ($approved)
Your claim for **{{ $claim->entity->name }}** was **approved**. You now own the page and can edit its details, links, locations, contacts and photos.

@component('mail::button', ['url' => $entityUrl])
Go to your page
@endcomponent
@else
Your claim for **{{ $claim->entity->name }}** was **not approved**.

If you think this was a mistake, reply with more detail about how you're affiliated, or [contact the site admin](mailto:{{ $adminEmail }}).
@endif

@if ($claim->review_note)
**Note from the reviewer:** {{ $claim->review_note }}
@endif

Thanks!
{{ $site }}
@endcomponent
