<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Annex K acknowledgment</title>
    <style>
        @page { margin: 36px 48px 48px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; line-height: 1.25; color: #111; }
        h1 { font-size: 11px; margin: 24px 0 18px; }
        .source { text-align: right; font-weight: bold; }
        .letterhead { text-align: center; white-space: pre-line; margin: 18px 0; font-size: 11px; }
        .lines { white-space: pre-line; overflow-wrap: anywhere; }
        p { margin: 12px 0; text-align: justify; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; table-layout: fixed; }
        th, td { border: 1px solid #111; padding: 6px; overflow-wrap: anywhere; }
        th { text-align: center; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .signature { margin-top: 28px; page-break-inside: avoid; }
        .toolbar { background: #eef3f8; padding: 14px; margin-bottom: 20px; font-size: 12px; }
    </style>
</head>
<body>
@unless($pdf)
    <div class="toolbar">
        Unsigned {{ $issued ? 'preserved preparation' : 'draft' }} — receipt/completeness only; does not authorize activities.
        <br>Submission {{ $letter['submission_reference'] }} (revision {{ $letter['submission_revision'] }}).
        <br>Counts are student participations, not unique individuals.
        <br><a href="{{ $downloadUrl }}">Download printable PDF</a>
    </div>
@endunless
<div class="source">Annex K of CMO No. 24, series of 2026</div>
<h1>ANNEX K. TEMPLATE: CHEDRO ACKNOWLEDGEMENT LETTER</h1>
<div class="letterhead">{{ $letter['fields']['letterhead_text'] }}</div>
<p>{{ \Carbon\CarbonImmutable::parse($letter['fields']['letter_date'])->format('F d, Y') }}<br>
Reference: {{ $letter['fields']['reference'] }}</p>
<div class="lines"><strong>{{ $letter['fields']['recipient'] }}</strong>
{{ $letter['fields']['recipient_position'] }}
{{ $letter['hei_name'] }}
{{ $letter['fields']['address'] }}</div>
<p>Dear {{ $letter['fields']['salutation'] }}:</p>
<p>This is to acknowledge receipt of your submission of the duly signed Calendar of Local Off-Campus Activities (CLOCA) and the duly notarized Sworn Affidavit of Accountability (SAA), received by this Office on {{ \Carbon\CarbonImmutable::parse($letter['received_at'])->setTimezone('Asia/Manila')->format('F d, Y') }}. The submission has been reviewed for completeness and is hereby recorded for monitoring and evaluation purposes.</p>
<p>The submission has been reviewed for completeness and is hereby recorded by this Office for monitoring and reference purposes. This acknowledgment confirms receipt of the submitted documents and information and serves as part of the records of the CHED Regional Office. The information provided in the CLOCA may be used for monitoring and reference, as appropriate.</p>
<p>For records purposes, this Office hereby notes the following information based on the submitted CLOCA:</p>
<table>
    <thead><tr><th>PROGRAM/<br>COURSE</th><th>PERIOD COVERED</th><th>NO. OF ACTIVITIES</th><th>NO. OF PARTICIPATING STUDENTS</th></tr></thead>
    <tbody>
    @foreach($letter['programs'] as $row)
        <tr><td>{{ $row['program_course'] }}</td><td>{{ \Carbon\CarbonImmutable::parse($row['period_from'])->setTimezone('Asia/Manila')->format('M d, Y') }} – {{ \Carbon\CarbonImmutable::parse($row['period_until'])->setTimezone('Asia/Manila')->format('M d, Y') }}</td><td>{{ $row['activity_count'] }}</td><td>{{ $row['student_participations'] }}</td></tr>
    @endforeach
    </tbody>
</table>
<p>Pursuant to <strong>CHED Memorandum Order No. 24, Series of 2026</strong>, the HEI remains responsible for ensuring compliance with all applicable requirements governing the conduct of off-campus activities, including measures relating to student safety, security, welfare, and well-being, risk management, institutional oversight, and documentary submissions. The HEI retains full responsibility, accountability, and jurisdiction over participating students and the conduct of the authorized off-campus activities.</p>
<p>Furthermore, please be reminded that pursuant to the provision of <strong>CMO No. 24, Series of 2026</strong>, the Commission shall enforce sanctions and/or penalties indicated in this and other relevant laws that have been violated.</p>
<p>Very truly yours,</p>
<div class="signature"><strong>{{ $letter['fields']['signatory'] }}</strong><br>Director IV</div>
</body>
</html>
