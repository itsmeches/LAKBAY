<?php

return [
    'version' => 1,
    'groups' => [
        'A. Relevance and Academic Justification' => [
            'course_alignment' => 'Is the activity directly aligned with a course outcome or program requirement?',
            'off_campus_necessity' => 'Can the learning objective not be achieved through in-campus or virtual means?',
            'proper_approval' => 'Has the activity been approved by the department/institution through proper channels?',
        ],
        'B. Accessibility and Cost Considerations' => [
            'reasonable_cost' => 'Has the cost per student been reviewed to ensure it is reasonable and inclusive?',
            'financial_capacity' => 'Has student financial capacity been considered (e.g., consultation or survey)?',
            'cost_support' => 'Are there available subsidies, discounts, or cost-sharing options for students?',
        ],
        'C. Destination and Distance Assessment' => [
            'appropriate_destination' => 'Is the destination appropriate for the intended academic activity?',
            'justifiable_distance' => 'Is the distance justifiable given the length, purpose, and budget of the activity?',
            'safe_transport' => 'Is the destination accessible by safe, legal, and authorized transport providers?',
        ],
        'D. Safety, Risk, and Readiness' => [
            'risk_assessment' => 'Has a risk assessment been prepared for the activity and destination?',
            'agency_coordination' => 'Has coordination been initiated with relevant government agencies (LTO, PNP, LGU, etc.)?',
            'safety_focal_person' => 'Is there an identified faculty/staff lead and designated safety focal person?',
            'emergency_plan' => 'Has a contingency/emergency plan been developed?',
        ],
        'E. Student and Stakeholder Engagement' => [
            'identified_participants' => 'Have the participants, supervising personnel, and authorized representatives been identified and documented?',
            'group_size_justification' => 'Is the number of participating students clearly stated, with justification for group size (large, small, or limited participants)?',
            'student_co_planning' => 'Has it been co-planned with students in the conduct of activity?',
            'student_consultation' => 'Has prior consultation with students been conducted?',
            'minor_consent' => 'Has consent from parents/guardians been secured, if below 18?',
            'orientation' => 'Has a pre-departure orientation been scheduled or conducted?',
            'evaluation' => 'Is post-activity feedback or evaluation planned?',
        ],
    ],
    'recommendations' => [
        'proceed' => 'Minimum criteria met - Proceed with planning and documentation',
        'revise' => 'Gaps identified - Revise the proposed activity and complete the necessary requirements prior to implementation',
        'defer' => 'Not aligned with academic or policy requirements - Recommend cancellation, redesign, or deferment',
    ],
];
