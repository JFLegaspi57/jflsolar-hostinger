@if (\App\Support\Shop::previewing())
    <p role="note" class="mt-6 rounded-[var(--radius-control)] border border-[#f0d39a] bg-warn-50 p-4 text-warn-800"><span class="font-semibold">Staff preview.</span> Only signed-in staff can see the shop. Customers don’t see it until online payment is switched on, and checkout here saves no order and charges nothing.</p>
@endif
