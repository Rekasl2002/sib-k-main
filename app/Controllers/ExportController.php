<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Config\Database;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportController extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
        helper(['url']);
    }

    public function options()
    {
        return view('admin/export/options');
    }

    public function students()
    {
        $rows = $this->db->table('students s')
            ->select('s.id, s.full_name, s.nis, s.nisn, c.class_name, s.gender, s.birth_date')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->orderBy('c.class_name')->orderBy('s.full_name')->get()->getResultArray();

        $sheet = new Spreadsheet();
        $sheet->getProperties()->setCreator('SIB-K')->setTitle('Daftar Siswa');
        $ws = $sheet->getActiveSheet();
        $ws->setTitle('Siswa');

        $ws->fromArray(['ID','Nama','NIS','NISN','Kelas','Gender','Tanggal Lahir'], null, 'A1');
        $ws->fromArray($rows ? array_map(fn($r)=>[
            $r['id'],$r['full_name'],$r['nis'],$r['nisn'],$r['class_name'],$r['gender'],$r['birth_date']
        ], $rows) : [], null, 'A2');

        $this->outputSpreadsheet($sheet, 'Export_Siswa.xlsx');
    }

    public function violations()
    {
        $from = $this->request->getGet('from');
        $to   = $this->request->getGet('to');

        $b = $this->db->table('violations v')
            ->select('v.id, v.violation_date, s.full_name as student, c.class_name, vc.category_name, v.description, v.points')
            ->join('students s','s.id=v.student_id','left')
            ->join('classes c','c.id=s.class_id','left')
            ->join('violation_categories vc','vc.id=v.category_id','left');
        if ($from) $b->where('v.violation_date >=', $from);
        if ($to)   $b->where('v.violation_date <=', $to);
        $rows = $b->orderBy('v.violation_date','ASC')->get()->getResultArray();

        $sheet = new Spreadsheet();
        $sheet->getProperties()->setCreator('SIB-K')->setTitle('Pelanggaran');
        $ws = $sheet->getActiveSheet();
        $ws->setTitle('Pelanggaran');

        $ws->fromArray(['ID','Tanggal','Siswa','Kelas','Kategori','Deskripsi','Poin'], null, 'A1');
        $ws->fromArray($rows ? array_map(fn($r)=>[
            $r['id'],$r['violation_date'],$r['student'],$r['class_name'],$r['category_name'],$r['description'],$r['points']
        ], $rows) : [], null, 'A2');

        $this->outputSpreadsheet($sheet, 'Export_Pelanggaran.xlsx');
    }

    public function sessions()
    {
        $from = $this->request->getGet('from');
        $to   = $this->request->getGet('to');

        $b = $this->db->table('counseling_sessions cs')
            ->select('cs.id, cs.session_date, s.full_name as student, c.class_name, u.full_name as counselor, cs.duration_minutes, cs.topic')
            ->join('students s','s.id=cs.student_id','left')
            ->join('classes c','c.id=s.class_id','left')
            ->join('users u','u.id=cs.counselor_id','left');
        if ($from) $b->where('cs.session_date >=', $from);
        if ($to)   $b->where('cs.session_date <=', $to);
        $rows = $b->orderBy('cs.session_date','ASC')->get()->getResultArray();

        $sheet = new Spreadsheet();
        $sheet->getProperties()->setCreator('SIB-K')->setTitle('Sesi Konseling');
        $ws = $sheet->getActiveSheet();
        $ws->setTitle('Sesi');

        $ws->fromArray(['ID','Tanggal','Siswa','Kelas','Konselor','Durasi (mnt)','Topik'], null, 'A1');
        $ws->fromArray($rows ? array_map(fn($r)=>[
            $r['id'],$r['session_date'],$r['student'],$r['class_name'],$r['counselor'],$r['duration_minutes'],$r['topic']
        ], $rows) : [], null, 'A2');

        $this->outputSpreadsheet($sheet, 'Export_Sesi.xlsx');
    }

    protected function outputSpreadsheet(Spreadsheet $spreadsheet, string $filename)
    {
        // header download
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment;filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
