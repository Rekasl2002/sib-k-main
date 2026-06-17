<?php // app/Views/student/career/university_detail.php — memakai badan detail bersama ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?= $this->include('career/university_detail_body', ['backUrl' => site_url('student/career?tab=universities')]) ?>
<?= $this->endSection() ?>
