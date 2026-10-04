<nav class="navbar navbar-expand-lg navbar-light app-nav" aria-label="<?= $admin ? 'Account navigation' : 'Main navigation' ?>">
<div class="container-fluid app-nav-inner"><a class="navbar-brand" href="index.php" aria-label="COVID-19 Testing Management System home"><span class="brand-mark" aria-hidden="true"><?= ui_icon('activity') ?></span><span class="brand-copy"><strong>COVID19 <span>TMS</span></strong><small>Testing Management System</small></span></a>
<?php if ($admin): ?><span class="workspace-label">Operations workspace</span><?php endif; ?>
<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navigation" aria-controls="navigation" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="navigation"><ul class="navbar-nav ml-auto">
<?php $links = $admin ? ['dashboard.php'=>'Dashboard','profile.php'=>'Profile','change-password.php'=>'Password'] : ['index.php'=>'Home','index.php#about'=>'About','new-user-testing.php'=>'Testing','patient-search-report.php'=>'Track test','live-test-updates.php'=>'Live updates','login.php'=>'Admin login'];
foreach ($links as $url=>$label):
    $uiActive = !str_contains($url, '#') && ($uiRoute === $url || ($url === 'new-user-testing.php' && $uiRoute === 'registered-user-testing.php') || ($url === 'patient-search-report.php' && in_array($uiRoute, ['patient-report.php','patient-report-details.php'], true)));
?><li class="nav-item"><a class="nav-link <?= $uiActive ? 'active' : '' ?> <?= $url === 'login.php' ? 'nav-admin' : '' ?>" <?= $uiActive ? 'aria-current="page"' : '' ?> href="<?= e($url) ?>"><?= $url === 'login.php' ? ui_icon('shield') : '' ?><?= e($label) ?></a></li><?php endforeach; ?>
</ul><?php if ($admin): ?><form action="logout.php" method="post" class="nav-signout"><?= csrf() ?><button class="btn btn-light btn-sm"><?= ui_icon('logout') ?>Sign out</button></form><?php endif; ?></div></div></nav>
