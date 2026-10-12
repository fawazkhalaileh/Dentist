<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string $message
 * @property string $locale
 * @property string|null $ip_address
 * @property CarbonImmutable|null $handled_at
 * @property int|null $handled_by
 * @property CarbonImmutable $created_at
 */
#[Fillable(['name', 'email', 'phone', 'message', 'locale', 'ip_address'])]
class ContactMessage extends Model
{
    protected function casts(): array
    {
        return [
            'message' => 'encrypted',
            'handled_at' => 'datetime',
        ];
    }
}
