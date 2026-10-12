<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class CaseEvidenceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DocumentType::firstOrCreate(['code' => 'case_evidence'], ['name' => 'Restricted case procedural evidence', 'confidentiality' => 'restricted', 'source_page' => 16]);
    }
}
