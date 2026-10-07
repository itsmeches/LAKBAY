<?php

return [
    'version' => 1,
    'rows' => [
        'mission' => ['page' => 1, 'requirement' => 'Aligned with the HEI mission-vision', 'supporting' => []],
        'purpose' => ['page' => 1, 'requirement' => 'In fulfillment of academic requirement, HEI representation, or for purposes of student well-being', 'supporting' => []],
        'cloca' => ['page' => 1, 'requirement' => 'Included in the Calendar of Local Off-Campus Activities (CLOCA)', 'supporting' => ['calendar' => 'Approved Calendar of Local Off-Campus Activities (CLOCA)']],
        'padp' => ['page' => 1, 'requirement' => 'Prepared Parallel Activity Design and Plan (PADP)', 'supporting' => ['plan' => 'Approved Parallel Activity Design and Plan (PADP)']],
        'costs' => ['page' => 1, 'requirement' => 'For activities, event, or engagement that will entail cost to be covered by the student, parents, or guardian, conducted consultation with students, parents, or guardian including presentation of breakdown of expenses', 'supporting' => ['consultation' => 'Highlights of consultation', 'expenses' => 'Breakdown of expenses']],
        'invitation' => ['page' => 1, 'requirement' => 'Ensured legitimacy of the invitation from the government or external organization', 'supporting' => ['copy' => 'Copy of invitation from the government or external organization']],
        'lgu' => ['page' => 1, 'requirement' => 'Coordinated with LGU;', 'supporting' => ['coordination' => 'Proof of coordination with LGU (e.g. correspondences)']],
        'dot' => ['page' => 1, 'requirement' => 'Ensured Accommodation or Venue to be utilized for the activity is accredited by Department of Tourism (DOT)', 'supporting' => ['accreditation' => 'Proof of information on the Department of Tourism (DOT) accreditation of the accommodation or venue']],
        'authorization' => ['page' => 1, 'requirement' => 'Approved by the HEI through its authorized approving authority;', 'supporting' => ['saa' => 'Sworn Affidavit of Accountability (SAA)']],
        'pic' => ['page' => 1, 'requirement' => 'Assignment or Designation of Personnel-in-Charge (PIC) with first aid training and following the student-PIC ratio', 'supporting' => ['designation' => 'Copy of the Designation of PIC', 'training' => 'Proof of first-aid training of PIC']],
        'assessments' => ['page' => 1, 'requirement' => 'Prepared Detailed Itinerary, Diagnostic Tool, Risk Assessment and Contingency Plan (RACP)', 'supporting' => ['itinerary' => 'Approved Detailed Itinerary', 'diagnostic' => 'Diagnostic Tool', 'racp' => 'Risk Assessment and Contingency Plan (RACP)']],
        'high_risk' => ['page' => 2, 'requirement' => 'For activities, events, or engagements that will require strenuous activity and/or participation of students with physical or medical condition and/or visit to areas with risk (e.g. hazard or disaster prone areas, conflict areas, high-risk or hotspot areas):',
            'particulars' => ['Permission from parent, legal guardian or spouse, as applicable', 'Medical Clearance', 'Insurance Coverage'],
            'supporting' => ['proof' => 'Proof of permission, medical clearance, insurance coverage'], 'note' => 'Items a to c must be submitted immediately upon request of CHED'],
        'notification' => ['page' => 2, 'requirement' => 'Notified parent, legal guardian, or spouse;', 'supporting' => ['written' => 'Written notification']],
        'orientation' => ['page' => 2, 'requirement' => 'Conducted pre-departure orientation including safety protocols;', 'supporting' => ['proof' => 'Proof of conduct of pre-orientation including safety protocols (e.g. Highlights of orientation, IEC materials)']],
    ],
    'attestation' => 'The HEI further certifies that all supporting documents are complete, authentic, and available for verification, monitoring, inspection, or submission to the Commission on Higher Education (CHED), whenever required.',
    'closing' => 'This Certification is issued for purposes of compliance with CHED Memorandum Order No. 24, Series of 2026.',
];
