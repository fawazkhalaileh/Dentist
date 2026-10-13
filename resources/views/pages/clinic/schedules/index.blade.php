<?php

use App\Actions\Scheduling\SaveWeeklySchedule;
use App\Enums\Permission;
use App\Enums\Role;
use App\Models\BlockedPeriod;
use App\Models\User;
use App\Support\Audit;
use App\Support\ClinicTime;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Working hours and closures')] class extends Component {
    #[Url]
    public ?int $dentistId = null;

    /** @var array<int, list<array{start: string, end: string}>> */
    public array $hours = [];

    /** @var array<int, list<array{start: string, end: string, label: string}>> */
    public array $breaks = [];

    /** @var array{dentist_id: string, from_date: string, from_time: string, to_date: string, to_time: string, reason: string} */
    public array $closure = ['dentist_id' => '', 'from_date' => '', 'from_time' => '00:00', 'to_date' => '', 'to_time' => '23:59', 'reason' => ''];

    public function boot(): void
    {
        Gate::authorize(Permission::SchedulesManage->value);
    }

    public function mount(): void
    {
        if (! $this->dentists->contains('id', $this->dentistId)) {
            $this->dentistId = $this->dentists->first()?->id;
        }
        $this->loadSchedule();
        $this->closure['from_date'] = $this->closure['to_date'] = ClinicTime::today();
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function dentists(): Collection
    {
        return User::havingRole(Role::Dentist)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Closures that have not ended yet, clinic-wide and per dentist.
     *
     * @return Collection<int, BlockedPeriod>
     */
    #[Computed]
    public function closures(): Collection
    {
        return BlockedPeriod::with('dentist:id,name')->where('ends_at', '>=', now())->orderBy('starts_at')->get();
    }

    public function updatedDentistId(): void
    {
        abort_unless($this->dentists->contains('id', $this->dentistId), 404);
        $this->resetValidation();
        $this->loadSchedule();
    }

    private function loadSchedule(): void
    {
        $this->hours = $this->breaks = array_fill(0, 7, []);
        if ($this->dentistId === null) {
            return;
        }

        foreach (DB::table('working_hours')->where('dentist_id', $this->dentistId)->orderBy('starts_at')->get() as $row) {
            $this->hours[$row->weekday][] = ['start' => substr($row->starts_at, 0, 5), 'end' => substr($row->ends_at, 0, 5)];
        }
        foreach (DB::table('schedule_breaks')->where('dentist_id', $this->dentistId)->orderBy('starts_at')->get() as $row) {
            $this->breaks[$row->weekday][] = ['start' => substr($row->starts_at, 0, 5), 'end' => substr($row->ends_at, 0, 5), 'label' => (string) $row->label];
        }
    }

    public function addInterval(string $type, int $weekday): void
    {
        abort_unless(in_array($type, ['hours', 'breaks'], true) && $weekday >= 0 && $weekday <= 6, 404);
        $this->{$type}[$weekday][] = $type === 'hours' ? ['start' => '09:00', 'end' => '17:00'] : ['start' => '13:00', 'end' => '14:00', 'label' => ''];
    }

    public function removeInterval(string $type, int $weekday, int $index): void
    {
        abort_unless(in_array($type, ['hours', 'breaks'], true), 404);
        unset($this->{$type}[$weekday][$index]);
        $this->{$type}[$weekday] = array_values($this->{$type}[$weekday]);
    }

    /**
     * Copy one day's hours and breaks to the other days that are still empty,
     * except Friday (the weekend in Jordan); staff can still add Friday hours by hand.
     */
    public function copyDay(int $from): void
    {
        abort_unless($from >= 0 && $from <= 6, 404);
        foreach (range(0, 6) as $day) {
            if ($day !== $from && $this->hours[$day] === [] && ! in_array($day, [5], true)) {
                $this->hours[$day] = $this->hours[$from];
                $this->breaks[$day] = $this->breaks[$from];
            }
        }
    }

    public function saveSchedule(SaveWeeklySchedule $save): void
    {
        abort_unless($this->dentists->contains('id', $this->dentistId), 404);
        $time = ['required', 'date_format:H:i'];

        $this->validate([
            'hours' => ['array', 'size:7'],
            'hours.*' => ['array'],
            'hours.*.*.start' => $time,
            'hours.*.*.end' => $time,
            'breaks' => ['array', 'size:7'],
            'breaks.*' => ['array'],
            'breaks.*.*.start' => $time,
            'breaks.*.*.end' => $time,
            'breaks.*.*.label' => ['nullable', 'string', 'max:100'],
        ]);

        $save->handle(User::findOrFail($this->dentistId), $this->hours, $this->breaks);
        Flux::toast(variant: 'success', text: __('Working hours saved.'));
    }

    public function addClosure(): void
    {
        $this->validate([
            'closure.dentist_id' => ['nullable', Rule::in(['', ...$this->dentists->pluck('id')->map(fn ($id) => (string) $id)->all()])],
            'closure.from_date' => ['required', 'date_format:Y-m-d'],
            'closure.from_time' => ['required', 'date_format:H:i'],
            'closure.to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:closure.from_date'],
            'closure.to_time' => ['required', 'date_format:H:i'],
            'closure.reason' => ['nullable', 'string', 'max:255'],
        ]);

        $starts = ClinicTime::toUtc($this->closure['from_date'], $this->closure['from_time']);
        $ends = ClinicTime::toUtc($this->closure['to_date'], $this->closure['to_time']);
        if ($ends <= $starts) {
            $this->addError('closure.to_time', __('The closure must end after it starts.'));

            return;
        }

        $period = BlockedPeriod::create([
            'dentist_id' => $this->closure['dentist_id'] !== '' ? (int) $this->closure['dentist_id'] : null,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'reason' => trim($this->closure['reason']) ?: null,
            'created_by' => Auth::id(),
        ]);
        Audit::record('schedule.closure_added', $period);

        $this->closure['reason'] = '';
        unset($this->closures);
        Flux::toast(variant: 'success', text: __('Closure added.'));
    }

    public function removeClosure(int $id): void
    {
        $period = BlockedPeriod::findOrFail($id);
        $period->delete();
        Audit::record('schedule.closure_removed', null, ['blocked_period_id' => $id]);
        unset($this->closures);
        Flux::toast(variant: 'success', text: __('Closure removed.'));
    }
}; ?>

<section class="w-full max-w-5xl">
    <x-ui.page-header :title="__('Working hours and closures')" :description="__('Times are in the clinic\'s time zone (:zone).', ['zone' => \App\Support\ClinicTime::zone()])" />

    @if ($this->dentists->isEmpty())
        <x-ui.empty-state :title="__('No dentists yet')" :description="__('Add your dentists first; then set their working hours here.')">
            @can(\App\Enums\Permission::StaffManage->value)
                <flux:button variant="primary" icon="user-plus" :href="route('clinic.staff.create')" wire:navigate>{{ __('Add a dentist') }}</flux:button>
            @endcan
        </x-ui.empty-state>
    @else
        <flux:card class="mb-6 space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <flux:select wire:model.live="dentistId" :label="__('Dentist')" class="max-w-xs">
                    @foreach ($this->dentists as $dentist)
                        <flux:select.option value="{{ $dentist->id }}">{{ $dentist->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:button variant="primary" wire:click="saveSchedule">{{ __('Save working hours') }}</flux:button>
            </div>

            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach (\App\Support\ClinicTime::weekdays() as $day => $dayName)
                    <div class="grid gap-3 py-3 md:grid-cols-[9rem_1fr_1fr]" wire:key="day-{{ $day }}">
                        <div class="font-medium">
                            {{ $dayName }}
                            @if ($hours[$day] !== [])
                                <button type="button" wire:click="copyDay({{ $day }})" class="mt-1 block text-xs font-normal underline">{{ __('Copy to empty days') }}</button>
                            @endif
                        </div>
                        @foreach (['hours' => __('Working hours'), 'breaks' => __('Breaks')] as $type => $typeLabel)
                            <div class="space-y-2">
                                <p class="text-xs text-zinc-500">{{ $typeLabel }}</p>
                                @forelse ($this->{$type}[$day] as $i => $interval)
                                    @php $field = 'h-8 rounded-md border border-zinc-200 bg-white px-2 text-sm dark:border-zinc-600 dark:bg-zinc-800'; @endphp
                                    <div class="flex flex-wrap items-center gap-2" wire:key="{{ $type }}-{{ $day }}-{{ $i }}">
                                        <input type="time" step="900" wire:model="{{ $type }}.{{ $day }}.{{ $i }}.start" class="{{ $field }} w-32" aria-label="{{ __('From') }}" dir="ltr">
                                        <span aria-hidden="true" class="text-zinc-400">–</span>
                                        <input type="time" step="900" wire:model="{{ $type }}.{{ $day }}.{{ $i }}.end" class="{{ $field }} w-32" aria-label="{{ __('To') }}" dir="ltr">
                                        @if ($type === 'breaks')
                                            <input type="text" wire:model="breaks.{{ $day }}.{{ $i }}.label" placeholder="{{ __('Label') }}" class="{{ $field }} w-28" aria-label="{{ __('Label') }}">
                                        @endif
                                        <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="removeInterval('{{ $type }}', {{ $day }}, {{ $i }})" :aria-label="__('Remove')" />
                                    </div>
                                @empty
                                    <p class="text-sm text-zinc-500">{{ $type === 'hours' ? __('Not working') : __('None') }}</p>
                                @endforelse
                                <flux:button size="xs" variant="ghost" icon="plus" wire:click="addInterval('{{ $type }}', {{ $day }})">{{ $type === 'hours' ? __('Add hours') : __('Add break') }}</flux:button>
                                <flux:error name="{{ $type }}.{{ $day }}" />
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
            <div class="flex justify-end">
                <flux:button variant="primary" wire:click="saveSchedule">{{ __('Save working hours') }}</flux:button>
            </div>
        </flux:card>

        <flux:card class="space-y-4">
            <flux:heading size="lg">{{ __('Closures and time off') }}</flux:heading>
            <form wire:submit="addClosure" class="grid gap-3 md:grid-cols-3">
                <flux:select wire:model="closure.dentist_id" :label="__('Who')">
                    <flux:select.option value="">{{ __('Whole clinic') }}</flux:select.option>
                    @foreach ($this->dentists as $dentist)
                        <flux:select.option value="{{ $dentist->id }}">{{ $dentist->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <div class="flex gap-2">
                    <flux:input type="date" wire:model="closure.from_date" :label="__('From')" />
                    <flux:input type="time" wire:model="closure.from_time" :label="__('Time')" class="w-28" />
                </div>
                <div class="flex gap-2">
                    <flux:input type="date" wire:model="closure.to_date" :label="__('Until')" />
                    <flux:input type="time" wire:model="closure.to_time" :label="__('Time')" class="w-28" />
                </div>
                <flux:input wire:model="closure.reason" :label="__('Reason')" :placeholder="__('e.g. Public holiday')" class="md:col-span-2" />
                <div class="flex items-end"><flux:button type="submit" variant="primary">{{ __('Add closure') }}</flux:button></div>
            </form>

            @if ($this->closures->isEmpty())
                <flux:text class="text-sm">{{ __('No upcoming closures.') }}</flux:text>
            @else
                <ul class="divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                    @foreach ($this->closures as $period)
                        <li class="flex flex-wrap items-center justify-between gap-2 py-2" wire:key="closure-{{ $period->id }}">
                            <span>
                                <span class="font-medium">{{ $period->dentist?->name ?? __('Whole clinic') }}</span>
                                · {{ \App\Support\ClinicTime::local($period->starts_at)->format('Y-m-d H:i') }} – {{ \App\Support\ClinicTime::local($period->ends_at)->format('Y-m-d H:i') }}
                                @if ($period->reason) · {{ $period->reason }} @endif
                            </span>
                            <flux:button size="xs" variant="ghost" wire:click="removeClosure({{ $period->id }})" wire:confirm="{{ __('Remove this closure?') }}">{{ __('Remove') }}</flux:button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </flux:card>
    @endif
</section>
