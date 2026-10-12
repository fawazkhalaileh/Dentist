<?php

namespace App\Livewire\Forms;

use App\Support\ClinicDetails;
use App\Support\Locales;
use Livewire\Form;

class ClinicDetailsForm extends Form
{
    public string $phone = '';

    public string $email = '';

    public string $whatsapp = '';

    public string $map_url = '';

    /** @var array<string, string> */
    public array $name = [];

    /** @var array<string, string> */
    public array $address = [];

    /** @var array<string, string> */
    public array $opening_hours = [];

    public function load(): void
    {
        $details = ClinicDetails::all();

        $this->phone = $details['phone'] ?? '';
        $this->email = $details['email'] ?? '';
        $this->whatsapp = $details['whatsapp'] ?? '';
        $this->map_url = $details['map_url'] ?? '';

        foreach (Locales::supported() as $l) {
            $this->name[$l] = $details['name'][$l] ?? '';
            $this->address[$l] = $details['address'][$l] ?? '';
            $this->opening_hours[$l] = $details['opening_hours'][$l] ?? '';
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = [
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'map_url' => ['nullable', 'url:https', 'max:500'],
        ];

        foreach (Locales::supported() as $l) {
            $rules["name.{$l}"] = ['nullable', 'string', 'max:150'];
            $rules["address.{$l}"] = ['nullable', 'string', 'max:500'];
            $rules["opening_hours.{$l}"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $clean = fn (string $v) => trim($v) === '' ? null : trim($v);

        return [
            'phone' => $clean($this->phone),
            'email' => $clean($this->email),
            'whatsapp' => $clean($this->whatsapp),
            'map_url' => $clean($this->map_url),
            'name' => array_map($clean, $this->name),
            'address' => array_map($clean, $this->address),
            'opening_hours' => array_map($clean, $this->opening_hours),
        ];
    }
}
