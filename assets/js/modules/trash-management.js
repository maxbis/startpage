/**
 * Category Trash: list, restore, and permanently delete soft-deleted categories.
 */

const categoryTrashModal = document.getElementById('categoryTrashModal');
const categoryTrashClose = document.getElementById('categoryTrashClose');
const categoryTrashContent = document.getElementById('categoryTrashContent');
const permanentCategoryDeleteModal = document.getElementById('permanentCategoryDeleteModal');
const permanentCategoryDeleteForm = document.getElementById('permanentCategoryDeleteForm');
const permanentCategoryDeleteClose = document.getElementById('permanentCategoryDeleteClose');
const permanentCategoryDeleteCancel = document.getElementById('permanentCategoryDeleteCancel');
const permanentCategoryDeleteSummary = document.getElementById('permanentCategoryDeleteSummary');
const permanentCategoryDeleteName = document.getElementById('permanentCategoryDeleteName');
const permanentCategoryDeleteConfirm = document.getElementById('permanentCategoryDeleteConfirm');

let categoryTrashReturnFocus = null;
let permanentDeleteReturnFocus = null;
let pendingPermanentDelete = null;
let permanentDeleteSubmitting = false;

async function readJsonResponse(response) {
  const data = await response.json();
  if (!response.ok || !data.success) {
    throw new Error(data.message || 'The request failed');
  }
  return data;
}

function formatDeletedAt(value) {
  if (!value) return 'Unknown date';
  const normalizedValue = value.includes('T') ? value : value.replace(' ', 'T');
  const date = new Date(normalizedValue);
  return Number.isNaN(date.getTime())
    ? value
    : new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(date);
}

function createTrashAction(label, className, action, category) {
  const button = document.createElement('button');
  button.type = 'button';
  button.className = className;
  button.dataset.trashAction = action;
  button.dataset.categoryId = String(category.id);
  button.dataset.categoryName = category.name;
  button.dataset.bookmarkCount = String(category.bookmark_count || 0);
  button.textContent = label;
  return button;
}

function createTrashRow(category, pages) {
  const row = document.createElement('article');
  row.className = 'trash-row';
  row.dataset.categoryId = String(category.id);

  const details = document.createElement('div');
  details.className = 'trash-row-details';

  const title = document.createElement('h3');
  title.className = 'trash-row-title';
  title.textContent = category.name;
  title.title = category.name;

  const metadata = document.createElement('p');
  metadata.className = 'trash-row-metadata';
  const pageName = category.page_name || 'Deleted page';
  const bookmarkCount = Number(category.bookmark_count || 0);
  metadata.textContent = `${pageName} · ${bookmarkCount} link${bookmarkCount === 1 ? '' : 's'} · ${formatDeletedAt(category.deleted_at)}`;

  details.append(title, metadata);

  if (!category.page_name && pages.length) {
    const destination = document.createElement('label');
    destination.className = 'trash-restore-destination';
    const labelText = document.createElement('span');
    labelText.textContent = 'Restore to';
    const select = document.createElement('select');
    select.dataset.restorePage = 'true';
    select.setAttribute('aria-label', `Page for restoring ${category.name}`);
    pages.forEach(page => {
      const option = document.createElement('option');
      option.value = String(page.id);
      option.textContent = page.name;
      select.append(option);
    });
    destination.append(labelText, select);
    details.append(destination);
  }

  const actions = document.createElement('div');
  actions.className = 'trash-row-actions';
  actions.append(
    createTrashAction('Restore', 'trash-action trash-action-restore', 'restore', category),
    createTrashAction('Delete permanently', 'trash-action trash-action-delete', 'delete-permanently', category)
  );

  row.append(details, actions);
  return row;
}

function renderTrash(categories, pages = []) {
  categoryTrashContent.replaceChildren();

  if (!categories.length) {
    const emptyState = document.createElement('div');
    emptyState.className = 'trash-empty-state';
    const title = document.createElement('h3');
    title.textContent = 'Trash is empty';
    const description = document.createElement('p');
    description.textContent = 'Categories moved to Trash will appear here.';
    emptyState.append(title, description);
    categoryTrashContent.append(emptyState);
    return;
  }

  const list = document.createElement('div');
  list.className = 'trash-list';
  categories.forEach(category => list.append(createTrashRow(category, pages)));
  categoryTrashContent.append(list);
}

async function loadTrash() {
  categoryTrashContent.innerHTML = '<p class="trash-state">Loading Trash…</p>';

  try {
    const response = await fetch('../api/get-trashed-categories.php', {
      headers: { Accept: 'application/json' }
    });
    const data = await readJsonResponse(response);
    renderTrash(data.categories || [], data.pages || []);
  } catch (error) {
    categoryTrashContent.innerHTML = '';
    const message = document.createElement('p');
    message.className = 'trash-state trash-state-error';
    message.textContent = error.message;
    categoryTrashContent.append(message);
  }
}

function openCategoryTrash() {
  if (!categoryTrashModal) return;
  categoryTrashReturnFocus = document.getElementById('accountMenuButton') || document.activeElement;
  categoryTrashModal.dataset.dialogBackdropDismiss = 'true';
  categoryTrashModal.classList.remove('hidden');
  categoryTrashModal.classList.add('flex');
  categoryTrashClose?.focus();
  loadTrash();
}

function closeCategoryTrash() {
  if (!categoryTrashModal || !permanentCategoryDeleteModal?.classList.contains('hidden')) return;
  categoryTrashModal.classList.add('hidden');
  categoryTrashModal.classList.remove('flex');
  categoryTrashReturnFocus?.focus();
  categoryTrashReturnFocus = null;
}

async function restoreCategory(button) {
  button.disabled = true;
  categoryTrashModal.dataset.dialogBackdropDismiss = 'false';
  try {
    const restorePage = button.closest('.trash-row')?.querySelector('[data-restore-page]');
    const payload = { id: button.dataset.categoryId };
    if (restorePage) payload.page_id = restorePage.value;
    const response = await fetch('../api/restore-category.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    await readJsonResponse(response);
    window.invalidateSearchData?.();
    showFlashMessage('Category restored.', 'success');
    setTimeout(() => location.reload(), 600);
  } catch (error) {
    button.disabled = false;
    categoryTrashModal.dataset.dialogBackdropDismiss = 'true';
    showFlashMessage(error.message, 'error');
  }
}

function openPermanentDeleteConfirmation(button) {
  pendingPermanentDelete = {
    id: button.dataset.categoryId,
    name: button.dataset.categoryName,
    bookmarkCount: Number(button.dataset.bookmarkCount || 0)
  };
  permanentDeleteReturnFocus = button;

  const linkLabel = `${pendingPermanentDelete.bookmarkCount} link${pendingPermanentDelete.bookmarkCount === 1 ? '' : 's'}`;
  permanentCategoryDeleteSummary.textContent =
    `This permanently deletes “${pendingPermanentDelete.name}” and its ${linkLabel}. This cannot be undone.`;
  permanentCategoryDeleteName.value = '';
  permanentCategoryDeleteConfirm.disabled = true;
  permanentCategoryDeleteModal.classList.remove('hidden');
  permanentCategoryDeleteModal.classList.add('flex');
  permanentCategoryDeleteName.focus();
}

function closePermanentDeleteConfirmation(options = {}) {
  if (!permanentCategoryDeleteModal || (permanentDeleteSubmitting && !options.force)) return;
  permanentCategoryDeleteModal.classList.add('hidden');
  permanentCategoryDeleteModal.classList.remove('flex');
  permanentCategoryDeleteForm?.reset();
  permanentCategoryDeleteConfirm.disabled = true;
  permanentCategoryDeleteModal.removeAttribute('aria-busy');
  permanentDeleteSubmitting = false;
  pendingPermanentDelete = null;
  permanentDeleteReturnFocus?.focus();
  permanentDeleteReturnFocus = null;
}

categoryTrashContent?.addEventListener('click', (event) => {
  const button = event.target.closest('[data-trash-action]');
  if (!button) return;

  if (button.dataset.trashAction === 'restore') {
    restoreCategory(button);
  } else if (button.dataset.trashAction === 'delete-permanently') {
    openPermanentDeleteConfirmation(button);
  }
});

permanentCategoryDeleteName?.addEventListener('input', () => {
  permanentCategoryDeleteConfirm.disabled =
    !pendingPermanentDelete || permanentCategoryDeleteName.value !== pendingPermanentDelete.name;
});

permanentCategoryDeleteForm?.addEventListener('submit', async (event) => {
  event.preventDefault();
  if (!pendingPermanentDelete || permanentCategoryDeleteName.value !== pendingPermanentDelete.name) return;

  permanentCategoryDeleteConfirm.disabled = true;
  permanentDeleteSubmitting = true;
  permanentCategoryDeleteModal.setAttribute('aria-busy', 'true');
  permanentCategoryDeleteCancel.disabled = true;
  permanentCategoryDeleteClose.disabled = true;
  try {
    const response = await fetch('../api/permanently-delete-category.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id: pendingPermanentDelete.id,
        confirmation_name: permanentCategoryDeleteName.value
      })
    });
    const data = await readJsonResponse(response);
    const deletedCategoryId = pendingPermanentDelete.id;
    closePermanentDeleteConfirmation({ force: true });
    categoryTrashContent.querySelector(`[data-category-id="${CSS.escape(String(deletedCategoryId))}"]`)?.remove();
    if (!categoryTrashContent.querySelector('.trash-row')) {
      renderTrash([]);
    }
    window.invalidateSearchData?.();
    showFlashMessage(
      `Category and ${data.deleted_bookmark_count} link${data.deleted_bookmark_count === 1 ? '' : 's'} permanently deleted.`,
      'success'
    );
  } catch (error) {
    permanentDeleteSubmitting = false;
    permanentCategoryDeleteModal.removeAttribute('aria-busy');
    permanentCategoryDeleteConfirm.disabled = false;
    showFlashMessage(error.message, 'error');
  } finally {
    permanentCategoryDeleteCancel.disabled = false;
    permanentCategoryDeleteClose.disabled = false;
  }
});

categoryTrashClose?.addEventListener('click', closeCategoryTrash);
permanentCategoryDeleteClose?.addEventListener('click', closePermanentDeleteConfirmation);
permanentCategoryDeleteCancel?.addEventListener('click', closePermanentDeleteConfirmation);

document.addEventListener('keydown', (event) => {
  if (event.key !== 'Escape') return;
  if (permanentCategoryDeleteModal && !permanentCategoryDeleteModal.classList.contains('hidden')) {
    event.preventDefault();
    closePermanentDeleteConfirmation();
  } else if (categoryTrashModal && !categoryTrashModal.classList.contains('hidden')) {
    event.preventDefault();
    closeCategoryTrash();
  }
});

window.openCategoryTrash = openCategoryTrash;
window.closeCategoryTrash = closeCategoryTrash;
