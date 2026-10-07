<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><title>Annex L consent form</title>
    <style>
        @page { margin: 32px 48px 42px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; line-height: 1.22; color: #111; }
        .source { text-align: right; font-weight: bold; }
        h1 { font-size: 11px; margin: 26px 0 12px; }
        h2 { font-size: 10px; text-align: center; margin: 12px 0; }
        .letterhead { text-align: center; white-space: pre-line; margin: 12px 0; }
        .letterhead img { max-width: 100%; max-height: 65px; }
        p { text-align: justify; margin: 12px 0; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td { padding: 5px 10px 5px 0; vertical-align: top; overflow-wrap: anywhere; }
        .line { border-bottom: 1px solid #777; display: inline-block; min-width: 85px; max-width: 100%; word-wrap: break-word; }
        .signatures { page-break-inside: avoid; }
        .signatures td { width: 50%; }
        .signature-space { height: 20px; }
        .privacy { border-top: 1px solid #777; margin-top: 12px; }
        .toolbar { background: #eef4ff; padding: 16px; font-size: 12px; }
    </style>
</head>
<body>
@php
    $fields = $consent['fields'];
    $entry = fn ($key) => filled($fields[$key] ?? null) ? $fields[$key] : '________________________';
    $date = fn ($key) => filled($fields[$key] ?? null) ? \Carbon\CarbonImmutable::parse($fields[$key])->format('d M Y') : '________________';
@endphp
@unless($pdf)
    <div class="toolbar">Unsigned preparation — no consent/signature has been recorded.
        Personal particulars are not saved by this preparation feature.
        <form method="post" action="{{ $formUrl }}">
            @csrf
            @foreach($fields as $key => $value)
                @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
            @endforeach
            <button name="output" value="pdf">Download this printable PDF</button>
        </form>
        <a href="{{ $formUrl }}">Start another form</a>; use browser Back to edit these entries where supported.</div>
@endunless
<div class="source">Annex L of CMO No. 24, series of 2026</div>
<h1>ANNEX L. SAMPLE: CONSENT FORM</h1>
<div class="letterhead">@if($consent['letterhead_image'])<img src="{{ $consent['letterhead_image'] }}" alt="HEI letterhead">@else{{ $consent['header']['letterhead_text'] }}@endif</div>
<h2>STUDENT CONSENT FORM<br>For Participation in the Off-Campus Activity</h2>
<table><tr><td>Activity Title:<br><span class="line">{{ $consent['basic']['title'] }}</span></td><td>Date/s of Activity:<br><span class="line">{{ $consent['basic']['dates'] }}</span></td></tr>
<tr><td>Organized by:<br><span class="line">{{ $consent['basic']['organized_by'] }}</span></td><td>Venue/Location:<br><span class="line">{{ $consent['basic']['venue'] }}</span></td></tr></table>
<p>I, <span class="line">{{ $entry('student_name') }}</span>, a bona fide student of <span class="line">{{ $consent['header']['hei_name'] }}</span>, currently enrolled in <span class="line">{{ $entry('program_year_level') }}</span>, understand that the above activity is part of my academic, co-curricular requirements, or extracurricular activities.</p>
<p>I willingly and voluntarily consent to participate in the said activity, with full understanding of the following:</p>
<ul>
    <li>The objective, purpose, nature, and schedule of the activity have been explained to me;</li>
    <li>I shall follow institutional policies, activity guidelines, and safety protocols during the activity;</li>
    <li>I am responsible for my actions and belongings while participating; and</li>
    <li>If any emergency happens, my listed contact person will be informed.</li>
</ul>
<strong>Student Information:</strong>
<table class="signatures"><tr><td><div class="signature-space"></div><span class="line">{{ $entry('student_name') }}</span><br>Signature over printed name<br>Date: {{ $date('student_date') }}</td>
<td><div class="signature-space"></div>Student Number: {{ $entry('student_number') }}<br>Contact Number: {{ $entry('student_contact') }}</td></tr></table>
<h2 class="privacy">PARENT/GUARDIAN ACKNOWLEDGMENT</h2>
<p>I, <span class="line">{{ $entry('guardian_name') }}</span>, the parent/legal guardian of <span class="line">{{ $entry('student_name') }}</span>, hereby certify that I have been fully informed of and understand the nature, objectives, schedule, venue, and other relevant details of the off-campus activity stated above. I acknowledge that I have been apprised of the arrangements, safety measures, and guidelines governing the conduct of the activity, and I understand the responsibilities and expectations attendant to my child's/ward's participation.</p>
<p>I further affirm that I am aware of the activity and have affixed my signature below to signify my informed consent and acknowledgment. In the event of any incident, emergency, or circumstance requiring immediate communication with the family, I may be contacted through the following contact details:</p>
<table class="signatures"><tr><td><div class="signature-space"></div><span class="line">{{ $entry('guardian_name') }}</span><br>Signature over printed name<br>Date: {{ $date('guardian_date') }}</td>
<td><div class="signature-space"></div>Relationship: {{ $entry('relationship') }}<br>Emergency Contact Number: {{ $entry('emergency_contact') }}</td></tr></table>
<div class="privacy">
    <h2>Data Privacy Notice:</h2>
    <p>We understand that the personal information I provided in this form will be used only for planning, safety, and documentation of the activity. The school will handle my data in line with the Data Privacy Act of 2012, and it will not be shared without my consent unless required by law.</p>
    <table class="signatures"><tr><td><div class="signature-space"></div><span class="line">{{ $entry('student_name') }}</span><br>Students' signature over printed name<br>Date: {{ $date('student_privacy_date') }}</td>
    <td><div class="signature-space"></div><span class="line">{{ $entry('guardian_name') }}</span><br>Parents' signature over printed name<br>Date: {{ $date('guardian_privacy_date') }}</td></tr></table>
</div>
</body>
</html>
