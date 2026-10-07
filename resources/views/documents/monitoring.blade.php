<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><title>Annex M monitoring and evaluation</title>
    <style>
        @page { margin: 32px 44px 42px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; line-height: 1.2; color: #111; }
        .source { text-align: right; font-weight: bold; margin-bottom: 22px; }
        h1 { font-size: 11px; margin: 16px 0; } h2 { font-size: 10px; margin: 14px 0 8px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #333; padding: 4px; vertical-align: top; word-wrap: break-word; }
        thead { display: table-header-group; } tr { page-break-inside: avoid; }
        .line { border-bottom: 1px solid #777; }
        .second { page-break-before: always; }
        .signatures { margin-top: 20px; page-break-inside: avoid; }
        .signatures td { border: 0; padding: 8px; width: 33%; }
        .space { height: 28px; }
        .toolbar { background: #eef4ff; padding: 16px; margin-bottom: 20px; font-size: 12px; }
    </style>
</head><body>
@unless($pdf)<div class="toolbar">
    Preserved unsigned preparation. No signature, HEI conforme or activity authorization is established by this output.
    Unentered signatory particulars print blank for external completion.
    <a href="{{ $downloadUrl }}">Download printable PDF</a>
</div>@endunless
<div class="source">Annex M of CMO No. 24, series of 2026</div>
<h1>ANNEX M. TEMPLATE: MONITORING AND EVALUATION TOOL OF CHED REGIONAL OFFICES (CHEDROs)</h1>
<h2>I. CHEDRO Monitoring Information</h2>
<div>Period Covered: <span class="line">{{ $monitoring['period'] }}</span></div>
<div>Date of monitoring: {{ \Carbon\CarbonImmutable::parse($monitoring['monitored_on'])->format('d M Y') }}</div>
<div>Region: {{ $monitoring['region'] }}</div>
<div>Monitored by: {{ $monitoring['monitored_by'] }} [CHEDRO Personnel]</div>
<div>Type of Monitoring: {{ $monitoring['monitoring_type'] }}</div>
<h2>II. Profile of HEI</h2>
<div>HEI Name: {{ $monitoring['hei_name'] }} &nbsp; Type: {{ $monitoring['hei_type'] }}</div>
<div>HEI Respondent: {{ $monitoring['respondent'] }}</div>
<div>Position/Designation: {{ $monitoring['respondent_position'] ?: '________________________' }}</div>
@foreach([$firstItems, $lastItems] as $index => $rows)
    @if($index === 1)<div class="second"></div><div class="source">Annex M of CMO No. 24, series of 2026</div>@endif
    <table>
        <thead><tr><th style="width: 40%">Item</th><th style="width: 12%">Compliance<br>(yes/no)</th><th style="width: 31%">Means of Verification</th><th style="width: 17%">Remarks</th></tr></thead>
        <tbody>@foreach($rows as $row)
            <tr><td>{{ $row['criterion'] }}</td><td>{{ $row['compliance'] }}</td><td>{{ $row['verification'] }}</td><td>{{ $row['remarks'] }}</td></tr>
        @endforeach</tbody>
    </table>
@endforeach
<h2>III. FINDINGS AND OBSERVATIONS</h2>
<table><thead><tr><th style="width: 40%">Items</th><th style="width: 60%">Details</th></tr></thead>
<tbody>@foreach($monitoring['findings'] as $label => $details)
    @foreach(mb_str_split($details ?: ' ', 650) as $part => $chunk)
        <tr><td>{{ $label }}{{ $part ? ' (continued)' : '' }}</td><td style="height: 30px">{{ $chunk }}</td></tr>
    @endforeach
@endforeach</tbody></table>
<table class="signatures"><tr>@foreach($monitoring['signatories'] as $label => $person)
    <td><strong>{{ $label }}:</strong><div class="space"></div><div class="line">{{ $person['name'] ?: '________________________' }}</div>
    Signature over printed name<br>{{ $person['position'] ?: '________________________' }}<br>Designation/Position</td>
@endforeach</tr></table>
<div class="signatures"><strong>HEI Conforme:</strong><div class="space"></div>
    <span class="line">{{ $monitoring['hei_conforme']['name'] ?: '________________________' }}</span><br>
    Signature over printed name<br>Date: {{ $monitoring['hei_conforme']['date'] ? \Carbon\CarbonImmutable::parse($monitoring['hei_conforme']['date'])->format('d M Y') : '________________________' }}
</div>
</body></html>
