</main></div>
<footer class="app-footer <?= $admin ? 'footer-compact' : '' ?>">
<div class="footer-inner">
<div class="footer-product"><a class="footer-brand" href="index.php"><span class="brand-mark"><?= ui_icon('activity') ?></span><strong>COVID-19 Testing<br>Management System</strong></a><p>Connecting test requests, collection teams and laboratory reports in one clear workflow.</p></div>
<nav class="footer-nav" aria-label="Footer navigation"><a href="index.php">Home</a><a href="new-user-testing.php">Request a test</a><a href="patient-search-report.php">Track a test</a><a href="live-test-updates.php">Live updates</a><a href="login.php">Admin login</a></nav>
<div class="footer-context"><span class="eyebrow">PORTFOLIO PROJECT</span><p>A demonstration of healthcare operations management built with PHP, MySQL and Bootstrap.</p></div>
</div><div class="footer-bottom"><span>COVID19 TMS <span aria-hidden="true">&middot;</span> Testing requests, clearly connected.</span><a href="index.php#about">About the system <?= ui_icon('arrow') ?></a></div>
</footer>
<div class="modal fade" id="ui-confirm-modal" tabindex="-1" role="dialog" aria-labelledby="ui-confirm-title" aria-describedby="ui-confirm-description" aria-modal="true">
<div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content">
<div class="modal-body"><span class="confirmation-icon"><?= ui_icon('team') ?></span><h2 id="ui-confirm-title">Delete team member?</h2><p id="ui-confirm-description"></p><p class="helper">This removes the selected unassigned phlebotomist from your collection team.</p></div>
<div class="modal-footer"><button class="btn btn-light" type="button" data-dismiss="modal" id="ui-confirm-cancel">Keep team member</button><button class="btn btn-danger" type="button" id="ui-confirm-accept">Delete team member</button></div>
</div></div></div>
<script src="vendor/jquery/jquery.min.js"></script><script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script><script src="js/app.js"></script>
</body></html>
