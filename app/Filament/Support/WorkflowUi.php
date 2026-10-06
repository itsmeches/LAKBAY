<?php

namespace App\Filament\Support;

use App\Actions\Activities\RecordActivityConclusion;
use App\Actions\Clearances\RecordClearanceDecision;
use App\Actions\Clearances\RecordReconsideration;
use App\Actions\Clearances\RecordReconsiderationDecision;
use App\Actions\Documents\RequestEvidence;
use App\Actions\Operations\ManageCorrectiveAction;
use App\Actions\Operations\RecordCaseEvent;
use App\Actions\Submissions\IssueAcknowledgment;
use App\Actions\Submissions\ReviewSubmission;
use App\Actions\Submissions\SubmitActivity;
use App\Actions\Submissions\SubmitCalendar;
use App\Filament\Hei\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\Calendar;
use App\Models\CaseRecord;
use App\Models\ClearanceRequest;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\MonitoringRecord;
use App\Models\PolicyVersion;
use App\Models\Submission;
use App\Services\Compliance\ActivityReadiness;
use App\Services\Compliance\RequirementResolver;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;

class WorkflowUi
{
    public static function actions(string $model): array
    {
        if ($model === Calendar::class || $model === Activity::class) {
            $a = [];
            if ($model === Activity::class && Filament::getCurrentPanel()->getId() === 'hei') {
                $a[] = Action::make('revise')->url(fn ($record) => ActivityResource::getUrl('edit', ['record' => $record]))->visible(fn () => auth()->user()->can('activities.manage'));
            }
            $a[] = Action::make('readiness')->label('Authorization / restrictions')->schema(fn ($record) => [Text::make(implode(' ', app(ActivityReadiness::class)->summary($record)) ?: 'No recorded restriction found. This is not CHED approval or a guarantee of safety.')])->modalSubmitAction(false);
            if ($model === Activity::class) {
                $a[] = Action::make('concluded')->label('Record actual conclusion')->visible(fn ($record) => auth()->user()->can('reports.submit') && ! $record->actual_ended_at)->schema([DateTimePicker::make('ended_at')->required()->helperText('Asia/Manila; records the completion-report deadline anchor.')])->action(fn ($record, array $data) => app(RecordActivityConclusion::class)->execute(auth()->user(), $record, $data['ended_at']));
            }
            $a[] = self::submitAction();

            return $a;
        }
        if ($model === MonitoringRecord::class) {
            return [Action::make('corrective_action')->visible(fn () => auth()->user()->can('monitoring.manage'))->schema([Textarea::make('action')->required(), TextInput::make('responsible_party')->required(), DatePicker::make('due_on')->required()])->action(fn ($record, array $data) => app(ManageCorrectiveAction::class)->create(auth()->user(), $record, $data))];
        }
        if ($model === Document::class) {
            return [Action::make('preview')->label('Open image')->url(fn ($record) => route('documents.preview', $record->public_id))->visible(fn ($record) => $record->scan_status === 'clean' && in_array($record->mime, ['image/png', 'image/jpeg'], true))->openUrlInNewTab(), Action::make('download')->url(fn ($record) => route('documents.download', $record->public_id))->visible(fn ($record) => $record->scan_status === 'clean')->openUrlInNewTab()];
        }
        if ($model === CaseRecord::class) {
            return [
                Action::make('case_status')->label('Record case event')->visible(fn () => auth()->user()->can('cases.sensitive'))->schema(fn ($record) => [Select::make('status')->options(['received' => 'Received', 'referred' => 'Referred', 'investigating' => 'Investigating', 'decided' => 'Decided', 'closed' => 'Closed'])->required(), Textarea::make('notes')->required(), Select::make('document_id')->options(fn () => Fields::related(Document::class, 'original_name', $record->institution_id))])->action(fn ($record, array $data) => app(RecordCaseEvent::class)->execute(auth()->user(), $record, $data['status'], $data['notes'], empty($data['document_id']) ? null : (int) $data['document_id'])),
            ];
        }
        if ($model === Submission::class) {
            return [
                Action::make('request_evidence')->visible(fn () => auth()->user()->can('submissions.review'))->schema([Select::make('document_type_id')->options(fn () => DocumentType::where('confidentiality', '!=', 'restricted')->pluck('name', 'id'))->required(), Textarea::make('reason')->required(), DateTimePicker::make('due_at')->required()])->action(fn ($record, array $data) => app(RequestEvidence::class)->execute(auth()->user(), $record, $data)),
                Action::make('review')->visible(fn ($record) => auth()->user()->can('submissions.review') && in_array($record->status, ['submitted', 'under_review']))->schema([Select::make('outcome')->options(['complete' => 'Complete for acknowledgment', 'corrections_requested' => 'Corrections requested'])->required(), Textarea::make('remarks')->required()])->action(function ($record, array $data) {
                    app(ReviewSubmission::class)->execute(auth()->user(), $record, $data['outcome'], $data['remarks']);
                    Notification::make()->title('Review recorded')->success()->send();
                }),
                Action::make('acknowledge')->label('Record signed acknowledgment')->visible(fn ($record) => auth()->user()->can('acknowledgments.issue') && $record->status === 'ready_for_acknowledgment')->schema(fn ($record) => [
                    Text::make('Receipt/completeness only. Does not authorize the activity.'),
                    Select::make('document_id')->options(fn () => Document::where('institution_id', $record->institution_id)->where('scan_status', 'clean')->whereHas('documentType', fn ($q) => $q->where('code', 'acknowledgment'))->pluck('original_name', 'id'))->required(),
                    TextInput::make('reference')->required(), TextInput::make('recipient')->required(), TextInput::make('signatory')->required(), DatePicker::make('letter_date')->required(),
                ])->action(function ($record, array $data) {
                    app(IssueAcknowledgment::class)->execute(auth()->user(), $record, $data);
                    Notification::make()->title('Acknowledgment recorded')->success()->send();
                }),
                Action::make('download_acknowledgment')->label('Signed acknowledgment')->visible(fn ($record) => $record->acknowledgment !== null)->url(fn ($record) => $record->acknowledgment ? route('documents.download', $record->acknowledgment->document->public_id) : null)->openUrlInNewTab(),
                Action::make('timeline')->schema(fn ($record) => array_merge([Text::make('Received '.$record->received_at->timezone(config('lakbay.timezone'))->format('M d, Y H:i').' • '.$record->status)], $record->reviews->map(fn ($r) => Text::make($r->created_at->timezone(config('lakbay.timezone'))->format('M d, Y H:i').' — '.$r->outcome.': '.$r->remarks))->all()))->modalSubmitAction(false),
            ];
        }
        if ($model === ClearanceRequest::class) {
            return [
                Action::make('decision')->label('Record external CEB decision')->visible(fn ($record) => ! $record->decision && auth()->user()->can('cases.sensitive'))->schema(fn ($record) => [
                    Select::make('decision')->options(['granted' => 'Granted', 'denied' => 'Denied'])->required(), Select::make('document_id')->options(fn () => Fields::related(Document::class, 'original_name', $record->institution_id))->required(),
                    DateTimePicker::make('decided_at')->required(), DateTimePicker::make('received_at')->required(),
                ])->action(fn ($record, array $data) => app(RecordClearanceDecision::class)->execute(auth()->user(), $record, $data['decision'], (int) $data['document_id'], $data['decided_at'], $data['received_at'])),
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

    private static function evidenceSchema($record, ?int $policyId): array
    {
        if (! $policyId) {
            return [];
        } $policy = PolicyVersion::find($policyId);
        if (! $policy) {
            return [];
        }
        $rules = collect();
        foreach (self::versions($record) as $v) {
            $rules = $rules->merge(app(RequirementResolver::class)->resolve($v, $policy));
        }
        $fields = [];
        foreach ($rules->unique('id') as $r) {
            $name = 'evidence.'.$r->id;
            $fields[] = Group::make()->schema([
                Text::make($r->requirementDefinition->title.' • '.$r->source_reference),
                Select::make($name.'.status')->options($r->evidence_mode === 'certified_retained' ? ['certified_retained' => 'Certified as retained by HEI', 'submitted' => 'Submitted'] : ['submitted' => 'Submitted'])->default($r->evidence_mode)->required(),
                Select::make($name.'.document_ids')->label('Signed evidence covering included activities')->multiple()->options(fn () => Document::where('institution_id', $record->institution_id)->where('document_type_id', $r->requirementDefinition->document_type_id)->where('scan_status', 'clean')->where('is_signed', true)->pluck('original_name', 'id')),
                Textarea::make($name.'.reason')->label('Certification / retained-evidence reference'),
            ]);
        }

        return $fields;
    }

    private static function submitAction(): Action
    {
        return Action::make('submit')->label('Submit / resubmit')->visible(fn () => auth()->user()->can('submissions.submit'))->modalHeading('Submit signed compliance package')->schema(fn ($record) => [
            Text::make('A frozen revision will be created. Documents selected here must cover every included activity. Late submissions are recorded and flagged.'),
            Select::make('policy_id')->options(fn () => PolicyVersion::query()->when(! config('lakbay.allow_draft_policy'), fn ($q) => $q->where('status', 'confirmed'))->pluck('title', 'id'))->required()->live(),
            Select::make('previous_id')->label('Previous correction request (optional)')->options(fn () => Submission::where('institution_id', $record->institution_id)->where('status', 'corrections_requested')->where('calendar_id', $record instanceof Calendar ? $record->id : null)->pluck('reference', 'id')),
            Group::make()->schema(fn (Get $get) => self::evidenceSchema($record, $get('policy_id') ? (int) $get('policy_id') : null)),
        ])->action(function ($record, array $data) {
            $evidence = [];
            foreach (self::versions($record) as $v) {
                $evidence[$v->id] = $data['evidence'] ?? [];
            }
            if ($record instanceof Calendar) {
                $s = app(SubmitCalendar::class)->execute(auth()->user(), $record, (int) $data['policy_id'], $evidence, empty($data['previous_id']) ? null : (int) $data['previous_id']);
            } else {
                $s = app(SubmitActivity::class)->execute(auth()->user(), [$record->id], (int) $data['policy_id'], $evidence, null, empty($data['previous_id']) ? null : (int) $data['previous_id']);
            }
            Notification::make()->title('Recorded '.$s->reference)->success()->send();
        });
    }
}
