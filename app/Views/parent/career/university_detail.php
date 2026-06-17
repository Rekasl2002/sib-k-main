<?php // app/Views/parent/career/university_detail.php — memakai badan detail bersama ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?= $this->include('career/university_detail_body', ['backUrl' => site_url('parent/career?tab=universities')]) ?>
<?= $this->endSection() ?>
