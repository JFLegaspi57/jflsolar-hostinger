{{-- Welcome email to a new member (HTML). Same text as the welcome newsletter sent on 17 Nov 2026. --}}
@php
    $a = 'color:#1d4ed8;';
    $h = 'margin:22px 0 4px;font-size:17px;color:#111827;';
    $link = fn (string $path, ?string $label = null) => '<a href="'.e($u($path)).'" style="'.$a.'">'.e($label ?? preg_replace('#^https?://(www\.)?#', '', $u($path))).'</a>';
@endphp
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Welcome to JFL Solar</title></head>
<body style="margin:0;padding:16px;background:#ffffff;">
<div style="font-family:Arial,Helvetica,sans-serif;max-width:680px;margin:0 auto;font-size:15px;line-height:1.6;color:#111827;">
<p>Hello{{ $name !== '' ? ' '.$name : '' }},</p>
<p>On behalf of the whole JFL Solar team, <b>thank you</b> for signing up and for being part of our growing community.</p>
<p>When we started JFL Solar, our goal was simple: to help Filipinos build solar PV systems the right way, with quality materials, honest advice and real technical support. Seeing so many of you join us, ask questions and learn with us means more to us than we can say. You are the reason we keep going.</p>
<p>To help you get the most out of your membership, here is a quick tour of everything you can do on {!! $link('/', 'jflsolar.ph') !!}.</p>

<h3 style="{{ $h }}">1. Your account</h3>
<p style="margin-top:0">Sign in and open the <b>Account</b> menu at the top of the page. From there you can go straight to <b>Your Cart</b>, <b>Your Quotations</b>, <b>Online Course</b>, <b>Your Orders</b> and the <b>Forum</b>. On your account page you can also add a profile photo, update your details and choose whether you receive our promos and news.<br>{!! $link('/account') !!}</p>

<h3 style="{{ $h }}">2. Learn</h3>
<ul style="margin-top:0;padding-left:20px;">
<li><b>Free Online Course: The Fundamentals of Solar PV System</b>. 25 lessons in English and Tagalog, from the basics of solar energy to sizing, protection devices, diagrams, PV*SOL, financial analysis and proposals. Each lesson ends with 7 questions: write your answer and the website tells you right away if you got it right. At the end of each lesson you see if you passed, and your account remembers where you left off. A certificate of completion is available on request for a fee of ₱3,000. It is required that you have completed the whole course without the help of any AI, like ChatGPT. It is issued after an assessment of your learning and a number of on-the-job training (OJT) sessions as your hands-on training with the JFL Solar technical team.<br>{!! $link('/courses/solar-pv-system-design') !!}</li>
<li><b>Technical guides</b>, in English and Filipino: how a hybrid solar PV system works, how a grid-tied solar PV system works, and net metering in the Philippines.<br>{!! $link('/hybrid-pv-system-guide', 'Hybrid guide') !!} · {!! $link('/grid-tied-pv-system-guide', 'Grid-tied guide') !!} · {!! $link('/net-metering', 'Net metering guide') !!}</li>
<li><b>FAQ</b>: answers to the questions we hear most, also in Tagalog.<br>{!! $link('/faq') !!}</li>
<li><b>Blog</b>: notes, lessons and stories from the field and from our visits to the factories of the brands we carry.<br>{!! $link('/blog') !!}</li>
</ul>

<h3 style="{{ $h }}">3. Plan your solar PV system</h3>
<ul style="margin-top:0;padding-left:20px;">
<li><b>PV Calculator</b>: get an idea of how much solar you need.<br>{!! $link('/pv-calculator') !!}</li>
<li><b>Packages</b>: Materials Only bundles, <b>Build Your Own</b> with a price for every part, and Complete packages with installation. You can also compare packages side by side.<br>{!! $link('/packages') !!}</li>
<li><b>Make a Quotation</b>: put together a quotation for your own solar PV system and keep it in your account.<br>{!! $link('/quotation') !!}</li>
<li><b>Services</b>: what JFL Solar can do for you.<br>{!! $link('/services') !!}</li>
</ul>

<h3 style="{{ $h }}">4. Buy</h3>
<ul style="margin-top:0;padding-left:20px;">
<li><b>Products</b>: our online shop. Add items to your cart and check out for pickup.<br>{!! $link('/products') !!}</li>
<li><b>Our Brands</b>: the brands we have tested and trust.<br>{!! $link('/brands') !!}</li>
<li><b>Payment Methods</b>: cash, GCash, bank transfer, check (it must clear before pickup) and card at our warehouse (credit card payments have additional charges). We do not offer cash on delivery (COD).<br>{!! $link('/payment-methods') !!}</li>
<li><b>Delivery Rates</b>: besides pickup, we also deliver via Lalamove. See the estimated delivery fees from our warehouse in Dasmariñas, Cavite.<br>{!! $link('/delivery-rates') !!}</li>
</ul>

<h3 style="{{ $h }}">5. Community and help</h3>
<ul style="margin-top:0;padding-left:20px;">
<li><b>Forum</b>: ask questions, share your setup and help other members.<br>{!! $link('/forum') !!}</li>
<li><b>Buckny, our chat assistant</b>: tap the chat button at the bottom right of any page for quick answers.</li>
<li><b>Projects</b>: photos and details of the residential, commercial and industrial solar PV systems we have worked on.<br>{!! $link('/projects') !!}</li>
<li><b>Contact us</b>: send an inquiry, or call or text our Sales team at <b>{{ $phone }}</b>.<br>{!! $link('/contact') !!}</li>
</ul>

<p>Once again, maraming salamat po! We are truly grateful for your trust and support.</p>
<p>Learn from the best... learn it the right way!</p>
<p>Warm regards,<br><b>JF Legaspi</b><br>and the whole JFL Solar team<br>{!! $link('/', 'www.jflsolar.ph') !!}</p>
<p style="margin-top:24px;font-size:12px;color:#6b7280;">You are receiving this email because you created a member account on jflsolar.ph.@if ($unsubscribeUrl) To stop our promos and news, <a href="{{ $unsubscribeUrl }}" style="color:#6b7280;">unsubscribe here</a>.@else You can choose whether you receive our promos and news on your <a href="{{ $u('/account') }}" style="color:#6b7280;">account page</a>.@endif</p>
</div>
</body>
</html>
