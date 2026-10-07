<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Annex G - Report of Completion</title>
<style>
@page { margin: 34px 42px 44px; }
body { font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.3; color: #111; }
.source { text-align: right; font-size: 9px; margin-bottom: 26px; }
h1 { font-size: 11px; margin: 0 0 12px; }
h2 { font-size: 10px; margin: 16px 0 4px; page-break-after: avoid; }
.letterhead { text-align: center; white-space: pre-line; margin-bottom: 16px; }
.letterhead img { max-width: 470px; max-height: 72px; }
table { width: 100%; border-collapse: collapse; table-layout: fixed; }
td { border: 1px solid #222; padding: 3px 4px; vertical-align: top; overflow-wrap: anywhere; }
.label { font-weight: bold; }
.narrative { white-space: pre-line; overflow-wrap: anywhere; margin: 5px 0 12px; }
.box { display: inline-block; width: 8px; height: 8px; border: 1px solid #111; text-align: center; line-height: 8px; font-size: 8px; margin-right: 4px; }
.certification { margin-top: 15px; }
.signatory { text-align: center; page-break-inside: avoid; margin-top: 20px; }
.signature { margin: 35px auto 4px; border-bottom: 1px solid #222; width: 70%; }
.controls { padding: 16px; border: 1px solid #ccc; margin-bottom: 20px; }
@unless($pdf)
body { background: #eee; max-width: 720px; margin: 25px auto; padding: 25px; }
.sheet { background: white; padding: 30px; }
.controls a { color: #124c80; }
@endunless
@media print { .controls { display: none; } body { background: white; margin: 0; padding: 0; } .sheet { padding: 0; } }
</style>
</head>
<body>
@php
    $details = $roc['details'];
    $changed = ($details['implementation'] ?? null) === 'with_changes';
    $asApproved = ($details['implementation'] ?? null) === 'as_authorized';
    $incidents = (bool) ($details['incidents_encountered'] ?? false);
    $issued = filled($details['issued_on'] ?? null) ? \Carbon\CarbonImmutable::parse($details['issued_on']) : null;
@endphp
@if(!$pdf)
<div class="controls">
    <strong>{{ $preserved ? 'Preserved Annex G preparation copy' : 'Annex G working draft' }}</strong>
    <p>This is an unsigned preparation copy. Print, obtain the authorized official's signature, upload the signed completion-report PDF and wait for a clean scan before filing. Generating this copy does not submit a report or establish HEI/CHED authorization.</p>
    <p>Proposed dates, office and PIC/APIC use activity revision {{ $roc['activity_revision'] }}. Venue and total participants are reported actual values.</p>
    <p>Actual conclusion: {{ filled($details['ended_at'] ?? null) ? \Carbon\CarbonImmutable::parse($details['ended_at'], config('lakbay.timezone'))->timezone(config('lakbay.timezone'))->format('d M Y, H:i').' (Asia/Manila)' : 'Not recorded in this draft' }}. This anchors the report deadline; it is separate from the template's proposed dates.</p>
    @if($missing)
        <strong>Missing print fields:</strong><ul>@foreach($missing as $field)<li>{{ $field }}</li>@endforeach</ul>
    @else
        <a href="{{ $downloadUrl }}">Download printable PDF</a>
    @endif
    @if($incidents)<p>Linked incident records: {{ implode(', ', $details['incident_report_ids'] ?? []) ?: 'None linked yet' }}. Retrieve and attach the authorized incident report separately where applicable.</p>@endif
</div>
@endif
<div class="sheet">
<div class="source">Annex G of CMO No. 24, series of 2026</div>
<h1>ANNEX G. &nbsp; TEMPLATE: REPORT OF COMPLETION OF ACTIVITY, EVENT OR ENGAGEMENT (ROC)</h1>
<div class="letterhead">@if($letterhead)<img src="{{ $letterhead }}" alt="HEI letterhead">@else{{ $roc['header']['letterhead_text'] }}@endif</div>
<h2>I. &nbsp; BASIC INFORMATION</h2>
<table>
<tr><td colspan="2"><span class="label">Name of HEI:</span> {{ $roc['header']['hei_name'] }}</td></tr>
<tr><td><span class="label">Address:</span> {{ $roc['header']['address'] }}</td><td><span class="label">Type of HEI (SUC/LUC/Private):</span> {{ $roc['header']['hei_type'] }}</td></tr>
<tr><td><span class="label">Title of off-campus activity/event/engagement:</span> {{ $roc['basic']['title'] }}</td><td><span class="label">Category (1, 2, or 3):</span> {{ $roc['basic']['category'] }}</td></tr>
<tr><td><span class="label">Proposed Date/s:</span> {{ $roc['basic']['proposed_dates'] }}</td><td><span class="label">Total No. of Participants:</span> {{ $roc['basic']['participants'] }}</td></tr>
<tr><td colspan="2"><span class="label">Venue or Location/s:</span> {{ $roc['basic']['venue'] }}</td></tr>
<tr><td colspan="2"><span class="label">HEI Office/Organization Responsible:</span> {{ $roc['basic']['office'] }}</td></tr>
<tr><td colspan="2"><span class="label">Personnel-in-Charge (PIC):</span> {{ $roc['basic']['pic'] }}</td></tr>
<tr><td colspan="2"><span class="label">Assistant PIC (if applicable):</span> {{ $roc['basic']['apic'] }}</td></tr>
</table>
<h2>II. &nbsp; STATUS OF IMPLEMENTATION</h2>
<table>
<tr><td><span class="box">{{ $asApproved ? 'X' : '' }}</span>Conducted as approved</td></tr>
<tr><td><span class="box">{{ $changed ? 'X' : '' }}</span>Conducted with changes</td></tr>
</table>
@if($changed)<div class="narrative">{{ $details['change_explanation'] ?? '' }}</div>@endif
<h2>III. &nbsp; SUMMARY OF IMPLEMENTATION</h2>
<div class="narrative">{{ $details['summary'] ?? '' }}</div>
<h2>IV. &nbsp; INCIDENTS ENCOUNTERED</h2>
<table>
<tr><td><span class="box">{{ ! $incidents ? 'X' : '' }}</span>None</td></tr>
<tr><td><span class="box">{{ $incidents ? 'X' : '' }}</span>Incident/s encountered (attach the accomplished Incident Report, if applicable)</td></tr>
</table>
@if($incidents)<div class="narrative">{{ $details['incident_explanation'] ?? '' }}</div>@endif
<h2>V. &nbsp; CERTIFICATION</h2>
<p class="certification">{{ $roc['certification'] }}</p>
<p>Issued this {{ $issued ? $issued->format('j') : '____' }} day of {{ $issued ? $issued->format('F') : '____________________' }}, {{ $issued ? $issued->format('Y') : '20____' }}.</p>
<div class="signatory">
<strong>Certified Correct:</strong>
<div class="signature"></div>
<strong>{{ $details['official'] ?? '' }}</strong><br>
Name and Signature of Authorized HEI Official<br>
{{ $details['position'] ?? '' }}<br>
Designation/Position
</div>
</div>
</body>
</html>
