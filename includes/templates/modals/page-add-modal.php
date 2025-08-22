<!-- Page Add Modal -->
<div id="pageAddModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold mb-4">Add Page</h3>
        <form id="pageAddForm" class="space-y-4">
            <div>
                <label for="page-add-name" class="block text-sm font-medium text-gray-700 mb-1">Page Name</label>
                <input type="text" id="page-add-name" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Enter page name..." required>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition">Add Page</button>
                <button type="button" id="pageAddCancel" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition">Cancel</button>
            </div>
        </form>
    </div>
</div>
