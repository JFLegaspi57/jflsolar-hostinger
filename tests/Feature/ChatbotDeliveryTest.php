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
        cache()->forget('chatbot-system-v15');
        $prompt = Chatbot::systemPrompt();
        $this->assertStringContainsString('Cash on delivery (COD) is not available', $prompt);
        $this->assertStringContainsString('[Delivery Rates](/delivery-rates)', $prompt);
        $this->assertStringContainsString('Batangas City 89 km '.DeliveryRates::peso(DeliveryRates::vanFee(89)).' / '.DeliveryRates::peso(DeliveryRates::longVanFee(89)), $prompt);
        $this->assertStringNotContainsString('delivery is not included', $prompt);
        $this->assertLessThan(90000, mb_strlen($prompt));   // keep the prompt a sensible size
        $this->assertStringContainsString('[Make a Quotation](/quotation)', $prompt);
        $this->assertStringContainsString('years ≈ rated cycle life ÷ (cycles per day × 365)', $prompt);
        $this->assertStringContainsString('Q: What does JFL Solar do?', $prompt);
        $this->assertStringContainsString('(/brands/trina-solar)', $prompt);
    }

    public function test_replies_say_solar_pv_system(): void
    {
        $this->assertSame('A 6 kW hybrid solar PV system and two Solar PV Systems; ang solar PV sistema mo', Chatbot::pvWording('A 6 kW hybrid solar system and two Solar Systems; ang solar sistema mo'));
        $this->assertSame('Your solar PV system is fine.', Chatbot::pvWording('Your solar PV system is fine.'));
        $this->assertSame('A 51.2V battery, two 51.2V LiFePO4 batteries, a 51.2V battery pack; the battery is 51.2V.', Chatbot::pvWording('A 48V battery, two 48 V LiFePO4 batteries, a 48-volt battery pack; the battery is 48V.'));
        $this->assertSame('Works with 48V inverters.', Chatbot::pvWording('Works with 48V inverters.'));
    }
}
