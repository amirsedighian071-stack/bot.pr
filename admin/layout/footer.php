</div>
</div>
</div>

<!-- Custom Modal System - Dark Neon (replaces browser alert/confirm) -->
<div class="custom-modal" id="customModal" aria-hidden="true">
  <div class="custom-modal-backdrop" onclick="closeModal()"></div>
  <div class="custom-modal-dialog">
    <div class="custom-modal-header" id="customModalHeader">
      <h5 id="customModalTitle"><i class="bi bi-info-circle"></i> پیام سیستم</h5>
      <button class="custom-modal-close" onclick="closeModal()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="custom-modal-body" id="customModalBody"></div>
    <div class="custom-modal-footer" id="customModalFooter">
      <button class="btn btn-outline-light" onclick="closeModal()">بستن</button>
      <button class="btn btn-primary" id="customModalConfirmBtn" style="display:none">تأیید</button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= \Core\Helper::appUrl('public/assets/js/app.js') ?>"></script>
</body>
</html>
