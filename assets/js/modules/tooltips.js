/**
 * Accessible floating tooltips for elements carrying data-tooltip attributes.
 */

let activeTooltip = null;
let activeTooltipTarget = null;
let tooltipTimer = null;

function positionTooltip(tooltip, target) {
  const margin = 10;
  const gap = 7;
  const targetRect = target.getBoundingClientRect();
  const tooltipRect = tooltip.getBoundingClientRect();
  let left = targetRect.left + ((targetRect.width - tooltipRect.width) / 2);
  let top = targetRect.bottom + gap;

  left = Math.max(margin, Math.min(left, window.innerWidth - tooltipRect.width - margin));
  if (top + tooltipRect.height > window.innerHeight - margin) {
    top = targetRect.top - tooltipRect.height - gap;
  }
  top = Math.max(margin, top);

  tooltip.style.left = `${left}px`;
  tooltip.style.top = `${top}px`;
}

function hideTooltip() {
  clearTimeout(tooltipTimer);
  tooltipTimer = null;
  if (activeTooltipTarget) activeTooltipTarget.removeAttribute('aria-describedby');
  activeTooltip?.remove();
  activeTooltip = null;
  activeTooltipTarget = null;
}

function showTooltip(target) {
  if (!target?.dataset.tooltip) return;
  hideTooltip();

  const tooltip = document.createElement('div');
  const tooltipId = `app-tooltip-${Date.now()}`;
  tooltip.id = tooltipId;
  tooltip.className = 'app-tooltip';
  tooltip.setAttribute('role', 'tooltip');

  const title = document.createElement('div');
  title.className = 'app-tooltip-title';
  title.textContent = target.dataset.tooltip;
  tooltip.appendChild(title);

  if (target.dataset.tooltipDetail) {
    const detail = document.createElement('div');
    detail.className = 'app-tooltip-detail';
    detail.textContent = target.dataset.tooltipDetail;
    tooltip.appendChild(detail);
  }

  document.body.appendChild(tooltip);
  positionTooltip(tooltip, target);
  target.setAttribute('aria-describedby', tooltipId);
  activeTooltip = tooltip;
  activeTooltipTarget = target;
}

document.addEventListener('pointerover', (event) => {
  const target = event.target.closest('[data-tooltip]');
  if (!target || target === activeTooltipTarget) return;
  clearTimeout(tooltipTimer);
  tooltipTimer = setTimeout(() => showTooltip(target), 350);
});

document.addEventListener('pointerout', (event) => {
  const target = event.target.closest('[data-tooltip]');
  if (!target || target.contains(event.relatedTarget)) return;
  hideTooltip();
});

document.addEventListener('focusin', (event) => {
  const target = event.target.closest('[data-tooltip]');
  if (target) showTooltip(target);
});

document.addEventListener('focusout', (event) => {
  if (event.target.closest('[data-tooltip]')) hideTooltip();
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') hideTooltip();
});

window.addEventListener('scroll', hideTooltip, true);
window.addEventListener('resize', hideTooltip);

window.hideAppTooltip = hideTooltip;
