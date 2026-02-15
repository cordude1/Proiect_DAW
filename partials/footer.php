<?php
// partials/footer.php
?>
<footer class="site-footer" style="margin-top:24px;">
  <div class="container" style="max-width:1200px;margin:0 auto;padding:12px 16px;color:#666;">
    © <?= date('Y') ?> Agenție de Turism
  </div>
</footer>

<script>
// auto-hide flash-uri dacă există (compat cu partials/flash.php)
setTimeout(function(){
  var ok  = document.getElementById('flash-ok');
  var err = document.getElementById('flash-err');
  if (ok)  ok.style.display  = 'none';
  if (err) err.style.display = 'none';
}, 2500);
</script>
