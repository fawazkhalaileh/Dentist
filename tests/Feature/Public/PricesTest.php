<?php

namespace Tests\Feature\Public;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricesTest extends TestCase
{
    use RefreshDatabase;

    public function test_treatments_show_the_english_price_list(): void
    {
        $response = $this->get('/en/services')->assertOk()->assertSee('id="prices-heading"', false);
        foreach (config('site.prices') as $group) {
            $response->assertSee($group['label']['en']);
            foreach ($group['items'] as $item) {
                $response->assertSee($item['label']['en']);
                if ($item['amount'] !== null) {
                    $response->assertSee($item['amount'].' JD');
                }
                if (isset($item['note'])) {
                    $response->assertSee($item['note']['en']);
                }
            }
        }
        $response->assertSee('From')
            ->assertSee('Price depends on your treatment plan')
            ->assertSee('Prices are a guide. Your exact price is confirmed after an examination.');
    }

    public function test_treatments_show_the_arabic_price_list(): void
    {
        $response = $this->get('/ar/services')->assertOk()->assertSee('الأسعار');
        foreach (config('site.prices') as $group) {
            $response->assertSee($group['label']['ar']);
            foreach ($group['items'] as $item) {
                $response->assertSee($item['label']['ar']);
                if ($item['amount'] !== null) {
                    $response->assertSee($item['amount'].' د.أ');
                }
            }
        }
        $response->assertSee('ابتداءً من')->assertSee('يُحدَّد السعر حسب خطة علاجك')
            ->assertSee('الأسعار استرشادية. تُحدَّد التكلفة الدقيقة بعد الفحص.');
    }

    public function test_priced_treatments_show_starting_prices_in_both_booking_areas(): void
    {
        foreach (['whitening' => 150, 'implants' => 500] as $key => $amount) {
            foreach (['en' => "Starting from {$amount} JD", 'ar' => "ابتداءً من {$amount} د.أ"] as $locale => $text) {
                $response = $this->get("/{$locale}/services/{$key}")->assertOk()
                    ->assertSee($text)
                    ->assertDontSee(__('Sample treatment page, to be confirmed by the clinic'));
                $this->assertSame(2, substr_count($response->getContent(), $text));
            }
        }

        $this->get('/en/services/cosmetic')->assertOk()
            ->assertSee('Sample treatment page, to be confirmed by the clinic');
    }

    public function test_llms_lists_the_owner_supplied_prices(): void
    {
        $response = $this->get('/llms.txt')->assertOk()
            ->assertSee('## Prices (JD, starting from)')
            ->assertSee('Clear aligners: from 1500 JD (Depends on the complexity of the case)')
            ->assertSee('Single implant: from 500 JD (Per tooth, including the zirconia crown)')
            ->assertSee('All-on-X (full-arch implants): Price depends on your treatment plan');
        foreach (config('site.prices') as $group) {
            foreach ($group['items'] as $item) {
                $response->assertSee($item['label']['en']);
            }
        }
    }

    public function test_homepage_clinic_json_ld_has_the_price_range_and_currency(): void
    {
        $html = $this->get('/en')->assertOk()->getContent();
        preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);
        $schemas = array_map(fn (string $json) => json_decode($json, true, flags: JSON_THROW_ON_ERROR), $matches[1]);
        $clinic = collect($schemas)->firstWhere('@type', 'Dentist');
        $this->assertNotNull($clinic);
        $this->assertSame('JD 30 – 1,500', $clinic['priceRange']);
        $this->assertSame('JOD', $clinic['currenciesAccepted']);
    }
}
