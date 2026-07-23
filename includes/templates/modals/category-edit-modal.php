<!-- Category Edit Modal -->
<div id="categoryEditModal" class="modal-backdrop hidden fixed inset-0 flex items-center justify-center z-50" role="dialog" aria-modal="true" aria-labelledby="categoryEditModalTitle" data-dialog-dismiss="categoryEditCancel">
    <div class="modal-panel p-6 w-full max-w-md mx-4">
        <div class="dialog-header">
            <h3 id="categoryEditModalTitle" class="dialog-title">Edit Category</h3>
            <button type="button" class="dialog-close-button" data-dialog-dismiss="categoryEditCancel" aria-label="Close edit category dialog">&times;</button>
        </div>
        <form id="categoryEditForm" class="dialog-form space-y-4">
            <input type="hidden" id="category-edit-id">
            <div>
                <label for="category-edit-name" class="block text-sm font-medium text-gray-700 mb-1">Category Name</label>
                <input type="text" id="category-edit-name" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
            </div>
            <div>
                <label for="category-edit-page" class="block text-sm font-medium text-gray-700 mb-1">Page</label>
                <select id="category-edit-page" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                    <?php foreach ($allPages as $page): ?>
                        <option value="<?= $page['id'] ?>"><?= htmlspecialchars($page['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="category-edit-width" class="block text-sm font-medium text-gray-700 mb-1">Category Width</label>
                <select id="category-edit-width" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                    <option value="1">Very Small</option>
                    <option value="2">Small</option>
                    <option value="3">Normal</option>
                    <option value="4">Large</option>
                </select>
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" id="category-edit-show-description" class="mr-2">
                    <span class="text-sm text-gray-700">Show descriptions</span>
                </label>
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" id="category-edit-show-favicon" class="mr-2">
                    <span class="text-sm text-gray-700">Show favicons</span>
                </label>
            </div>
            <div class="dialog-actions">
                <button type="button" id="categoryEditDelete" class="dialog-button dialog-button-danger-subtle">Move to Trash</button>
                <span class="dialog-action-spacer"></span>
                <button type="button" id="categoryEditCancel" class="dialog-button dialog-button-secondary">Cancel</button>
                <button type="submit" class="dialog-button dialog-button-primary">Save</button>
            </div>
        </form>
    </div>
</div>
