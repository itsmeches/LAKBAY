<?php

namespace App\Filament\Support;

use App\Models\Document;
use App\Models\Institution;
use App\Services\Access;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

abstract class ScopedResource extends Resource
{
    protected static string $readPermission = 'records.view';

    protected static ?string $createPermission = null;

    private static function workspaceNavigation(): array
    {
        $hei = str_contains(static::class, '\\Hei\\');

        return match (class_basename(static::$model)) {
            'ChangeReportDraft' => [$hei ? '3. After the activity' : 'Activity reports', $hei ? 'Prepare changes (Annex I)' : 'Change preparations (Annex I)', 15, 'heroicon-o-arrow-path'],
            'IncidentReportDraft' => [$hei ? '3. After the activity' : 'Activity reports', $hei ? 'Prepare incident (Annex H)' : 'Incident preparations (Annex H)', 25, 'heroicon-o-exclamation-triangle'],
            'CompletionReportDraft' => [$hei ? '3. After the activity' : 'Activity reports', $hei ? 'Prepare ROC (Annex G)' : 'ROC preparation records (Annex G)', 5, 'heroicon-o-document-text'],
            'AccountabilityAffidavit' => [$hei ? '1. Prepare' : 'Activity records', 'Sworn affidavits (SAA)', 25, 'heroicon-o-document-check'],
            'CertificationDraft' => [$hei ? '1. Prepare' : 'Activity records', 'Certifications (Annex F)', 26, 'heroicon-o-clipboard-document-check'],
            'AcademicPeriod' => [$hei ? '1. Prepare' : 'Activity records', 'Academic periods', 10, 'heroicon-o-calendar'],
            'Calendar' => [$hei ? '1. Prepare' : 'Activity records', 'Annual calendars (CLOCA)', 20, 'heroicon-o-calendar-days'],
            'Activity' => [$hei ? '1. Prepare' : 'Activity records', 'Activity plans', 30, 'heroicon-o-map'],
            'Document' => [$hei ? '2. Submit & respond' : 'Review & correspondence', 'Document library', 30, 'heroicon-o-document-text'],
            'Submission' => [$hei ? '2. Submit & respond' : 'Review & correspondence', $hei ? 'Submissions & letters' : 'Submission inbox', 10, 'heroicon-o-inbox'],
            'DocumentRequest' => [$hei ? '2. Submit & respond' : 'Review & correspondence', 'Evidence requests', 20, 'heroicon-o-document-magnifying-glass'],
            'CompletionReport' => [$hei ? '3. After the activity' : 'Activity reports', 'Completion reports', 10, 'heroicon-o-check-circle'],
            'ChangeReport' => [$hei ? '3. After the activity' : 'Activity reports', 'Change reports', 20, 'heroicon-o-arrow-path'],
            'IncidentReport' => [$hei ? '3. After the activity' : 'Activity reports', 'Incident reports', 30, 'heroicon-o-exclamation-triangle'],
            'MonitoringRecord' => ['Monitoring & support', 'Monitoring visits', 10, 'heroicon-o-clipboard-document-check'],
            'TechnicalAssistanceLog' => ['Monitoring & support', 'Technical assistance', 20, 'heroicon-o-chat-bubble-left-right'],
            'CorrectiveAction' => [$hei ? '4. Follow up' : 'Monitoring & support', 'Corrective actions', 30, 'heroicon-o-list-bullet'],
            'CaseRecord' => ['Restricted cases', 'Case records', 10, 'heroicon-o-lock-closed'],
            'ClearanceRequest' => ['Restricted cases', 'Clearance requests', 20, 'heroicon-o-shield-check'],
            'InstitutionalRestriction' => ['Restricted cases', 'Institutional restrictions', 30, 'heroicon-o-no-symbol'],
            'Institution' => ['Administration', 'Institutions', 10, 'heroicon-o-building-library'],
            'Campus' => ['Administration', 'Campuses', 20, 'heroicon-o-building-office-2'],
            default => [null, parent::getNavigationLabel(), 50, 'heroicon-o-folder'],
        };
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return static::workspaceNavigation()[0];
    }

    public static function getNavigationLabel(): string
    {
        return static::workspaceNavigation()[1];
    }

    public static function getNavigationSort(): ?int
    {
        return static::workspaceNavigation()[2];
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return static::workspaceNavigation()[3];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(static::$readPermission) ?? false;
    }

    public static function canCreate(): bool
    {
        return static::getCreateAuthorizationResponse()->allowed();
    }

    public static function getCreateAuthorizationResponse(): Response
    {
        $user = auth()->user();
        $panel = Filament::getCurrentPanel();

        return static::$createPermission && $user && $panel
            && $user->can(static::$createPermission) && $user->canAccessPanel($panel)
            ? Response::allow() : Response::deny();
    }

    public static function isGloballySearchable(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $q = app(Access::class)->scope(parent::getEloquentQuery(), auth()->user(), static::$model === Institution::class ? 'id' : 'institution_id');
        if (class_basename(static::$model) === 'IncidentReportDraft' && ! auth()->user()->can('panel.hei') && ! auth()->user()->can('incidents.view')) {
            $q->whereRaw('1=0');
        }
        if (class_basename(static::$model) === 'CompletionReportDraft' && ! auth()->user()->can('panel.hei') && ! auth()->user()->can('incidents.view')) {
            $q->where('details->incidents_encountered', false);
        }
        if (static::$model === Document::class) {
            $q->where(function ($q) {
                $q->where('confidentiality', 'institutional');
                if (auth()->user()->can('cases.sensitive')) {
                    $q->orWhere('confidentiality', 'restricted');
                }if (auth()->user()->can('incidents.view') || auth()->user()->can('panel.hei')) {
                    $q->orWhere('confidentiality', 'sensitive');
                }
            });
        }

        return $q;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(Definitions::form(static::$model));
    }

    public static function infolist(Schema $schema): Schema
    {
        $fields = Definitions::detailFields(static::$model);
        $entries = array_map(
            fn ($field) => static::recordEntry($field),
            array_values(array_diff($fields, ['snapshot'])),
        );
        $sections = [Section::make('Record overview')->schema($entries)->columns(2)->columnSpanFull()];
        if (in_array('snapshot', $fields, true)) {
            $sections[] = Section::make('Historical submission snapshot')->description('The record preserved at submission.')->schema([
                static::recordEntry('snapshot')->columnSpanFull(),
            ])->collapsible()->collapsed()->columnSpanFull();
        }

        if (static::$model === Document::class) {
            $sections[] = Section::make('Document preview')->schema([
                ImageEntry::make('preview')
                    ->state(fn ($record) => $record->scan_status === 'clean' && in_array($record->mime, ['image/png', 'image/jpeg'], true) ? route('documents.preview', $record->public_id) : null)
                    ->visible(fn ($record) => $record->scan_status === 'clean' && in_array($record->mime, ['image/png', 'image/jpeg'], true))
                    ->hiddenLabel()->imageHeight(400)
                    ->extraImgAttributes(['style' => 'max-width:100%;object-fit:contain'])
                    ->alt(fn ($record) => $record->original_name)->columnSpanFull(),
                TextEntry::make('preview_guidance')->hiddenLabel()->state(fn ($record) => match ($record->scan_status) {
                    'pending' => 'Your file is saved and awaiting malware scanning. Preview and download will become available after a successful scan.',
                    'infected' => 'This file failed malware scanning. Preview and download are blocked. Contact your administrator.',
                    default => in_array($record->mime, ['image/png', 'image/jpeg'], true) ? 'Use Open image to view at full size, or Download to save a copy.' : 'Preview supports PNG and JPG. Use Download to open this document.',
                })->columnSpanFull(),
            ])->columnSpanFull();
        }

        return $schema->components($sections);
    }

    public static function table(Table $table): Table
    {
        $columns = array_map(function ($field) {
            $column = static::formatRecordField(TextColumn::make($field)->searchable()->sortable()->wrap(), $field);
            if (in_array($field, ['status', 'scan_status', 'execution_status'], true)) {
                $column->badge()->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state)))
                    ->color(fn ($state) => match ($state) {
                        'acknowledged', 'clean', 'completed' => 'success',
                        'corrections_requested', 'pending', 'under_review' => 'warning',
                        'infected', 'failed', 'denied' => 'danger',
                        default => 'gray',
                    });
            }

            return $column;
        }, Definitions::columns(static::$model));
        if (class_basename(static::$model) === 'Submission') {
            $table->filters([
                SelectFilter::make('status')->options([
                    'submitted' => 'Submitted',
                    'under_review' => 'Under review',
                    'corrections_requested' => 'Corrections requested',
                    'ready_for_acknowledgment' => 'Ready for acknowledgment',
                    'acknowledged' => 'Acknowledged',
                    'superseded' => 'Superseded revision',
                ]),
            ]);
        }

        return $table->columns($columns)->recordActions([ViewAction::make(), ActionGroup::make(WorkflowUi::actions(static::$model))->label('More actions')->icon('heroicon-o-ellipsis-horizontal')])->defaultSort('id', 'desc')->paginated([10, 25, 50]);
    }

    private static function formatRecordField(TextColumn|TextEntry $component, string $field): TextColumn|TextEntry
    {
        $model = new (static::getModel());
        $segments = explode('.', $field);
        $attribute = array_pop($segments);
        foreach ($segments as $relationship) {
            if (! method_exists($model, $relationship)) {
                return $component;
            }
            $model = $model->{$relationship}()->getRelated();
        }

        $cast = explode(':', $model->getCasts()[$attribute] ?? '')[0];
        $label = Str::headline($attribute);
        if (in_array($cast, ['datetime', 'immutable_datetime'], true)) {
            $timezone = config('lakbay.timezone');

            return $component->label($label.' ('.$timezone.')')->dateTime('d M Y, H:i', timezone: $timezone);
        }
        if (in_array($cast, ['date', 'immutable_date'], true)) {
            return $component->label($label)->date('d M Y', timezone: 'UTC');
        }
        if ($cast === 'boolean') {
            return $component->label($label)->formatStateUsing(fn (?bool $state): ?string => $state === null ? null : ($state ? 'Yes' : 'No'));
        }

        return str_starts_with($field, 'latestVersion.') ? $component->label($label) : $component;
    }

    private static function recordEntry(string $field): TextEntry|ViewEntry
    {
        $model = new (static::getModel());
        foreach (explode('.', $field) as $segment) {
            if (method_exists($model, $segment)) {
                $model = $model->{$segment}()->getRelated();
            } else {
                $cast = explode(':', $model->getCasts()[$segment] ?? '')[0];
                if (in_array($cast, ['array', 'json', 'object', 'collection'], true)) {
                    return ViewEntry::make($field)->view('filament.infolists.structured-record')->columnSpanFull();
                }
            }
        }

        return static::formatRecordField(TextEntry::make($field), $field);
    }
}
