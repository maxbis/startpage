<!-- Quick Add Modal (via bookmarklet) -->
<div id="quickAddModal" class="modal-backdrop <?= $isAddingBookmark ? 'flex' : 'hidden' ?> fixed inset-0 items-center justify-center z-50" role="dialog" aria-modal="true" aria-labelledby="quickAddModalTitle" data-dialog-dismiss="quickAddCancel" data-dialog-backdrop-dismiss="false">
    <div id="quickAddPanel" class="modal-panel p-6 w-full max-w-md mx-4">
        <div class="dialog-header">
            <h3 id="quickAddModalTitle" class="dialog-title">📌 Add Bookmark</h3>
            <button type="button" class="dialog-close-button" data-dialog-dismiss="quickAddCancel" aria-label="Close add bookmark dialog">&times;</button>
        </div>
        <form id="quickAddForm" class="dialog-form space-y-4">
            <div>
                <label for="quick-title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                <input type="text" id="quick-title" value="<?= htmlspecialchars($prefillTitle) ?>" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
            </div>
            <div id="quick-add-compact-summary" class="quick-add-compact-summary hidden" aria-live="polite">
                <span class="quick-add-summary-label">Destination</span>
                <div class="quick-add-summary-destination">
                    <span aria-hidden="true">📁</span>
                    <strong id="quick-add-summary-category"></strong>
                    <span id="quick-add-summary-page"></span>
                </div>
                <p id="quick-add-summary-url" class="quick-add-summary-url"></p>
            </div>
            <div class="quick-add-full-field">
                <label for="quick-url" class="block text-sm font-medium text-gray-700 mb-1">URL</label>
                <input type="url" id="quick-url" value="<?= htmlspecialchars($prefillUrl) ?>" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
            </div>
            <div class="quick-add-full-field">
                <label for="quick-description" class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                <textarea id="quick-description" rows="3" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"><?= htmlspecialchars($prefillDesc) ?></textarea>
            </div>
            <div class="quick-add-full-field">
                <label for="quick-category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
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
                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
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
                <div id="quick-other-category-field" class="quick-add-full-field<?= empty($currentPageCategories) ? '' : ' hidden' ?>">
                    <label for="quick-other-category" class="block text-sm font-medium text-gray-700 mb-1">Category on another page</label>
                    <select
                        id="quick-other-category"
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
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
            <div class="dialog-actions">
                <span class="dialog-action-spacer"></span>
                <button type="button" id="quickAddCancel" class="dialog-button dialog-button-secondary">Cancel</button>
                <button type="button" id="quickAddMoreOptions" class="dialog-button dialog-button-secondary hidden" aria-expanded="false" aria-controls="quick-url quick-description quick-category">More options…</button>
                <button type="submit" id="quickAddSubmit" class="dialog-button dialog-button-primary">Add Bookmark</button>
            </div>
        </form>
    </div>
</div>
