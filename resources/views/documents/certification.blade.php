<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Certification - Annex F</title>
    <style>
        @page { margin: 32px 52px 36px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.2; color: #111; }
        .sheet { max-width: 700px; margin: 0 auto; }
        .source { text-align: center; font-size: 9px; margin-bottom: 22px; }
        h1 { font-size: 12px; margin: 10px 0 18px; }
        .letterhead { text-align: center; white-space: pre-line; font-weight: bold; margin: 14px 0 18px; }
        .letterhead img { max-width: 100%; max-height: 60px; }
        p { text-align: justify; margin: 12px 0; }
        table { border-collapse: collapse; width: 100%; table-layout: fixed; }
        td, th { border: 1px solid #111; padding: 5px; vertical-align: top; overflow-wrap: break-word; }
        .requirement { width: 55%; }
        .supporting { width: 45%; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .checked-item { margin: 0 0 4px; }
        .box { display: inline-block; width: 8px; height: 8px; border: 1px solid #111; font-size: 8px; line-height: 8px; text-align: center; }
        .particulars { margin: 5px 0; padding-left: 24px; }
        .note { font-style: italic; margin-top: 12px; }
        .next-page { page-break-before: always; padding-top: 32px; }
        .signature { margin-top: 65px; text-align: center; page-break-inside: avoid; }
        .signature-line { width: 70%; border-bottom: 1px solid #555; height: 35px; margin: 0 auto 6px; }
        .footer { margin-top: 20px; font-size: 7px; color: #444; }
        .controls { font-size: 12px; background: #f8fafc; border: 1px solid #aaa; padding: 16px; margin-bottom: 18px; }
        .controls a { display: inline-block; padding: 10px; background: #173b66; color: white; }
        .applicability-note { page-break-inside: avoid; }
        @unless($pdf)
        @media screen { body { background: #e8ecf0; } .sheet { background: white; padding: 26px; } .next-page { border-top: 1px dashed #888; margin-top: 30px; padding-top: 22px; } }
        @endunless
        @media print { .controls { display: none; } .sheet { max-width: none; padding: 0; } }
    </style>
</head>
<body>
@php
    $details = $certification['details'];
    $definition = $details['definition'];
    $issued = !empty($details['issued_on']) ? \Carbon\CarbonImmutable::parse($details['issued_on']) : null;
    $notApplicable = collect($details['items'] ?? [])->filter(fn ($item) => ($item['status'] ?? null) === 'not_applicable');
@endphp
<div class="sheet">
    @unless($pdf)
        <div class="controls">
            <strong>{{ $reference ? 'Preserved certification: '.$reference : 'Certification preparation preview' }}</strong>
            <p>Unsigned preparation copy. Attestations and supporting-document checks are entered by the HEI; generation does not verify compliance, signing authority or document authenticity. Sign externally and upload the signed PDF under the applicable policy checklist.</p>
            @if($missing)
                <strong>Complete these print fields:</strong>
                <ul>@foreach($missing as $item)<li>{{ $item }}</li>@endforeach</ul>
            @else
                <a href="{{ $downloadUrl }}">Download printable certification PDF</a>
            @endif
            <p>The template contains two pages. N/A explanations are printed in an additional applicability note; pending items cannot be presented as completed certification.</p>
        </div>
    @endunless
    @foreach([1, 2] as $page)
        <div class="{{ $page === 2 ? 'next-page' : '' }}">
            <div class="source">Annex F of CMO No. 24, series of 2026</div>
            @if($page === 1)
                <h1>ANNEX F. &nbsp; TEMPLATE: CERTIFICATION</h1>
                <div class="letterhead">@if($letterhead)<img src="{{ $letterhead }}" alt="HEI letterhead">@else{{ $certification['header']['letterhead_text'] }}@endif</div>
                <p>This is to certify that <strong>{{ $certification['header']['hei_name'] }}, {{ $certification['header']['address'] }}</strong> has complied with the applicable requirements under <strong>Section 14 of CHED Memorandum Order No. 24, Series of 2026</strong> entitled "Enhanced Guidelines for the Local Off-Campus Activity for Student Knowledge, Well-Being and Accessible Learning Opportunity (LAKBAY)."</p>
                <p>The HEI attests to its compliance with the following requirements and supporting documents, where applicable:</p>
            @endif
            <table>
                <thead><tr><th class="requirement">Requirements</th><th class="supporting">Supporting Documents, where applicable</th></tr></thead>
                <tbody>@foreach($definition['rows'] as $code => $row)
                    @if($row['page'] === $page)
                        @php($item = $details['items'][$code] ?? [])
                        <tr>
                            <td><div class="checked-item"><span class="box">{{ ($item['status'] ?? null) === 'attested' ? 'X' : '' }}</span> {{ $row['requirement'] }}</div>
                                @if(isset($row['particulars']))<ol class="particulars" type="a">@foreach($row['particulars'] as $particular)<li>{{ $particular }}</li>@endforeach</ol>@endif
                            </td>
                            <td>@foreach($row['supporting'] as $key => $label)<div class="checked-item"><span class="box">{{ !empty($item['supporting'][$key]) ? 'X' : '' }}</span> {{ $label }}</div>@endforeach
                                @if(isset($row['note']))<div class="note">{{ $row['note'] }}</div>@endif
                            </td>
                        </tr>
                    @endif
                @endforeach</tbody>
            </table>
            @if($page === 2)
                <p>{{ $definition['attestation'] }}</p>
                <p><strong>{{ $definition['closing'] }}</strong></p>
                <p>Issued this {{ $issued?->format('d') ?? '____' }} day of {{ $issued?->format('F') ?? '____________' }}, {{ $issued?->format('Y') ?? '20____' }}, at {{ $details['issued_at'] ?? '____________________' }}.</p>
                <div class="signature">
                    <strong>Certified Correct:</strong>
                    <div class="signature-line"></div>
                    {{ $details['official'] ?? '' }}<br>
                    <strong>Name and Signature of Authorized HEI Official</strong><br>
                    {{ $details['position'] ?? '' }}<br>Designation/Position
                </div>
            @endif
            <div class="footer">Generated unsigned preparation copy | Annex F template version {{ $certification['template']['version'] }} | {{ $reference ? 'Submission '.$reference : 'Draft revision '.$certification['draft_revision'] }}</div>
        </div>
    @endforeach
    @if($notApplicable->isNotEmpty())
        <div class="next-page">
            <h1>Annex F - Applicability notes</h1>
            <p>{{ $certification['header']['hei_name'] }}. Unchecked requirements listed here are declared not applicable by the HEI. These explanations do not establish CHED acceptance of applicability.</p>
            @foreach($notApplicable as $code => $item)
                <div class="applicability-note"><p><strong>{{ $definition['rows'][$code]['requirement'] }}</strong><br>Reason: {{ $item['reason'] ?? '' }}</p></div>
            @endforeach
        </div>
    @endif
    @if(!empty($certification['classification_context']))
        <div class="next-page"><h1>Application classification context — supplementary record</h1>
            <p>This is not an additional CMO certification clause. Typed preparations do not establish signatures or CHED authorization.</p>
            @foreach($certification['classification_context'] as $assessment)
                <p>Assessment #{{ $assessment['assessment_id'] }}: declared Category {{ $assessment['declared_category'] }}; confirmed requirement Category {{ $assessment['requirement_category'] }}.
                {{ $assessment['requirement_category'] === 3 ? 'Ordinary Category 3: SAA and row-12 PIC designation are not required.' : 'Category 1/2 treatment: applicable SAA and PIC requirements remain.' }}<br>Reason: {{ $assessment['reason'] }}</p>
            @endforeach
        </div>
    @endif
</div>
</body>
</html>
