<aside class="sidebar" aria-label="Admin navigation"><div class="sidebar-heading"><span class="sidebar-label">OPERATIONS</span><span class="sidebar-indicator" aria-hidden="true"></span></div>
<?php $links = [
    'dashboard.php'=>['Overview','grid'],
    'all-test.php'=>['All requests','request'],
    'new-test.php'=>['New requests','clock'],
    'assigned-test.php'=>['Assigned','team'],
    'ontheway-samplecollection-test.php'=>['On the way','truck'],
    'sample-collected-test.php'=>['Samples collected','flask'],
    'samplesent-lab-test.php'=>['Laboratory','activity'],
    'reportdelivered-test.php'=>['Delivered reports','report'],
    'manage-phlebotomist.php'=>['Phlebotomists','team'],
    'search-report.php'=>['Search records','search'],
    'bwdates-report-ds.php'=>['Date reports','calendar'],
];
foreach ($links as $url=>[$label,$icon]):
    $uiActive = $uiRoute === $url
        || ($url === 'all-test.php' && $uiRoute === 'test-details.php')
        || ($url === 'sample-collected-test.php' && $uiRoute === 'sample-collected-tests.php')
        || ($url === 'manage-phlebotomist.php' && in_array($uiRoute,['add-phlebotomist.php','edit-phlebotomist.php'],true))
        || ($url === 'search-report.php' && $uiRoute === 'search-report-result.php')
        || ($url === 'bwdates-report-ds.php' && $uiRoute === 'bwdates-report-result.php');
?><a class="<?= $uiActive ? 'active' : '' ?>" <?= $uiActive ? 'aria-current="page"' : '' ?> href="<?= e($url) ?>"><?= ui_icon($icon) ?><span><?= e($label) ?></span></a><?php endforeach; ?>
<div class="sidebar-note"><span class="sidebar-note-icon"><?= ui_icon('shield') ?></span><div><strong>Connected testing</strong><span>Every stage, one workspace.</span></div></div></aside>
