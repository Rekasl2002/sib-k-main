<?php

/**
 * File Path: app/Validation/SessionValidation.php
 * 
 * Session Validation
 * Validation rules untuk form counseling session (create, edit, add note)
 * 
 * @package    SIB-K
 * @subpackage Validation
 * @category   Counseling
 * @author     Development Team
 * @created    2025-01-06
 */

namespace App\Validation;

class SessionValidation
{
    /**
     * Validation rules untuk create session
     * 
     * @return array
     */
    public static function createRules(): array
    {
        return [
            'session_type' => [
                'rules' => 'required|in_list[Individu,Kelompok,Klasikal]',
                'errors' => [
                    'required' => 'Jenis sesi harus dipilih',
                    'in_list' => 'Jenis sesi tidak valid',
                ],
            ],
            'student_id' => [
                'rules' => 'permit_empty|integer|is_not_unique[students.id]',
                'errors' => [
                    'integer' => 'ID siswa tidak valid',
                    'is_not_unique' => 'Siswa tidak ditemukan',
                ],
            ],
            'class_id' => [
                'rules' => 'permit_empty|integer|is_not_unique[classes.id]',
                'errors' => [
                    'integer' => 'ID kelas tidak valid',
                    'is_not_unique' => 'Kelas tidak ditemukan',
                ],
            ],
            'session_date' => [
                'rules' => 'required|valid_date[Y-m-d]',
                'errors' => [
                    'required' => 'Tanggal sesi harus diisi',
                    'valid_date' => 'Format tanggal tidak valid (YYYY-MM-DD)',
                ],
            ],
            'session_time' => [
                'rules' => 'permit_empty|valid_date[H:i]',
                'errors' => [
                    'valid_date' => 'Format waktu tidak valid (HH:MM)',
                ],
            ],
            'topic' => [
                'rules' => 'required|min_length[3]|max_length[255]',
                'errors' => [
                    'required' => 'Topik sesi harus diisi',
                    'min_length' => 'Topik minimal 3 karakter',
                    'max_length' => 'Topik maksimal 255 karakter',
                ],
            ],
            'location' => [
                'rules' => 'permit_empty|max_length[100]',
                'errors' => [
                    'max_length' => 'Lokasi maksimal 100 karakter',
                ],
            ],
            'problem_description' => [
                'rules' => 'permit_empty|string',
                'errors' => [
                    'string' => 'Deskripsi masalah tidak valid',
                ],
            ],
            'is_confidential' => [
                'rules' => 'permit_empty|in_list[0,1]',
                'errors' => [
                    'in_list' => 'Nilai confidential tidak valid',
                ],
            ],
            'duration_minutes' => [
                'rules' => 'permit_empty|integer|greater_than[0]|less_than_equal_to[480]',
                'errors' => [
                    'integer' => 'Durasi harus berupa angka',
                    'greater_than' => 'Durasi minimal 1 menit',
                    'less_than_equal_to' => 'Durasi maksimal 480 menit (8 jam)',
                ],
            ],
            'participants' => [
                'label' => 'Peserta',
                'rules' => 'permit_empty', // array-ness ditangani oleh normalisasi + wildcard di bawah
            ],
            'participants.*' => [
                'label'  => 'Peserta',
                'rules'  => 'if_exist|is_natural_no_zero|is_not_unique[students.id]',
                'errors' => [
                    'is_natural_no_zero' => 'Setiap peserta harus berupa ID siswa yang valid.',
                    'is_not_unique'      => 'Peserta tidak ditemukan di data siswa.',
                ],
            ],
        ];
    }

    /**
     * Validation rules untuk update session
     * 
     * @return array
     */
    public static function updateRules(): array
    {
        return [
            'session_type' => [
                'rules' => 'required|in_list[Individu,Kelompok,Klasikal]',
                'errors' => [
                    'required' => 'Jenis sesi harus dipilih',
                    'in_list' => 'Jenis sesi tidak valid',
                ],
            ],
            'student_id' => [
                'rules' => 'permit_empty|integer|is_not_unique[students.id]',
                'errors' => [
                    'integer' => 'ID siswa tidak valid',
                    'is_not_unique' => 'Siswa tidak ditemukan',
                ],
            ],
            'class_id' => [
                'rules' => 'permit_empty|integer|is_not_unique[classes.id]',
                'errors' => [
                    'integer' => 'ID kelas tidak valid',
                    'is_not_unique' => 'Kelas tidak ditemukan',
                ],
            ],
            'session_date' => [
                'rules' => 'required|valid_date[Y-m-d]',
                'errors' => [
                    'required' => 'Tanggal sesi harus diisi',
                    'valid_date' => 'Format tanggal tidak valid (YYYY-MM-DD)',
                ],
            ],
            'session_time' => [
                'rules' => 'permit_empty|valid_date[H:i]',
                'errors' => [
                    'valid_date' => 'Format waktu tidak valid (HH:MM)',
                ],
            ],
            'topic' => [
                'rules' => 'required|min_length[3]|max_length[255]',
                'errors' => [
                    'required' => 'Topik sesi harus diisi',
                    'min_length' => 'Topik minimal 3 karakter',
                    'max_length' => 'Topik maksimal 255 karakter',
                ],
            ],
            'location' => [
                'rules' => 'permit_empty|max_length[100]',
                'errors' => [
                    'max_length' => 'Lokasi maksimal 100 karakter',
                ],
            ],
            'problem_description' => [
                'rules' => 'permit_empty|string',
                'errors' => [
                    'string' => 'Deskripsi masalah tidak valid',
                ],
            ],
            'session_summary' => [
                'rules' => 'permit_empty|string',
                'errors' => [
                    'string' => 'Ringkasan sesi tidak valid',
                ],
            ],
            'follow_up_plan' => [
                'rules' => 'permit_empty|string',
                'errors' => [
                    'string' => 'Rencana tindak lanjut tidak valid',
                ],
            ],
            'status' => [
                'rules' => 'permit_empty|in_list[Dijadwalkan,Selesai,Dibatalkan]',
                'errors' => [
                    'in_list' => 'Status tidak valid',
                ],
            ],
            'cancellation_reason' => [
                'rules' => 'permit_empty|string',
                'errors' => [
                    'string' => 'Alasan pembatalan tidak valid',
                ],
            ],
            'is_confidential' => [
                'rules' => 'permit_empty|in_list[0,1]',
                'errors' => [
                    'in_list' => 'Nilai confidential tidak valid',
                ],
            ],
            'duration_minutes' => [
                'rules' => 'permit_empty|integer|greater_than[0]|less_than_equal_to[480]',
                'errors' => [
                    'integer' => 'Durasi harus berupa angka',
                    'greater_than' => 'Durasi minimal 1 menit',
                    'less_than_equal_to' => 'Durasi maksimal 480 menit (8 jam)',
                ],
            ],
            // Peserta (untuk Kelompok/Klasikal). Jangan pakai rule "array".
            'participants' => [
                'label' => 'Peserta',
                'rules' => 'permit_empty', // array-ness ditangani oleh normalisasi + wildcard di bawah
            ],
            'participants.*' => [
                'label'  => 'Peserta',
                'rules'  => 'if_exist|is_natural_no_zero|is_not_unique[students.id]',
                'errors' => [
                    'is_natural_no_zero' => 'Setiap peserta harus berupa ID siswa yang valid.',
                    'is_not_unique'      => 'Peserta tidak ditemukan di data siswa.',
                ],
            ],
        ];
    }

    /**
     * Validation rules untuk add note
     * 
     * @return array
     */
    public static function addNoteRules(): array
    {
        return [
            'session_id' => [
                'rules' => 'required|integer|is_not_unique[counseling_sessions.id]',
                'errors' => [
                    'required' => 'Session ID harus diisi',
                    'integer' => 'Session ID tidak valid',
                    'is_not_unique' => 'Sesi konseling tidak ditemukan',
                ],
            ],
            'note_type' => [
                'rules' => 'required|in_list[Observasi,Diagnosis,Intervensi,Follow-up,Lainnya]',
                'errors' => [
                    'required' => 'Jenis catatan harus dipilih',
                    'in_list' => 'Jenis catatan tidak valid',
                ],
            ],
            'note_content' => [
                'rules' => 'required|min_length[10]',
                'errors' => [
                    'required' => 'Isi catatan harus diisi',
                    'min_length' => 'Isi catatan minimal 10 karakter',
                ],
            ],
            'is_confidential' => [
                'rules' => 'permit_empty|in_list[0,1]',
                'errors' => [
                    'in_list' => 'Nilai confidential tidak valid',
                ],
            ],
            'attachments' => [
                'rules' => 'permit_empty|string',
                'errors' => [
                    'string' => 'Format attachment tidak valid',
                ],
            ],
        ];
    }

    /**
     * Validation rules untuk update attendance (group/class session)
     * 
     * @return array
     */
    public static function updateAttendanceRules(): array
    {
        return [
            'participant_id' => [
                'rules' => 'required|integer|is_not_unique[session_participants.id]',
                'errors' => [
                    'required' => 'Participant ID harus diisi',
                    'integer' => 'Participant ID tidak valid',
                    'is_not_unique' => 'Peserta tidak ditemukan',
                ],
            ],
            'attendance_status' => [
                'rules' => 'required|in_list[Hadir,Tidak Hadir,Izin,Sakit]',
                'errors' => [
                    'required' => 'Status kehadiran harus dipilih',
                    'in_list' => 'Status kehadiran tidak valid',
                ],
            ],
            'participation_note' => [
                'rules' => 'permit_empty|string',
                'errors' => [
                    'string' => 'Catatan partisipasi tidak valid',
                ],
            ],
        ];
    }

    /**
     * Custom validation untuk memastikan student_id atau class_id terisi
     * berdasarkan session_type
     * 
     * @param array $data
     * @return array|bool
     */
    public static function validateSessionType(array $data)
    {
        $errors = [];

        $sessionType = $data['session_type'] ?? '';
        $studentId = $data['student_id'] ?? null;
        $classId = $data['class_id'] ?? null;

        // Untuk sesi Individu, student_id harus diisi
        if ($sessionType === 'Individu' && empty($studentId)) {
            $errors['student_id'] = 'Siswa harus dipilih untuk sesi individu';
        }

        // Untuk sesi Klasikal, class_id harus diisi
        if ($sessionType === 'Klasikal' && empty($classId)) {
            $errors['class_id'] = 'Kelas harus dipilih untuk sesi klasikal';
        }

        // Untuk sesi Kelompok, harus ada peserta
        if ($sessionType === 'Kelompok') {
            $participants = $data['participants'] ?? [];
            if (empty($participants) || !is_array($participants) || count($participants) < 2) {
                $errors['participants'] = 'Sesi kelompok minimal harus memiliki 2 peserta';
            }
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Custom validation untuk memastikan tanggal tidak di masa lalu
     * (opsional, bisa diaktifkan jika diperlukan)
     * 
     * @param string $date
     * @return bool
     */
    public static function validateFutureDate(string $date): bool
    {
        $sessionDate = strtotime($date);
        $today = strtotime(date('Y-m-d'));

        return $sessionDate >= $today;
    }

    /**
     * Custom validation untuk cancellation_reason
     * Harus diisi jika status = Dibatalkan
     * 
     * @param array $data
     * @return array|bool
     */
    public static function validateCancellation(array $data)
    {
        $errors = [];

        $status = $data['status'] ?? '';
        $reason = $data['cancellation_reason'] ?? '';

        if ($status === 'Dibatalkan' && empty($reason)) {
            $errors['cancellation_reason'] = 'Alasan pembatalan harus diisi jika status dibatalkan';
        }

        return empty($errors) ? true : $errors;
    }
}
