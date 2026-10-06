<?php

namespace App\Actions\Activities;

use App\Models\AcademicPeriod;
use App\Models\Activity;
use App\Models\ActivityClassification;
use App\Models\ActivityVersion;
use App\Models\Calendar;
use App\Models\Campus;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use App\Services\Compliance\RequirementResolver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaveActivity
{
    public function execute(User $actor, array $data, ?Activity $activity = null): Activity
    {
        Validator::make($data, ['institution_id' => 'required|integer', 'academic_period_id' => 'required|integer', 'title' => 'required|string|max:255', 'category' => 'required|integer|between:1,3', 'academic_nature' => 'required|in:curricular,non_curricular', 'activity_type' => 'required|string|max:100', 'initiator' => 'required|in:hei,student,external', 'description' => 'required|string', 'learning_objectives' => 'required|string', 'responsible_office' => 'required|string|max:255', 'starts_at' => 'required|date', 'ends_at' => 'required|date|after:starts_at', 'venue' => 'required|string|max:255', 'student_count' => 'required|integer|min:1', 'conditions' => 'present|array:has_student_costs,requires_accommodation,external_invitation,strenuous,vulnerable_participants,hazardous_destination,long_distance,class_size', 'conditions.*' => 'boolean', 'authorization' => 'required|array', 'personnel' => 'required|array|min:1', 'personnel.*.name' => 'required|string', 'personnel.*.role' => 'required|in:pic,apic,supervisor', 'personnel.*.position' => 'required|string', 'personnel.*.contact' => 'required|string'])->validate();
        app(Access::class)->authorize($actor, (int) $data['institution_id'], 'activities.manage');
        if ($activity) {
            abort_unless($activity->institution_id === $data['institution_id'], 403);
            abort_unless($activity->academic_period_id === $data['academic_period_id'] && $activity->calendar_id === ($data['calendar_id'] ?? null) && $activity->campus_id === ($data['campus_id'] ?? null), 422, 'Revise activity details without changing its institutional/calendar identity.');
        }
        if ((int) $data['category'] === 3 && $data['initiator'] !== 'student') {
            throw ValidationException::withMessages(['category' => 'Category 3 requires a student-initiated activity.']);
        }
        if ($activity && $activity->latestVersion->category !== (int) $data['category'] && empty(trim($data['classification_reason'] ?? ''))) {
            throw ValidationException::withMessages(['classification_reason' => 'Explain a change to the declared category.']);
        }
        if (! collect($data['personnel'])->contains('role', 'pic')) {
            throw ValidationException::withMessages(['personnel' => 'Designate a PIC.']);
        }
        $period = AcademicPeriod::findOrFail($data['academic_period_id']);
        abort_unless($period->institution_id === $data['institution_id'], 403);
        foreach (['calendar_id' => Calendar::class, 'campus_id' => Campus::class] as $key => $class) {
            if (! empty($data[$key])) {
                abort_unless($class::findOrFail($data[$key])->institution_id === $data['institution_id'], 403);
            }
        }

        if (! empty($data['calendar_id'])) {
            abort_unless(Calendar::findOrFail($data['calendar_id'])->academic_period_id === $period->id, 422, 'Use the academic period belonging to the selected calendar.');
        }

        return DB::transaction(function () use ($actor, $data, $activity) {
            if ($activity) {
                $activity = Activity::whereKey($activity->id)->lockForUpdate()->firstOrFail();
            } else {
                $activity = Activity::create(['institution_id' => $data['institution_id'], 'academic_period_id' => $data['academic_period_id'], 'calendar_id' => $data['calendar_id'] ?? null, 'campus_id' => $data['campus_id'] ?? null, 'title' => $data['title'], 'public_id' => (string) Str::uuid(), 'created_by' => $actor->id]);
            }
            $category = app(RequirementResolver::class)->requirementCategory((int) $data['category'], $data['conditions']);

            $fields = collect($data)->only(['category', 'academic_nature', 'activity_type', 'initiator', 'title', 'description', 'learning_objectives', 'program', 'responsible_office', 'starts_at', 'ends_at', 'departure_at', 'return_at', 'venue', 'student_count', 'other_count', 'conditions', 'authorization'])->all();
            foreach (['starts_at', 'ends_at', 'departure_at', 'return_at'] as $key) {
                if (! empty($fields[$key])) {
                    $fields[$key] = CarbonImmutable::parse($fields[$key], config('lakbay.timezone'))->utc();
                }
            }
            $version = ActivityVersion::create($fields + ['activity_id' => $activity->id, 'version' => ($activity->versions()->max('version') ?? 0) + 1, 'requirement_category' => $category, 'created_by' => $actor->id]);
            foreach ($data['personnel'] as $person) {
                $version->personnel()->create(collect($person)->only(['role', 'name', 'position', 'contact', 'qualifications', 'first_aid_trained'])->all());
            }
            foreach ($data['itinerary'] ?? [] as $item) {
                $version->itineraryItems()->create($item);
            }
            ActivityClassification::create(['activity_version_id' => $version->id, 'declared_category' => $data['category'], 'requirement_category' => $category, 'reason' => $data['classification_reason'] ?? ($category !== $data['category'] ? 'Category 3 circumstances require Category 2 evidence.' : 'Confirmed by preparer.'), 'confirmed_by' => $actor->id]);
            $activity->update(['title' => $data['title']]);
            Audit::record($actor, 'activity.revised', $activity, ['version' => $version->version]);

            return $activity->refresh();
        });
    }
}
