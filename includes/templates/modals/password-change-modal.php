<!-- Password Change Modal -->
<div id="passwordChangeModal" class="modal-backdrop hidden fixed inset-0 flex items-center justify-center z-50" role="dialog" aria-modal="true" aria-labelledby="passwordChangeModalTitle" data-dialog-dismiss="passwordChangeCancel">
    <div class="modal-panel p-6 w-full max-w-md mx-4">
        <div class="dialog-header">
            <h3 id="passwordChangeModalTitle" class="dialog-title">🔐 Change Password</h3>
            <button type="button" class="dialog-close-button" data-dialog-dismiss="passwordChangeCancel" aria-label="Close change password dialog">&times;</button>
        </div>
        <form id="passwordChangeForm" class="dialog-form space-y-4">
            <div>
                <label for="current-password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                <input type="password" id="current-password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
            </div>
            <div>
                <label for="new-password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                <input type="password" id="new-password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
            </div>
            <div>
                <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                <input type="password" id="confirm-password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
            </div>
            <div class="dialog-actions">
                <span class="dialog-action-spacer"></span>
                <button type="button" id="passwordChangeCancel" class="dialog-button dialog-button-secondary">Cancel</button>
                <button type="submit" class="dialog-button dialog-button-primary">Change Password</button>
            </div>
        </form>
    </div>
</div>
