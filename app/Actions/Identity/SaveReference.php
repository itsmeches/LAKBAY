<?php

namespace App\Actions\Identity;

use App\Models\AcademicPeriod;
use App\Models\Calendar;
use App\Models\Campus;
use App\Models\Institution;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use Illuminate\Support\Facades\Validator;

class SaveReference
{
    public function execute(User $actor, string $model, array $data)
    {
        if ($model === Institution::class) {
            abort_unless($actor->is_active && $actor->hasVerifiedEmail() && $actor->can('institutions.manage') && $actor->region_id, 403);
            Validator::make($data, ['ched_code' => 'required|unique:institutions,ched_code', 'name' => 'required|string', 'institution_type' => 'required|in:suc,luc,private'])->validate();
            $values = collect($data)->only(['ched_code', 'name', 'institution_type', 'address', 'email'])->all() + ['region_id' => $actor->region_id];
        } else {
            $id = (int) $data['institution_id'];
            app(Access::class)->authorize($actor, $id, $model === Campus::class ? 'institutions.manage' : 'activities.manage');
            $allowed = match ($model) {
                Campus::class => ['institution_id', 'name', 'address', 'locality'],AcademicPeriod::class => ['institution_id', 'label', 'starts_on', 'ends_on', 'term'],Calendar::class => ['institution_id', 'academic_period_id', 'title', 'effective_on', 'responsible_office'],default => throw new \InvalidArgumentException('Unsupported reference model')
            };
            $values = collect($data)->only($allowed)->all();
            if ($model === AcademicPeriod::class) {
                Validator::make($data, ['starts_on' => 'required|date', 'ends_on' => 'required|date|after:starts_on'])->validate();
            }
            if ($model === Calendar::class) {
                abort_unless(AcademicPeriod::findOrFail($data['academic_period_id'])->institution_id === $id, 403);
                $values['created_by'] = $actor->id;
            }
        }
        $r = $model::create($values);
        Audit::record($actor, 'reference.created', $r);

        return $r;
    }
}
