/**
 * Shared single-bookmark checks and category-wide link test results.
 */

const categoryLinkTestModal = document.getElementById('categoryLinkTestModal');
const categoryLinkTestTitle = document.getElementById('categoryLinkTestTitle');
const categoryLinkTestCategory = document.getElementById('categoryLinkTestCategory');
const categoryLinkTestSummary = document.getElementById('categoryLinkTestSummary');
const categoryLinkTestCount = document.getElementById('categoryLinkTestCount');
const categoryLinkTestProgress = document.getElementById('categoryLinkTestProgress');
const categoryLinkTestResults = document.getElementById('categoryLinkTestResults');
const categoryLinkTestClose = document.getElementById('categoryLinkTestClose');
const categoryLinkTestCancel = document.getElementById('categoryLinkTestCancel');

let activeCategoryLinkTest = null;
let categoryLinkTestReturnFocus = null;

async function requestBookmarkLinkTest(bookmarkId, signal = null) {
  const response = await fetch('../api/test-bookmark.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: bookmarkId }),
    signal,
  });
  const responseText = await response.text();
  let result = null;

  if (responseText) {
    try {
      result = JSON.parse(responseText);
    } catch (parseError) {
      DEBUG.log('BOOKMARK', 'Link test API returned invalid JSON:', responseText.slice(0, 200));
    }
  }

  if (!response.ok || !result?.success) {
    throw new Error(result?.message || `HTTP error! status: ${response.status}`);
  }

  return result;
}

function applyBookmarkLinkTestResult(bookmarkElement, result) {
  if (!bookmarkElement || (!result?.description_updated && !result?.favicon_refreshed)) return;

  window.updateBookmarkDisplay?.(bookmarkElement.dataset.id, {
    title: bookmarkElement.dataset.title,
    url: bookmarkElement.dataset.url,
    description: result.description || '',
    category_id: bookmarkElement.dataset.categoryId,
    favicon_url: result.favicon_refreshed
      ? (result.favicon_url || bookmarkElement.dataset.faviconUrl || '')
      : (bookmarkElement.dataset.faviconUrl || ''),
    background_color: bookmarkElement.dataset.backgroundColor || 'none',
    color: parseInt(bookmarkElement.dataset.color || '0', 10) || 0,
  });

  if (result.favicon_refreshed) {
    const faviconImg = bookmarkElement.querySelector('.bookmark-icon img');
    if (faviconImg && bookmarkElement.dataset.faviconUrl?.startsWith('cache/')) {
      faviconImg.src = `${window.formatBookmarkFaviconUrl(
        bookmarkElement.dataset.faviconUrl,
        bookmarkElement.dataset.url || ''
      )}?v=${Date.now()}`;
    }
  }
  window.invalidateSearchData?.();
}

function createCategoryLinkTestRow(bookmarkElement) {
  const row = document.createElement('div');
  row.className = 'link-test-result is-pending';
  row.dataset.bookmarkId = bookmarkElement.dataset.id;
  row.setAttribute('role', 'listitem');

  const status = document.createElement('span');
  status.className = 'link-test-status';
  status.setAttribute('aria-hidden', 'true');
  status.textContent = '…';

  const content = document.createElement('div');
  content.className = 'link-test-result-content';

  const title = document.createElement('div');
  title.className = 'link-test-result-title';
  title.textContent = bookmarkElement.dataset.title || 'Bookmark';
  title.title = bookmarkElement.dataset.title || '';

  const detail = document.createElement('div');
  detail.className = 'link-test-result-detail';
  detail.textContent = 'Waiting to be tested';

  const actions = document.createElement('div');
  actions.className = 'link-test-result-actions';

  content.append(title, detail);
  row.append(status, content, actions);
  return row;
}

function createLinkTestAction(label, action, bookmarkId, danger = false) {
  const button = document.createElement('button');
  button.type = 'button';
  button.className = `link-test-row-action${danger ? ' is-danger' : ''}`;
  button.dataset.linkTestAction = action;
  button.dataset.bookmarkId = bookmarkId;
  button.textContent = label;
  return button;
}

function renderCategoryLinkTestRow(run, bookmarkId, result) {
  const row = categoryLinkTestResults?.querySelector(`[data-bookmark-id="${CSS.escape(String(bookmarkId))}"]`);
  if (!row) return;

  const status = row.querySelector('.link-test-status');
  const detail = row.querySelector('.link-test-result-detail');
  const actions = row.querySelector('.link-test-result-actions');
  if (!status || !detail || !actions) return;

  row.className = 'link-test-result';
  actions.replaceChildren();

  if (result.deleted) {
    row.classList.add('is-deleted');
    status.textContent = '–';
    detail.textContent = 'Bookmark deleted';
    return;
  }

  if (result.testing) {
    row.classList.add('is-testing');
    status.textContent = '…';
    detail.textContent = 'Testing…';
    return;
  }

  if (result.cancelled) {
    row.classList.add('is-pending');
    status.textContent = '–';
    detail.textContent = 'Not tested';
    actions.append(createLinkTestAction('Test again', 'retest', bookmarkId));
    return;
  }

  if (result.exists === true) {
    row.classList.add('is-working');
    status.textContent = '✓';
    detail.textContent = result.message;
    return;
  }

  if (result.exists === false) {
    row.classList.add('is-broken');
    status.textContent = '×';
    detail.textContent = result.message;
    actions.append(
      createLinkTestAction('Test again', 'retest', bookmarkId),
      createLinkTestAction('Edit', 'edit', bookmarkId),
      createLinkTestAction('Delete', 'delete', bookmarkId, true)
    );
    return;
  }

  row.classList.add('is-warning');
  status.textContent = '!';
  detail.textContent = result.message || 'The link could not be verified.';
  actions.append(
    createLinkTestAction('Test again', 'retest', bookmarkId),
    createLinkTestAction('Edit', 'edit', bookmarkId)
  );
}

function updateCategoryLinkTestSummary(run) {
  if (!run || activeCategoryLinkTest !== run) return;

  const results = Array.from(run.results.values());
  const deleted = results.filter(result => result.deleted).length;
  const testedResults = results.filter(result => !result.deleted && !result.cancelled && !result.testing);
  const working = testedResults.filter(result => result.exists === true).length;
  const broken = testedResults.filter(result => result.exists === false).length;
  const warnings = testedResults.filter(result => result.exists === null).length;
  const updated = testedResults.filter(result => result.description_updated).length;
  const faviconsRefreshed = testedResults.filter(result => result.favicon_refreshed).length;
  const finished = results.filter(result => !result.testing).length;

  categoryLinkTestProgress.max = Math.max(1, run.total);
  categoryLinkTestProgress.value = Math.min(run.total, finished);
  categoryLinkTestProgress.setAttribute(
    'aria-valuetext',
    `${Math.min(run.total, finished)} of ${run.total} links tested`
  );
  categoryLinkTestCount.textContent = `${Math.min(run.total, finished)} / ${run.total}`;

  const parts = [];
  if (working) parts.push(`${working} working`);
  if (broken) parts.push(`${broken} unavailable`);
  if (warnings) parts.push(`${warnings} not verified`);
  if (updated) parts.push(`${updated} description${updated === 1 ? '' : 's'} added`);
  if (faviconsRefreshed) {
    parts.push(`${faviconsRefreshed} favicon${faviconsRefreshed === 1 ? '' : 's'} refreshed`);
  }
  if (deleted) parts.push(`${deleted} deleted`);

  if (run.running) {
    categoryLinkTestSummary.textContent = `Testing links…${parts.length ? ` ${parts.join(' · ')}` : ''}`;
  } else if (run.cancelled) {
    categoryLinkTestSummary.textContent = `Testing cancelled.${parts.length ? ` ${parts.join(' · ')}` : ''}`;
  } else {
    categoryLinkTestSummary.textContent = parts.length ? parts.join(' · ') : 'No links were tested.';
  }
}

async function testCategoryBookmark(run, bookmarkElement) {
  const bookmarkId = bookmarkElement.dataset.id;
  run.results.set(bookmarkId, { testing: true });
  renderCategoryLinkTestRow(run, bookmarkId, { testing: true });

  try {
    const result = await requestBookmarkLinkTest(bookmarkId, run.controller.signal);
    applyBookmarkLinkTestResult(bookmarkElement, result);
    run.results.set(bookmarkId, result);
    renderCategoryLinkTestRow(run, bookmarkId, result);
  } catch (error) {
    if (error.name === 'AbortError') {
      const cancelled = { cancelled: true };
      run.results.set(bookmarkId, cancelled);
      renderCategoryLinkTestRow(run, bookmarkId, cancelled);
      return;
    }

    const result = {
      exists: null,
      message: `Could not test link: ${error.message}`,
    };
    run.results.set(bookmarkId, result);
    renderCategoryLinkTestRow(run, bookmarkId, result);
  } finally {
    updateCategoryLinkTestSummary(run);
  }
}

async function runCategoryLinkTestWorkers(run) {
  const worker = async () => {
    while (!run.cancelled) {
      const index = run.nextIndex;
      run.nextIndex += 1;
      if (index >= run.bookmarks.length) return;
      await testCategoryBookmark(run, run.bookmarks[index]);
    }
  };

  const workerCount = Math.min(3, run.bookmarks.length);
  await Promise.all(Array.from({ length: workerCount }, () => worker()));

  if (activeCategoryLinkTest !== run) return;
  run.running = false;

  if (run.cancelled) {
    run.bookmarks.slice(run.nextIndex).forEach(bookmarkElement => {
      const result = { cancelled: true };
      run.results.set(bookmarkElement.dataset.id, result);
      renderCategoryLinkTestRow(run, bookmarkElement.dataset.id, result);
    });
  }

  categoryLinkTestCancel.textContent = 'Close';
  updateCategoryLinkTestSummary(run);
}

function closeCategoryLinkTest() {
  if (!categoryLinkTestModal) return;

  if (activeCategoryLinkTest?.running) {
    activeCategoryLinkTest.cancelled = true;
    activeCategoryLinkTest.controller.abort();
  }
  activeCategoryLinkTest?.retestControllers?.forEach(controller => controller.abort());

  categoryLinkTestModal.classList.add('hidden');
  categoryLinkTestModal.classList.remove('flex');
  const returnFocus = categoryLinkTestReturnFocus;
  categoryLinkTestReturnFocus = null;
  if (returnFocus?.isConnected) returnFocus.focus();
}

function openCategoryLinkTest(categoryId, trigger = null) {
  const categorySection = document.querySelector(`section[data-category-id="${CSS.escape(String(categoryId))}"]`);
  if (!categorySection || !categoryLinkTestModal || !categoryLinkTestResults) return;

  const bookmarks = Array.from(categorySection.querySelectorAll('.bookmark-item[data-id]'));
  if (bookmarks.length === 0) {
    window.showFlashMessage?.('This category has no links to test.', 'info');
    return;
  }

  if (activeCategoryLinkTest?.running) {
    activeCategoryLinkTest.cancelled = true;
    activeCategoryLinkTest.controller.abort();
  }
  activeCategoryLinkTest?.retestControllers?.forEach(controller => controller.abort());

  const categoryName = categorySection.querySelector('.category-title')?.textContent?.trim() || 'Category';
  const run = {
    categoryId: String(categoryId),
    bookmarks,
    total: bookmarks.length,
    nextIndex: 0,
    results: new Map(),
    controller: new AbortController(),
    retestControllers: new Set(),
    cancelled: false,
    running: true,
  };
  activeCategoryLinkTest = run;
  categoryLinkTestReturnFocus = trigger || document.activeElement;

  categoryLinkTestTitle.textContent = 'Test category links';
  categoryLinkTestCategory.textContent = categoryName;
  categoryLinkTestSummary.textContent = 'Preparing link tests…';
  categoryLinkTestCount.textContent = `0 / ${run.total}`;
  categoryLinkTestProgress.max = Math.max(1, run.total);
  categoryLinkTestProgress.value = 0;
  categoryLinkTestProgress.setAttribute('aria-valuetext', `0 of ${run.total} links tested`);
  categoryLinkTestCancel.textContent = 'Cancel testing';
  categoryLinkTestResults.replaceChildren(...bookmarks.map(createCategoryLinkTestRow));

  categoryLinkTestModal.classList.remove('hidden');
  categoryLinkTestModal.classList.add('flex');
  categoryLinkTestClose.focus();
  runCategoryLinkTestWorkers(run);
}

async function retestCategoryBookmark(bookmarkId) {
  const run = activeCategoryLinkTest;
  if (!run || run.running) return;

  const bookmarkElement = run.bookmarks.find(bookmark => bookmark.dataset.id === String(bookmarkId));
  if (!bookmarkElement?.isConnected) return;

  const controller = new AbortController();
  run.retestControllers.add(controller);
  const result = { testing: true };
  run.results.set(String(bookmarkId), result);
  renderCategoryLinkTestRow(run, bookmarkId, result);

  try {
    const testedResult = await requestBookmarkLinkTest(bookmarkId, controller.signal);
    if (activeCategoryLinkTest !== run) return;
    applyBookmarkLinkTestResult(bookmarkElement, testedResult);
    run.results.set(String(bookmarkId), testedResult);
    renderCategoryLinkTestRow(run, bookmarkId, testedResult);
  } catch (error) {
    if (error.name === 'AbortError' || activeCategoryLinkTest !== run) return;
    const failedResult = {
      exists: null,
      message: `Could not test link: ${error.message}`,
    };
    run.results.set(String(bookmarkId), failedResult);
    renderCategoryLinkTestRow(run, bookmarkId, failedResult);
  } finally {
    run.retestControllers.delete(controller);
  }
  if (activeCategoryLinkTest === run) updateCategoryLinkTestSummary(run);
}

categoryLinkTestResults?.addEventListener('click', event => {
  const button = event.target.closest('[data-link-test-action]');
  if (!button) return;

  const bookmarkId = button.dataset.bookmarkId;
  const bookmarkElement = document.querySelector(`.bookmark-item[data-id="${CSS.escape(String(bookmarkId))}"]`);
  const action = button.dataset.linkTestAction;

  if (action === 'retest') {
    retestCategoryBookmark(bookmarkId);
  } else if (action === 'edit' && bookmarkElement) {
    window.openBookmarkEditor?.(bookmarkElement);
  } else if (action === 'delete' && bookmarkElement) {
    window.openDeleteModal?.(
      bookmarkId,
      bookmarkElement.dataset.title || 'Bookmark',
      'bookmark',
      {
        title: 'Delete unavailable bookmark?',
        prompt: 'The link appears unavailable. Do you want to delete this bookmark?',
        note: 'Check the address before deleting. This action cannot be undone.',
        confirmLabel: 'Delete bookmark',
      }
    );
  }
});

document.addEventListener('bookmark-deleted', event => {
  const bookmarkId = String(event.detail?.id || '');
  const run = activeCategoryLinkTest;
  if (!run || !run.results.has(bookmarkId)) return;

  const result = { deleted: true };
  run.results.set(bookmarkId, result);
  renderCategoryLinkTestRow(run, bookmarkId, result);
  updateCategoryLinkTestSummary(run);
});

categoryLinkTestClose?.addEventListener('click', closeCategoryLinkTest);
categoryLinkTestCancel?.addEventListener('click', closeCategoryLinkTest);

window.requestBookmarkLinkTest = requestBookmarkLinkTest;
window.applyBookmarkLinkTestResult = applyBookmarkLinkTestResult;
window.openCategoryLinkTest = openCategoryLinkTest;
window.closeCategoryLinkTest = closeCategoryLinkTest;
