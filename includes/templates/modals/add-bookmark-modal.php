<!-- Quick Add Modal (via bookmarklet) -->
<div id="quickAddModal" class="wp-dialog-backdrop modal-backdrop<?= $isAddingBookmark ? ' is-open' : '' ?>" role="dialog" aria-modal="true" aria-labelledby="quickAddModalTitle" aria-hidden="<?= $isAddingBookmark ? 'false' : 'true' ?>" data-dialog-dismiss="quickAddCancel" data-dialog-backdrop-dismiss="false">
    <div id="quickAddPanel" class="wp-dialog wp-dialog--compact modal-panel">
        <div class="wp-dialog__header dialog-header">
            <h3 id="quickAddModalTitle" class="wp-dialog__title dialog-title">📌 Add Bookmark</h3>
            <button type="button" class="wp-icon-button wp-dialog__close dialog-close-button" data-dialog-dismiss="quickAddCancel" aria-label="Close add bookmark dialog">&times;</button>
        </div>
        <form id="quickAddForm" class="wp-dialog__body wp-stack dialog-form">
            <div class="wp-field">
                <label for="quick-title" class="wp-label">Title</label>
                <input type="text" id="quick-title" value="<?= htmlspecialchars($prefillTitle) ?>" class="wp-input" required>
            </div>
            <div id="quick-add-compact-summary" class="quick-add-compact-summary" aria-live="polite" hidden>
                <span class="quick-add-summary-label">Destination</span>
                <div class="quick-add-summary-destination">
                    <span aria-hidden="true">📁</span>
                    <strong id="quick-add-summary-category"></strong>
                    <span id="quick-add-summary-page"></span>
                </div>
                <p id="quick-add-summary-url" class="quick-add-summary-url"></p>
            </div>
            <div class="wp-field quick-add-full-field">
                <label for="quick-url" class="wp-label">URL</label>
                <input type="url" id="quick-url" value="<?= htmlspecialchars($prefillUrl) ?>" class="wp-input" required>
            </div>
            <div class="wp-field quick-add-full-field">
                <label for="quick-description" class="wp-label">Description (optional)</label>
                <textarea id="quick-description" rows="3" class="wp-textarea"><?= htmlspecialchars($prefillDesc) ?></textarea>
            </div>
            <div class="wp-field quick-add-full-field">
                <label for="quick-category" class="wp-label">Category</label>
                <?php
                $currentPageCategories = $categoriesByPage[$currentPageId]['categories'] ?? [];
                $otherPageCategories = array_filter(
                    $categoriesByPage,
                    static fn($pageId) => (string)$pageId !== (string)$currentPageId,
                    ARRAY_FILTER_USE_KEY
                );
                $defaultQuickCategoryId = $currentPageCategories[0]['id'] ?? '';
                ?>
                <select
                    id="quick-category"
                    data-default-category-id="<?= htmlspecialchars((string)$defaultQuickCategoryId) ?>"
                    class="wp-select"
                    required
                >
                    <?php foreach ($currentPageCategories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" data-page-name="<?= htmlspecialchars($currentPageName) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                    <?php if (!empty($otherPageCategories)): ?>
                        <option value="__other_pages__"<?= empty($currentPageCategories) ? ' selected' : '' ?>>Other pages…</option>
                    <?php endif; ?>
                </select>
            </div>
            <?php if (!empty($otherPageCategories)): ?>
                <div id="quick-other-category-field" class="wp-field quick-add-full-field"<?= empty($currentPageCategories) ? '' : ' hidden' ?>>
                    <label for="quick-other-category" class="wp-label">Category on another page</label>
                    <select
                        id="quick-other-category"
                        class="wp-select"
                        <?= empty($currentPageCategories) ? 'required' : '' ?>
                    >
                        <option value="">Choose a category…</option>
                        <?php foreach ($otherPageCategories as $pageData): ?>
                            <optgroup label="📄 <?= htmlspecialchars($pageData['page_name']) ?>">
                                <?php foreach ($pageData['categories'] as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" data-page-name="<?= htmlspecialchars($pageData['page_name']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="wp-dialog__actions dialog-actions">
                <span class="dialog-action-spacer"></span>
                <button type="button" id="quickAddCancel" class="wp-button wp-button--secondary dialog-button dialog-button-secondary">Cancel</button>
                <button type="button" id="quickAddMoreOptions" class="wp-button wp-button--secondary dialog-button dialog-button-secondary" aria-expanded="false" aria-controls="quick-url quick-description quick-category" hidden>More options…</button>
                <button type="submit" id="quickAddSubmit" class="wp-button wp-button--primary dialog-button dialog-button-primary">Add Bookmark</button>
            </div>
        </form>
    </div>
</div>
