<?php

namespace App\Actions\Treatments;

use App\Enums\TreatmentEventType;
use App\Enums\TreatmentPlanStatus;
use App\Enums\TreatmentStatus;
use App\Models\TreatmentItem;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Moves a treatment item to a new status. The item row is locked, the transition
 * is re-checked against the stored status, and an append-only event records who,
 * when, from and to, in the same transaction. History is never overwritten.
 */
class ChangeTreatmentStatus
{
    public function handle(TreatmentItem $item, TreatmentStatus $to, User $actor, ?string $note = null): TreatmentItem
    {
        return DB::transaction(function () use ($item, $to, $actor, $note) {
            /** @var TreatmentItem $locked */
            $locked = TreatmentItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            if (! $from->canTransitionTo($to)) {
                throw ValidationException::withMessages([
                    'status' => __('A :from treatment cannot be changed to :to.', ['from' => strtolower($from->label()), 'to' => strtolower($to->label())]),
                ]);
            }

            if ($to === TreatmentStatus::Cancelled && trim((string) $note) === '') {
                throw ValidationException::withMessages(['note' => __('Give a reason for cancelling.')]);
            }

            $locked->status = $to;
            match ($to) {
                TreatmentStatus::InProgress => $locked->started_at = now(),
                TreatmentStatus::Completed => $locked->completed_at = now(),
                TreatmentStatus::Cancelled => $locked->cancelled_at = now(),
                TreatmentStatus::Planned => null,
            };
            if ($to === TreatmentStatus::Completed && $locked->started_at === null) {
                $locked->started_at = $locked->completed_at;
            }
            $locked->save();

            $locked->events()->create([
                'type' => TreatmentEventType::StatusChanged,
                'from_status' => $from,
                'to_status' => $to,
                'performed_by' => $actor->id,
                'note' => trim((string) $note) === '' ? null : trim((string) $note),
                'occurred_at' => now(),
            ]);

            $this->updatePlan($locked);

            Audit::record('treatment.status_changed', $locked, ['from' => $from->value, 'to' => $to->value]);

            return $locked;
        });
    }

    /**
     * A plan becomes active when work starts and completed once every item is
     * finished (completed or cancelled, with at least one completed).
     */
    private function updatePlan(TreatmentItem $item): void
    {
        $plan = $item->plan;
        if ($plan === null || in_array($plan->status, [TreatmentPlanStatus::Cancelled], true)) {
            return;
        }

        $statuses = $plan->items()->pluck('status')->map(fn ($s) => $s instanceof TreatmentStatus ? $s : TreatmentStatus::from($s));
        $allFinal = $statuses->every(fn (TreatmentStatus $s) => $s->isFinal());
        $anyCompleted = $statuses->contains(TreatmentStatus::Completed);

        $plan->status = match (true) {
            $allFinal && $anyCompleted => TreatmentPlanStatus::Completed,
            $allFinal => TreatmentPlanStatus::Cancelled,
            default => TreatmentPlanStatus::Active,
        };
        $plan->save();
    }
}
