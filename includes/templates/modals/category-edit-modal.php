<!-- Category Edit Modal -->
<div id="categoryEditModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold mb-4">Edit Category</h3>
        <form id="categoryEditForm" class="space-y-4">
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
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition">Save</button>
                <button type="button" id="categoryEditDelete" class="flex-1 bg-red-500 text-white py-2 px-4 rounded-lg hover:bg-red-600 transition">Delete</button>
                <button type="button" id="categoryEditCancel" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition">Cancel</button>
            </div>
        </form>
    </div>
</div>
