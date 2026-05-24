    </main><!-- end .page-content -->
</div><!-- end .main-wrapper -->

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="modal-delete">
    <div class="modal-box" style="max-width:400px;">
        <div class="modal-header">
            <h5 style="display:flex;align-items:center;gap:6px;"><i data-lucide="alert-triangle" style="width:18px;height:18px;color:#e53e3e;"></i> Konfirmasi Hapus</h5>
            <button class="modal-close" onclick="Modal.close('modal-delete')">×</button>
        </div>
        <div class="modal-body">
            <p style="margin:0;font-size:15px;color:#4a5568;">
                Apakah Anda yakin ingin menghapus <strong id="delete-item-name">item ini</strong>?
                Tindakan ini tidak dapat dibatalkan.
            </p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modal-delete')">Batal</button>
            <button class="btn btn-danger" id="btn-confirm-delete">Ya, Hapus</button>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toast-container"></div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
    lucide.createIcons();
</script>
<?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
