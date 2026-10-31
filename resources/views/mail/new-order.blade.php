{{-- Plain-text email: values are printed raw (no HTML escaping needed in text/plain). --}}
@foreach ($rows as $k => $v)
{!! $k !!}: {!! $v !!}
@endforeach

Items:
@foreach ($lines as $line)
- {!! $line !!}
@endforeach
@if ($notes)

Customer notes:
{!! $notes !!}
@endif

@if ($sendDetails)
Remember to send the customer your {!! $sendDetails !!} when you confirm the order.

@endif
Open in the website admin: {!! $adminUrl !!}

— Sent from the {!! $site !!} online shop
