<!-- Quick Add Modal (via bookmarklet) -->
<div id="quickAddModal" class="<?= $isAddingBookmark ? 'flex' : 'hidden' ?> fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold mb-4">📌 Add Bookmark</h3>
        <form id="quickAddForm" class="space-y-4">
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
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition">Add Bookmark</button>
                <button type="button" id="quickAddCancel" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition">Cancel</button>
            </div>
        </form>
    </div>
</div>
