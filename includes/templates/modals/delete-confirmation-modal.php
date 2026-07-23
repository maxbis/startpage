<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal-backdrop hidden fixed inset-0 flex items-center justify-center z-50" role="alertdialog" aria-modal="true" aria-labelledby="deleteModalTitle" data-dialog-dismiss="deleteCancel">
    <div class="modal-panel p-8 w-full max-w-md mx-4">
        <div class="dialog-header">
            <h3 id="deleteModalTitle" class="dialog-title">Delete Item</h3>
            <button type="button" class="dialog-close-button" data-dialog-dismiss="deleteCancel" aria-label="Close delete confirmation">&times;</button>
        </div>
        <div class="dialog-body text-center">
            <!-- Warning Icon -->
            <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-red-100 mb-4">
                <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            
            <p id="deletePrompt" class="text-gray-600 mb-0">Are you sure you want to delete?</p>
            <p class="mt-3 mb-2"><span id="deleteBookmarkTitle" class="font-medium text-gray-900"></span></p>
            <p id="deleteNote" class="text-sm text-gray-500 mb-8">This action cannot be undone.</p>
            
            <div class="dialog-actions">
                <span class="dialog-action-spacer"></span>
                <button id="deleteCancel" class="dialog-button dialog-button-secondary">Cancel</button>
                <button id="deleteConfirm" class="dialog-button dialog-button-danger">Delete Item</button>
            </div>
        </div>
    </div>
</div>
