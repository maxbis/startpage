<!-- Category Edit Modal -->
<div id="categoryEditModal" class="wp-dialog-backdrop modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="categoryEditModalTitle" aria-hidden="true" data-dialog-dismiss="categoryEditCancel" data-dialog-backdrop-dismiss="false">
    <div class="wp-dialog wp-dialog--compact modal-panel">
        <div class="wp-dialog__header dialog-header">
            <h3 id="categoryEditModalTitle" class="wp-dialog__title dialog-title">Edit Category</h3>
            <button type="button" class="wp-icon-button wp-dialog__close dialog-close-button" data-dialog-dismiss="categoryEditCancel" aria-label="Close edit category dialog">&times;</button>
        </div>
        <form id="categoryEditForm" class="wp-dialog__body wp-stack dialog-form">
            <input type="hidden" id="category-edit-id">
            <div class="wp-field">
                <label for="category-edit-name" class="wp-label">Category Name</label>
                <input type="text" id="category-edit-name" class="wp-input" required>
            </div>
            <div class="wp-field">
                <label for="category-edit-page" class="wp-label">Page</label>
                <select id="category-edit-page" class="wp-select" required>
                    <?php foreach ($allPages as $page): ?>
                        <option value="<?= $page['id'] ?>"><?= htmlspecialchars($page['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="wp-field">
                <label for="category-edit-width" class="wp-label">Category Width</label>
                <select id="category-edit-width" class="wp-select" required>
                    <option value="1">Very Small</option>
                    <option value="2">Small</option>
                    <option value="3">Normal</option>
                    <option value="4">Large</option>
                </select>
            </div>
            <div class="wp-field">
                <label for="category-edit-collapsed-link-limit" class="wp-label">Links shown when collapsed</label>
                <input type="number" id="category-edit-collapsed-link-limit" min="1" max="20" step="1" class="wp-input" required>
            </div>
            <div>
                <label class="wp-check">
                    <input type="checkbox" id="category-edit-show-description">
                    <span>Show descriptions</span>
                </label>
            </div>
            <div>
                <label class="wp-check">
                    <input type="checkbox" id="category-edit-show-favicon">
                    <span>Show favicons</span>
                </label>
            </div>
            <div class="wp-dialog__actions dialog-actions">
                <button type="button" id="categoryEditDelete" class="wp-button wp-button--danger-subtle dialog-button dialog-button-danger-subtle">Move to Trash</button>
                <span class="dialog-action-spacer"></span>
                <button type="button" id="categoryEditCancel" class="wp-button wp-button--secondary dialog-button dialog-button-secondary">Cancel</button>
                <button type="submit" class="wp-button wp-button--primary dialog-button dialog-button-primary">Save</button>
            </div>
        </form>
    </div>
</div>
