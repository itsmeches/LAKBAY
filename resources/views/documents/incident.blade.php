<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Annex H - Incident Report</title>
<style>
@page { margin: 34px 42px 44px; }
body { font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.3; color: #111; }
.source { text-align: right; font-size: 9px; margin-bottom: 18px; }
h1 { font-size: 11px; margin: 0 0 12px; }
h2 { font-size: 10px; margin: 10px 0 4px; page-break-after: avoid; }
.letterhead { text-align: center; white-space: pre-line; margin-bottom: 10px; }
.letterhead img { max-width: 470px; max-height: 72px; }
table { width: 100%; border-collapse: collapse; table-layout: fixed; }
td { border: 1px solid #222; padding: 3px 4px; vertical-align: top; overflow-wrap: anywhere; word-wrap: break-word; }
.label { font-weight: bold; }
.narrative { white-space: pre-line; overflow-wrap: anywhere; word-wrap: break-word; margin: 4px 0 6px; }
.box { display: inline-block; width: 8px; height: 8px; border: 1px solid #111; text-align: center; line-height: 8px; font-size: 8px; margin-right: 4px; }
.certification { margin-top: 8px; }
.signatory { text-align: center; page-break-inside: avoid; margin-top: 12px; }
.signature { margin: 24px auto 4px; border-bottom: 1px solid #222; width: 70%; }
.controls { padding: 16px; border: 1px solid #ccc; margin-bottom: 20px; }
@unless($pdf)
body { background: #eee; max-width: 720px; margin: 25px auto; padding: 25px; }
.sheet { background: white; padding: 30px; }
.controls a { color: #124c80; }
@endunless
@media print { .controls { display: none; } body { background: white; margin: 0; padding: 0; } .sheet { padding: 0; } }
thead { display: table-header-group; }
th { border: 1px solid #222; padding: 3px 4px; }
</style>
</head>
<body>
@php
    $details = $incident['details'];
    $issued = filled($details['issued_on'] ?? null) ? \Carbon\CarbonImmutable::parse($details['issued_on']) : null;
    $persons = array_pad($details['persons'] ?? [], 3, ['name' => '', 'role' => '', 'remarks' => '']);
@endphp
@if(!$pdf)
<div class="controls">
    <strong>{{ $preserved ? 'Preserved Annex H preparation copy' : 'Annex H working preparation' }}</strong>
    <p>Confidential incident information. This copy is unsigned. Saving or printing does not send a notice to CHED. File an initial notice promptly without waiting for signature/supporting documents; use a supplement for updates.</p>
    <p>Basic information uses activity revision {{ $incident['activity_revision'] }}; the incident occurrence is a separate field and anchors the reporting deadline. Supplements retain the original occurrence.</p>
    @if($missing)
        <strong>Missing print fields:</strong><ul>@foreach($missing as $field)<li>{{ $field }}</li>@endforeach</ul>
    @else
        <a href="{{ $downloadUrl }}">Download printable PDF</a>
    @endif
    @if($incident['supplements_report_id'])<p>Supplement to incident record #{{ $incident['supplements_report_id'] }}.</p>@endif
    <p>Supporting files are separate protected attachments. Retrieve authorized clean files through the Document library; PDF generation does not merge them or bypass scanning.</p>
</div>
@endif
<div class="sheet">
<div class="source">Annex H of CMO No. 24, series of 2026</div>
<h1>ANNEX H. &nbsp; TEMPLATE: INCIDENT REPORT</h1>
<div class="letterhead">@if($letterhead)<img src="{{ $letterhead }}" alt="HEI letterhead">@else{{ $incident['header']['letterhead_text'] }}@endif</div>
<h2>I. &nbsp; BASIC INFORMATION</h2>
<table>
<tr><td colspan="2"><span class="label">Name of HEI:</span> {{ $incident['header']['hei_name'] }}</td></tr>
<tr><td><span class="label">Address:</span> {{ $incident['header']['address'] }}</td><td><span class="label">Type of HEI (SUC/LUC/Private):</span> {{ $incident['header']['hei_type'] }}</td></tr>
<tr><td><span class="label">Title of off-campus activity/event/engagement:</span> {{ $incident['basic']['title'] }}</td><td><span class="label">Category (1, 2, or 3):</span> {{ $incident['basic']['category'] }}</td></tr>
<tr><td><span class="label">Proposed Date/s:</span> {{ $incident['basic']['proposed_dates'] }}</td><td><span class="label">Total No. of Participants:</span> {{ $incident['basic']['participants'] }}</td></tr>
<tr><td colspan="2"><span class="label">Venue or Location/s:</span> {{ $incident['basic']['venue'] }}</td></tr>
<tr><td colspan="2"><span class="label">HEI Office/Organization Responsible:</span> {{ $incident['basic']['office'] }}</td></tr>
<tr><td colspan="2"><span class="label">Personnel-in-Charge (PIC):</span> {{ $incident['basic']['pic'] }}</td></tr>
<tr><td colspan="2"><span class="label">Assistant PIC (if applicable):</span> {{ $incident['basic']['apic'] }}</td></tr>
</table>
<h2>II. &nbsp; INCIDENT INFORMATION</h2>
<table>
<tr><td><span class="label">Date and Time:</span> {{ filled($details['occurred_at'] ?? null) ? \Carbon\CarbonImmutable::parse($details['occurred_at'], config('lakbay.timezone'))->timezone(config('lakbay.timezone'))->format('d M Y, H:i').' (Asia/Manila)' : '' }}</td></tr>
<tr><td><span class="label">Exact Location:</span> {{ $details['location'] ?? '' }}</td></tr>
<tr><td><span class="label">Type of Incident:</span> {{ $details['type'] ?? '' }}</td></tr>
</table>
<h2>III. &nbsp; PERSONS INVOLVED</h2>
<table>
<thead><tr><th>Name</th><th>Role</th><th>Remarks</th></tr></thead>
<tbody>@foreach($persons as $person)<tr><td>{{ $person['name'] }}</td><td>{{ $person['role'] }}</td><td>{{ $person['remarks'] ?? '' }}</td></tr>@endforeach</tbody>
</table>
<h2>IV. &nbsp; BRIEF DESCRIPTION</h2>
<div class="narrative">{{ $details['description'] ?? '' }}</div>
<h2>V. &nbsp; IMMEDIATE ACTIONS TAKEN</h2>
<div class="narrative">{{ $details['immediate_actions'] ?? '' }}</div>
<h2>VI. &nbsp; CURRENT STATUS</h2>
<div class="narrative">{{ $details['current_status'] ?? '' }}</div>
<h2>VII. &nbsp; LIST OF SUPPORTING DOCUMENTS</h2>
<div class="narrative">{{ $details['supporting_details'] ?? '' }}</div>
@if($incident['supporting_documents'])<ul>@foreach($incident['supporting_documents'] as $document)<li>{{ $document['name'] }}</li>@endforeach</ul>@endif
<h2>VIII. &nbsp; CERTIFICATION</h2>
<p class="certification">{{ $incident['certification'] }}</p>
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
