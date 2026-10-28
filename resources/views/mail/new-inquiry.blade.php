{{-- Plain-text email: values are printed raw (no HTML escaping needed in text/plain). --}}
@foreach ($rows as $k => $v)
{!! $k !!}: {!! $v !!}
@endforeach

Message:
{!! $message !!}

Open in the website admin: {!! $adminUrl !!}

— Sent from the {!! $site !!} website inquiry form
