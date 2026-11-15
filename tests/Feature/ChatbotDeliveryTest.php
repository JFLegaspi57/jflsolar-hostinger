<?php

namespace Tests\Feature;

use App\Support\Chatbot;
use App\Support\DeliveryRates;
use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_buckny_knows_delivery_rules_and_town_rates(): void
    {
        $this->seed();
        PublicContent::flush();
        cache()->forget('chatbot-system-v2');
        $prompt = Chatbot::systemPrompt();
        $this->assertStringContainsString('We do NOT offer cash on delivery (COD).', $prompt);
        $this->assertStringContainsString('[Delivery Rates](/delivery-rates)', $prompt);
        $this->assertStringContainsString('Batangas City 89 km '.DeliveryRates::peso(DeliveryRates::vanFee(89)).' / '.DeliveryRates::peso(DeliveryRates::longVanFee(89)), $prompt);
        $this->assertStringNotContainsString('delivery is not included', $prompt);
        $this->assertLessThan(60000, mb_strlen($prompt));   // keep the prompt a sensible size
    }
}
