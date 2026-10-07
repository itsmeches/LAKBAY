<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>CLOCA - {{ $cloca['header']['hei_name'] }}</title>
    <style>
        @page { margin: 28px 24px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #111; }
        .sheet { max-width: 760px; margin: 0 auto; }
        .source { text-align: center; font-size: 8px; margin-bottom: 24px; }
        h1 { font-size: 11px; border-bottom: 1px solid #222; padding-bottom: 8px; }
        .letterhead { text-align: center; font-size: 11px; margin: 18px 0; white-space: pre-line; }
        .letterhead img { max-width: 100%; max-height: 75px; }
        .header { width: 100%; margin-bottom: 16px; border-collapse: collapse; }
        .header td { padding: 4px 2px; vertical-align: top; }
        .activities { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 6.5px; }
        .activities th, .activities td { border: 1px solid #111; padding: 4px 2px; vertical-align: top; white-space: pre-line; overflow-wrap: anywhere; word-wrap: break-word; }
        .activities th { font-weight: bold; }
        .activities thead { display: table-header-group; }
        .activities tr { page-break-inside: avoid; }
        .description { margin-top: 12px; font-size: 7px; page-break-inside: avoid; }
        .descriptions { width: 100%; border-collapse: collapse; }
        .descriptions td { width: 50%; vertical-align: top; padding: 0 7px; }
        ul { padding-left: 12px; }
        li { margin: 5px 0; }
        .signatories { width: 100%; margin-top: 28px; border-collapse: collapse; page-break-inside: avoid; }
        .signatories td { width: 33.33%; padding: 0 10px; vertical-align: top; }
        .signature { border-bottom: 1px solid #222; height: 26px; margin: 0 0 4px; }
        .footer { margin-top: 18px; font-size: 6px; color: #444; }
        .controls { padding: 16px; border: 1px solid #b5b5b5; margin-bottom: 20px; font-size: 12px; background: #f8fafc; }
        .controls a { display: inline-block; padding: 10px; background: #173b66; color: #fff; }
        .controls li { margin: 3px 0; }
        @unless($pdf)
        @media screen { body { background: #e8ecf0; } .sheet { padding: 25px; background: white; } }
        @endunless
        @media print { .controls { display: none; } .sheet { max-width: none; padding: 0; } }
    </style>
</head>
<body>
<div class="sheet">
    @unless($pdf)
        <div class="controls">
            <strong>{{ $reference ? 'Preserved submission: '.$reference : 'Current calendar preview' }}</strong>
            <p>This is a generated unsigned copy. Sign the printed document externally, upload the signed PDF in the Document library, and attach it through Submit / resubmit. Printing does not submit or authorize an activity.</p>
            @if($missing)
                <strong>Complete these print fields before downloading:</strong>
                <ul>@foreach($missing as $item)<li>{{ $item }}</li>@endforeach</ul>
            @else
                <a href="{{ $downloadUrl }}">Download printable PDF</a>
            @endif
            <p>Blank signature spaces are intentional. Generated data does not certify that signing or notarization occurred.</p>
        </div>
    @endunless
    <div class="source">Annex B of CMO No. 24, series of 2026</div>
    <h1>ANNEX B.<br>TEMPLATE: CALENDAR OF LOCAL OFF-CAMPUS ACTIVITIES (CLOCA)</h1>
    <div class="letterhead">
        @if($letterhead)<img src="{{ $letterhead }}" alt="HEI letterhead">@else{{ $cloca['header']['letterhead_text'] }}@endif
    </div>
    <table class="header">
        <tr><td colspan="3"><strong>Name of HEI:</strong> {{ $cloca['header']['hei_name'] }}</td></tr>
        <tr><td colspan="3"><strong>Type of HEI (SUC/LUC/Private):</strong> {{ $cloca['header']['hei_type'] }}</td></tr>
        <tr><td colspan="3"><strong>Address:</strong> {{ $cloca['header']['address'] ?: '[Not provided]' }}</td></tr>
        <tr>
            <td><strong>Effectivity:</strong> {{ $cloca['header']['effective_on'] ? \Carbon\CarbonImmutable::parse($cloca['header']['effective_on'])->format('d M Y') : '[Not provided]' }}</td>
            <td><strong>Academic Year:</strong> {{ $cloca['header']['academic_year'] ?: '[Not provided]' }}</td>
            <td><strong>Term/Semester:</strong> {{ $cloca['header']['semester'] ?: '[Not provided]' }}</td>
        </tr>
        <tr><td colspan="3"><strong>HEI Office/Organization Responsible:</strong> {{ $cloca['header']['office'] }}</td></tr>
    </table>
    <table class="activities">
        <colgroup>
            <col style="width:5%"><col style="width:5%"><col style="width:4%"><col style="width:7%"><col style="width:11%">
            <col style="width:8%"><col style="width:7%"><col style="width:9%"><col style="width:4%"><col style="width:9%">
            <col style="width:5%"><col style="width:8%"><col style="width:8%">
        </colgroup>
        <thead><tr>
            <th>Month</th><th>Category</th><th>Type</th><th>Name</th><th>Brief description</th>
            <th>Personnel-in-charge (PIC)</th><th>Assistant PIC (if applicable)</th><th>Contact Details</th>
            <th>No. of days</th><th>Location/s</th><th>Approx. No. of Pax</th>
            <th>Coordination with PNP, LGU, etc.</th><th>Requested provision for assistance from PNP, LGU, etc.</th>
        </tr></thead>
        <tbody>
        @forelse($cloca['rows'] as $row)
            <tr>
                @foreach(['month', 'category', 'type', 'name', 'description', 'pic', 'apic', 'contacts', 'days', 'locations', 'participants', 'coordination', 'assistance'] as $key)
                    <td>{{ filled($row[$key]) ? $row[$key] : '[Not provided]' }}</td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="13">No activities added to this calendar.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="description">
        <strong>COLUMN DESCRIPTION:</strong>
        <table class="descriptions"><tr><td><ul>
            <li><strong>Month</strong> - indicate month of off-campus activity, event or engagement.</li>
            <li><strong>Category</strong> - indicate Category 1, Category 2 or Category 3.</li>
            <li><strong>Type</strong> - I for Institutional, P for Program-based, C for Curriculum-based, S for Student-initiated, G for NGA/LGU invitations, O for others.</li>
            <li><strong>Name</strong> - name of the off-campus activity, event or engagement.</li>
            <li><strong>Brief Description</strong> - short description, including purpose or nature.</li>
            <li><strong>PIC</strong> - full name of the designated contact person of the HEI.</li>
            <li><strong>APIC</strong> - full name, if applicable; write NA if not applicable.</li>
        </ul></td><td><ul>
            <li><strong>Contact Details</strong> - PIC/APIC details necessary for activity-related coordination and communication.</li>
            <li><strong>No. of days</strong> - total duration of the activity in days.</li>
            <li><strong>Location/s</strong> - all itinerary locations, indicating within or outside the region.</li>
            <li><strong>Approx. No. of Pax</strong> - estimated total number of participants.</li>
            <li><strong>Coordination with PNP, LGU, etc.</strong> - agencies or local authorities coordinated with. Write NA for research or field studies.</li>
            <li><strong>Requested Provision for Assistance</strong> - agencies and type of assistance requested, e.g. PNP - security assistance. Write NA for research or field studies.</li>
        </ul></td></tr></table>
    </div>
    <table class="signatories"><tr>
        @foreach(['prepared_by' => 'Prepared by:', 'reviewed_by' => 'Reviewed by:', 'noted_by' => 'Noted by:'] as $key => $label)
            <td>
                <strong>{{ $label }}</strong><div class="signature"></div>
                {{ $cloca['header'][$key]['name'] ?? '[Printed name]' }}<br>
                {{ $cloca['header'][$key]['position'] ?? '[Designation/Position]' }}<br>
                <small>{{ $key === 'prepared_by' ? 'Signature over printed name' : 'Signature over printed name of authorized HEI official' }}</small>
            </td>
        @endforeach
    </tr></table>
    <div class="footer">Generated unsigned copy | CMO 24 Annex B, template version {{ $cloca['template']['version'] }} | {{ $reference ? 'Submission '.$reference : 'Working calendar' }} | Signing, submission and CHED acknowledgment are separate steps.</div>
</div>
</body>
</html>
