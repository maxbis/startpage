<!-- Page Edit Modal -->
<div id="pageEditModal" class="wp-dialog-backdrop modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="pageEditModalTitle" aria-hidden="true" data-dialog-dismiss="pageEditCancel" data-dialog-backdrop-dismiss="false">
    <div class="wp-dialog wp-dialog--compact modal-panel">
        <div class="wp-dialog__header dialog-header">
            <h3 id="pageEditModalTitle" class="wp-dialog__title dialog-title">Edit Page</h3>
            <button type="button" class="wp-icon-button wp-dialog__close dialog-close-button" data-dialog-dismiss="pageEditCancel" aria-label="Close edit page dialog">&times;</button>
        </div>
        <form id="pageEditForm" class="wp-dialog__body wp-stack dialog-form">
            <input type="hidden" id="page-edit-id">
            <div class="wp-field">
                <label for="page-edit-name" class="wp-label">Page Name</label>
                <input type="text" id="page-edit-name" class="wp-input" required>
            </div>
            <div class="wp-dialog__actions dialog-actions">
                <button type="button" id="pageEditDelete" class="wp-button wp-button--danger-subtle dialog-button dialog-button-danger-subtle">Delete</button>
                <span class="dialog-action-spacer"></span>
                <button type="button" id="pageEditCancel" class="wp-button wp-button--secondary dialog-button dialog-button-secondary">Cancel</button>
                <button type="submit" class="wp-button wp-button--primary dialog-button dialog-button-primary">Save</button>
            </div>
        </form>
    </div>
</div>
