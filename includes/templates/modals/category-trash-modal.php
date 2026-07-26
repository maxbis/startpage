<!-- Category Trash -->
<div id="categoryTrashModal" class="wp-dialog-backdrop modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="categoryTrashTitle" aria-hidden="true" data-dialog-dismiss="categoryTrashClose" data-dialog-backdrop-dismiss="true">
    <div class="wp-dialog wp-dialog--wide modal-panel trash-panel">
        <div class="wp-dialog__header dialog-header">
            <div>
                <h2 id="categoryTrashTitle" class="wp-dialog__title dialog-title">Trash</h2>
                <p class="trash-subtitle">Restore categories or permanently delete them and their links.</p>
            </div>
            <button id="categoryTrashClose" type="button" class="wp-icon-button wp-dialog__close dialog-close-button" aria-label="Close Trash">&times;</button>
        </div>
        <div id="categoryTrashContent" class="wp-dialog__body dialog-body trash-content" aria-live="polite">
            <p class="trash-state">Loading Trash…</p>
        </div>
    </div>
</div>

<!-- Permanent category deletion confirmation -->
<div id="permanentCategoryDeleteModal" class="wp-dialog-backdrop modal-backdrop" role="alertdialog" aria-modal="true" aria-labelledby="permanentCategoryDeleteTitle" aria-hidden="true">
    <div class="wp-dialog wp-dialog--compact modal-panel">
        <div class="wp-dialog__header dialog-header">
            <h2 id="permanentCategoryDeleteTitle" class="wp-dialog__title dialog-title">Delete category permanently?</h2>
            <button id="permanentCategoryDeleteClose" type="button" class="wp-icon-button wp-dialog__close dialog-close-button" aria-label="Cancel permanent deletion">&times;</button>
        </div>
        <form id="permanentCategoryDeleteForm" class="wp-dialog__body wp-stack dialog-form">
            <p id="permanentCategoryDeleteSummary" class="trash-delete-summary"></p>
            <div class="wp-field">
                <label for="permanentCategoryDeleteName" class="wp-label">
                    Type the category name to confirm
                </label>
                <input id="permanentCategoryDeleteName" type="text" class="wp-input" autocomplete="off" required>
            </div>
            <div class="wp-dialog__actions dialog-actions">
                <span class="dialog-action-spacer"></span>
                <button id="permanentCategoryDeleteCancel" type="button" class="wp-button wp-button--secondary dialog-button dialog-button-secondary">Cancel</button>
                <button id="permanentCategoryDeleteConfirm" type="submit" class="wp-button wp-button--danger dialog-button dialog-button-danger" disabled>Delete permanently</button>
            </div>
        </form>
    </div>
</div>
