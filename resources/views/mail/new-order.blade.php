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
@if ($preview)
STAFF PREVIEW: no order was saved. This is the copy staff receive for each new order.

@endif
@if ($printUrl)
Printable summary: {!! $printUrl !!}
@endif
@if ($adminUrl)
Open in the website admin: {!! $adminUrl !!}
@endif

— Sent from the {!! $site !!} online shop
