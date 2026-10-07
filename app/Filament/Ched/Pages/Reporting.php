<?php

namespace App\Filament\Ched\Pages;

use App\Actions\Operations\ManageRegionalReport;
use App\Filament\Support\Fields;
use App\Jobs\GenerateExport;
use App\Models\Document;
use App\Models\Export;
use App\Models\RegionalReport;
use App\Services\Reporting\RegionalReportBuilder;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Reporting extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.reporting';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('reports.export') ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')->schema([Select::make('format')->options(['csv' => 'CSV', 'pdf' => 'PDF'])->required(), DateTimePicker::make('cutoff_at')->required()->default(now()),
                Select::make('regional_report_id')->label('Frozen snapshot (optional)')->options(fn () => RegionalReport::where('region_id', auth()->user()->region_id)->pluck('period', 'id'))])->action(function (array $data) {
                    $e = Export::create(['requested_by' => auth()->id(), 'filters' => $data]);
                    GenerateExport::dispatch($e->id);
                    Notification::make()->title('Export queued. Refresh this page after the worker runs.')->success()->send();
                }),
            Action::make('snapshot')->schema([TextInput::make('period')->required(), DateTimePicker::make('cutoff_at')->required()->default(now()),
                Select::make('report_type')->options(['regional' => 'Regional snapshot', 'annual_monitoring' => 'Annual M&E report to OSDS'])->default('regional')->required(), TextInput::make('reporting_year')->numeric(),
                DateTimePicker::make('period_from')->helperText('Annual reports require an explicit period start. The cutoff is its end; confirm the reporting period with CHED.')])->action(function (array $data) {
                    app(ManageRegionalReport::class)->prepare(auth()->user(), $data);
                    Notification::make()->title('Regional snapshot frozen')->success()->send();
                }),
            Action::make('dispatch')->label('Record OSDS dispatch')->schema([
                Select::make('report_id')->options(fn () => RegionalReport::where('region_id', auth()->user()->region_id)->whereNull('sent_to_osds_at')->pluck('period', 'id'))->required(),
                DateTimePicker::make('sent_at')->required(), TextInput::make('reference')->required(), Select::make('document_id')->options(fn () => Fields::related(Document::class, 'original_name'))->required(),
            ])->action(fn (array $data) => app(ManageRegionalReport::class)->dispatch(auth()->user(), RegionalReport::findOrFail($data['report_id']), $data)),
            Action::make('receipt')->label('Record OSDS receipt')->schema([
                Select::make('report_id')->options(fn () => RegionalReport::where('region_id', auth()->user()->region_id)->whereNotNull('sent_to_osds_at')->whereNull('osds_received_at')->pluck('period', 'id'))->required(),
                DateTimePicker::make('received_at')->required(), TextInput::make('reference')->required(),
            ])->action(fn (array $data) => app(ManageRegionalReport::class)->receive(auth()->user(), RegionalReport::findOrFail($data['report_id']), $data)),
        ];
    }

    protected function getViewData(): array
    {
        return ['report' => app(RegionalReportBuilder::class)->build(auth()->user()), 'exports' => Export::where('requested_by', auth()->id())->latest()->limit(20)->get(), 'snapshots' => RegionalReport::where('region_id', auth()->user()->region_id)->latest()->limit(20)->get()];
    }
}
