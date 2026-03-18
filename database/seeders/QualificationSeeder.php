<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hr\Qualification;
use App\Models\Hr\QualificationDocument;
use Illuminate\Support\Facades\DB;

class QualificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $qualifications = [
            [
                'name' => 'Certificate',
                'code' => 'CERT',
                'level' => 'certificate',
                'description' => 'Certificate level qualification',
                'documents' => [
                    ['document_name' => 'Certificate Copy', 'document_type' => 'certificate', 'is_required' => true],
                    ['document_name' => 'Transcript', 'document_type' => 'transcript', 'is_required' => false],
                ]
            ],
            [
                'name' => 'Diploma',
                'code' => 'DIP',
                'level' => 'diploma',
                'description' => 'Diploma level qualification',
                'documents' => [
                    ['document_name' => 'Diploma Certificate', 'document_type' => 'diploma', 'is_required' => true],
                    ['document_name' => 'Academic Transcript', 'document_type' => 'transcript', 'is_required' => true],
                ]
            ],
            [
                'name' => 'Bachelor\'s Degree',
                'code' => 'BACHELOR',
                'level' => 'degree',
                'description' => 'Bachelor\'s degree qualification',
                'documents' => [
                    ['document_name' => 'Degree Certificate', 'document_type' => 'degree_certificate', 'is_required' => true],
                    ['document_name' => 'Academic Transcript', 'document_type' => 'transcript', 'is_required' => true],
                ]
            ],
            [
                'name' => 'Master\'s Degree',
                'code' => 'MASTERS',
                'level' => 'masters',
                'description' => 'Master\'s degree qualification',
                'documents' => [
                    ['document_name' => 'Master\'s Degree Certificate', 'document_type' => 'degree_certificate', 'is_required' => true],
                    ['document_name' => 'Master\'s Transcript', 'document_type' => 'transcript', 'is_required' => true],
                    ['document_name' => 'Bachelor\'s Degree Certificate', 'document_type' => 'degree_certificate', 'is_required' => true],
                ]
            ],
            [
                'name' => 'PhD / Doctorate',
                'code' => 'PHD',
                'level' => 'phd',
                'description' => 'Doctorate level qualification',
                'documents' => [
                    ['document_name' => 'PhD Certificate', 'document_type' => 'degree_certificate', 'is_required' => true],
                    ['document_name' => 'PhD Transcript', 'document_type' => 'transcript', 'is_required' => true],
                    ['document_name' => 'Master\'s Degree Certificate', 'document_type' => 'degree_certificate', 'is_required' => true],
                ]
            ],
            [
                'name' => 'Professional Certification',
                'code' => 'PROF_CERT',
                'level' => 'professional',
                'description' => 'Professional certification or license',
                'documents' => [
                    ['document_name' => 'Professional Certificate', 'document_type' => 'certificate', 'is_required' => true],
                    ['document_name' => 'License Document', 'document_type' => 'license', 'is_required' => false],
                ]
            ],
            [
                'name' => 'CPA (T)',
                'code' => 'CPAT',
                'level' => 'professional',
                'description' => 'Certified Public Accountant (Tanzania)',
                'documents' => [
                    ['document_name' => 'CPA Certificate', 'document_type' => 'certificate', 'is_required' => true],
                    ['document_name' => 'NBAA Membership Proof', 'document_type' => 'license', 'is_required' => true],
                ]
            ],
            [
                'name' => 'ACCA',
                'code' => 'ACCA',
                'level' => 'professional',
                'description' => 'Association of Chartered Certified Accountants',
                'documents' => [
                    ['document_name' => 'ACCA Certificate', 'document_type' => 'certificate', 'is_required' => true],
                ]
            ],
            [
                'name' => 'CISA',
                'code' => 'CISA',
                'level' => 'professional',
                'description' => 'Certified Information Systems Auditor',
                'documents' => [
                    ['document_name' => 'CISA Certificate', 'document_type' => 'certificate', 'is_required' => true],
                ]
            ],
            [
                'name' => 'Procurement Professional (PSPTB)',
                'code' => 'PSPTB',
                'level' => 'professional',
                'description' => 'Procurement and Supplies Professionals and Technicians Board',
                'documents' => [
                    ['document_name' => 'PSPTB Certificate', 'document_type' => 'certificate', 'is_required' => true],
                ]
            ],
        ];

        foreach ($qualifications as $qualData) {
            $documents = $qualData['documents'];
            unset($qualData['documents']);

            $qualification = Qualification::create($qualData);

            foreach ($documents as $index => $docData) {
                QualificationDocument::create([
                    'qualification_id' => $qualification->id,
                    'document_name' => $docData['document_name'],
                    'document_type' => $docData['document_type'],
                    'is_required' => $docData['is_required'],
                    'sort_order' => $index,
                ]);
            }
        }
    }
}
