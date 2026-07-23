<!-- Category Add Modal -->
<div id="categoryAddModal" class="modal-backdrop hidden fixed inset-0 flex items-center justify-center z-50" role="dialog" aria-modal="true" aria-labelledby="categoryAddModalTitle" data-dialog-dismiss="categoryAddCancel">
    <div class="modal-panel p-6 w-full max-w-md mx-4">
        <div class="dialog-header">
            <h3 id="categoryAddModalTitle" class="dialog-title">Add Category</h3>
            <button type="button" class="dialog-close-button" data-dialog-dismiss="categoryAddCancel" aria-label="Close add category dialog">&times;</button>
        </div>
        <form id="categoryAddForm" class="dialog-form space-y-4">
            <div>
                <label for="category-add-name" class="block text-sm font-medium text-gray-700 mb-1">Category Name</label>
                <input type="text" id="category-add-name" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Enter category name..." required>
            </div>
            <div class="dialog-actions">
                <span class="dialog-action-spacer"></span>
                <button type="button" id="categoryAddCancel" class="dialog-button dialog-button-secondary">Cancel</button>
                <button type="submit" class="dialog-button dialog-button-primary">Add Category</button>
            </div>
        </form>
    </div>
</div>
