<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>Annex I - Report of Change of Details</title>
<style>
@page { margin: 34px 42px 44px; }
body { font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.3; color: #111; }
.source { text-align: right; font-size: 9px; margin-bottom: 18px; }
h1 { font-size: 11px; margin: 0 0 12px; }
h2 { font-size: 10px; margin: 12px 0 5px; page-break-after: avoid; }
.letterhead { text-align: center; white-space: pre-line; margin-bottom: 12px; }
.letterhead img { max-width: 470px; max-height: 72px; }
table { width: 100%; border-collapse: collapse; table-layout: fixed; }
td, th { border: 1px solid #222; padding: 4px; vertical-align: top; overflow-wrap: anywhere; word-wrap: break-word; }
.label { font-weight: bold; }
.narrative, .comparison td { white-space: pre-line; overflow-wrap: anywhere; word-wrap: break-word; }
.narrative { margin: 6px 0 12px; }
thead { display: table-header-group; }
.signatories { margin-top: 34px; page-break-inside: avoid; }
.signatories td { border: 0; font-size: 8px; padding: 8px; }
.signature { margin: 30px 0 4px; border-bottom: 1px solid #222; }
.controls { padding: 16px; border: 1px solid #ccc; margin-bottom: 20px; }
@unless($pdf)
body { background: #eee; max-width: 720px; margin: 25px auto; padding: 25px; }
.sheet { background: white; padding: 30px; }
.controls a { color: #124c80; }
@endunless
@media print { .controls { display: none; } body { background: white; margin: 0; padding: 0; } .sheet { padding: 0; } }
</style></head><body>
@php
    $details = $change['details'];
@endphp
@if(!$pdf)
<div class="controls">
<strong>{{ $preserved ? 'Preserved Annex I preparation copy' : 'Annex I working preparation' }}</strong>
<p>This copy is unsigned. Saving or printing does not notify CHED. Send immediate notice promptly; print/sign externally and file the scanned signed PDF for formal reporting. Acknowledgment is not authorization.</p>
<p>Original revision {{ $change['original_revision'] }} to revised revision {{ $change['revised_revision'] }}.
@if($change['original_submission'])Original reference: {{ $change['original_submission']['reference'] }} ({{ str_replace('_', ' ', $change['original_submission']['source']) }}).@endif
</p>
@if($change['supplements_report_id'])<p>Follow-up to change report #{{ $change['supplements_report_id'] }}.</p>@endif
<p>The CMO formal 72-hour deadline anchor is unresolved; immediate notification remains separate.</p>
@if($missing)<p><strong>Missing print fields:</strong> {{ implode('; ', $missing) }}</p>@else<a href="{{ $downloadUrl }}">Download printable PDF</a>@endif
</div>
@endif
<div class="sheet">
<div class="source">Annex I of CMO No. 24, series of 2026</div>
<h1>ANNEX I. &nbsp; TEMPLATE: REPORT OF CHANGE OF DETAILS (RCD)</h1>
<div class="letterhead">@if($letterhead)<img src="{{ $letterhead }}" alt="HEI letterhead">@else{{ $change['header']['letterhead_text'] }}@endif</div>
<h2>I. &nbsp; BASIC INFORMATION</h2>
<table>
<tr><td colspan="2"><span class="label">Name of HEI:</span> {{ $change['header']['hei_name'] }}</td></tr>
<tr><td><span class="label">Address:</span> {{ $change['header']['address'] }}</td><td><span class="label">Type of HEI (SUC/LUC/Private):</span> {{ $change['header']['hei_type'] }}</td></tr>
<tr><td><span class="label">Title of activity / event / engagement:</span> {{ $change['basic']['title'] }}</td><td><span class="label">Category of Activity (1, 2, or 3):</span> {{ $change['basic']['category'] }}</td></tr>
<tr><td colspan="2"><span class="label">Date of original submission:</span> {{ filled($change['original_submission']['date'] ?? null) ? \Carbon\CarbonImmutable::parse($change['original_submission']['date'])->format('d F Y') : '' }}</td></tr>
</table>
<h2>II. &nbsp; PARTICULARS OF CHANGE</h2>
<table class="comparison"><thead><tr><th style="width:24%">Particulars</th><th>Previously Submitted</th><th>Revised Information</th></tr></thead><tbody>
@foreach($change['rows'] as $row)
{{-- Bound continuation rows so long comparison cells can paginate. --}}
@php
    $splitCell = static fn (string $text): array => collect(preg_split('/\R/u', $text))
        ->flatMap(static fn (string $line): array => mb_str_split($line, 80) ?: [''])
        ->chunk(6)->map(static fn ($lines): string => $lines->implode("\n"))->all();
    $previousParts = $splitCell((string) ($row['previous'] ?? ''));
    $revisedParts = $splitCell((string) ($row['revised'] ?? ''));
    $parts = max(1, count($previousParts), count($revisedParts));
@endphp
@for($part = 0; $part < $parts; $part++)
<tr><td class="label">{{ $row['label'] }}{{ $part ? ' (continued)' : '' }}</td><td>{{ $previousParts[$part] ?? '' }}</td><td>{{ $revisedParts[$part] ?? '' }}</td></tr>
@endfor
@endforeach
</tbody></table>
<h2>III. &nbsp; REASON FOR CHANGE <span style="font-weight:normal">(Please provide brief summary)</span></h2>
<div class="narrative">{{ $details['reason'] ?? '' }}</div>
<h2>IV. &nbsp; LIST OF SUPPORTING DOCUMENTS <span style="font-weight:normal">(Please provide brief details &amp; attach supporting documents, if any)</span></h2>
<div class="narrative">{{ $details['supporting_details'] ?? '' }}</div>
@foreach($change['supporting_documents'] as $document)<div class="narrative">{{ $document['name'] }}</div>@endforeach
<table class="signatories"><tr>
@foreach(['prepared' => 'Prepared by:', 'reviewed' => 'Reviewed by:', 'noted' => 'Noted by:'] as $key => $label)
<td><strong>{{ $label }}</strong><div class="signature"></div>
{{ $details[$key.'_name'] ?? '' }}<br>
Signature over printed name{{ $key !== 'prepared' ? ' of authorized HEI official' : '' }}<br>
{{ $details[$key.'_position'] ?? '' }}<br>Designation/Position
@if($key === 'prepared')<br>Contact Number: {{ $details['prepared_contact'] ?? '' }}<br>Email address: {{ $details['prepared_email'] ?? '' }}@endif
</td>
@endforeach
</tr></table></div></body></html>
