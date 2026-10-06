<x-filament-panels::page>
    @php
        $namespace = $panel === 'hei' ? 'App\\Filament\\Hei\\Resources\\' : 'App\\Filament\\Ched\\Resources\\';
        $link = function (string $resource) use ($namespace): ?string {
            $class = $namespace.$resource.'Resource';
            return class_exists($class) && $class::canViewAny() ? $class::getUrl() : null;
        };
        $submissionUrl = $link('Submission');
        $steps = $panel === 'hei' ? [
            ['Prepare your calendar', 'Set the academic period, create the annual CLOCA, then add activity plans.', 'Calendar'],
            ['Attach signed evidence', 'Upload the signed forms and supporting documents before submitting.', 'Document'],
            ['Submit and respond', 'Submit from the calendar or activity row. Track reviews, corrections and acknowledgment letters here.', 'Submission'],
            ['Report the outcome', 'Record the activity conclusion, then file completion, change or incident reports.', 'CompletionReport'],
            ['Follow up', 'Respond to evidence requests and any corrective actions from CHED.', 'CorrectiveAction'],
        ] : [
            ['Receive and review', 'Open a submission, check its evidence and request corrections when needed.', 'Submission'],
            ['Acknowledge', 'An authorized signatory issues the signed acknowledgment for a complete revision.', 'Submission'],
            ['Monitor and assist', 'Record monitoring findings, technical assistance and corrective follow-up.', 'MonitoringRecord'],
            ['Track activity reports', 'Check completion and change reports. Incident access depends on your role.', 'CompletionReport'],
        ];
    @endphp
    <div class="lakbay-workspace">
        <section class="lakbay-intro" aria-labelledby="workspace-heading">
            <div>
                <p class="lakbay-eyebrow">{{ $panel === 'hei' ? 'HEI portal' : 'CHED regional workspace' }}</p>
                <h2 id="workspace-heading">{{ $panel === 'hei' ? 'A clear path from planning to completion' : 'Review, acknowledge and follow up' }}</h2>
                <p>{{ $panel === 'hei' ? 'Work through the stages below. Your sidebar follows the same order.' : 'Start with the submission inbox, then continue with monitoring and reporting.' }}</p>
            </div>
            @if($panel === 'hei' && auth()->user()->can('reports.submit') && $link('IncidentReport'))
                <a class="lakbay-button lakbay-button-secondary" href="{{ $link('IncidentReport') }}">Report an incident</a>
            @endif
        </section>

        @if($panel === 'hei' && $corrections > 0 && $submissionUrl)
            <section class="lakbay-attention" aria-label="Action needed">
                <strong>{{ $corrections }} submission(s) need corrections</strong>
                <p>Open the review findings, update the plan or evidence, then resubmit from the original calendar or activity.</p>
                <a class="lakbay-button" href="{{ ($namespace.'SubmissionResource')::getUrl('index', ['tableFilters' => ['status' => ['value' => 'corrections_requested']]]) }}">Review correction requests</a>
            </section>
        @elseif($panel === 'ched' && $submissionUrl && ($reviewPending > 0 || $ackPending > 0))
            <section class="lakbay-attention" aria-label="Submission queue">
                <strong>{{ $reviewPending }} awaiting review · {{ $ackPending }} ready for acknowledgment</strong>
                <p>Open the inbox to continue the actions available to your account.</p>
                <a class="lakbay-button" href="{{ $submissionUrl }}">Open submission inbox</a>
            </section>
        @endif

        <div class="lakbay-stats" aria-label="Workspace totals">
            <div><strong>{{ $activities }}</strong><span>Activity plans</span></div>
            <div><strong>{{ $submissions }}</strong><span>Current submissions</span></div>
            <div><strong>{{ $corrections }}</strong><span>Corrections requested</span></div>
        </div>

        <section aria-labelledby="steps-heading">
            <h2 id="steps-heading" class="lakbay-section-title">{{ $panel === 'hei' ? 'Your activity workflow' : 'Your review workflow' }}</h2>
            <p class="lakbay-section-description">These are workflow stages, not a completion checklist. Each activity progresses separately.</p>
            <ol class="lakbay-steps">
                @foreach($steps as [$title, $description, $resource])
                    @if($url = $link($resource))
                        <li>
                            <span class="lakbay-step-number" aria-hidden="true">{{ $loop->iteration }}</span>
                            <div><h3>{{ $title }}</h3><p>{{ $description }}</p><a href="{{ $url }}">Open {{ strtolower(($namespace.$resource.'Resource')::getNavigationLabel()) }} <span aria-hidden="true">→</span></a></div>
                        </li>
                    @endif
                @endforeach
            </ol>
        </section>

        @if($panel === 'ched' && (auth()->user()->can('users.invite') || auth()->user()->can('policy.manage') || auth()->user()->can('reports.export')))
            <section class="lakbay-card">
                <h2 class="lakbay-section-title">Administration and reporting</h2>
                <div class="lakbay-links">
                    @can('users.invite')<a href="{{ \App\Filament\Ched\Pages\Invitations::getUrl() }}">Invite an institutional or CHED user</a>@endcan
                    @can('policy.manage')<a href="{{ \App\Filament\Ched\Pages\PolicyConfiguration::getUrl() }}">Manage policy requirements and templates</a>@endcan
                    @can('reports.export')<a href="{{ \App\Filament\Ched\Pages\Reporting::getUrl() }}">Regional reports and exports</a>@endcan
                </div>
            </section>
        @endif

        <section class="lakbay-card" aria-labelledby="recent-heading">
            <h2 id="recent-heading" class="lakbay-section-title">Recent submissions</h2>
            @forelse($recent as $submission)
                <div class="lakbay-submission">
                    <div>
                        @if($submissionUrl)
                            <a href="{{ ($namespace.'SubmissionResource')::getUrl('view', ['record' => $submission]) }}">{{ $submission->reference }}</a>
                        @else
                            <strong>{{ $submission->reference }}</strong>
                        @endif
                        <span>{{ $submission->submitted_at?->timezone('Asia/Manila')->format('d M Y, g:i A') }}</span>
                    </div>
                    <span class="lakbay-status">{{ ucfirst(str_replace('_', ' ', $submission->status)) }}{{ $submission->is_late ? ' · Late' : '' }}</span>
                </div>
            @empty
                <p class="lakbay-empty">{{ $panel === 'hei' ? 'No submissions yet. Start with your annual calendar, add activity plans and upload signed documents.' : 'No submissions in your workspace yet. Verified HEIs will appear here once they submit.' }}</p>
            @endforelse
        </section>

        <details class="lakbay-policy">
            <summary>Policy and acknowledgment guidance{{ $draftPolicy ? ' — confirmation pending' : '' }}</summary>
            @if($draftPolicy)<p>The July 2026 draft requires CHED confirmation. Production submissions require a confirmed policy. Incident reminders provisionally use 24 hours.</p>@endif
            <p>CHED acknowledgment records receipt and completeness. HEI authorization, implementation status and exceptional clearance are tracked separately.</p>
        </details>
    </div>
</x-filament-panels::page>