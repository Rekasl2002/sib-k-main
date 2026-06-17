<?php // app/Views/student/career/detail.php — memakai badan detail bersama agar konsisten ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?= $this->include('career/career_detail_body', ['backUrl' => site_url('student/career')]) ?>
<?= $this->endSection() ?>
