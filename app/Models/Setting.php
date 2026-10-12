<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Key/value clinic settings. Read through App\Support\ClinicDetails (cached).
 *
 * @property string $key
 * @property array<string, mixed> $value
 * @property int|null $updated_by
 */
#[Fillable(['key', 'value', 'updated_by'])]
class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}
