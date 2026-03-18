<?php

namespace App\Services\Hr;

use App\Models\Hr\Applicant;
use App\Models\Hr\ApplicantNormalizedProfile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NormalizationService
{
    /**
     * Standardize applicant profile using hybrid approach (Rule-based + AI placeholder)
     */
    public function normalizeProfile(Applicant $applicant): ApplicantNormalizedProfile
    {
        $log = [];
        
        // --- Step 1: Rule-Based Normalization (Primary) ---
        $normalizedData = $this->applyRuleBasedNormalization($applicant, $log);
        
        // --- Step 2: AI-Assisted Enhancement (Placeholder) ---
        // In a production environment, this would call an LLM API to parse unstructured CV data
        $aiData = $this->simulateAINormalization($applicant, $log);
        
        // Merge data based on confidence scores
        $finalData = $this->mergeNormalizationData($normalizedData, $aiData, $log);
        
        // --- Step 3: Persistence ---
        return ApplicantNormalizedProfile::updateOrCreate(
            ['applicant_id' => $applicant->id],
            array_merge($finalData, [
                'normalization_log' => $log
            ])
        );
    }

    /**
     * Rule-based normalization using structured inputs and keyword mappings
     */
    protected function applyRuleBasedNormalization(Applicant $applicant, &$log): array
    {
        $data = [
            'education_level' => null,
            'education_field' => null,
            'years_of_experience' => (float)$applicant->years_of_experience,
            'current_role' => null,
            'skills' => [],
            'certifications' => [],
            'confidence' => 100 // Rule-based is high confidence for structured data
        ];

        // 1. Normalize Education from structured qualifications
        if (is_array($applicant->qualifications) && !empty($applicant->qualifications)) {
            // Find highest level
            $levels = array_column($applicant->qualifications, 'qualification_level');
            $data['education_level'] = $this->getHighestEducationLevel($levels);
            
            $log['rule_based_education'] = "Extracted highest level: " . $data['education_level'];
        }

        // 2. Map existing skills/certs if they exist in some other fields (placeholder)
        // For now, let's assume we extract them from 'qualification' field if it's text
        if ($applicant->qualification && Str::contains(strtolower($applicant->qualification), ['cpa', 'acca', 'cisa'])) {
            $data['certifications'][] = Str::upper($applicant->qualification);
            $log['rule_based_certs'] = "Extracted certification from text field";
        }

        return $data;
    }

    /**
     * AI-Assisted normalization simulator
     */
    protected function simulateAINormalization(Applicant $applicant, &$log): array
    {
        // Simulate parsing unstructured data like cover letter or resume path
        $confidence = rand(60, 95); // Simulated confidence
        
        $aiData = [
            'education_level' => null,
            'years_of_experience' => null,
            'current_role' => null,
            'skills' => ['Communication', 'Teamwork'],
            'confidence' => $confidence
        ];

        if ($applicant->cover_letter) {
            // Simulate extracting "current role" from text
            if (Str::contains(strtolower($applicant->cover_letter), 'manager')) {
                $aiData['current_role'] = 'Managerial Position';
            }
        }

        $log['ai_simulation'] = "AI parsing simulated with {$confidence}% confidence";
        
        return $aiData;
    }

    /**
     * Merge rule-based and AI data
     */
    protected function mergeNormalizationData(array $ruleData, array $aiData, &$log): array
    {
        $final = $ruleData;
        $aiConfidence = $aiData['confidence'];
        
        // If AI is high confidence and rule data is missing, use AI
        if ($aiConfidence >= 90) {
            foreach ($aiData as $key => $value) {
                if (empty($final[$key]) && !empty($value)) {
                    $final[$key] = $value;
                    $log["merging_{$key}"] = "Used AI data (High confidence: {$aiConfidence}%)";
                }
            }
        }

        $final['ai_confidence_score'] = $aiConfidence;
        $final['requires_hr_review'] = ($aiConfidence < 90);
        
        return $final;
    }

    /**
     * Get highest level from education levels array
     */
    protected function getHighestEducationLevel(array $levels): string
    {
        $hierarchy = ['phd', 'masters', 'degree', 'diploma', 'certificate', 'other'];
        foreach ($hierarchy as $h) {
            if (in_array($h, $levels)) {
                return $h;
            }
        }
        return 'other';
    }
}
