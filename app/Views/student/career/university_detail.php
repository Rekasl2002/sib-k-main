<?php // app/Views/student/career/university_detail.php — memakai badan detail bersama ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $this->setData([
    'backUrl' => site_url('student/career?tab=universities'),
    'crumbs'  => [
        ['label' => 'Siswa', 'url' => base_url('student/dashboard')],
        ['label' => 'Info Karier dan Studi Lanjut', 'url' => site_url('student/career?tab=universities')],
        ['label' => 'Detail Perguruan Tinggi'],
    ],
]) ?>
<?= $this->include('career/university_detail_body') ?>
<?= $this->endSection() ?>
