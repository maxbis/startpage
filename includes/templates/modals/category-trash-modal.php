<!-- Category Trash -->
<div id="categoryTrashModal" class="modal-backdrop hidden fixed inset-0 items-center justify-center z-50" role="dialog" aria-modal="true" aria-labelledby="categoryTrashTitle" data-dialog-dismiss="categoryTrashClose">
    <div class="modal-panel trash-panel w-full mx-4">
        <div class="dialog-header">
            <div>
                <h2 id="categoryTrashTitle" class="dialog-title">Trash</h2>
                <p class="trash-subtitle">Restore categories or permanently delete them and their links.</p>
            </div>
            <button id="categoryTrashClose" type="button" class="dialog-close-button" aria-label="Close Trash">&times;</button>
        </div>
        <div id="categoryTrashContent" class="dialog-body trash-content" aria-live="polite">
            <p class="trash-state">Loading Trash…</p>
        </div>
    </div>
</div>

<!-- Permanent category deletion confirmation -->
<div id="permanentCategoryDeleteModal" class="modal-backdrop hidden fixed inset-0 items-center justify-center z-50" role="alertdialog" aria-modal="true" aria-labelledby="permanentCategoryDeleteTitle">
    <div class="modal-panel w-full max-w-md mx-4">
        <div class="dialog-header">
            <h2 id="permanentCategoryDeleteTitle" class="dialog-title">Delete category permanently?</h2>
            <button id="permanentCategoryDeleteClose" type="button" class="dialog-close-button" aria-label="Cancel permanent deletion">&times;</button>
        </div>
        <form id="permanentCategoryDeleteForm" class="dialog-form">
            <p id="permanentCategoryDeleteSummary" class="trash-delete-summary"></p>
            <div>
                <label for="permanentCategoryDeleteName" class="block text-sm font-medium text-gray-700 mb-1">
                    Type the category name to confirm
                </label>
                <input id="permanentCategoryDeleteName" type="text" class="w-full px-3 py-2 border rounded-lg" autocomplete="off" required>
            </div>
            <div class="dialog-actions">
                <span class="dialog-action-spacer"></span>
                <button id="permanentCategoryDeleteCancel" type="button" class="dialog-button dialog-button-secondary">Cancel</button>
                <button id="permanentCategoryDeleteConfirm" type="submit" class="dialog-button dialog-button-danger" disabled>Delete permanently</button>
            </div>
        </form>
    </div>
</div>
