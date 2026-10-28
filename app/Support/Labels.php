<?php

namespace App\Support;

final class Labels
{
    public const PACKAGE_TYPE = [
        'installed_system' => 'Installed system',
        'equipment_bundle' => 'Equipment only',
    ];

    public const PACKAGE_TYPE_HINT = [
        'installed_system' => 'Supply, installation and commissioning by JFL Solar',
        'equipment_bundle' => 'Equipment supply only — installation not included',
    ];

    public const SYSTEM_TYPE = [
        'on_grid' => 'On-grid',
        'hybrid' => 'Hybrid',
        'off_grid' => 'Off-grid',
    ];

    public const FULFILLMENT = [
        'installed' => 'Installed on site',
        'pickup' => 'Pickup at warehouse',
        'delivery' => 'Delivery',
        'pickup_or_delivery' => 'Pickup or delivery',
        'to_be_confirmed' => 'To be confirmed at quotation',
    ];

    public const PRICING_MODE = [
        'fixed' => 'Fixed price',
        'starting' => 'Starting price',
        'quote' => 'Request a quotation',
    ];

    public const AVAILABILITY = [
        'available' => 'Available to order',
        'on_request' => 'Ask about availability',
        'preorder' => 'Available on pre-order',
        'unavailable' => 'Currently unavailable',
    ];

    public const PROJECT_TYPE = [
        'residential' => 'Residential',
        'commercial' => 'Commercial',
        'industrial' => 'Industrial',
    ];

    public const STATUS = [
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ];

    public const INQUIRY_TYPES = [
        'residential' => 'Residential solar installation',
        'commercial' => 'Commercial or industrial installation',
        'consultancy' => 'Consultancy or site assessment',
        'equipment' => 'Equipment or product quotation',
        'package' => 'Package inquiry',
        'support' => 'After-sales or technical support',
        'other' => 'Something else',
    ];

    public const BILL_RANGES = [
        '' => 'Prefer not to say',
        'under-3k' => 'Below ₱3,000',
        '3k-5k' => '₱3,000 – ₱5,000',
        '5k-10k' => '₱5,000 – ₱10,000',
        '10k-20k' => '₱10,000 – ₱20,000',
        '20k-50k' => '₱20,000 – ₱50,000',
        'over-50k' => 'Above ₱50,000',
        'unsure' => 'Not sure',
    ];

    /** Inquiry types for which the electricity-bill question is useful. */
    public const BILL_RELEVANT = ['', 'residential', 'commercial', 'consultancy', 'package'];
}
