<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-8 w-full max-w-md mx-4 shadow-2xl">
        <div class="text-center">
            <!-- Warning Icon -->
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-6">
                <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Delete Item</h3>
            <p class="text-gray-600 mb-0">Are you sure you want to delete?</p>
            <p class="mt-3 mb-2"><span id="deleteBookmarkTitle" class="font-medium text-gray-900"></span></p>
            <p class="text-sm text-gray-500 mb-8">This action cannot be undone.</p>
            
            <div class="flex gap-4">
                <button id="deleteConfirm" class="flex-1 bg-red-500 text-white py-3 px-6 rounded-lg hover:bg-red-600 transition font-medium">
                    Delete Item
                </button>
                <button id="deleteCancel" class="flex-1 bg-gray-200 text-gray-700 py-3 px-6 rounded-lg hover:bg-gray-300 transition font-medium">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>
