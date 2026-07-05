<?php // app/Views/student/career/detail.php — memakai badan detail bersama agar konsisten ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $this->setData([
    'backUrl' => site_url('student/career'),
    'crumbs'  => [
        ['label' => 'Siswa', 'url' => base_url('student/dashboard')],
        ['label' => 'Info Karier dan Studi Lanjut', 'url' => site_url('student/career')],
        ['label' => 'Detail Karier'],
    ],
]) ?>
<?= $this->include('career/career_detail_body') ?>
<?= $this->endSection() ?>
