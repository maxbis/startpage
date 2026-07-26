<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="wp-dialog-backdrop modal-backdrop" role="alertdialog" aria-modal="true" aria-labelledby="deleteModalTitle" aria-hidden="true" data-dialog-dismiss="deleteCancel" data-dialog-backdrop-dismiss="true">
    <div class="wp-dialog wp-dialog--compact modal-panel">
        <div class="wp-dialog__header dialog-header">
            <h3 id="deleteModalTitle" class="wp-dialog__title dialog-title">Delete Item</h3>
            <button type="button" class="wp-icon-button wp-dialog__close dialog-close-button" data-dialog-dismiss="deleteCancel" aria-label="Close delete confirmation">&times;</button>
        </div>
        <div class="wp-dialog__body dialog-body confirmation-dialog">
            <!-- Warning Icon -->
            <div class="confirmation-dialog__icon confirmation-dialog__icon--danger">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            
            <p id="deletePrompt" class="confirmation-dialog__prompt">Are you sure you want to delete?</p>
            <p class="confirmation-dialog__subject"><strong id="deleteBookmarkTitle"></strong></p>
            <p id="deleteNote" class="confirmation-dialog__note">This action cannot be undone.</p>
            
            <div class="wp-dialog__actions dialog-actions">
                <span class="dialog-action-spacer"></span>
                <button id="deleteCancel" class="wp-button wp-button--secondary dialog-button dialog-button-secondary">Cancel</button>
                <button id="deleteConfirm" class="wp-button wp-button--danger dialog-button dialog-button-danger">Delete Item</button>
            </div>
        </div>
    </div>
</div>
