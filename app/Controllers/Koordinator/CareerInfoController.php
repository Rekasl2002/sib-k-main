<?php

namespace App\Controllers\Koordinator;

use App\Controllers\BaseController;
use App\Models\CareerOptionModel;
use App\Models\UniversityInfoModel;

class CareerInfoController extends BaseController
{
    protected CareerOptionModel $careers;
    protected UniversityInfoModel $universities;

    public function __construct()
    {
        $this->careers      = new CareerOptionModel();
        $this->universities = new UniversityInfoModel();
        helper(['permission', 'url', 'form']);
    }

    public function index()
    {
        require_permission(['manage_career_info', 'view_career_info']);

        $careerFilters = [
            'q'      => trim((string)$this->request->getGet('q')),
            'sector' => $this->request->getGet('sector'),
            'edu'    => $this->request->getGet('edu'),
            'status' => $this->request->getGet('status'),
            'pub'    => $this->request->getGet('pub'),
        ];

        $careerBuilder = $this->careers
            ->select('career_options.*, creator.full_name AS created_by_name')
            ->join('users creator', 'creator.id = career_options.created_by', 'left');

        if ($careerFilters['q'] !== '') {
            $careerBuilder->groupStart()
                ->like('career_options.title', $careerFilters['q'])
                ->orLike('career_options.sector', $careerFilters['q'])
                ->orLike('career_options.description', $careerFilters['q'])
                ->groupEnd();
        }
        if ($careerFilters['sector']) {
            $careerBuilder->where('career_options.sector', $careerFilters['sector']);
        }
        if ($careerFilters['edu']) {
            $careerBuilder->where('career_options.min_education', $careerFilters['edu']);
        }
        if ($careerFilters['status'] !== null && $careerFilters['status'] !== '') {
            $careerBuilder->where('career_options.is_active', (int)$careerFilters['status']);
        }
        if ($careerFilters['pub'] !== null && $careerFilters['pub'] !== '') {
            $careerBuilder->where('career_options.is_public', (int)$careerFilters['pub']);
        }

        $careers = $careerBuilder
            ->orderBy('career_options.title', 'ASC')
            ->paginate(10, 'careers');

        $universities = $this->universities
            ->select('university_info.*, creator.full_name AS created_by_name')
            ->join('users creator', 'creator.id = university_info.created_by', 'left')
            ->orderBy('university_info.university_name', 'ASC')
            ->paginate(10, 'universities');

        return view('koordinator/career/index', [
            'title'         => 'Fitur Info Karier dan Info Studi Lanjut',
            'careers'       => $careers,
            'careerPager'   => $this->careers->pager,
            'careerFilters' => $careerFilters,
            'universities'  => $universities,
            'uniPager'      => $this->universities->pager,
            'activeTab'     => $this->request->getGet('tab') ?: 'careers',
        ]);
    }

    public function studentChoices()
    {
        require_permission(['manage_career_info', 'view_career_info']);

        $activeTab = $this->request->getGet('tab') === 'universities' ? 'universities' : 'careers';
        $q = trim((string)$this->request->getGet('q'));
        $perPage = (int)($this->request->getGet('per_page') ?: 10);
        $perPage = max(5, min(100, $perPage));

        $careerChoices = [];
        $universityChoices = [];

        if (db_connect()->tableExists('student_saved_careers')) {
            $careerChoices = $this->careers
                ->select('student_saved_careers.created_at AS saved_at, users.full_name AS student_name, students.nisn, classes.class_name, career_options.title AS career_title, career_options.sector, career_options.min_education')
                ->join('student_saved_careers', 'student_saved_careers.career_id = career_options.id', 'inner')
                ->join('students', 'students.id = student_saved_careers.student_id', 'inner')
                ->join('users', 'users.id = students.user_id', 'left')
                ->join('classes', 'classes.id = students.class_id', 'left');
            if ($q !== '') {
                $careerChoices->groupStart()
                    ->like('users.full_name', $q)
                    ->orLike('students.nisn', $q)
                    ->orLike('career_options.title', $q)
                    ->groupEnd();
            }
            $careerChoices = $careerChoices->orderBy('users.full_name', 'ASC')->paginate($perPage, 'student_careers');
        }

        if (db_connect()->tableExists('student_saved_universities')) {
            $universityChoices = $this->universities
                ->select('student_saved_universities.created_at AS saved_at, users.full_name AS student_name, students.nisn, classes.class_name, university_info.university_name, university_info.location, university_info.accreditation')
                ->join('student_saved_universities', 'student_saved_universities.university_id = university_info.id', 'inner')
                ->join('students', 'students.id = student_saved_universities.student_id', 'inner')
                ->join('users', 'users.id = students.user_id', 'left')
                ->join('classes', 'classes.id = students.class_id', 'left');
            if ($q !== '') {
                $universityChoices->groupStart()
                    ->like('users.full_name', $q)
                    ->orLike('students.nisn', $q)
                    ->orLike('university_info.university_name', $q)
                    ->groupEnd();
            }
            $universityChoices = $universityChoices->orderBy('users.full_name', 'ASC')->paginate($perPage, 'student_universities');
        }

        return view('koordinator/career/student_choices', [
            'title'             => 'Pilihan Siswa',
            'activeTab'         => $activeTab,
            'filters'           => ['q' => $q, 'per_page' => $perPage],
            'careerChoices'     => $careerChoices,
            'careerPager'       => $this->careers->pager,
            'universityChoices' => $universityChoices,
            'universityPager'   => $this->universities->pager,
        ]);
    }
}
