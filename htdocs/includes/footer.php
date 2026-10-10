<?php /** TaskFlow — shared page footer (scripts + closing tags) */ ?>
        </main>
    </div>
</div>

<!-- Generic confirm modal -->
<div class="modal-overlay" id="confirmOverlay">
  <div class="modal modal--sm">
    <div class="modal__header"><h3 id="confirmTitle">Confirm</h3><button class="modal__close" data-close-modal>&times;</button></div>
    <div class="modal__body"><p id="confirmMsg" style="color:var(--text-soft);line-height:1.6">Are you sure?</p></div>
    <div class="modal__footer">
      <button class="btn btn--ghost" id="confirmCancel">Cancel</button>
      <button class="btn btn--danger" id="confirmOk">Delete</button>
    </div>
  </div>
</div>
<div class="toast-wrap" id="toastWrap"></div>

<script src="assets/js/chart.min.js"></script>
<script>window.Chart || document.write('<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"><\/script>');</script>
<script>window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>; window.APP_ROLE = <?= json_encode($_SESSION['user_role'] ?? '') ?>;</script>
<script src="assets/js/main.js"></script>
</body>
</html>
