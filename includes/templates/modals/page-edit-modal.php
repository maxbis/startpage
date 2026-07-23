<!-- Page Edit Modal -->
<div id="pageEditModal" class="modal-backdrop hidden fixed inset-0 flex items-center justify-center z-50" role="dialog" aria-modal="true" aria-labelledby="pageEditModalTitle" data-dialog-dismiss="pageEditCancel">
    <div class="modal-panel p-6 w-full max-w-md mx-4">
        <div class="dialog-header">
            <h3 id="pageEditModalTitle" class="dialog-title">Edit Page</h3>
            <button type="button" class="dialog-close-button" data-dialog-dismiss="pageEditCancel" aria-label="Close edit page dialog">&times;</button>
        </div>
        <form id="pageEditForm" class="dialog-form space-y-4">
            <input type="hidden" id="page-edit-id">
            <div>
                <label for="page-edit-name" class="block text-sm font-medium text-gray-700 mb-1">Page Name</label>
                <input type="text" id="page-edit-name" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
            </div>
            <div class="dialog-actions">
                <button type="button" id="pageEditDelete" class="dialog-button dialog-button-danger-subtle">Delete</button>
                <span class="dialog-action-spacer"></span>
                <button type="button" id="pageEditCancel" class="dialog-button dialog-button-secondary">Cancel</button>
                <button type="submit" class="dialog-button dialog-button-primary">Save</button>
            </div>
        </form>
    </div>
</div>
