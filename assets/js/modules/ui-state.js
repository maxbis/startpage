/**
 * Semantic UI state shared by application dialogs, menus, and flash messages.
 *
 * Product modules remain responsible for populating content, validation,
 * cleanup, and focus. This helper only exposes state to CSS and assistive
 * technology without depending on framework utility classes.
 */
(function attachUiState(global) {
  'use strict';

  function setExpanded(element, expanded) {
    if (!element) return;
    element.classList.toggle('is-open', expanded);
    element.setAttribute('aria-hidden', String(!expanded));
  }

  function openDialog(element) {
    setExpanded(element, true);
  }

  function closeDialog(element) {
    setExpanded(element, false);
  }

  function isDialogOpen(element) {
    return Boolean(element?.classList.contains('is-open'));
  }

  function openMenu(element) {
    setExpanded(element, true);
  }

  function closeMenu(element) {
    setExpanded(element, false);
  }

  function isMenuOpen(element) {
    return Boolean(element?.classList.contains('is-open'));
  }

  function showFlashRegion(element) {
    if (!element) return;
    element.classList.add('is-visible');
    element.setAttribute('aria-hidden', 'false');
  }

  function hideFlashRegion(element) {
    if (!element) return;
    element.classList.remove('is-visible');
    element.setAttribute('aria-hidden', 'true');
  }

  function setElementHidden(element, hidden) {
    if (!element) return;
    element.hidden = Boolean(hidden);
  }

  global.wpUiState = Object.freeze({
    openDialog,
    closeDialog,
    isDialogOpen,
    openMenu,
    closeMenu,
    isMenuOpen,
    showFlashRegion,
    hideFlashRegion,
    setElementHidden
  });
})(window);
