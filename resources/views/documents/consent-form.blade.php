<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prepare consent — Annex L</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f4f6fa; color: #172033; margin: 0; }
        main { max-width: 850px; margin: 24px auto; padding: 24px; background: white; border-radius: 12px; }
        h1 { font-size: 24px; } h2 { font-size: 18px; }
        .notice { background: #eef4ff; padding: 16px; border-radius: 8px; }
        .error { background: #fff0ef; padding: 12px; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        label { display: block; } input, textarea, select { display: block; box-sizing: border-box; width: 100%; margin-top: 6px; padding: 10px; border: 1px solid #9aacc0; border-radius: 6px; font: inherit; }
        button { padding: 12px; border-radius: 6px; border: 0; background: #174674; color: white; cursor: pointer; margin: 16px 8px 0 0; }
        dt { font-weight: bold; } dd { margin: 4px 0 12px; }
        @media(max-width: 600px) { main { margin: 0; padding: 16px; } .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body><main>
    <a href="{{ \App\Filament\Hei\Resources\ActivityResource::getUrl('index', panel: 'hei') }}">Back to Activity plans</a>
    <h1>Prepare consent (Annex L)</h1>
    <div class="notice">Generate one form per participant, or leave personal particulars blank for completion on paper.
        Personal entries populate this output without being saved as app records.
        Print and sign externally. This page does not record consent, notify CHED or authorize participation.
        Upload signed evidence as a sensitive Consent form only when required.</div>
    <h2>Activity revision {{ $consent['activity_revision'] }}</h2>
    <dl>
        <dt>Activity title</dt><dd>{{ $consent['basic']['title'] }}</dd>
        <dt>Date/s</dt><dd>{{ $consent['basic']['dates'] }}</dd>
        <dt>Organized by</dt><dd>{{ $consent['basic']['organized_by'] }}</dd>
        <dt>Venue/location</dt><dd>{{ $consent['basic']['venue'] }}</dd>
    </dl>
    @if($validationErrors)
        <div class="error" role="alert"><strong>Please correct:</strong><ul>
            @foreach($validationErrors as $messages) @foreach($messages as $message)<li>{{ $message }}</li>@endforeach @endforeach
        </ul></div>
    @endif
    <form method="post" action="{{ $actionUrl }}" autocomplete="off">
        @csrf
        <h2>Student particulars (optional)</h2>
        <div class="grid">
        @foreach(['student_name' => ['Full name', 'text', 150], 'program_year_level' => ['Program and year level', 'text', 150],
            'student_number' => ['Student number', 'text', 50], 'student_contact' => ['Contact number', 'tel', 50],
            'student_date' => ['Consent signing date', 'date', 10], 'student_privacy_date' => ['Privacy acknowledgment date', 'date', 10]] as $key => $field)
            <label>{{ $field[0] }}<input type="{{ $field[1] }}" name="{{ $key }}" maxlength="{{ $field[2] }}" value="{{ is_scalar($fields[$key] ?? '') ? ($fields[$key] ?? '') : '' }}"></label>
        @endforeach
        </div>
        <h2>Parent/guardian particulars (optional)</h2>
        <div class="grid">
        @foreach(['guardian_name' => ['Full name', 'text', 150], 'relationship' => ['Relationship', 'text', 100],
            'emergency_contact' => ['Emergency contact number', 'tel', 50], 'guardian_date' => ['Acknowledgment signing date', 'date', 10],
            'guardian_privacy_date' => ['Privacy acknowledgment date', 'date', 10]] as $key => $field)
            <label>{{ $field[0] }}<input type="{{ $field[1] }}" name="{{ $key }}" maxlength="{{ $field[2] }}" value="{{ is_scalar($fields[$key] ?? '') ? ($fields[$key] ?? '') : '' }}"></label>
        @endforeach
        </div>
        <p>Signature lines remain blank. Entering names/dates is not a signature or verification of consent. Parent/guardian applicability follows the HEI's confirmed requirements.</p>
        <h2>HEI letterhead</h2>
        <label>Letterhead text<textarea name="letterhead_text" rows="3" maxlength="1000">{{ is_string($fields['letterhead_text'] ?? null) ? $fields['letterhead_text'] : $consent['header']['letterhead_text'] }}</textarea></label>
        <label>Optional clean private letterhead image (PNG/JPG, up to 2 MB)
            <select name="letterhead_document_id"><option value="">Use text only</option>
                @foreach($letterheads as $id => $name)<option value="{{ $id }}" @selected(($fields['letterhead_document_id'] ?? null) == $id)>{{ $name }}</option>@endforeach
            </select>
        </label>
        <button name="output" value="preview">Preview filled form</button>
        <button name="output" value="pdf">Download printable PDF</button>
    </form>
</main></body>
</html>
