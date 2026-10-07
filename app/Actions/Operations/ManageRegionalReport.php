<?php

namespace App\Actions\Operations;

use App\Models\Document;
use App\Models\RegionalReport;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use App\Services\Reporting\RegionalReportBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ManageRegionalReport
{
    private function authorize(User $actor): void
    {
        abort_unless($actor->is_active && $actor->hasVerifiedEmail() && $actor->region_id && $actor->can('panel.ched') && $actor->can('reports.export'), 403);
    }

    public function prepare(User $actor, array $data): RegionalReport
    {
        $this->authorize($actor);
        Validator::make($data, ['period' => 'required|string|max:255', 'cutoff_at' => 'required|date',
            'report_type' => 'required|in:regional,annual_monitoring', 'reporting_year' => 'required_if:report_type,annual_monitoring|nullable|integer|between:2026,2100',
            'period_from' => 'required_if:report_type,annual_monitoring|nullable|date'])->validate();
        abort_if(CarbonImmutable::parse($data['cutoff_at'], config('lakbay.timezone'))->isFuture(), 422, 'Reporting cutoff cannot be in the future (Asia/Manila).');
        $report = app(RegionalReportBuilder::class)->build($actor, $data['cutoff_at'], $data['period_from'] ?? null);
        $r = RegionalReport::create(['region_id' => $actor->region_id, 'period' => $data['period'], 'cutoff_at' => $report['cutoff_at'],
            'report_type' => $data['report_type'], 'reporting_year' => $data['reporting_year'] ?? null,
            'due_window' => $data['report_type'] === 'annual_monitoring' ? 'Last week of August; exact cutoff pending CHED confirmation' : null,
            'filters' => ['period_from' => $report['period_from'], 'activities' => $report['activities'], 'monitoring' => $report['monitoring'] ?? []], 'totals' => $report['totals'], 'prepared_by' => $actor->id]);
        Audit::record($actor, 'regional_report.snapshot', $r);

        return $r;
    }

    public function dispatch(User $actor, RegionalReport $report, array $data): void
    {
        $this->authorize($actor);
        abort_unless($report->region_id === $actor->region_id, 403);
        Validator::make($data, ['sent_at' => 'required|date', 'reference' => 'required|string|max:255', 'document_id' => 'required|integer'])->validate();
        abort_if(CarbonImmutable::parse($data['sent_at'], config('lakbay.timezone'))->isFuture(), 422, 'Dispatch time cannot be in the future (Asia/Manila).');
        $doc = Document::findOrFail($data['document_id']);
        abort_unless(app(Access::class)->regional($actor, $doc->institution_id) && app(Access::class)->document($actor, $doc) &&
            $doc->scan_status === 'clean' && $doc->is_signed && $doc->documentType->code === 'monitoring', 422);
        DB::transaction(function () use ($actor, $report, $data) {
            $r = RegionalReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
            abort_if($r->sent_to_osds_at !== null, 409);
            $r->update(['sent_to_osds_at' => CarbonImmutable::parse($data['sent_at'], config('lakbay.timezone'))->utc(), 'dispatch_reference' => $data['reference'], 'document_id' => $data['document_id']]);
            Audit::record($actor, 'regional_report.external_dispatch_recorded', $r);
        });
    }

    public function receive(User $actor, RegionalReport $report, array $data): void
    {
        $this->authorize($actor);
        abort_unless($report->region_id === $actor->region_id, 403);
        Validator::make($data, ['received_at' => 'required|date', 'reference' => 'required|string|max:255'])->validate();
        DB::transaction(function () use ($actor, $report, $data) {
            $r = RegionalReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
            $received = CarbonImmutable::parse($data['received_at'], config('lakbay.timezone'))->utc();
            abort_unless($r->sent_to_osds_at && ! $r->osds_received_at && $received->gte($r->sent_to_osds_at) && ! $received->isFuture(), 422);
            $r->update(['osds_received_at' => $received, 'receipt_reference' => $data['reference']]);
            Audit::record($actor, 'regional_report.osds_receipt_recorded', $r);
        });
    }
}
