/*
 * Warm Paper and Ink flash-message controller
 *
 * Optional, dependency-free behavior for .wp-flash-region containers.
 * The CSS theme does not require this file.
 */

(function attachWarmPaperFlash(global) {
    "use strict";

    const TYPES = {
        info: { icon: "ⓘ", role: "status" },
        success: { icon: "✓", role: "status" },
        warning: { icon: "⚠", role: "alert" },
        error: { icon: "×", role: "alert" },
        danger: { icon: "×", role: "alert", className: "error" }
    };

    const controllers = new WeakMap();
    let defaultController = null;
    let nextId = 0;

    function emit(region, name, message, reason) {
        region.dispatchEvent(new CustomEvent(name, {
            bubbles: true,
            detail: { message, reason }
        }));
    }

    class FlashRegion {
        constructor(region, options = {}) {
            if (!(region instanceof HTMLElement)) {
                throw new TypeError("WarmPaper.FlashRegion requires a flash-region element.");
            }

            this.region = region;
            this.options = options;
            this.messages = new Map();

            if (!this.region.hasAttribute("aria-label")) {
                this.region.setAttribute("aria-label", options.label || "Notifications");
            }
        }

        show(type, text, options = {}) {
            const normalizedType = String(type || "info").toLowerCase();
            const definition = TYPES[normalizedType];

            if (!definition) {
                throw new RangeError(`Unknown Warm Paper flash type: ${type}`);
            }

            const message = document.createElement("div");
            const messageId = options.id || `wp-flash-${++nextId}`;
            const className = definition.className || normalizedType;

            message.id = messageId;
            message.className = `wp-alert wp-alert--${className} wp-flash`;
            message.setAttribute("role", options.role || definition.role);
            message.setAttribute("aria-atomic", "true");
            message.dataset.wpFlashId = messageId;

            const icon = document.createElement("span");
            icon.className = "wp-flash__icon";
            icon.setAttribute("aria-hidden", "true");
            icon.textContent = options.icon || definition.icon;

            const content = document.createElement("span");
            content.className = "wp-flash__message";
            content.textContent = String(text);

            message.append(icon, content);

            if (options.dismissible !== false) {
                const dismiss = document.createElement("button");
                dismiss.className = "wp-icon-button wp-flash__dismiss";
                dismiss.type = "button";
                dismiss.setAttribute("aria-label", options.dismissLabel || "Dismiss notification");
                dismiss.textContent = "×";
                dismiss.addEventListener("click", () => this.dismiss(message, "dismiss"));
                message.append(dismiss);
            }

            this.region.append(message);

            const timeout = Number(options.timeout ?? this.options.timeout ?? 0);
            const record = { element: message, timer: null };
            this.messages.set(messageId, record);

            if (Number.isFinite(timeout) && timeout > 0) {
                const startTimer = () => {
                    record.timer = global.setTimeout(() => this.dismiss(message, "timeout"), timeout);
                };
                const stopTimer = () => {
                    global.clearTimeout(record.timer);
                    record.timer = null;
                };

                message.addEventListener("mouseenter", stopTimer);
                message.addEventListener("mouseleave", startTimer);
                message.addEventListener("focusin", stopTimer);
                message.addEventListener("focusout", startTimer);
                startTimer();
            }

            emit(this.region, "wp:flash-show", message, "show");
            return message;
        }

        info(text, options) {
            return this.show("info", text, options);
        }

        success(text, options) {
            return this.show("success", text, options);
        }

        warning(text, options) {
            return this.show("warning", text, options);
        }

        error(text, options) {
            return this.show("error", text, options);
        }

        dismiss(messageOrId, reason = "dismiss") {
            const message = messageOrId instanceof HTMLElement
                ? messageOrId
                : this.region.querySelector(`[data-wp-flash-id="${CSS.escape(String(messageOrId))}"]`);

            if (!message || !this.region.contains(message)) return false;

            const record = this.messages.get(message.dataset.wpFlashId);
            if (record?.timer) global.clearTimeout(record.timer);

            this.messages.delete(message.dataset.wpFlashId);
            message.remove();
            emit(this.region, "wp:flash-dismiss", message, reason);
            return true;
        }

        clear(reason = "clear") {
            [...this.messages.values()].forEach(({ element }) => this.dismiss(element, reason));
        }
    }

    function initFlash(root = document, options = {}) {
        const regions = new Map();

        root.querySelectorAll("[data-wp-flash-region]").forEach((region, index) => {
            let controller = controllers.get(region);

            if (!controller) {
                controller = new FlashRegion(region, options);
                controllers.set(region, controller);
            }

            const id = region.dataset.wpFlashRegion || region.id || `region-${index + 1}`;
            regions.set(id, controller);
            if (!defaultController) defaultController = controller;
        });

        return regions;
    }

    function getDefaultController() {
        if (!defaultController) initFlash();
        if (!defaultController) {
            throw new Error("Warm Paper flash messages require a data-wp-flash-region container.");
        }
        return defaultController;
    }

    const flash = {
        show: (...args) => getDefaultController().show(...args),
        info: (...args) => getDefaultController().info(...args),
        success: (...args) => getDefaultController().success(...args),
        warning: (...args) => getDefaultController().warning(...args),
        error: (...args) => getDefaultController().error(...args),
        dismiss: (...args) => getDefaultController().dismiss(...args),
        clear: (...args) => getDefaultController().clear(...args)
    };

    global.WarmPaper = Object.assign(global.WarmPaper || {}, {
        FlashRegion,
        flash,
        initFlash
    });
})(window);
