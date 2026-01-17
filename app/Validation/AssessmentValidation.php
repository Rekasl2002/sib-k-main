<?php

namespace App\Validation;

class AssessmentValidation
{
    public static function rules(): array
    {
        return [
            'title'                   => 'required|min_length[3]|max_length[200]',
            'assessment_type'         => 'required|max_length[50]',
            'target_audience'         => 'required|in_list[Individual,Class,Grade,All]',
            'duration_minutes'        => 'permit_empty|is_natural',
            'passing_score'           => 'permit_empty|decimal|less_than_equal_to[100]|greater_than_equal_to[0]',
            'max_attempts'            => 'required|is_natural_no_zero',
            'show_result_immediately' => 'permit_empty|in_list[0,1]',
            'allow_review'            => 'permit_empty|in_list[0,1]',
            'start_date'              => 'permit_empty|valid_date',
            'end_date'                => 'permit_empty|valid_date',

            // opsional tapi berguna utk sanitasi
            'target_class_id'         => 'permit_empty|integer',
            'target_grade'            => 'permit_empty|string|max_length[10]',
            'is_active'               => 'permit_empty|in_list[0,1]',
            'is_published'            => 'permit_empty|in_list[0,1]',
            'instructions'            => 'permit_empty|string',
            'description'             => 'permit_empty|string',
        ];
    }

    public static function messages(): array
    {
        return [
            'title' => [
                'required'   => 'Judul asesmen wajib diisi.',
                'min_length' => 'Judul minimal 3 karakter.',
            ],
            'assessment_type' => [
                'required' => 'Jenis asesmen wajib dipilih.',
            ],
            'target_audience' => [
                'required' => 'Sasaran asesmen wajib dipilih.',
                'in_list'  => 'Sasaran asesmen tidak valid.',
            ],
            'max_attempts' => [
                'required'            => 'Maks. percobaan wajib diisi.',
                'is_natural_no_zero'  => 'Maks. percobaan harus angka ≥ 1.',
            ],
            'passing_score' => [
                'less_than_equal_to'    => 'Nilai lulus maksimal 100.',
                'greater_than_equal_to' => 'Nilai lulus minimal 0.',
            ],
        ];
    }
}
