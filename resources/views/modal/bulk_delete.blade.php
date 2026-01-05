<div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-labelledby="bulkDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden;">

            <div class="modal-body p-4 text-center">
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"
                    aria-label="Close"></button>

                <div class="d-inline-flex align-items-center justify-content-center mb-4"
                    style="width: 80px; height: 80px; background-color: #fee2e2; border-radius: 50%;">
                    <i class="fas fa-trash-alt" style="font-size: 32px; color: #dc2626;"></i>
                </div>

                <h4 class="mb-2 fw-bold text-dark" id="bulkDeleteModalLabel">Hapus Banyak Data?</h4>
                <p class="text-muted mb-4 px-3" style="font-size: 15px; line-height: 1.6;">
                    Anda akan menghapus <b><span id="bulk-delete-count">0</span></b> pelanggan secara permanen. <br>
                    <span class="text-danger fw-medium">Tindakan ini tidak dapat dibatalkan.</span>
                </p>

                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-light px-4 py-2 fw-semibold" data-bs-dismiss="modal"
                        style="border-radius: 12px; min-width: 120px; color: #4b5563; background: #f3f4f6;">
                        Batalkan
                    </button>

                    <button type="button" id="confirm-bulk-delete" class="btn btn-danger px-4 py-2 fw-semibold"
                        style="border-radius: 12px; min-width: 120px; background-color: #dc2626; border: none; box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.3);">
                        Ya, Hapus
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
