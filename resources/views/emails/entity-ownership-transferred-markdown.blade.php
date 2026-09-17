@component('mail::message')
Hello {{ $previousOwner->name }},

An admin approved a claim for **[{{ $entity->name }}]({{ $entityUrl }})**, so ownership of the page has moved to its new owner. You can no longer edit it.

If you think this is wrong, please [contact the site admin](mailto:{{ $adminEmail }}).

Thanks!
{{ $site }}
@endcomponent
