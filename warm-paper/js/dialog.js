/*
 * Warm Paper and Ink dialog controller
 *
 * Optional, dependency-free behavior for .wp-dialog components.
 * The CSS theme does not require this file.
 */

(function attachWarmPaperDialog(global) {
    "use strict";

    const FOCUSABLE_SELECTOR = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled]):not([type="hidden"])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])'
    ].join(",");

    function getFocusableElements(container) {
        return [...container.querySelectorAll(FOCUSABLE_SELECTOR)].filter(element => {
            return !element.hidden && element.getAttribute("aria-hidden") !== "true" && element.getClientRects().length > 0;
        });
    }

    function emit(backdrop, name, controller, reason) {
        backdrop.dispatchEvent(new CustomEvent(name, {
            bubbles: true,
            detail: { controller, reason }
        }));
    }

    class Dialog {
        constructor(backdrop, options = {}) {
            if (!(backdrop instanceof HTMLElement)) {
                throw new TypeError("WarmPaper.Dialog requires a dialog backdrop element.");
            }

            this.backdrop = backdrop;
            this.panel = backdrop.matches('[role="dialog"], [role="alertdialog"]')
                ? backdrop
                : backdrop.querySelector('[role="dialog"], [role="alertdialog"]');

            if (!this.panel) {
                throw new Error("Warm Paper dialog markup requires role=\"dialog\" or role=\"alertdialog\".");
            }

            this.id = options.id || backdrop.dataset.wpDialog || backdrop.id;
            if (!this.id) {
                throw new Error("Warm Paper dialogs require data-wp-dialog or an id.");
            }

            this.options = options;
            this.returnFocus = null;
            this.inertTargets = [];
            this.openerHandlers = new Map();
            this.closeHandlers = new Map();
            this.addedPanelTabIndex = !this.panel.hasAttribute("tabindex");

            if (this.addedPanelTabIndex) {
                this.panel.setAttribute("tabindex", "-1");
            }

            this.handleBackdropClick = this.handleBackdropClick.bind(this);
            this.handleKeydown = this.handleKeydown.bind(this);

            this.backdrop.addEventListener("click", this.handleBackdropClick);
            this.panel.addEventListener("keydown", this.handleKeydown);
            this.connectCloseControls();
            this.connectOpeners(options.root || document);

            if (!this.backdrop.classList.contains("is-open")) {
                this.backdrop.hidden = true;
                this.backdrop.setAttribute("aria-hidden", "true");
            }
        }

        get isOpen() {
            return this.backdrop.classList.contains("is-open");
        }

        connectOpeners(root = document) {
            root.querySelectorAll("[data-wp-dialog-open]").forEach(opener => {
                if (opener.dataset.wpDialogOpen !== this.id || this.openerHandlers.has(opener)) {
                    return;
                }

                const handler = event => {
                    event.preventDefault();
                    this.open(opener);
                };

                opener.addEventListener("click", handler);
                this.openerHandlers.set(opener, handler);
            });

            return this;
        }

        connectCloseControls() {
            this.backdrop.querySelectorAll("[data-wp-dialog-close]").forEach(control => {
                if (this.closeHandlers.has(control)) return;

                const handler = event => {
                    event.preventDefault();
                    this.close(control.dataset.wpDialogClose || "dismiss");
                };

                control.addEventListener("click", handler);
                this.closeHandlers.set(control, handler);
            });
        }

        open(opener = document.activeElement) {
            if (this.isOpen) return;

            this.returnFocus = opener instanceof HTMLElement ? opener : null;
            this.applyInertState();
            this.backdrop.hidden = false;
            this.backdrop.classList.add("is-open");
            this.backdrop.removeAttribute("aria-hidden");
            emit(this.backdrop, "wp:dialog-open", this, "open");

            requestAnimationFrame(() => {
                const initialFocus = this.panel.querySelector("[data-wp-dialog-initial-focus]");
                const firstFocusable = getFocusableElements(this.panel)[0];
                (initialFocus || firstFocusable || this.panel).focus();
            });
        }

        close(reason = "dismiss") {
            if (!this.isOpen) return;

            this.backdrop.classList.remove("is-open");
            this.backdrop.hidden = true;
            this.backdrop.setAttribute("aria-hidden", "true");
            this.restoreInertState();
            emit(this.backdrop, "wp:dialog-close", this, reason);

            if (this.returnFocus && this.returnFocus.isConnected) {
                this.returnFocus.focus();
            }
        }

        applyInertState() {
            const selector = this.options.inertSelector || this.backdrop.dataset.wpDialogInert;
            if (!selector) return;

            let targets = [];
            try {
                targets = [...document.querySelectorAll(selector)];
            } catch {
                throw new Error(`Invalid data-wp-dialog-inert selector: ${selector}`);
            }

            this.inertTargets = targets
                .filter(target => !target.contains(this.backdrop))
                .map(target => ({
                    target,
                    wasInert: target.hasAttribute("inert")
                }));

            this.inertTargets.forEach(({ target }) => target.setAttribute("inert", ""));
        }

        restoreInertState() {
            this.inertTargets.forEach(({ target, wasInert }) => {
                if (!wasInert) target.removeAttribute("inert");
            });
            this.inertTargets = [];
        }

        handleBackdropClick(event) {
            if (event.target !== this.backdrop || this.backdrop.hasAttribute("data-wp-dialog-static")) {
                return;
            }

            this.close("backdrop");
        }

        handleKeydown(event) {
            if (event.key === "Escape" && !this.backdrop.hasAttribute("data-wp-dialog-no-escape")) {
                event.preventDefault();
                this.close("escape");
                return;
            }

            if (event.key !== "Tab") return;

            const focusable = getFocusableElements(this.panel);
            if (focusable.length === 0) {
                event.preventDefault();
                this.panel.focus();
                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }

        destroy() {
            if (this.isOpen) this.close("destroy");

            this.backdrop.removeEventListener("click", this.handleBackdropClick);
            this.panel.removeEventListener("keydown", this.handleKeydown);

            this.openerHandlers.forEach((handler, opener) => opener.removeEventListener("click", handler));
            this.closeHandlers.forEach((handler, control) => control.removeEventListener("click", handler));
            this.openerHandlers.clear();
            this.closeHandlers.clear();

            if (this.addedPanelTabIndex) {
                this.panel.removeAttribute("tabindex");
            }
        }
    }

    function initDialogs(root = document, options = {}) {
        const dialogs = new Map();

        root.querySelectorAll("[data-wp-dialog]").forEach(backdrop => {
            const controller = new Dialog(backdrop, { ...options, root });
            dialogs.set(controller.id, controller);
        });

        return dialogs;
    }

    global.WarmPaper = Object.assign(global.WarmPaper || {}, {
        Dialog,
        initDialogs
    });
})(window);
