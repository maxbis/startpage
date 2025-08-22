<!-- Category Add Modal -->
<div id="categoryAddModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold mb-4">Add Category</h3>
        <form id="categoryAddForm" class="space-y-4">
            <div>
                <label for="category-add-name" class="block text-sm font-medium text-gray-700 mb-1">Category Name</label>
                <input type="text" id="category-add-name" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Enter category name..." required>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition">Add Category</button>
                <button type="button" id="categoryAddCancel" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition">Cancel</button>
            </div>
        </form>
    </div>
</div>
