<!-- Category Add Modal -->
<div id="categoryAddModal" class="wp-dialog-backdrop modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="categoryAddModalTitle" aria-hidden="true" data-dialog-dismiss="categoryAddCancel" data-dialog-backdrop-dismiss="false">
    <div class="wp-dialog wp-dialog--compact modal-panel">
        <div class="wp-dialog__header dialog-header">
            <h3 id="categoryAddModalTitle" class="wp-dialog__title dialog-title">Add Category</h3>
            <button type="button" class="wp-icon-button wp-dialog__close dialog-close-button" data-dialog-dismiss="categoryAddCancel" aria-label="Close add category dialog">&times;</button>
        </div>
        <form id="categoryAddForm" class="wp-dialog__body wp-stack dialog-form">
            <div class="wp-field">
                <label for="category-add-name" class="wp-label">Category Name</label>
                <input type="text" id="category-add-name" class="wp-input" placeholder="Enter category name..." required>
            </div>
            <div class="wp-dialog__actions dialog-actions">
                <span class="dialog-action-spacer"></span>
                <button type="button" id="categoryAddCancel" class="wp-button wp-button--secondary dialog-button dialog-button-secondary">Cancel</button>
                <button type="submit" class="wp-button wp-button--primary dialog-button dialog-button-primary">Add Category</button>
            </div>
        </form>
    </div>
</div>
