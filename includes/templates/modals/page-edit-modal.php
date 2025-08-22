<!-- Page Edit Modal -->
<div id="pageEditModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold mb-4">Edit Page</h3>
        <form id="pageEditForm" class="space-y-4">
            <input type="hidden" id="page-edit-id">
            <div>
                <label for="page-edit-name" class="block text-sm font-medium text-gray-700 mb-1">Page Name</label>
                <input type="text" id="page-edit-name" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition">Save</button>
                <button type="button" id="pageEditDelete" class="flex-1 bg-red-500 text-white py-2 px-4 rounded-lg hover:bg-red-600 transition">Delete</button>
                <button type="button" id="pageEditCancel" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition">Cancel</button>
            </div>
        </form>
    </div>
</div>
