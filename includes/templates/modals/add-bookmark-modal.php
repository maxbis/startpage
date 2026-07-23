<!-- Quick Add Modal (via bookmarklet) -->
<div id="quickAddModal" class="modal-backdrop <?= $isAddingBookmark ? 'flex' : 'hidden' ?> fixed inset-0 items-center justify-center z-50" role="dialog" aria-modal="true" aria-labelledby="quickAddModalTitle" data-dialog-dismiss="quickAddCancel">
    <div class="modal-panel p-6 w-full max-w-md mx-4">
        <div class="dialog-header">
            <h3 id="quickAddModalTitle" class="dialog-title">📌 Add Bookmark</h3>
            <button type="button" class="dialog-close-button" data-dialog-dismiss="quickAddCancel" aria-label="Close add bookmark dialog">&times;</button>
        </div>
        <form id="quickAddForm" class="dialog-form space-y-4">
            <div>
                <label for="quick-title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                <input type="text" id="quick-title" value="<?= htmlspecialchars($prefillTitle) ?>" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
            </div>
            <div>
                <label for="quick-url" class="block text-sm font-medium text-gray-700 mb-1">URL</label>
                <input type="url" id="quick-url" value="<?= htmlspecialchars($prefillUrl) ?>" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
            </div>
            <div>
                <label for="quick-description" class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                <textarea id="quick-description" rows="3" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"><?= htmlspecialchars($prefillDesc) ?></textarea>
            </div>
            <div>
                <label for="quick-category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select id="quick-category" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                    <?php foreach ($categoriesByPage as $pageId => $pageData): ?>
                        <optgroup label="📄 <?= htmlspecialchars($pageData['page_name']) ?>">
                            <?php foreach ($pageData['categories'] as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="dialog-actions">
                <span class="dialog-action-spacer"></span>
                <button type="button" id="quickAddCancel" class="dialog-button dialog-button-secondary">Cancel</button>
                <button type="submit" class="dialog-button dialog-button-primary">Add Bookmark</button>
            </div>
        </form>
    </div>
</div>
