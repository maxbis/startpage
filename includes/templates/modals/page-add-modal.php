<!-- Page Add Modal -->
<div id="pageAddModal" class="wp-dialog-backdrop modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="pageAddModalTitle" aria-hidden="true" data-dialog-dismiss="pageAddCancel" data-dialog-backdrop-dismiss="false">
    <div class="wp-dialog wp-dialog--compact modal-panel">
        <div class="wp-dialog__header dialog-header">
            <h3 id="pageAddModalTitle" class="wp-dialog__title dialog-title">Add Page</h3>
            <button type="button" class="wp-icon-button wp-dialog__close dialog-close-button" data-dialog-dismiss="pageAddCancel" aria-label="Close add page dialog">&times;</button>
        </div>
        <form id="pageAddForm" class="wp-dialog__body wp-stack dialog-form">
            <div class="wp-field">
                <label for="page-add-name" class="wp-label">Page Name</label>
                <input type="text" id="page-add-name" class="wp-input" placeholder="Enter page name..." required>
            </div>
            <div class="wp-dialog__actions dialog-actions">
                <span class="dialog-action-spacer"></span>
                <button type="button" id="pageAddCancel" class="wp-button wp-button--secondary dialog-button dialog-button-secondary">Cancel</button>
                <button type="submit" class="wp-button wp-button--primary dialog-button dialog-button-primary">Add Page</button>
            </div>
        </form>
    </div>
</div>
