<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Risk assessment and contingency plan - Annex E</title>
    <style>
        @page { margin: 26px 38px 34px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.15; color: #111; }
        .sheet { max-width: 730px; margin: 0 auto; }
        .source { text-align: center; font-size: 8px; }
        h1 { font-size: 12px; margin: 8px 0; }
        h2 { font-size: 10px; margin: 10px 0 4px; }
        .letterhead { text-align: center; white-space: pre-line; font-weight: bold; margin: 8px 0; }
        .letterhead img { max-width: 100%; max-height: 45px; }
        table { border-collapse: collapse; width: 100%; table-layout: fixed; }
        td, th { border: 1px solid #111; padding: 1.5px 3px; vertical-align: top; overflow-wrap: break-word; }
        .basic td { width: 50%; }
        .criteria th { text-align: center; }
        .criteria .question { width: 66%; }
        .criteria .answer { width: 9%; text-align: center; }
        .criteria .remarks { width: 25%; }
        .risk-area { width: 48%; }
        .risk-level { width: 16%; text-align: center; }
        .measure { width: 36%; }
        .contact-office { width: 35%; }
        .contact-person { width: 30%; }
        .contact-details { width: 35%; }
        .certification { page-break-inside: avoid; margin-top: 12px; text-align: justify; }
        .group { font-weight: bold; background: #f0f0f0; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .summary { margin-top: 10px; page-break-inside: avoid; }
        .choice { margin: 4px 0; }
        .box { display: inline-block; width: 8px; height: 8px; border: 1px solid #111; text-align: center; font-size: 8px; line-height: 8px; }
        .signatures { margin-top: 14px; page-break-inside: avoid; }
        .signatures td { border: 0; width: 33.33%; padding: 0 10px 0 0; }
        .signature-space { height: 22px; border-bottom: 1px solid #555; margin: 0 0 4px; }
        .footer { font-size: 7px; margin-top: 8px; color: #444; }
        .controls { font-size: 12px; border: 1px solid #aaa; background: #f8fafc; padding: 16px; margin-bottom: 18px; }
        .controls a { display: inline-block; padding: 10px; background: #173b66; color: white; }
        @if(empty($pdf))
        @media screen { body { background: #e8ecf0; } .sheet { background: #fff; padding: 26px; } }
        @endif
        @media print { .controls { display: none; } .sheet { max-width: none; padding: 0; } }
    </style>
</head>
<body>
<div class="sheet">
@if(isset($entries))
    <h1>Preserved RACPs (Annex E)</h1>
    <p>Submission {{ $reference }}. These preparation copies do not replace signed RACP evidence.</p>
    <ul>@foreach($entries as $id => $entry)
        <li><a href="{{ route('racp.submission', ['publicId' => $publicId, 'version' => $id]) }}">{{ $entry['basic']['title'] }} - activity revision {{ $entry['activity_revision'] }}</a></li>
    @endforeach</ul>
@else
    @unless($pdf)
        <div class="controls">
            <strong>{{ $reference ? 'Preserved RACP: '.$reference : 'RACP preparation preview' }}</strong>
            <p>Unsigned Annex E sample-based preparation copy. Ratings are entered by the HEI; this copy does not establish signing, certification or authorization. Sign externally and retain/upload signed evidence under the applicable checklist.</p>
            @if($missing)
                <strong>Complete these print fields:</strong>
                <ul>@foreach($missing as $item)<li>{{ $item }}</li>@endforeach</ul>
            @else
                <a href="{{ $downloadUrl }}">Download printable RACP PDF</a>
            @endif
        </div>
    @endunless
    @php
        $issued = !empty($racp['issued_on']) ? \Carbon\CarbonImmutable::parse($racp['issued_on']) : null;
    @endphp
    <div class="source">Annex E of CMO No. 24, series of 2026</div>
    <h1>ANNEX E. SAMPLE: RISK ASSESSMENT AND CONTINGENCY PLAN (RACP)</h1>
    <div class="letterhead">@if($letterhead)<img src="{{ $letterhead }}" alt="HEI letterhead">@else{{ $racp['header']['letterhead_text'] }}@endif</div>
    <h2>I. BASIC INFORMATION</h2>
    <table class="basic">
        <tr><td colspan="2"><strong>Name of HEI:</strong> {{ $racp['header']['hei_name'] }}</td></tr>
        <tr><td><strong>Address:</strong> {{ $racp['header']['address'] }}</td><td><strong>Type of HEI (SUC/LUC/Private):</strong> {{ $racp['header']['hei_type'] }}</td></tr>
        <tr><td><strong>Title of off-campus activity/event/engagement:</strong> {{ $racp['basic']['title'] }}</td><td><strong>Category (1, 2, or 3):</strong> {{ $racp['basic']['category'] }}</td></tr>
        <tr><td><strong>Proposed Date/s:</strong> {{ $racp['basic']['proposed_dates'] }}</td><td><strong>Total No. of Participants:</strong> {{ $racp['basic']['participants'] }}</td></tr>
        @foreach(['venue' => 'Venue or Location/s', 'office' => 'HEI Office/Organization Responsible', 'pic' => 'Personnel-in-Charge (PIC)', 'apic' => 'Assistant PIC (if applicable)'] as $key => $label)
            <tr><td colspan="2"><strong>{{ $label }}:</strong> {{ $racp['basic'][$key] }}</td></tr>
        @endforeach
    </table>
    <h2>II. RISK ASSESSMENT</h2>
    <table class="criteria">
        <thead><tr><th class="risk-area">Area</th><th class="risk-level">Risk level<br>(low / medium / high)</th><th class="measure">Contingency Measure</th></tr></thead>
        <tbody>
        @foreach($racp['areas'] as $code => $label)
            <tr><td>{{ $label }}</td><td class="risk-level">{{ ucfirst(data_get($racp, 'risks.'.$code.'.level') ?? '') }}</td><td>{{ data_get($racp, 'risks.'.$code.'.measure') }}</td></tr>
        @endforeach
        @forelse($racp['other_risks'] as $risk)
            <tr><td><strong>Other Risks (please specify):</strong> {{ $risk['area'] }}</td><td class="risk-level">{{ ucfirst($risk['level'] ?? '') }}</td><td>{{ $risk['measure'] ?? '' }}</td></tr>
        @empty
            <tr><td>Other Risks (please specify)</td><td></td><td></td></tr>
        @endforelse
        </tbody>
    </table>
    <h2>III. EMERGENCY CONTACT INFORMATION</h2>
    <table>
        <thead><tr><th class="contact-office">Agency/Office</th><th class="contact-person">Contact Person</th><th class="contact-details">Contact Details</th></tr></thead>
        <tbody>
        @foreach($racp['offices'] as $code => $label)
            <tr><td>{{ $label }}</td><td>{{ data_get($racp, 'contacts.'.$code.'.person') }}</td><td>{{ data_get($racp, 'contacts.'.$code.'.details') }}</td></tr>
        @endforeach
        @forelse($racp['other_contacts'] as $contact)
            <tr><td>{{ $contact['office'] }}</td><td>{{ $contact['person'] ?? '' }}</td><td>{{ $contact['details'] ?? '' }}</td></tr>
        @empty
            <tr><td>Others (please specify)</td><td></td><td></td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="certification">
        <h2>IV. CERTIFICATION</h2>
        <p>{{ $racp['certification'] }}</p>
        <p>Issued this {{ $issued?->format('d') ?? '____' }} day of {{ $issued?->format('F') ?? '____________' }}, {{ $issued?->format('Y') ?? '20____' }}.</p>
    </div>
    <table class="signatures"><tr>
        @foreach(['prepared_by' => 'Prepared by', 'reviewed_by' => 'Reviewed by', 'noted_by' => 'Noted by'] as $role => $label)
            <td><strong>{{ $label }}:</strong><div class="signature-space"></div>
                {{ data_get($racp, 'signatories.'.$role.'.name') }}<br>
                {{ $role === 'prepared_by' ? 'Signature over printed name' : 'Signature over printed name of authorized HEI official' }}<br>
                {{ data_get($racp, 'signatories.'.$role.'.position') }}<br>Designation/Position
                @if($role === 'prepared_by')<br>Contact Number: {{ data_get($racp, 'signatories.prepared_by.contact') }}<br>Email address: {{ data_get($racp, 'signatories.prepared_by.email') }}@endif
            </td>
        @endforeach
    </tr></table>

    <div class="footer">Unsigned preparation copy | Annex E sample version {{ $racp['template']['version'] }} | Activity revision {{ $racp['activity_revision'] }} | Assessment version {{ $racp['assessment_version'] }}</div>
@endif
</div>
</body>
</html>
