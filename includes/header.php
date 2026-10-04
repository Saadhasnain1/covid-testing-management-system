<?php
$title ??= 'COVID-19 Testing Management System'; $admin ??= false;
require_once __DIR__ . '/icons.php';
$uiRoute = basename($_SERVER['SCRIPT_NAME']);
$uiHome = !$admin && $uiRoute === 'index.php';
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="COVID-19 Testing Management System: connected test requests, sample collection and report management.">
<title><?= e($title) ?> | COVID19 TMS</title>
<link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css"><link rel="stylesheet" href="css/app.css">
</head><body class="<?= $admin ? 'view-admin' : ($uiHome ? 'view-home' : 'view-public') ?>"><a class="skip-link" href="#main">Skip to content</a>
<?php require __DIR__ . '/navbar.php'; ?>
<div class="<?= $admin ? 'workspace' : 'public-shell' ?>">
<?php if ($admin) { require __DIR__ . '/sidebar.php'; } ?>
<main id="main" tabindex="-1" class="<?= $admin ? 'admin-main' : 'container public-main' ?>">
<?php if ($admin && $uiRoute !== 'dashboard.php'): ?>
<nav class="page-breadcrumb" aria-label="Breadcrumb"><a href="dashboard.php">Workspace</a><span aria-hidden="true">/</span><span aria-current="page"><?= e($title) ?></span></nav>
<?php endif; ?>
<?php if (isset($_SESSION['flash'])): ?><div class="alert alert-success" role="status"><span class="feedback-icon"><?= ui_icon('check') ?></span><div><?= e($_SESSION['flash']) ?></div></div><?php unset($_SESSION['flash']); endif; ?>
