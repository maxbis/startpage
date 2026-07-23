<!-- Page Add Modal -->
<div id="pageAddModal" class="modal-backdrop hidden fixed inset-0 flex items-center justify-center z-50" role="dialog" aria-modal="true" aria-labelledby="pageAddModalTitle" data-dialog-dismiss="pageAddCancel">
    <div class="modal-panel p-6 w-full max-w-md mx-4">
        <div class="dialog-header">
            <h3 id="pageAddModalTitle" class="dialog-title">Add Page</h3>
            <button type="button" class="dialog-close-button" data-dialog-dismiss="pageAddCancel" aria-label="Close add page dialog">&times;</button>
        </div>
        <form id="pageAddForm" class="dialog-form space-y-4">
            <div>
                <label for="page-add-name" class="block text-sm font-medium text-gray-700 mb-1">Page Name</label>
                <input type="text" id="page-add-name" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Enter page name..." required>
            </div>
            <div class="dialog-actions">
                <span class="dialog-action-spacer"></span>
                <button type="button" id="pageAddCancel" class="dialog-button dialog-button-secondary">Cancel</button>
                <button type="submit" class="dialog-button dialog-button-primary">Add Page</button>
            </div>
        </form>
    </div>
</div>
