</main>
<script src="<?= e($config['app']['url']) ?>/assets/js/jquery-3.7.1.min.js"></script>
<script src="<?= e($config['app']['url']) ?>/assets/js/bootstrap.bundle.min.js"></script>
<script src="<?= e($config['app']['url']) ?>/assets/select2/dist/js/select2.min.js"></script>
<script src="<?= e($config['app']['url']) ?>/assets/toastr/js/toastr.min.js"></script>
<script src="<?= e($config['app']['url']) ?>/assets/sweetalert2/sweetalert2.all.min.js"></script>
<script src="<?= e($config['app']['url']) ?>/assets/DataTables/jquery.dataTables.min.js"></script>
<script src="<?= e($config['app']['url']) ?>/assets/DataTables/dataTables.bootstrap5.min.js"></script>
<script src="<?= e($config['app']['url']) ?>/assets/js/chart.umd.min.js"></script>
<script>
  window.CWA = {
    base: '<?= e($config['app']['url']) ?>',
    csrf: '<?= csrf_token() ?>'
  };
</script>
<script src="<?= e($config['app']['url']) ?>/assets/js/app.js"></script>
<script>
  $(function() {
    var $b = $('#pendingApprovalsBadge');
    if (!$b.length) return;
    $.getJSON(CWA.base + '/api/pending-approvals.php').done(function(r) {
      if (r && r.count > 0) {
        $b.text(r.count).show();
      }
    });
  });
</script>
</body>

</html>