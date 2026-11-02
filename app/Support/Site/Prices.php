<?php

namespace App\Support\Site;

use Illuminate\Support\Facades\Lang;

final class Prices
{
    public static function amount(int $amount): string
    {
        return $amount.' '.config('clinic.currency.symbol.'.app()->getLocale());
    }

    public static function startingFrom(string $treatment): ?string
    {
        $amounts = [];
        foreach (config('site.prices') as $group) {
            foreach ($group['items'] as $item) {
                if ($item['treatment'] === $treatment && $item['amount'] !== null) {
                    $amounts[] = $item['amount'];
                }
            }
        }

        return $amounts === [] ? null : Lang::string('Starting from :price', ['price' => self::amount(min($amounts))]);
    }
}
