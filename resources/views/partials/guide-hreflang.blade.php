{{-- English and Tagalog versions of a guide point at each other for search engines. $en is the English path; the Tagalog page is $en/tagalog. --}}
<link rel="alternate" hreflang="en" href="{{ \App\Support\Site::url($en) }}">
<link rel="alternate" hreflang="fil" href="{{ \App\Support\Site::url($en.'/tagalog') }}">
<link rel="alternate" hreflang="x-default" href="{{ \App\Support\Site::url($en) }}">
