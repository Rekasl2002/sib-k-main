<?php // app/Views/parent/career/detail.php — memakai badan detail bersama agar konsisten ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?= $this->include('career/career_detail_body', ['backUrl' => site_url('parent/career')]) ?>
<?= $this->endSection() ?>
