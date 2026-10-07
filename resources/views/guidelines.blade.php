@extends('layouts.public')
@section('title','Guidelines')
@section('content')
<section class="content">
    <p class="eyebrow">POLICY AND PREPARATION</p><h1>Guidelines and templates</h1>
    <p>CMO No. 24, s. 2026 provides the new LAKBAY guidelines. Its publication and effectivity must be verified before official activation in this platform. Your institution remains responsible for authorization, student welfare and compliance.</p>
    <div class="cards">
        <article><h2>Category 1</h2><p>Pre-planned calendar activities. Prepare the annual package, then the evidence due before each activity. Ordinary annual requirements use the academic-year start; AY 2026–2027 CLOCA/SAA have a publication-based transition rule.</p></article>
        <article><h2>Category 2</h2><p>New or changed activities outside the original calendar treatment. Applicable pre-activity requirements generally use three working days before conduct.</p></article>
        <article><h2>Category 3</h2><p>Eligible student-initiated activities under faculty supervision. Specified circumstances require Category 2 evidence. SAA is marked as required; some deadlines still need CHED clarification.</p></article>
    </div>
    <div class="notice"><strong>Approved templates</strong><p>@forelse($templates as $template)<a href="{{ route('templates.download',$template->id) }}">{{ $template->title }} (version {{ $template->version }})</a><br>@empty No approved public template downloads are available. Obtain current instructions from CHED Region IV-A. @endforelse</p></div>
    <h2>Before conduct</h2><ul><li>Secure HEI authorization and any applicable exceptional CEB clearance.</li><li>Prepare the required forms, copies, consultation records and expense breakdown.</li><li>Confirm PIC qualifications, first-aid evidence and the applicable student/PIC ratio.</li><li>Record safety, inclusion, parallel learning, grievance and emergency preparations.</li><li>Ensure applicable third-party contracts are effective before commencement.</li><li>Keep retained assessments and supporting evidence available immediately on CHED request.</li></ul>
    <h2>After or during the activity</h2><p>CMO 24 specifies incident reporting within 48 hours of occurrence and completion reporting within 72 hours of actual conclusion. Report deviations immediately; the formal change-report anchor requires clarification. Late and incomplete incident notices remain accepted. Use established emergency channels for urgent assistance.</p>
    <p>CHED acknowledgment records receipt/completeness; it does not authorize conduct. Contact CHED Region IV-A through its established channels for policy clarification.</p>
</section>
@endsection
