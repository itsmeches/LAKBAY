<?php

return [
    'version' => 1,
    'areas' => [
        'transport' => 'Transport & Travel (e.g. Delays, accidents, unauthorized/unregistered transport, no LTO/LTFRB compliance)',
        'health' => 'Health & Medical (e.g. Illness, pre-existing conditions, lack of first aid, medical clearance, insurance coverage)',
        'weather' => 'Weather & Natural Hazards (e.g. Typhoons, floods, extreme heat, unsafe terrain, earthquake, tsunami, etc.)',
        'security' => 'Safety & Security (e.g. Theft, harassment, lack of LGU/PNP coordination)',
        'welfare' => 'Student Welfare (e.g. No parallel activity, lack of inclusivity)',
        'preparedness' => 'Emergency Preparedness (e.g. No contingency plan, missing emergency contacts, no focal person)',
    ],
    'risk_levels' => ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'],
    'offices' => [
        'lgu' => 'LGU/Barangay',
        'pnp' => 'PNP',
        'hospital' => 'Hospital/Clinic',
        'ched' => 'CHED Regional Office',
        'hei' => 'HEI Emergency Focal',
    ],
    'certification' => 'We certify that this Risk Assessment and Contingency Plan (RACP) has been completed prior to the conduct of the local off-campus activity. Appropriate measures have been identified to address the assessed risks and ensure the safety, security, and welfare of the participants.',
];
