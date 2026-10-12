# Audit method and evidence manifest

> Evidence snapshot: 12 October 2026; application commit `741f29d`. Documentation-only audit; no FIX-06 implementation, policy activation, data mutation or new dependency.

This review inventories the complete first-party source/test/config/migration tree and reads key transition, access, policy, file, reporting and clearance paths against existing campaign records. It is not a new exhaustive execution of every route or a line-by-line security certification. No app code or existing DB rows were edited.

## Locked versions

| Package | Version |
|---|---|
| barryvdh/laravel-dompdf | v3.1.2 |
| dompdf/dompdf | v3.1.6 |
| filament/filament | v5.9.0 |
| laravel/framework | v13.34.0 |
| livewire/livewire | v4.4.7 |
| spatie/laravel-permission | 8.3.0 |
| larastan/larastan | v3.12.3 |
| laravel/pint | v1.32.1 |
| phpunit/phpunit | 12.5.38 |

| JS package | Installed lock version |
|---|---|
| @playwright/test | 1.64.0 |
| tailwindcss | 4.3.3 |
| vite | 8.3.3 |

## First-party inventory

| Tree | Files inspected/inventoried |
|---|---|
| `app` | 278 |
| `database/migrations` | 26 |
| `database/seeders` | 8 |
| `routes` | 2 |
| `config` | 16 |
| `resources` | 25 |
| `tests` | 25 |
| `.github` | 1 |

### Shared Actions and entry points

- `app/Actions/Activities/ConfirmActivityClassification.php`: `execute`.
- `app/Actions/Activities/PrepareConsentForm.php`: `rules`, `authorize`, `execute`.
- `app/Actions/Activities/RecordActivityConclusion.php`: `execute`.
- `app/Actions/Activities/RecordClassificationAssessment.php`: `execute`.
- `app/Actions/Activities/SaveActivity.php`: `execute`.
- `app/Actions/Activities/SaveCertificationDraft.php`: `execute`.
- `app/Actions/Activities/SaveClocaCalendar.php`: `execute`.
- `app/Actions/Activities/SaveSaaDraft.php`: `execute`.
- `app/Actions/Clearances/RecordClearanceDecision.php`: `execute`.
- `app/Actions/Clearances/RecordReconsideration.php`: `execute`.
- `app/Actions/Clearances/RecordReconsiderationDecision.php`: `execute`.
- `app/Actions/Documents/RegisterPublicTemplate.php`: `execute`.
- `app/Actions/Documents/RequestEvidence.php`: `execute`, `respond`.
- `app/Actions/Documents/UploadDocument.php`: `execute`.
- `app/Actions/Identity/InviteUser.php`: `execute`.
- `app/Actions/Identity/SaveReference.php`: `execute`.
- `app/Actions/Operations/ManageCorrectiveAction.php`: `create`, `respond`, `close`.
- `app/Actions/Operations/ManageRegionalReport.php`: `prepare`, `dispatch`, `receive`.
- `app/Actions/Operations/RecordCaseEvent.php`: `execute`.
- `app/Actions/Operations/RecordClearanceRouting.php`: `execute`.
- `app/Actions/Operations/RecordOperationalRecord.php`: `execute`.
- `app/Actions/Reports/SaveChangeReportDraft.php`: `execute`.
- `app/Actions/Reports/SaveCompletionReportDraft.php`: `execute`.
- `app/Actions/Reports/SaveIncidentReportDraft.php`: `execute`.
- `app/Actions/Reports/SubmitReport.php`: `execute`.
- `app/Actions/Submissions/ConfirmPolicy.php`: `execute`.
- `app/Actions/Submissions/CorrectSaaPolicy.php`: `plan`, `execute`.
- `app/Actions/Submissions/IssueAcknowledgment.php`: `execute`.
- `app/Actions/Submissions/ProposeClassificationRuleSet.php`: `execute`.
- `app/Actions/Submissions/ReviewSubmission.php`: `execute`.
- `app/Actions/Submissions/SaveAcknowledgmentDraft.php`: `execute`.
- `app/Actions/Submissions/SubmitActivity.php`: `execute`.
- `app/Actions/Submissions/SubmitCalendar.php`: `execute`.
- `app/Http/Controllers/AcknowledgmentController.php`: `show`.
- `app/Http/Controllers/CertificationController.php`: `draft`, `submission`.
- `app/Http/Controllers/ChangeReportController.php`: `draft`, `report`.
- `app/Http/Controllers/ClocaController.php`: `calendar`, `submission`.
- `app/Http/Controllers/CompletionReportController.php`: `draft`, `report`.
- `app/Http/Controllers/ConsentFormController.php`: `form`, `generate`.
- `app/Http/Controllers/Controller.php`: .
- `app/Http/Controllers/DiagnosticController.php`: `activity`, `submission`.
- `app/Http/Controllers/DocumentController.php`: `preview`, `__invoke`.
- `app/Http/Controllers/ExportController.php`: `__invoke`.
- `app/Http/Controllers/IncidentReportController.php`: `draft`, `report`.
- `app/Http/Controllers/InvitationController.php`: `show`, `accept`.
- `app/Http/Controllers/ItineraryController.php`: `activity`, `submission`.
- `app/Http/Controllers/MonitoringController.php`: `show`.
- `app/Http/Controllers/PublicTemplateController.php`: `available`, `index`, `download`.
- `app/Http/Controllers/RacpController.php`: `activity`, `submission`.
- `app/Http/Controllers/SaaController.php`: `draft`, `submission`.
- `app/Jobs/GenerateExport.php`: `__construct`, `handle`.
- `app/Jobs/NotifyChedChangeReport.php`: `__construct`, `middleware`, `handle`.
- `app/Jobs/NotifyInstitution.php`: `__construct`, `handle`.
- `app/Jobs/ScanDocument.php`: `__construct`, `handle`.
- `app/Policies/InstitutionRecordPolicy.php`: `viewAny`, `view`, `create`, `update`, `delete`.
- `app/Console/Commands/BootstrapQaDemo.php`: `handle`.
- `app/Console/Commands/CorrectSaaPolicyCommand.php`: `handle`.
- `app/Console/Commands/CreateAdministrator.php`: `handle`.
- `app/Console/Commands/SendReminders.php`: `handle`.

### Eloquent relationship inventory

- `app/Models/AcademicPeriod.php`: institution: BelongsTo.
- `app/Models/AccountabilityAffidavit.php`: institution: BelongsTo, academicPeriod: BelongsTo, formTemplate: BelongsTo.
- `app/Models/Acknowledgment.php`: submission: BelongsTo, document: BelongsTo.
- `app/Models/AcknowledgmentDraft.php`: submission: BelongsTo.
- `app/Models/Activity.php`: institution: BelongsTo, campus: BelongsTo, calendar: BelongsTo, academicPeriod: BelongsTo, versions: HasMany, latestVersion: HasOne.
- `app/Models/ActivityClassification.php`: activityVersion: BelongsTo.
- `app/Models/ActivityPersonnel.php`: activityVersion: BelongsTo.
- `app/Models/ActivityVersion.php`: activity: BelongsTo, personnel: HasMany, itineraryItems: HasMany.
- `app/Models/AgencyCoordination.php`: activityVersion: BelongsTo.
- `app/Models/AuditEvent.php`: actor: BelongsTo, institution: BelongsTo.
- `app/Models/Calendar.php`: institution: BelongsTo, academicPeriod: BelongsTo, activities: HasMany.
- `app/Models/Campus.php`: institution: BelongsTo.
- `app/Models/CaseEvent.php`: caseRecord: BelongsTo, document: BelongsTo.
- `app/Models/CaseRecord.php`: institution: BelongsTo, activity: BelongsTo, jurisdictionRegion: BelongsTo, events: HasMany.
- `app/Models/CertificationDraft.php`: institution: BelongsTo, academicPeriod: BelongsTo, formTemplate: BelongsTo.
- `app/Models/ChangeReport.php`: institution: BelongsTo, activity: BelongsTo, originalVersion: BelongsTo, revisedVersion: BelongsTo, document: BelongsTo, supplementsReport: BelongsTo, changeReportDraft: BelongsTo.
- `app/Models/ChangeReportDraft.php`: institution: BelongsTo, activity: BelongsTo, originalVersion: BelongsTo, revisedVersion: BelongsTo, originalSubmission: BelongsTo, supplementsReport: BelongsTo, changeReport: HasOne.
- `app/Models/ChangeReportItem.php`: changeReport: BelongsTo.
- `app/Models/ClassificationAssessment.php`: activityVersion: BelongsTo, baselineVersion: BelongsTo, baselineSubmission: BelongsTo, ruleSet: BelongsTo, previousAssessment: BelongsTo, recorder: BelongsTo, confirmer: BelongsTo.
- `app/Models/ClassificationRuleSet.php`: policyVersion: BelongsTo, supersedes: BelongsTo, assessments: HasMany.
- `app/Models/ClearanceRequest.php`: institution: BelongsTo, activityVersion: BelongsTo, caseRecord: BelongsTo, decisionDocument: BelongsTo, reconsideration: HasOne.
- `app/Models/CompletionReport.php`: institution: BelongsTo, activity: BelongsTo, submission: BelongsTo, document: BelongsTo.
- `app/Models/CompletionReportDraft.php`: institution: BelongsTo, activity: BelongsTo, activityVersion: BelongsTo, completionReport: HasOne.
- `app/Models/CorrectiveAction.php`: institution: BelongsTo, monitoringRecord: BelongsTo.
- `app/Models/Document.php`: institution: BelongsTo, documentType: BelongsTo, supersedesDocument: BelongsTo.
- `app/Models/DocumentLink.php`: document: BelongsTo.
- `app/Models/DocumentRequest.php`: institution: BelongsTo, submission: BelongsTo, documentType: BelongsTo, responseDocument: BelongsTo.
- `app/Models/DocumentType.php`: No explicitly typed relationship method found.
- `app/Models/Export.php`: No explicitly typed relationship method found.
- `app/Models/FormTemplate.php`: policyVersion: BelongsTo, document: BelongsTo.
- `app/Models/Holiday.php`: No explicitly typed relationship method found.
- `app/Models/IncidentPerson.php`: incidentReport: BelongsTo.
- `app/Models/IncidentReport.php`: institution: BelongsTo, activity: BelongsTo, supplementsReport: BelongsTo, document: BelongsTo, persons: HasMany.
- `app/Models/IncidentReportDraft.php`: institution: BelongsTo, activity: BelongsTo, activityVersion: BelongsTo, supplementsReport: BelongsTo, incidentReport: HasOne.
- `app/Models/Institution.php`: region: BelongsTo, campuses: HasMany, users: BelongsToMany.
- `app/Models/InstitutionalRestriction.php`: institution: BelongsTo, activity: BelongsTo, caseRecord: BelongsTo, document: BelongsTo.
- `app/Models/Invitation.php`: institution: BelongsTo, region: BelongsTo.
- `app/Models/ItineraryItem.php`: activityVersion: BelongsTo.
- `app/Models/MonitoringItem.php`: monitoringRecord: BelongsTo.
- `app/Models/MonitoringRecord.php`: institution: BelongsTo, activity: BelongsTo, items: HasMany.
- `app/Models/NotificationDelivery.php`: No explicitly typed relationship method found.
- `app/Models/PolicyRequirement.php`: policyVersion: BelongsTo, requirementDefinition: BelongsTo.
- `app/Models/PolicyVersion.php`: requirements: HasMany, formTemplates: HasMany.
- `app/Models/Reconsideration.php`: clearanceRequest: BelongsTo, document: BelongsTo, decisionDocument: BelongsTo.
- `app/Models/Region.php`: No explicitly typed relationship method found.
- `app/Models/RegionalReport.php`: region: BelongsTo, document: BelongsTo.
- `app/Models/RequirementDefinition.php`: documentType: BelongsTo.
- `app/Models/ReviewFinding.php`: submissionReview: BelongsTo.
- `app/Models/Submission.php`: institution: BelongsTo, calendar: BelongsTo, policyVersion: BelongsTo, supersedesSubmission: BelongsTo, activityVersions: BelongsToMany, requirements: HasMany, reviews: HasMany, acknowledgment: HasOne.
- `app/Models/SubmissionActivity.php`: submission: BelongsTo, activityVersion: BelongsTo.
- `app/Models/SubmissionRequirement.php`: submission: BelongsTo, policyRequirement: BelongsTo, activityVersion: BelongsTo.
- `app/Models/SubmissionReview.php`: submission: BelongsTo.
- `app/Models/TechnicalAssistanceLog.php`: institution: BelongsTo, activity: BelongsTo.
- `app/Models/User.php`: institutions: BelongsToMany.

### Explicitly not verified

Actual publication, final legal authenticity, external OSDS/CEB actions, cross-region restricted access transfer, CI run result, PostgreSQL/Docker runtime, live production SMTP/hosting/keys/retention, load and complete accessibility conformance. No blanket “all features work” conclusion follows from file existence. The normal DB migration mismatch was verified read-only.

## Authoritative policy source

Supplied file: `CMO NO. 24 S. 2026 (1).pdf`; 36 PDF pages; issued 28 September 2026; SHA-256 `a7915e3eea2f89a6a5cb63ac83ce3947aa40abbd79bf42499aa4a17d22174c01`. Source page count/hash rechecked during this audit. Publication is unverified. July-draft source/rules remain history. Source references/interpretations follow the final-order clarification register; incorporated external guidance is not independently verified by this package.

## Read-only route inventory

`artisan route:list --except-vendor --json`; generated panel routes are included when returned, vendor routes excluded. No new API inferred.

| Method | URI | Action |
|---|---|---|
| GET|HEAD | `/` | `Illuminate\Routing\ViewController` |
| GET|HEAD | `activities/{activity}/diagnostic` | `App\Http\Controllers\DiagnosticController@activity` |
| GET|HEAD | `activities/{activity}/itinerary` | `App\Http\Controllers\ItineraryController@activity` |
| GET|HEAD | `activities/{activity}/racp` | `App\Http\Controllers\RacpController@activity` |
| GET|HEAD | `activity-versions/{version}/consent` | `App\Http\Controllers\ConsentFormController@form` |
| POST | `activity-versions/{version}/consent` | `App\Http\Controllers\ConsentFormController@generate` |
| GET|HEAD | `affidavits/{affidavit}/saa` | `App\Http\Controllers\SaaController@draft` |
| GET|HEAD | `calendars/{calendar}/cloca` | `App\Http\Controllers\ClocaController@calendar` |
| GET|HEAD | `certifications/{draft}/print` | `App\Http\Controllers\CertificationController@draft` |
| GET|HEAD | `change-drafts/{draft}/print` | `App\Http\Controllers\ChangeReportController@draft` |
| GET|HEAD | `change-reports/{report}/print` | `App\Http\Controllers\ChangeReportController@report` |
| GET|HEAD | `ched/academic-periods` | `App\Filament\Ched\Resources\Pages\ListAcademicPeriod` |
| GET|HEAD | `ched/academic-periods/{record}` | `App\Filament\Ched\Resources\Pages\ViewAcademicPeriod` |
| GET|HEAD | `ched/accountability-affidavits` | `App\Filament\Ched\Resources\Pages\ListAccountabilityAffidavit` |
| GET|HEAD | `ched/accountability-affidavits/{record}` | `App\Filament\Ched\Resources\Pages\ViewAccountabilityAffidavit` |
| GET|HEAD | `ched/activities` | `App\Filament\Ched\Resources\Pages\ListActivity` |
| GET|HEAD | `ched/activities/{record}` | `App\Filament\Ched\Resources\Pages\ViewActivity` |
| GET|HEAD | `ched/calendars` | `App\Filament\Ched\Resources\Pages\ListCalendar` |
| GET|HEAD | `ched/calendars/{record}` | `App\Filament\Ched\Resources\Pages\ViewCalendar` |
| GET|HEAD | `ched/campuses` | `App\Filament\Ched\Resources\Pages\ListCampus` |
| GET|HEAD | `ched/campuses/{record}` | `App\Filament\Ched\Resources\Pages\ViewCampus` |
| GET|HEAD | `ched/case-records` | `App\Filament\Ched\Resources\Pages\ListCaseRecord` |
| GET|HEAD | `ched/case-records/{record}` | `App\Filament\Ched\Resources\Pages\ViewCaseRecord` |
| GET|HEAD | `ched/certification-drafts` | `App\Filament\Ched\Resources\Pages\ListCertificationDraft` |
| GET|HEAD | `ched/certification-drafts/{record}` | `App\Filament\Ched\Resources\Pages\ViewCertificationDraft` |
| GET|HEAD | `ched/change-report-drafts` | `App\Filament\Ched\Resources\Pages\ListChangeReportDraft` |
| GET|HEAD | `ched/change-report-drafts/{record}` | `App\Filament\Ched\Resources\Pages\ViewChangeReportDraft` |
| GET|HEAD | `ched/change-reports` | `App\Filament\Ched\Resources\Pages\ListChangeReport` |
| GET|HEAD | `ched/change-reports/{record}` | `App\Filament\Ched\Resources\Pages\ViewChangeReport` |
| GET|HEAD | `ched/clearance-requests` | `App\Filament\Ched\Resources\Pages\ListClearanceRequest` |
| GET|HEAD | `ched/clearance-requests/{record}` | `App\Filament\Ched\Resources\Pages\ViewClearanceRequest` |
| GET|HEAD | `ched/completion-report-drafts` | `App\Filament\Ched\Resources\Pages\ListCompletionReportDraft` |
| GET|HEAD | `ched/completion-report-drafts/{record}` | `App\Filament\Ched\Resources\Pages\ViewCompletionReportDraft` |
| GET|HEAD | `ched/completion-reports` | `App\Filament\Ched\Resources\Pages\ListCompletionReport` |
| GET|HEAD | `ched/completion-reports/{record}` | `App\Filament\Ched\Resources\Pages\ViewCompletionReport` |
| GET|HEAD | `ched/corrective-actions` | `App\Filament\Ched\Resources\Pages\ListCorrectiveAction` |
| GET|HEAD | `ched/corrective-actions/{record}` | `App\Filament\Ched\Resources\Pages\ViewCorrectiveAction` |
| GET|HEAD | `ched/document-requests` | `App\Filament\Ched\Resources\Pages\ListDocumentRequest` |
| GET|HEAD | `ched/document-requests/{record}` | `App\Filament\Ched\Resources\Pages\ViewDocumentRequest` |
| GET|HEAD | `ched/documents` | `App\Filament\Ched\Resources\Pages\ListDocument` |
| GET|HEAD | `ched/documents/{record}` | `App\Filament\Ched\Resources\Pages\ViewDocument` |
| GET|HEAD | `ched/incident-report-drafts` | `App\Filament\Ched\Resources\Pages\ListIncidentReportDraft` |
| GET|HEAD | `ched/incident-report-drafts/{record}` | `App\Filament\Ched\Resources\Pages\ViewIncidentReportDraft` |
| GET|HEAD | `ched/incident-reports` | `App\Filament\Ched\Resources\Pages\ListIncidentReport` |
| GET|HEAD | `ched/incident-reports/{record}` | `App\Filament\Ched\Resources\Pages\ViewIncidentReport` |
| GET|HEAD | `ched/institutional-restrictions` | `App\Filament\Ched\Resources\Pages\ListInstitutionalRestriction` |
| GET|HEAD | `ched/institutional-restrictions/{record}` | `App\Filament\Ched\Resources\Pages\ViewInstitutionalRestriction` |
| GET|HEAD | `ched/institutions` | `App\Filament\Ched\Resources\Pages\ListInstitution` |
| GET|HEAD | `ched/institutions/{record}` | `App\Filament\Ched\Resources\Pages\ViewInstitution` |
| GET|HEAD | `ched/invitations` | `App\Filament\Ched\Pages\Invitations` |
| GET|HEAD | `ched/monitoring-records` | `App\Filament\Ched\Resources\Pages\ListMonitoringRecord` |
| GET|HEAD | `ched/monitoring-records/create` | `App\Filament\Ched\Resources\Pages\CreateMonitoringRecord` |
| GET|HEAD | `ched/monitoring-records/{record}` | `App\Filament\Ched\Resources\Pages\ViewMonitoringRecord` |
| GET|HEAD | `ched/overview` | `App\Filament\Ched\Pages\Overview` |
| GET|HEAD | `ched/policy-configuration` | `App\Filament\Ched\Pages\PolicyConfiguration` |
| GET|HEAD | `ched/reporting` | `App\Filament\Ched\Pages\Reporting` |
| GET|HEAD | `ched/submissions` | `App\Filament\Ched\Resources\Pages\ListSubmission` |
| GET|HEAD | `ched/submissions/{record}` | `App\Filament\Ched\Resources\Pages\ViewSubmission` |
| GET|HEAD | `ched/technical-assistance-logs` | `App\Filament\Ched\Resources\Pages\ListTechnicalAssistanceLog` |
| GET|HEAD | `ched/technical-assistance-logs/{record}` | `App\Filament\Ched\Resources\Pages\ViewTechnicalAssistanceLog` |
| GET|HEAD | `completion-drafts/{draft}/print` | `App\Http\Controllers\CompletionReportController@draft` |
| GET|HEAD | `completion-reports/{report}/print` | `App\Http\Controllers\CompletionReportController@report` |
| GET|HEAD | `documents/{publicId}` | `App\Http\Controllers\DocumentController` |
| GET|HEAD | `documents/{publicId}/preview` | `App\Http\Controllers\DocumentController@preview` |
| GET|HEAD | `exports/{export}` | `App\Http\Controllers\ExportController` |
| GET|HEAD | `faq` | `Illuminate\Routing\ViewController` |
| GET|HEAD | `guidelines` | `App\Http\Controllers\PublicTemplateController@index` |
| GET|HEAD | `hei/academic-periods` | `App\Filament\Hei\Resources\Pages\ListAcademicPeriod` |
| GET|HEAD | `hei/academic-periods/{record}` | `App\Filament\Hei\Resources\Pages\ViewAcademicPeriod` |
| GET|HEAD | `hei/accountability-affidavits` | `App\Filament\Hei\Resources\Pages\ListAccountabilityAffidavit` |
| GET|HEAD | `hei/accountability-affidavits/{record}` | `App\Filament\Hei\Resources\Pages\ViewAccountabilityAffidavit` |
| GET|HEAD | `hei/activities` | `App\Filament\Hei\Resources\Pages\ListActivity` |
| GET|HEAD | `hei/activities/{record}` | `App\Filament\Hei\Resources\Pages\ViewActivity` |
| GET|HEAD | `hei/activities/{record}/revise` | `App\Filament\Hei\Resources\Pages\EditActivity` |
| GET|HEAD | `hei/calendars` | `App\Filament\Hei\Resources\Pages\ListCalendar` |
| GET|HEAD | `hei/calendars/{record}` | `App\Filament\Hei\Resources\Pages\ViewCalendar` |
| GET|HEAD | `hei/certification-drafts` | `App\Filament\Hei\Resources\Pages\ListCertificationDraft` |
| GET|HEAD | `hei/certification-drafts/{record}` | `App\Filament\Hei\Resources\Pages\ViewCertificationDraft` |
| GET|HEAD | `hei/change-report-drafts` | `App\Filament\Hei\Resources\Pages\ListChangeReportDraft` |
| GET|HEAD | `hei/change-report-drafts/{record}` | `App\Filament\Hei\Resources\Pages\ViewChangeReportDraft` |
| GET|HEAD | `hei/change-reports` | `App\Filament\Hei\Resources\Pages\ListChangeReport` |
| GET|HEAD | `hei/change-reports/{record}` | `App\Filament\Hei\Resources\Pages\ViewChangeReport` |
| GET|HEAD | `hei/completion-report-drafts` | `App\Filament\Hei\Resources\Pages\ListCompletionReportDraft` |
| GET|HEAD | `hei/completion-report-drafts/{record}` | `App\Filament\Hei\Resources\Pages\ViewCompletionReportDraft` |
| GET|HEAD | `hei/completion-reports` | `App\Filament\Hei\Resources\Pages\ListCompletionReport` |
| GET|HEAD | `hei/completion-reports/{record}` | `App\Filament\Hei\Resources\Pages\ViewCompletionReport` |
| GET|HEAD | `hei/corrective-actions` | `App\Filament\Hei\Resources\Pages\ListCorrectiveAction` |
| GET|HEAD | `hei/corrective-actions/{record}` | `App\Filament\Hei\Resources\Pages\ViewCorrectiveAction` |
| GET|HEAD | `hei/document-requests` | `App\Filament\Hei\Resources\Pages\ListDocumentRequest` |
| GET|HEAD | `hei/document-requests/{record}` | `App\Filament\Hei\Resources\Pages\ViewDocumentRequest` |
| GET|HEAD | `hei/documents` | `App\Filament\Hei\Resources\Pages\ListDocument` |
| GET|HEAD | `hei/documents/{record}` | `App\Filament\Hei\Resources\Pages\ViewDocument` |
| GET|HEAD | `hei/incident-report-drafts` | `App\Filament\Hei\Resources\Pages\ListIncidentReportDraft` |
| GET|HEAD | `hei/incident-report-drafts/{record}` | `App\Filament\Hei\Resources\Pages\ViewIncidentReportDraft` |
| GET|HEAD | `hei/incident-reports` | `App\Filament\Hei\Resources\Pages\ListIncidentReport` |
| GET|HEAD | `hei/incident-reports/{record}` | `App\Filament\Hei\Resources\Pages\ViewIncidentReport` |
| GET|HEAD | `hei/overview` | `App\Filament\Hei\Pages\Overview` |
| GET|HEAD | `hei/submissions` | `App\Filament\Hei\Resources\Pages\ListSubmission` |
| GET|HEAD | `hei/submissions/{record}` | `App\Filament\Hei\Resources\Pages\ViewSubmission` |
| GET|HEAD | `incident-drafts/{draft}/print` | `App\Http\Controllers\IncidentReportController@draft` |
| GET|HEAD | `incident-reports/{report}/print` | `App\Http\Controllers\IncidentReportController@report` |
| GET|HEAD | `invitations/{token}` | `App\Http\Controllers\InvitationController@show` |
| POST | `invitations/{token}` | `App\Http\Controllers\InvitationController@accept` |
| GET|HEAD | `login` | `Closure` |
| GET|HEAD | `monitoring-records/{record}/print` | `App\Http\Controllers\MonitoringController@show` |
| GET|HEAD | `submissions/{publicId}/acknowledgment` | `App\Http\Controllers\AcknowledgmentController@show` |
| GET|HEAD | `submissions/{publicId}/certification` | `App\Http\Controllers\CertificationController@submission` |
| GET|HEAD | `submissions/{publicId}/cloca` | `App\Http\Controllers\ClocaController@submission` |
| GET|HEAD | `submissions/{publicId}/diagnostics/{version?}` | `App\Http\Controllers\DiagnosticController@submission` |
| GET|HEAD | `submissions/{publicId}/itineraries/{version?}` | `App\Http\Controllers\ItineraryController@submission` |
| GET|HEAD | `submissions/{publicId}/racps/{version?}` | `App\Http\Controllers\RacpController@submission` |
| GET|HEAD | `submissions/{publicId}/saa` | `App\Http\Controllers\SaaController@submission` |
| GET|HEAD | `templates/{id}` | `App\Http\Controllers\PublicTemplateController@download` |
