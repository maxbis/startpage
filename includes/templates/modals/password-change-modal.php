<!-- Password Change Modal -->
<div id="passwordChangeModal" class="wp-dialog-backdrop modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="passwordChangeModalTitle" aria-hidden="true" data-dialog-dismiss="passwordChangeCancel" data-dialog-backdrop-dismiss="false">
    <div class="wp-dialog wp-dialog--compact modal-panel">
        <div class="wp-dialog__header dialog-header">
            <h3 id="passwordChangeModalTitle" class="wp-dialog__title dialog-title">🔐 Change Password</h3>
            <button type="button" class="wp-icon-button wp-dialog__close dialog-close-button" data-dialog-dismiss="passwordChangeCancel" aria-label="Close change password dialog">&times;</button>
        </div>
        <form id="passwordChangeForm" class="wp-dialog__body wp-stack dialog-form">
            <div class="wp-field">
                <label for="current-password" class="wp-label">Current Password</label>
                <input type="password" id="current-password" class="wp-input" required>
            </div>
            <div class="wp-field">
                <label for="new-password" class="wp-label">New Password</label>
                <input type="password" id="new-password" class="wp-input" required>
            </div>
            <div class="wp-field">
                <label for="confirm-password" class="wp-label">Confirm New Password</label>
                <input type="password" id="confirm-password" class="wp-input" required>
            </div>
            <div class="wp-dialog__actions dialog-actions">
                <span class="dialog-action-spacer"></span>
                <button type="button" id="passwordChangeCancel" class="wp-button wp-button--secondary dialog-button dialog-button-secondary">Cancel</button>
                <button type="submit" class="wp-button wp-button--primary dialog-button dialog-button-primary">Change Password</button>
            </div>
        </form>
    </div>
</div>
