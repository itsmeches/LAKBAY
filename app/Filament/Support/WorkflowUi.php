<?php

namespace App\Filament\Support;

use App\Actions\Activities\ConfirmActivityClassification;
use App\Actions\Activities\RecordActivityConclusion;
use App\Actions\Activities\SaveCertificationDraft;
use App\Actions\Activities\SaveClocaCalendar;
use App\Actions\Activities\SaveSaaDraft;
use App\Actions\Clearances\RecordClearanceDecision;
use App\Actions\Clearances\RecordReconsideration;
use App\Actions\Clearances\RecordReconsiderationDecision;
use App\Actions\Documents\RequestEvidence;
use App\Actions\Operations\ManageCorrectiveAction;
use App\Actions\Operations\RecordCaseEvent;
use App\Actions\Operations\RecordClearanceRouting;
use App\Actions\Reports\SaveChangeReportDraft;
use App\Actions\Reports\SaveCompletionReportDraft;
use App\Actions\Reports\SaveIncidentReportDraft;
use App\Actions\Reports\SubmitReport;
use App\Actions\Submissions\IssueAcknowledgment;
use App\Actions\Submissions\ReviewSubmission;
use App\Actions\Submissions\SaveAcknowledgmentDraft;
use App\Actions\Submissions\SubmitActivity;
use App\Actions\Submissions\SubmitCalendar;
use App\Filament\Hei\Resources\ActivityResource;
use App\Models\AccountabilityAffidavit;
use App\Models\AcknowledgmentDraft;
use App\Models\Activity;
use App\Models\Calendar;
use App\Models\CaseRecord;
use App\Models\CertificationDraft;
use App\Models\ChangeReport;
use App\Models\ChangeReportDraft;
use App\Models\ClearanceRequest;
use App\Models\CompletionReport;
use App\Models\CompletionReportDraft;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\IncidentReport;
use App\Models\IncidentReportDraft;
use App\Models\MonitoringRecord;
use App\Models\PolicyVersion;
use App\Models\Submission;
use App\Services\Access;
use App\Services\Compliance\ActivityReadiness;
use App\Services\Compliance\ClassificationPresentation;
use App\Services\Compliance\PolicyRules;
use App\Services\Compliance\RequirementResolver;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Validation\ValidationException;

class WorkflowUi
{
    public static function actions(string $model): array
    {
        if ($model === ChangeReportDraft::class) {
            return [
                Action::make('change_details')->label('Edit change preparation')
                    ->visible(fn ($record) => Filament::getCurrentPanel()->getId() === 'hei' && auth()->user()->can('reports.submit') && ! $record->changeReport()->exists())
                    ->fillForm(fn ($record) => $record->only(['title', 'details']))
                    ->schema(fn ($record) => ChangeReportForms::details($record->institution_id))
                    ->action(fn ($record, array $data) => app(SaveChangeReportDraft::class)->execute(auth()->user(), $data, $record)),
                Action::make('change_preview')->label('Preview / print change (Annex I)')
                    ->url(fn ($record) => route('change.draft', $record))->openUrlInNewTab(),
                Action::make('change_file')->label('Send immediate notice / file signed report')->requiresConfirmation()
                    ->modalDescription('Leave the document empty for prompt immediate intake. Select the corresponding clean signed Change report PDF for signed filing. This freezes the preparation; later updates use a new follow-up.')
                    ->visible(fn ($record) => Filament::getCurrentPanel()->getId() === 'hei' && auth()->user()->can('reports.submit') && ! $record->changeReport()->exists())
                    ->schema(fn ($record) => [
                        Select::make('document_id')->label('Clean signed Change report PDF (optional for an immediate notice)')->searchable()
                            ->options(fn () => Document::whereIn('id', array_keys(Fields::related(Document::class, 'original_name', $record->institution_id)))
                                ->where('is_signed', true)->where('mime', 'application/pdf')->where('confidentiality', 'institutional')
                                ->whereHas('documentType', fn ($q) => $q->where('code', 'change'))->pluck('original_name', 'id')->all()),
                    ])
                    ->action(function ($record, array $data, Action $action): void {
                        try {
                            app(SubmitReport::class)->execute(auth()->user(), 'change', ['change_report_draft_id' => $record->id,
                                'document_id' => empty($data['document_id']) ? null : (int) $data['document_id']]);
                        } catch (ValidationException $exception) {
                            throw ValidationException::withMessages(['mountedActions.'.$action->getNestingIndex().'.data.document_id' => collect($exception->errors())->flatten()->implode(' ')]);
                        }
                        Notification::make()->title('Change received; preserved copy is in Change reports.')->success()->send();
                    }),
            ];
        }
        if ($model === ChangeReport::class) {
            return [
                Action::make('change_snapshot')->label('Preserved change (Annex I)')
                    ->visible(fn ($record) => ! empty($record->annex_i))
                    ->url(fn ($record) => route('change.report', $record))->openUrlInNewTab(),
            ];
        }
        if ($model === IncidentReportDraft::class) {
            return [
                Action::make('incident_details')->label('Edit incident preparation')
                    ->visible(fn ($record) => Filament::getCurrentPanel()->getId() === 'hei' && auth()->user()->can('reports.submit') && ! $record->incidentReport()->exists())
                    ->fillForm(fn ($record) => $record->only(['title', 'details']))
                    ->schema(fn ($record) => IncidentReportForms::details($record->institution_id))
                    ->action(fn ($record, array $data) => app(SaveIncidentReportDraft::class)->execute(auth()->user(), $data, $record)),
                Action::make('incident_preview')->label('Preview / print incident (Annex H)')
                    ->url(fn ($record) => route('incident.draft', $record))->openUrlInNewTab(),
                Action::make('incident_file')->label('Send initial notice / file signed report')->requiresConfirmation()
                    ->modalDescription('Leave the document empty for prompt initial intake. Select the corresponding clean signed Incident report PDF for signed filing. This freezes the preparation; later updates use a new supplement.')
                    ->visible(fn ($record) => Filament::getCurrentPanel()->getId() === 'hei' && auth()->user()->can('reports.submit') && ! $record->incidentReport()->exists())
                    ->schema(fn ($record) => [
                        Select::make('document_id')->label('Clean signed Incident report PDF (optional for an initial notice)')->searchable()
                            ->options(fn () => Document::whereIn('id', array_keys(Fields::related(Document::class, 'original_name', $record->institution_id)))
                                ->where('is_signed', true)->where('mime', 'application/pdf')->where('confidentiality', 'sensitive')
                                ->whereHas('documentType', fn ($q) => $q->where('code', 'incident'))->pluck('original_name', 'id')->all()),
                    ])
                    ->action(function ($record, array $data, Action $action): void {
                        try {
                            app(SubmitReport::class)->execute(auth()->user(), 'incident', ['incident_report_draft_id' => $record->id,
                                'document_id' => empty($data['document_id']) ? null : (int) $data['document_id']]);
                        } catch (ValidationException $exception) {
                            throw ValidationException::withMessages(['mountedActions.'.$action->getNestingIndex().'.data.document_id' => collect($exception->errors())->flatten()->implode(' ')]);
                        }
                        Notification::make()->title('Incident received; preserved copy is in Incident reports.')->success()->send();
                    }),
            ];
        }
        if ($model === IncidentReport::class) {
            return [
                Action::make('incident_snapshot')->label('Preserved incident (Annex H)')
                    ->visible(fn ($record) => ! empty($record->annex_h))
                    ->url(fn ($record) => route('incident.report', $record))->openUrlInNewTab(),
            ];
        }
        if ($model === CompletionReportDraft::class) {
            return [
                Action::make('roc_details')->label('Edit ROC draft')
                    ->visible(fn ($record) => Filament::getCurrentPanel()->getId() === 'hei' && auth()->user()->can('reports.submit') && ! $record->completionReport()->exists())
                    ->fillForm(fn ($record) => $record->only(['title', 'details']))
                    ->schema(fn ($record) => CompletionReportForms::details($record->institution_id, $record->activity_id))
                    ->action(fn ($record, array $data) => app(SaveCompletionReportDraft::class)->execute(auth()->user(), $data, $record)),
                Action::make('roc_preview')->label('Preview / print ROC (Annex G)')
                    ->url(fn ($record) => route('roc.draft', $record))->openUrlInNewTab(),
                Action::make('roc_file')->label('File signed ROC')->requiresConfirmation()
                    ->modalDescription('This files the prepared report, freezes its data and records the actual conclusion. Select the signed original corresponding to this draft. The file must have passed malware scanning.')
                    ->visible(fn ($record) => Filament::getCurrentPanel()->getId() === 'hei' && auth()->user()->can('reports.submit') && ! $record->completionReport()->exists())
                    ->schema(fn ($record) => [
                        Select::make('document_id')->label('Clean signed completion-report PDF')->required()->searchable()
                            ->options(fn () => Document::whereIn('id', array_keys(Fields::related(Document::class, 'original_name', $record->institution_id)))
                                ->where('is_signed', true)->where('mime', 'application/pdf')->whereHas('documentType', fn ($q) => $q->where('code', 'completion'))
                                ->pluck('original_name', 'id')->all()),
                    ])
                    ->action(function ($record, array $data, Action $action): void {
                        try {
                            app(SubmitReport::class)->execute(auth()->user(), 'completion', ['completion_report_draft_id' => $record->id, 'document_id' => (int) $data['document_id']]);
                        } catch (ValidationException $exception) {
                            throw ValidationException::withMessages(['mountedActions.'.$action->getNestingIndex().'.data.document_id' => collect($exception->errors())->flatten()->implode(' ')]);
                        }
                        Notification::make()->title('Signed ROC filed; preserved copy is in Completion reports.')->success()->send();
                    }),
            ];
        }
        if ($model === CompletionReport::class) {
            return [
                Action::make('roc_snapshot')->label('Preserved ROC (Annex G)')
                    ->visible(fn ($record) => ! empty($record->annex_g) && (! $record->incidents_encountered || auth()->user()->can('panel.hei') || auth()->user()->can('incidents.view')))
                    ->url(fn ($record) => route('roc.report', $record))->openUrlInNewTab(),
            ];
        }
        if ($model === CertificationDraft::class) {
            return [
                Action::make('certification_details')->label('Edit certification draft')
                    ->visible(fn () => Filament::getCurrentPanel()->getId() === 'hei' && auth()->user()->can('activities.manage'))
                    ->fillForm(fn ($record) => ['title' => $record->title, 'details' => collect($record->details)->except('definition')->all()])
                    ->schema(fn ($record) => CertificationForms::details($record->institution_id, $record->details['definition']))
                    ->action(fn ($record, array $data) => app(SaveCertificationDraft::class)->execute(auth()->user(), $data, $record)),
                Action::make('certification_preview')->label('Preview / print certification (Annex F)')
                    ->url(fn ($record) => route('certification.draft', $record))->openUrlInNewTab(),
            ];
        }
        if ($model === AccountabilityAffidavit::class) {
            return [
                Action::make('saa_details')->label('Edit SAA draft')
                    ->visible(fn () => Filament::getCurrentPanel()->getId() === 'hei' && auth()->user()->can('activities.manage'))
                    ->fillForm(fn ($record) => $record->only(['title', 'details']))
                    ->schema(fn ($record) => SaaForms::details($record->institution_id))
                    ->action(fn ($record, array $data) => app(SaveSaaDraft::class)->execute(auth()->user(), $data, $record)),
                Action::make('saa_preview')->label('Preview / print SAA (Annex C)')
                    ->url(fn ($record) => route('saa.draft', $record))->openUrlInNewTab(),
            ];
        }
        if ($model === Calendar::class || $model === Activity::class) {
            $a = [];
            if ($model === Calendar::class) {
                $a[] = Action::make('cloca_details')->label('Edit CLOCA header / signatories')
                    ->visible(fn () => Filament::getCurrentPanel()->getId() === 'hei' && auth()->user()->can('activities.manage'))
                    ->fillForm(fn ($record) => $record->only(['title', 'effective_on', 'responsible_office', 'annex_b']))
                    ->schema(fn ($record) => ClocaForms::calendarDetails($record->institution_id))
                    ->action(fn ($record, array $data) => app(SaveClocaCalendar::class)->execute(auth()->user(), $data, $record));
                $a[] = Action::make('cloca_preview')->label('Preview / print CLOCA (Annex B)')
                    ->url(fn ($record) => route('cloca.calendar', $record))->openUrlInNewTab();
            }
            if ($model === Activity::class && Filament::getCurrentPanel()->getId() === 'hei') {
                $a[] = Action::make('consent_form')->label('Prepare / print consent (Annex L)')
                    ->visible(fn () => auth()->user()->can('activities.manage'))
                    ->url(fn ($record) => route('consent.form', $record->latestVersion))->openUrlInNewTab();
                $a[] = Action::make('revise')->url(fn ($record) => ActivityResource::getUrl('edit', ['record' => $record]))->visible(fn () => auth()->user()->can('activities.manage'));
            }
            if ($model === Activity::class) {
                $a[] = Action::make('classification_history')->label('Classification history and requirements')->modal()
                    ->schema(fn ($record) => [Text::make(ClassificationPresentation::history($record))])->modalSubmitAction(false);
                $a[] = Action::make('confirm_classification')->label('Confirm saved classification')
                    ->visible(fn ($record) => Filament::getCurrentPanel()->getId() === 'hei' && auth()->user()->can('submissions.submit') && app(Access::class)->owns(auth()->user(), $record->institution_id, true) && ClassificationPresentation::latest($record)?->state === 'recommended')
                    ->fillForm(fn ($record) => ['expected_version_id' => $record->latestVersion->id])
                    ->schema(fn ($record) => [Text::make(ClassificationPresentation::history($record)),
                        Hidden::make('expected_version_id')->required(), Textarea::make('reason')->label('Authorized confirmation / change explanation')->required()->maxLength(4000)])
                    ->action(fn ($record, array $data) => app(ConfirmActivityClassification::class)->execute(auth()->user(), $record, (int) $data['expected_version_id'], $data['reason']));
                $a[] = Action::make('racp_preview')->label('Preview / print RACP (Annex E)')
                    ->visible(fn ($record) => ! empty($record->latestVersion->racp))
                    ->url(fn ($record) => route('racp.activity', $record))->openUrlInNewTab();
                $a[] = Action::make('itinerary_preview')->label('Preview / print itinerary (Annex J)')
                    ->visible(fn ($record) => ! empty($record->latestVersion->annex_j) || $record->latestVersion->itineraryItems()->exists())
                    ->url(fn ($record) => route('itinerary.activity', $record))->openUrlInNewTab();
                $a[] = Action::make('diagnostic_preview')->label('Preview / print diagnostic (Annex D)')
                    ->visible(fn ($record) => ! empty($record->latestVersion->diagnostic))
                    ->url(fn ($record) => route('diagnostic.activity', $record))->openUrlInNewTab();
            }
            $a[] = Action::make('readiness')->label('Authorization / restrictions')->schema(fn ($record) => [Text::make(implode(' ', app(ActivityReadiness::class)->summary($record)) ?: 'No recorded restriction found. This is not CHED approval or a guarantee of safety.')])->modalSubmitAction(false);
            if ($model === Activity::class) {
                $a[] = Action::make('concluded')->label('Record actual conclusion')->visible(fn ($record) => auth()->user()->can('reports.submit') && ! $record->actual_ended_at)->schema([DateTimePicker::make('ended_at')->required()->helperText('Asia/Manila; records the completion-report deadline anchor.')])->action(fn ($record, array $data) => app(RecordActivityConclusion::class)->execute(auth()->user(), $record, $data['ended_at']));
            }
            $a[] = self::submitAction();

            return $a;
        }
        if ($model === MonitoringRecord::class) {
            return [
                Action::make('monitoring_print')->label('Preview / print monitoring (Annex M)')
                    ->visible(fn ($record) => ! empty($record->annex_m))
                    ->url(fn ($record) => route('monitoring.print', $record))->openUrlInNewTab(),
                Action::make('corrective_action')->visible(fn () => auth()->user()->can('monitoring.manage'))->schema([Textarea::make('action')->required(), TextInput::make('responsible_party')->required(), DatePicker::make('due_on')->required()])->action(fn ($record, array $data) => app(ManageCorrectiveAction::class)->create(auth()->user(), $record, $data)),
            ];
        }
        if ($model === Document::class) {
            return [Action::make('preview')->label('Open image')->url(fn ($record) => route('documents.preview', $record->public_id))->visible(fn ($record) => $record->scan_status === 'clean' && in_array($record->mime, ['image/png', 'image/jpeg'], true))->openUrlInNewTab(), Action::make('download')->url(fn ($record) => route('documents.download', $record->public_id))->visible(fn ($record) => $record->scan_status === 'clean')->openUrlInNewTab()];
        }
        if ($model === CaseRecord::class) {
            return [
                Action::make('case_status')->label('Record case event')->visible(fn () => auth()->user()->can('cases.sensitive'))->schema(fn ($record) => [Select::make('status')->options(['received' => 'Received', 'referred' => 'Referred', 'investigating' => 'Investigating', 'fact_finding' => 'Fact-finding evidence', 'committee_recommendation' => 'Committee recommendation', 'osds_referral' => 'OSDS referral', 'decided' => 'Decided', 'closed' => 'Closed'])->required(), Textarea::make('notes')->required(), Select::make('document_id')->options(fn () => Fields::related(Document::class, 'original_name', $record->institution_id))])->action(fn ($record, array $data) => app(RecordCaseEvent::class)->execute(auth()->user(), $record, $data['status'], $data['notes'], empty($data['document_id']) ? null : (int) $data['document_id'])),
            ];
        }
        if ($model === Submission::class) {
            return [
                Action::make('submitted_classification')->label('Submitted classification and requirements')->modal()
                    ->schema(fn ($record) => [Text::make(ClassificationPresentation::submission($record))])->modalSubmitAction(false),
                Action::make('certification_snapshot')->label('Preserved certification (Annex F)')
                    ->visible(fn ($record) => ! empty($record->snapshot['annex_f']))
                    ->url(fn ($record) => route('certification.submission', $record->public_id))->openUrlInNewTab(),
                Action::make('racp_snapshot')->label('Preserved RACPs (Annex E)')
                    ->visible(fn ($record) => ! empty($record->snapshot['annex_e']))
                    ->url(fn ($record) => route('racp.submission', ['publicId' => $record->public_id]))->openUrlInNewTab(),
                Action::make('itinerary_snapshot')->label('Preserved itineraries (Annex J)')
                    ->visible(fn ($record) => ! empty($record->snapshot['annex_j']))
                    ->url(fn ($record) => route('itinerary.submission', ['publicId' => $record->public_id]))->openUrlInNewTab(),
                Action::make('diagnostic_snapshot')->label('Preserved diagnostics (Annex D)')
                    ->visible(fn ($record) => ! empty($record->snapshot['annex_d']))
                    ->url(fn ($record) => route('diagnostic.submission', ['publicId' => $record->public_id]))->openUrlInNewTab(),
                Action::make('saa_snapshot')->label('Preserved SAA draft (Annex C)')
                    ->visible(fn ($record) => ! empty($record->snapshot['annex_c']))
                    ->url(fn ($record) => route('saa.submission', $record->public_id))->openUrlInNewTab(),
                Action::make('cloca_snapshot')->label('Preserved CLOCA (Annex B)')
                    ->visible(fn ($record) => $record->calendar_id && ! empty($record->snapshot['annex_b']))
                    ->url(fn ($record) => route('cloca.submission', $record->public_id))->openUrlInNewTab(),
                Action::make('request_evidence')->visible(fn () => auth()->user()->can('submissions.review'))->schema([Select::make('document_type_id')->options(fn () => DocumentType::where('confidentiality', '!=', 'restricted')->pluck('name', 'id'))->required(), Textarea::make('reason')->required(), DateTimePicker::make('due_at')->required()])->action(fn ($record, array $data) => app(RequestEvidence::class)->execute(auth()->user(), $record, $data)),
                Action::make('review')->visible(fn ($record) => auth()->user()->can('submissions.review') && in_array($record->status, ['submitted', 'under_review']))->schema([Select::make('outcome')->options(['complete' => 'Complete for acknowledgment', 'corrections_requested' => 'Corrections requested'])->required(), Textarea::make('remarks')->required()])->action(function ($record, array $data) {
                    app(ReviewSubmission::class)->execute(auth()->user(), $record, $data['outcome'], $data['remarks']);
                    Notification::make()->title('Review recorded')->success()->send();
                }),
                Action::make('prepare_annex_k')->label('Prepare letter (Annex K)')->visible(fn ($record) => auth()->user()->can('acknowledgments.issue') && $record->type === 'calendar' && $record->status === 'ready_for_acknowledgment')
                    ->fillForm(fn ($record) => AcknowledgmentDraft::where('submission_id', $record->id)->first()?->payload['fields'] ?? [
                        'address' => data_get($record->snapshot, 'annex_b.header.address') ?: $record->institution->address,
                        'letter_date' => now()->timezone('Asia/Manila')->format('Y-m-d'),
                        'letterhead_text' => 'COMMISSION ON HIGHER EDUCATION'."\n".'Regional Office IV-A (CALABARZON)',
                    ])
                    ->schema([
                        Text::make('Review the exact submitted signed CLOCA and notarized SAA. This prepares an unsigned receipt/completeness letter, not activity authorization. The summary uses frozen plans; counts are student participations.'),
                        TextInput::make('reference')->required()->maxLength(255),
                        TextInput::make('recipient')->label('Addressee name')->required()->maxLength(255),
                        TextInput::make('recipient_position')->required()->maxLength(255),
                        Textarea::make('address')->label('HEI address')->required()->maxLength(1000),
                        TextInput::make('salutation')->label('Dear')->required()->maxLength(255),
                        DatePicker::make('letter_date')->required(),
                        TextInput::make('signatory')->label('CHEDRO Director name (Director IV)')->required()->maxLength(255),
                        Textarea::make('letterhead_text')->label('CHED letterhead text')->required()->maxLength(1000),
                        Checkbox::make('originals_checked')->label('I inspected the submitted duly signed CLOCA and duly notarized SAA originals.')->accepted()->required(),
                    ])->action(function ($record, array $data, Action $action) {
                        try {
                            app(SaveAcknowledgmentDraft::class)->execute(auth()->user(), $record, $data);
                        } catch (ValidationException $exception) {
                            throw ValidationException::withMessages(['mountedActions.'.$action->getNestingIndex().'.data.originals_checked' => collect($exception->errors())->flatten()->implode(' ')]);
                        }
                        Notification::make()->title('Annex K prepared. Preview / print, then sign externally.')->success()->send();
                    }),
                Action::make('print_annex_k')->label('Preview / print letter (Annex K)')->visible(fn ($record) => auth()->user()->can('acknowledgments.issue') && $record->status === 'ready_for_acknowledgment' && AcknowledgmentDraft::where('submission_id', $record->id)->exists())->url(fn ($record) => route('acknowledgment.print', $record->public_id))->openUrlInNewTab(),
                Action::make('preserved_annex_k')->label('Preserved letter preparation (Annex K)')->visible(fn ($record) => ! empty($record->acknowledgment?->summary['annex_k']))->url(fn ($record) => route('acknowledgment.print', ['publicId' => $record->public_id, 'issued' => 1]))->openUrlInNewTab(),
                Action::make('acknowledge')->label('Record signed acknowledgment')->visible(fn ($record) => auth()->user()->can('acknowledgments.issue') && $record->status === 'ready_for_acknowledgment')->fillForm(fn ($record) => ($fields = AcknowledgmentDraft::where('submission_id', $record->id)->first()?->payload['fields']) ? $fields + ['use_annex_k' => true] : [])->schema(fn ($record) => [
                    Text::make('Receipt/completeness only. Does not authorize the activity.'),
                    Checkbox::make('use_annex_k')->label('Preserve this submission’s saved Annex K preparation with the signed letter.')->default(false),
                    Select::make('document_id')->options(fn () => Document::where('institution_id', $record->institution_id)->where('scan_status', 'clean')->where('is_signed', true)->where('mime', 'application/pdf')->whereHas('documentType', fn ($q) => $q->where('code', 'acknowledgment'))->get()->filter(fn ($document) => app(Access::class)->document(auth()->user(), $document))->pluck('original_name', 'id'))->required(),
                    TextInput::make('reference')->required(), TextInput::make('recipient')->required(), TextInput::make('signatory')->required(), DatePicker::make('letter_date')->required(),
                ])->action(function ($record, array $data, Action $action) {
                    try {
                        app(IssueAcknowledgment::class)->execute(auth()->user(), $record, $data);
                    } catch (ValidationException $exception) {
                        throw ValidationException::withMessages(['mountedActions.'.$action->getNestingIndex().'.data.document_id' => collect($exception->errors())->flatten()->implode(' ')]);
                    }
                    Notification::make()->title('Acknowledgment recorded')->success()->send();
                }),
                Action::make('download_acknowledgment')->label('Signed acknowledgment')->visible(fn ($record) => $record->acknowledgment !== null)->url(fn ($record) => $record->acknowledgment ? route('documents.download', $record->acknowledgment->document->public_id) : null)->openUrlInNewTab(),
                Action::make('timeline')->schema(fn ($record) => array_merge([Text::make('Received '.$record->received_at->timezone(config('lakbay.timezone'))->format('M d, Y H:i').' • '.$record->status)], $record->reviews->map(fn ($r) => Text::make($r->created_at->timezone(config('lakbay.timezone'))->format('M d, Y H:i').' — '.$r->outcome.': '.$r->remarks))->all()))->modalSubmitAction(false),
            ];
        }
        if ($model === ClearanceRequest::class) {
            return [
                Action::make('routing')->label('Record CHEDRO / OSDS routing')->visible(fn ($record) => ! $record->decision && auth()->user()->can('cases.sensitive'))->schema(fn ($record) => [
                    Select::make('stage')->options(['chedro_endorsement' => 'CHEDRO endorsement', 'osds_received' => 'OSDS receipt', 'osds_review' => 'OSDS review', 'ceb_routed' => 'Routed to CEB'])->required(),
                    TextInput::make('reference')->required(), DateTimePicker::make('occurred_at')->required(),
                    Select::make('document_id')->options(fn () => Fields::related(Document::class, 'original_name', $record->institution_id))->required(),
                ])->action(fn ($record, array $data) => app(RecordClearanceRouting::class)->execute(auth()->user(), $record, $data)),
                Action::make('decision')->label('Record external CEB decision')->visible(fn ($record) => ! $record->decision && auth()->user()->can('cases.sensitive'))->schema(fn ($record) => [
                    Select::make('decision')->options(['granted' => 'Granted', 'denied' => 'Denied'])->required(), Select::make('document_id')->options(fn () => Fields::related(Document::class, 'original_name', $record->institution_id))->required(),
                    DateTimePicker::make('decided_at')->required(), DateTimePicker::make('received_at')->required(), TextInput::make('receipt_reference'),
                ])->action(fn ($record, array $data) => app(RecordClearanceDecision::class)->execute(auth()->user(), $record, $data['decision'], (int) $data['document_id'], $data['decided_at'], $data['received_at'], $data['receipt_reference'] ?? null)),
                Action::make('reconsideration_decision')->label('Record final external decision')->visible(fn ($record) => auth()->user()->can('cases.sensitive') && $record->reconsideration && ! $record->reconsideration->decision)->schema(fn ($record) => [Select::make('decision')->options(['granted' => 'Granted', 'denied' => 'Denied'])->required(), Select::make('document_id')->options(fn () => Fields::related(Document::class, 'original_name', $record->institution_id))->required()])->action(fn ($record, array $data) => app(RecordReconsiderationDecision::class)->execute(auth()->user(), $record, $data['decision'], (int) $data['document_id'])),
                Action::make('reconsideration')->visible(fn ($record) => $record->decision === 'denied' && ! $record->reconsideration)->schema(fn ($record) => [
                    DateTimePicker::make('filed_at')->required(), Textarea::make('grounds')->required(), Select::make('document_id')->options(fn () => Fields::related(Document::class, 'original_name', $record->institution_id))->required(),
                ])->action(fn ($record, array $data) => app(RecordReconsideration::class)->execute(auth()->user(), $record, $data['filed_at'], $data['grounds'], (int) $data['document_id'])),
            ];
        }

        return [];
    }

    private static function versions($record)
    {
        return $record instanceof Calendar ? $record->activities()->with('latestVersion')->get()->pluck('latestVersion')->filter() : collect([$record->latestVersion])->filter();
    }

    private static function evidenceSchema($record, ?int $policyId, ?int $previousId = null): array
    {
        if (! $policyId) {
            return [];
        } $policy = PolicyVersion::find($policyId);
        if (! $policy) {
            return [];
        }
        if (! $previousId) {
            $policy = app(PolicyRules::class)->forDraft($policy);
        }
        $rules = collect();
        $stage = $record instanceof Calendar ? 'annual_calendar' : 'pre_activity';
        foreach (self::versions($record) as $v) {
            $rules = $rules->merge(app(RequirementResolver::class)->resolve($v, $policy, $stage));
            if (PolicyRules::isCmo($policy) && $stage === 'pre_activity') {
                $rules = $rules->merge(app(RequirementResolver::class)->resolve($v, $policy, 'requested_evidence'));
            }
        }
        $fields = [];
        foreach ($rules->unique('id') as $r) {
            $name = 'evidence.'.$r->id;
            $fields[] = Group::make()->schema([
                Text::make($r->requirementDefinition->title.' • '.$r->source_reference),
                Select::make($name.'.status')->options($r->evidence_mode === 'certified_retained' ? ['certified_retained' => 'Certified as retained by HEI', 'submitted' => 'Submitted'] : ['submitted' => 'Submitted'])->default($r->evidence_mode)->required(),
                Text::make('Stage: '.$r->stage.'. Deadline: '.$r->deadline_status.'. Supporting evidence: '.implode(', ', $r->supporting_document_codes ?? [])),
                Select::make($name.'.document_ids')->label('Evidence covering included activities')->multiple()->options(fn () => Document::whereIn('id', array_keys(Fields::related(Document::class, 'original_name', $record->institution_id)))->with('documentType')->get()
                    ->filter(fn ($doc) => ($doc->document_type_id === $r->requirementDefinition->document_type_id || in_array($doc->documentType->code, $r->supporting_document_codes ?? [], true)) &&
                        (($doc->document_type_id !== $r->requirementDefinition->document_type_id || $r->signature_mode !== 'signed') && $doc->documentType->code !== 'certification' || $doc->is_signed))
                    ->pluck('original_name', 'id')),
                Textarea::make($name.'.reason')->label('Certification / retained-evidence reference'),
            ]);
        }

        return $fields;
    }

    private static function submitAction(): Action
    {
        return Action::make('submit')->label('Submit / resubmit')->visible(fn () => auth()->user()->can('submissions.submit'))->modalHeading('Submit compliance package')->schema(fn ($record) => [
            Text::make(fn ($livewire) => $livewire->getErrorBag()->first('mountedActions.'.$livewire->getMountedAction()->getNestingIndex().'.data.submission'))
                ->color('danger'),
            Text::make($record instanceof Calendar ? 'CMO 24: annual calendar package. Submit later pre-activity evidence from each activity.' : 'CMO 24: pre-activity package. Category 1 requires a recorded annual calendar package first.'),
            Text::make('A frozen revision will be created. Documents selected here must cover every included activity. Late submissions are recorded and flagged.'),
            Select::make('policy_id')->options(fn () => PolicyVersion::query()->when(! (app()->environment(['local', 'testing']) && config('lakbay.allow_draft_policy')), fn ($q) => $q->where('status', 'confirmed'))->pluck('title', 'id'))->required()->live()
                ->afterStateUpdated(function (Select $component, $state, Get $get) {
                    if ($state && ! $get('previous_id')) {
                        $component->state(app(PolicyRules::class)->forDraft(PolicyVersion::findOrFail($state))->id);
                    }
                    $component->getContainer()->getComponent('submissionEvidence')->getChildSchema()->fill();
                }),
            Select::make('previous_id')->label('Previous correction request (optional)')->options(fn () => Submission::where('institution_id', $record->institution_id)->where('status', 'corrections_requested')->where('calendar_id', $record instanceof Calendar ? $record->id : null)->pluck('reference', 'id'))
                ->live()->afterStateUpdated(function (Select $component, $state, Set $set, Get $get) {
                    if ($state) {
                        $set('policy_id', Submission::findOrFail($state)->policy_version_id);
                    } elseif ($get('policy_id')) {
                        $set('policy_id', app(PolicyRules::class)->forDraft(PolicyVersion::findOrFail($get('policy_id')))->id);
                    }
                    $component->getContainer()->getComponent('submissionEvidence')->getChildSchema()->fill();
                }),
            Select::make('certification_draft_id')->label('Matching certification draft to preserve (optional)')
                ->options(fn () => CertificationDraft::where('institution_id', $record->institution_id)->where('academic_period_id', $record->academic_period_id)->pluck('title', 'id'))
                ->helperText('Select the draft used for the signed certification covering this package. This preserves printable data; signed evidence and policy checklists remain separate.'),
            Select::make('saa_draft_id')->label('Matching SAA draft to preserve (optional)')
                ->options(fn () => AccountabilityAffidavit::where('institution_id', $record->institution_id)->where('academic_period_id', $record->academic_period_id)->pluck('title', 'id'))
                ->helperText('Select the draft used for the signed SAA covering this package. This preserves printable data; it does not replace the signed/notarized evidence upload.'),
            Group::make()->key('submissionEvidence')->schema(fn (Get $get) => self::evidenceSchema($record, $get('policy_id') ? (int) $get('policy_id') : null, $get('previous_id') ? (int) $get('previous_id') : null)),
        ])->action(function ($record, array $data, Action $action) {
            $evidence = [];
            foreach (self::versions($record) as $v) {
                $evidence[$v->id] = $data['evidence'] ?? [];
            }
            try {
                if ($record instanceof Calendar) {
                    $s = app(SubmitCalendar::class)->execute(auth()->user(), $record, (int) $data['policy_id'], $evidence, empty($data['previous_id']) ? null : (int) $data['previous_id'], empty($data['saa_draft_id']) ? null : (int) $data['saa_draft_id'], empty($data['certification_draft_id']) ? null : (int) $data['certification_draft_id']);
                } else {
                    $s = app(SubmitActivity::class)->execute(auth()->user(), [$record->id], (int) $data['policy_id'], $evidence, null, empty($data['previous_id']) ? null : (int) $data['previous_id'], saaDraftId: empty($data['saa_draft_id']) ? null : (int) $data['saa_draft_id'], certificationDraftId: empty($data['certification_draft_id']) ? null : (int) $data['certification_draft_id']);
                }
            } catch (ValidationException $exception) {
                throw ValidationException::withMessages(['mountedActions.'.$action->getNestingIndex().'.data.submission' => collect($exception->errors())->flatten()->implode(' ')]);
            }
            Notification::make()->title('Recorded '.$s->reference)->success()->send();
        });
    }
}
