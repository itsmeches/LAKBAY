<?php

namespace App\Filament\Ched\Pages;

use App\Jobs\GenerateExport;
use App\Models\Export;
use App\Models\RegionalReport;
use App\Services\Audit;
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
            Action::make('export')->schema([Select::make('format')->options(['csv' => 'CSV', 'pdf' => 'PDF'])->required(), DateTimePicker::make('cutoff_at')->required()->default(now())])->action(function (array $data) {
                $e = Export::create(['requested_by' => auth()->id(), 'filters' => $data]);
                GenerateExport::dispatch($e->id);
                Notification::make()->title('Export queued. Refresh this page after the worker runs.')->success()->send();
            }),
            Action::make('snapshot')->schema([TextInput::make('period')->required(), DateTimePicker::make('cutoff_at')->required()->default(now())])->action(function (array $data) {
                $report = app(RegionalReportBuilder::class)->build(auth()->user(), $data['cutoff_at']);
                $r = RegionalReport::create(['region_id' => auth()->user()->region_id, 'period' => $data['period'], 'cutoff_at' => $report['cutoff_at'], 'filters' => ['activities' => $report['activities']], 'totals' => $report['totals'], 'prepared_by' => auth()->id()]);
                Audit::record(auth()->user(), 'regional_report.snapshot', $r);
                Notification::make()->title('Regional snapshot frozen')->success()->send();
            }),
        ];
    }

    protected function getViewData(): array
    {
        return ['report' => app(RegionalReportBuilder::class)->build(auth()->user()), 'exports' => Export::where('requested_by', auth()->id())->latest()->limit(20)->get(), 'snapshots' => RegionalReport::where('region_id', auth()->user()->region_id)->latest()->limit(20)->get()];
    }
}
