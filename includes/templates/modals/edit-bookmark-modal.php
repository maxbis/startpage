<!-- Edit Bookmark Modal -->
<div id="editModal" class="modal-backdrop hidden fixed inset-0 flex items-center justify-center z-50" role="dialog" aria-modal="true" aria-labelledby="editModalTitle" data-dialog-dismiss="editCancel">
    <div class="modal-panel p-6 w-full max-w-md mx-4">
        <div class="dialog-header">
            <h3 id="editModalTitle" class="dialog-title">Edit Bookmark</h3>
            <button type="button" class="dialog-close-button" data-dialog-dismiss="editCancel" aria-label="Close edit bookmark dialog">&times;</button>
        </div>
        <form id="editForm" class="dialog-form space-y-4">
            <input type="hidden" id="edit-id">
            <input type="hidden" id="edit-favicon-storage">
            <div>
                <label for="edit-title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                <input type="text" id="edit-title" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
            </div>
            <div>
                <label for="edit-url" class="block text-sm font-medium text-gray-700 mb-1">URL</label>
                <input type="url" id="edit-url" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
            </div>
            <div>
                <label for="edit-description" class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                <textarea id="edit-description" rows="3" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Favicon</label>
                <div class="dialog-favicon-panel flex items-center gap-3 p-3 border rounded-lg bg-gray-50">
                    <img id="edit-favicon" src="<?= FaviconConfig::getDefaultFaviconDataUri() ?>" alt="?" class="w-6 h-6 rounded flex-shrink-0">
                    <div class="flex-1">
                        <p class="text-sm text-gray-600" id="edit-favicon-url">No favicon available</p>
                    </div>
                    <button type="button" id="edit-refresh-favicon" class="px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600 transition flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>
            <div>
                <label for="edit-category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select id="edit-category" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                    <?php foreach ($categoriesByPage as $pageId => $pageData): ?>
                        <optgroup label="Page: <?= htmlspecialchars($pageData['page_name']) ?>">
                            <?php foreach ($pageData['categories'] as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="edit-background-color" class="block text-sm font-medium text-gray-700 mb-1">Background Color</label>
                <div class="flex items-center gap-3">
                    <select id="edit-background-color" class="flex-1 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <?php $colorMap = getBookmarkColorMapping(); $labels = getBookmarkColorLabels(); ?>
                        <?php foreach ($colorMap as $int => $token): ?>
                            <?php $label = $labels[$token] ?? ucfirst($token); ?>
                            <option value="<?= htmlspecialchars($token) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div id="edit-color-preview" class="w-10 h-10 rounded border border-gray-300 bg-gray-50 flex items-center justify-center">
                        <span id="edit-color-label" class="text-xs text-gray-600">None</span>
                    </div>
                </div>
            </div>
            <div class="dialog-actions">
                <button type="button" id="editDelete" class="dialog-button dialog-button-danger-subtle">Delete</button>
                <span class="dialog-action-spacer"></span>
                <button type="button" id="editCancel" class="dialog-button dialog-button-secondary">Cancel</button>
                <button type="submit" class="dialog-button dialog-button-primary">Save</button>
            </div>
        </form>
    </div>
</div>
