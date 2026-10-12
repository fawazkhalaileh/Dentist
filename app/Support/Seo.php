<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Helpers for hreflang alternates and schema.org structured data.
 */
final class Seo
{
    /**
     * Alternate URLs for a translated record: only locales that actually exist.
     *
     * @param  Collection<int, covariant Model>  $translations  rows with `locale` and `slug`
     * @param  array<string, mixed>  $params  extra route parameters
     * @return array<string, string>
     */
    public static function alternates(Collection $translations, string $route, array $params = []): array
    {
        $urls = [];

        foreach ($translations as $translation) {
            $locale = (string) $translation->getAttribute('locale');
            $urls[$locale] = route($route, [...$params, 'locale' => $locale, 'slug' => $translation->getAttribute('slug')]);
        }

        // Keep the configured locale order.
        $ordered = [];
        foreach (Locales::supported() as $locale) {
            if (isset($urls[$locale])) {
                $ordered[$locale] = $urls[$locale];
            }
        }

        return $ordered;
    }

    /**
     * schema.org description of the clinic (type "Dentist").
     *
     * @return array<string, mixed>
     */
    public static function clinic(): array
    {
        $address = ClinicDetails::get('address');

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Dentist',
            'name' => ClinicDetails::name(),
            'url' => route('public.home'),
            'telephone' => ClinicDetails::get('phone'),
            'email' => ClinicDetails::get('email'),
            'address' => $address,
            'openingHours' => ClinicDetails::get('opening_hours'),
            'hasMap' => ClinicDetails::get('map_url'),
        ]);
    }

    /**
     * @param  list<array{question: string, answer: string}>  $faqs
     * @return array<string, mixed>|null
     */
    public static function faqPage(array $faqs): ?array
    {
        if ($faqs === []) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn (array $faq) => [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
            ], $faqs),
        ];
    }
}
