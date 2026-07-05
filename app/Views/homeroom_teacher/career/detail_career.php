<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $this->setData([
    'crumbs'  => [
        ['label' => 'Wali Kelas', 'url' => base_url('homeroom/dashboard')],
        ['label' => 'Info Karier dan Studi Lanjut', 'url' => site_url('homeroom/career-info')],
        ['label' => 'Detail Karier'],
    ],
]) ?>
<?= $this->include('career/career_detail_body') ?>
<?= $this->endSection() ?>
