<?php

namespace App\Providers;

use App\Models\AcademicPeriod;
use App\Models\AccountabilityAffidavit;
use App\Models\Activity;
use App\Models\Calendar;
use App\Models\CaseRecord;
use App\Models\CertificationDraft;
use App\Models\ChangeReport;
use App\Models\ChangeReportDraft;
use App\Models\ClearanceRequest;
use App\Models\CompletionReport;
use App\Models\CompletionReportDraft;
use App\Models\CorrectiveAction;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\IncidentReport;
use App\Models\IncidentReportDraft;
use App\Models\Institution;
use App\Models\InstitutionalRestriction;
use App\Models\MonitoringRecord;
use App\Models\Submission;
use App\Models\TechnicalAssistanceLog;
use App\Policies\InstitutionRecordPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        config(['livewire.temporary_file_upload.rules' => ['required', 'file', 'max:'.config('lakbay.upload_limit_kb')]]);
        config(['filesystems.disks.private' => ['driver' => 'local', 'root' => storage_path('app/private'), 'visibility' => 'private', 'throw' => true]]);
        Gate::policy(CertificationDraft::class, InstitutionRecordPolicy::class);
        Gate::policy(CompletionReportDraft::class, InstitutionRecordPolicy::class);
        Gate::policy(IncidentReportDraft::class, InstitutionRecordPolicy::class);
        Gate::policy(ChangeReportDraft::class, InstitutionRecordPolicy::class);
        foreach ([AccountabilityAffidavit::class, CorrectiveAction::class, DocumentRequest::class, Institution::class, AcademicPeriod::class, Calendar::class, Activity::class, Submission::class, Document::class, CompletionReport::class, IncidentReport::class, ChangeReport::class, MonitoringRecord::class, TechnicalAssistanceLog::class, CaseRecord::class, ClearanceRequest::class, InstitutionalRestriction::class] as $model) {
            Gate::policy($model, InstitutionRecordPolicy::class);
        }
    }
}
