<!-- Edit Bookmark Modal -->
<div id="editModal" class="wp-dialog-backdrop modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="editModalTitle" aria-hidden="true" data-dialog-dismiss="editCancel" data-dialog-backdrop-dismiss="false">
    <div class="wp-dialog wp-dialog--compact modal-panel">
        <div class="wp-dialog__header dialog-header">
            <h3 id="editModalTitle" class="wp-dialog__title dialog-title">Edit Bookmark</h3>
            <button type="button" class="wp-icon-button wp-dialog__close dialog-close-button" data-dialog-dismiss="editCancel" aria-label="Close edit bookmark dialog">&times;</button>
        </div>
        <form id="editForm" class="wp-dialog__body wp-stack dialog-form">
            <input type="hidden" id="edit-id">
            <input type="hidden" id="edit-favicon-storage">
            <div class="wp-field">
                <label for="edit-title" class="wp-label">Title</label>
                <input type="text" id="edit-title" class="wp-input" required>
            </div>
            <div class="wp-field">
                <label for="edit-url" class="wp-label">URL</label>
                <input type="url" id="edit-url" class="wp-input" required>
            </div>
            <div class="wp-field">
                <label for="edit-description" class="wp-label">Description (optional)</label>
                <textarea id="edit-description" rows="3" class="wp-textarea"></textarea>
            </div>
            <div class="wp-field">
                <label class="wp-label">Favicon</label>
                <div class="dialog-favicon-panel">
                    <img id="edit-favicon" src="<?= FaviconConfig::getDefaultFaviconDataUri() ?>" alt="?" class="dialog-favicon-image">
                    <div class="dialog-favicon-details">
                        <p class="dialog-favicon-url" id="edit-favicon-url">No favicon available</p>
                    </div>
                    <button type="button" id="edit-refresh-favicon" class="wp-button wp-button--primary wp-button--compact">
                        <svg class="dialog-button-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>
            <div class="wp-field">
                <label for="edit-category" class="wp-label">Category</label>
                <select id="edit-category" class="wp-select" required>
                    <?php foreach ($categoriesByPage as $pageId => $pageData): ?>
                        <optgroup label="Page: <?= htmlspecialchars($pageData['page_name']) ?>">
                            <?php foreach ($pageData['categories'] as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="wp-field">
                <label for="edit-background-color" class="wp-label">Background Color</label>
                <div class="dialog-color-control">
                    <select id="edit-background-color" class="wp-select">
                        <?php $colorMap = getBookmarkColorMapping(); $labels = getBookmarkColorLabels(); ?>
                        <?php foreach ($colorMap as $int => $token): ?>
                            <?php $label = $labels[$token] ?? ucfirst($token); ?>
                            <option value="<?= htmlspecialchars($token) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div id="edit-color-preview" class="dialog-color-preview">
                        <span id="edit-color-label">None</span>
                    </div>
                </div>
            </div>
            <div class="wp-dialog__actions dialog-actions">
                <button type="button" id="editDelete" class="wp-button wp-button--danger-subtle dialog-button dialog-button-danger-subtle">Delete</button>
                <span class="dialog-action-spacer"></span>
                <button type="button" id="editCancel" class="wp-button wp-button--secondary dialog-button dialog-button-secondary">Cancel</button>
                <button type="submit" class="wp-button wp-button--primary dialog-button dialog-button-primary">Save</button>
            </div>
        </form>
    </div>
</div>
