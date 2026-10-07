<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sworn Affidavit of Accountability - {{ $saa['header']['hei_name'] }}</title>
    <style>
        @page { margin: 34px 44px 35px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.25; color: #111; }
        .sheet { max-width: 730px; margin: 0 auto; }
        .source { font-size: 9px; text-align: center; margin-bottom: 14px; }
        .annex { font-weight: bold; font-size: 12px; }
        .letterhead { margin: 14px 0; text-align: center; white-space: pre-line; font-weight: bold; }
        .letterhead img { max-width: 100%; max-height: 62px; }
        h1 { font-size: 14px; text-align: center; margin: 16px 0; }
        p { margin: 9px 0; text-align: justify; }
        ol { padding-left: 28px; margin: 8px 0; }
        li { margin: 4px 0; }
        .undertakings { list-style-type: lower-alpha; margin-top: 4px; padding-left: 20px; }
        .signature { margin: 16px 0 14px; page-break-inside: avoid; }
        .notarial { page-break-inside: avoid; }
        .footer { margin-top: 10px; font-size: 8px; color: #444; }
        .controls { font-size: 12px; padding: 16px; border: 1px solid #aaa; margin-bottom: 20px; background: #f8fafc; }
        .controls a { padding: 10px; display: inline-block; background: #173b66; color: white; }
        @unless($pdf)
        @media screen { body { background: #e8ecf0; } .sheet { background: white; padding: 26px; } }
        @endunless
        @media print { .controls { display: none; } .sheet { max-width: none; padding: 0; } }
    </style>
</head>
<body>
@php
    $details = $saa['details'];
    $execution = !empty($details['execution_date']) ? \Carbon\CarbonImmutable::parse($details['execution_date']) : null;
    $notarial = !empty($details['notarial_date']) ? \Carbon\CarbonImmutable::parse($details['notarial_date']) : null;
@endphp
<div class="sheet">
    @unless($pdf)
        <div class="controls">
            <strong>{{ $reference ? 'Preserved preparation copy: '.$reference : 'SAA preparation preview' }}</strong>
            <p>This is an unsigned preparation copy, not proof of execution or notarization. Print, sign and notarize externally, then upload the signed PDF as SAA evidence. Generation does not submit the package.</p>
            @if($missing)
                <strong>Complete these fields before downloading:</strong>
                <ul>@foreach($missing as $item)<li>{{ $item }}</li>@endforeach</ul>
            @else
                <a href="{{ $downloadUrl }}">Download printable SAA PDF</a>
            @endif
            <p>Unknown execution and notarial particulars remain blank. Do not infer the affiant from the account or enter government ID numbers into the preparation form.</p>
        </div>
    @endunless
    <div class="source">Annex C of CMO No. 24, series of 2026</div>
    <div class="annex">ANNEX C.<br>TEMPLATE: SWORN AFFIDAVIT OF ACCOUNTABILITY (SAA)</div>
    <div class="letterhead">@if($letterhead)<img src="{{ $letterhead }}" alt="HEI letterhead">@else{{ $saa['header']['letterhead_text'] }}@endif</div>
    <h1>SWORN AFFIDAVIT OF ACCOUNTABILITY (SAA)</h1>
    <p>I, <strong>{{ $details['name'] ?? '____________________' }}</strong>, of legal age,
        {{ $details['citizenship'] ?? '____________' }}, with office address at
        <strong>{{ $details['office_address'] ?? '____________________' }}</strong>, being the
        <strong>{{ $details['position'] ?? '____________________' }}</strong> of
        <strong>{{ $saa['header']['hei_name'] }}</strong>, duly authorized to execute this Sworn Affidavit of Accountability on behalf of the institution,
        after having been duly sworn in accordance with law, hereby depose and state that:</p>
    <ol>
        <li>For the <strong>{{ $details['semester'] ?? '____________' }}</strong>{{ preg_match('/semester/i', $details['semester'] ?? '') ? '' : ' Semester' }},
            Academic Year <strong>{{ $details['academic_year'] ?? '____________' }}</strong>, the Higher Education
            Institution (HEI) undertakes to conduct all Off-Campus Activities in accordance with
            <strong>CHED Memorandum Order No. 24, Series of 2026</strong>, and other applicable laws, rules, and regulations.</li>
        <li>The HEI further undertakes to:
            <ol class="undertakings">
                <li>ensure that all Off-Campus Activities are duly reviewed and approved through the institution's authorized approval process;</li>
                <li>uphold the safety, security, welfare, inclusivity, and accessibility of all participating students;</li>
                <li>maintain all documentary requirements and supporting records prescribed under the CMO and make them available for verification, monitoring, inspection, or submission to the Commission on Higher Education (CHED), whenever required;</li>
                <li>cooperate with CHED and other competent authorities in the conduct of monitoring, validation, fact-finding, or other lawful activities related to the implementation of this CMO; and</li>
                <li>assume full institutional responsibility and accountability for the proper implementation of all Off-Campus Activities covered by this Affidavit.</li>
            </ol>
        </li>
    </ol>
    <p>I hereby certify under oath that the foregoing information is true and correct, and I take full responsibility for the compliance of our institution with the applicable guidelines and provisions.</p>
    <p>IN WITNESS WHEREOF, I have hereunto set my hand this {{ $execution?->format('d') ?? '____' }}
        day of {{ $execution?->format('F') ?? '____________' }}, {{ $execution?->format('Y') ?? '20____' }},
        at {{ $details['execution_place'] ?? '____________________' }}.</p>
    <div class="signature">
        AFFIANT:<br><br>
        ______________________________<br>
        {{ $details['name'] ?? '[Printed name of the HEAD OF HEI]' }}<br>
        [Signature Over Printed Name of the HEAD OF HEI]<br>
        {{ $saa['header']['hei_name'] }}
    </div>
    <div class="notarial">
        <p>SUBSCRIBED AND SWORN to before me this {{ $notarial?->format('d') ?? '____' }}
            day of {{ $notarial?->format('F') ?? '____________' }}, {{ $notarial?->format('Y') ?? '20____' }},
            at {{ $details['notarial_place'] ?? '____________________' }}, affiant having exhibited to me his/her competent evidence of identity, to wit:
            {{ $details['identity_description'] ?? '______________________________' }}.</p>
        {{ $details['notary_name'] ?? '' }}<br>
        Notary Public<br>
        Doc. No. {{ $details['doc_number'] ?? '________' }};<br>
        Page No. {{ $details['page_number'] ?? '________' }};<br>
        Book No. {{ $details['book_number'] ?? '________' }};<br>
        Series of {{ $details['series'] ?? '________' }}.
    </div>
    <div class="footer">Generated preparation copy | Annex C template version {{ $saa['template']['version'] }} | {{ $reference ? 'Submission '.$reference : 'Draft revision '.$saa['draft_revision'] }} | Signing and notarization are completed externally.</div>
</div>
</body>
</html>
