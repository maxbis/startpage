# Warm Paper and Ink CSS

A dependency-free, framework-independent CSS theme for calm, dense web
applications. It provides namespaced design tokens, application structure,
cards, rows, forms, buttons, menus, dialogs, feedback states, tables,
responsive behavior, and reduced-motion support.

## Use

Copy the `warm-paper` folder into a project and include the entry point:

```html
<link rel="stylesheet" href="/warm-paper/warm-paper.css">
```

Apply `wp-theme` to the application root. Using it on `<body>` is the simplest
option:

```html
<body class="wp-theme">
  <div class="wp-app">
    <main class="wp-main">
      <article class="wp-card">
        <header class="wp-card__header">
          <h2 class="wp-card__heading">Notes</h2>
        </header>
        <div class="wp-card__body">...</div>
      </article>
    </main>
  </div>
</body>
```

No JavaScript, package manager, CSS processor, font download, or UI framework
is required for the visual theme. The optional dialog and flash-message
controllers add accessible behavior to their supplied component styles.

The supplied text tokens meet WCAG AA contrast for normal text on both the page
and panel surfaces. Muted control tokens are reserved for icons, placeholders,
and other non-essential indicators.

## Files

- `warm-paper.css` — single entry point; imports the other stylesheets.
- `tokens.css` — prefixed colors, typography, spacing, shape, shadow, and motion.
- `base.css` — scoped baseline styles inside `.wp-theme`.
- `components.css` — application shell and reusable UI components.
- `utilities.css` — a small set of layout and accessibility helpers.
- `responsive.css` — mobile, reduced-motion, and forced-color behavior.
- `js/dialog.js` — optional accessible dialog controller.
- `js/flash.js` — optional accessible flash-message controller.
- `demo.html` — component preview with a deletion dialog and dynamic flash feedback.

## Components

| Area | Classes |
|---|---|
| Shell | `wp-app`, `wp-header`, `wp-header__inner`, `wp-main` |
| Page | `wp-page-heading`, `wp-page-title`, `wp-page-description`, `wp-toolbar` |
| Panels | `wp-panel`, `wp-card`, `wp-card__header`, `wp-card__body`, `wp-grid` |
| Lists | `wp-list`, `wp-row`, `wp-row__icon`, `wp-row__content`, `wp-row__trailing` |
| Buttons | `wp-button`, `wp-button--primary`, `--secondary`, `--quiet`, `--danger` |
| Forms | `wp-field`, `wp-label`, `wp-input`, `wp-select`, `wp-textarea`, `wp-check` |
| Layers | `wp-menu`, `wp-tooltip`, `wp-dialog-backdrop`, `wp-dialog` |
| Feedback | `wp-alert`, `wp-badge`, plus info/success/warning/danger/error modifiers |
| Data | `wp-table-wrap`, `wp-table`, `wp-empty`, `wp-spinner` |

## Customize

Override prefixed tokens on the same root or a nested theme boundary:

```css
.wp-theme.project-theme {
  --wp-accent: #49627a;
  --wp-accent-hover: #354b61;
  --wp-content-width: 1200px;
}
```

Components inherit tokens, so a project can also theme a subtree:

```html
<section class="wp-theme project-theme">
  ...
</section>
```

## Integration guidance

- Load the theme after a reset or framework stylesheet.
- Prefer the `wp-*` component classes over mixing framework color utilities
  into the same element.
- Keep native favicon and brand colors; the theme does not recolor images.
- Add accessible labels to icon-only buttons.
- Use `role="status"` for routine info and success feedback. Reserve
  `role="alert"` for errors and urgent warnings that need immediate announcement.
- `wp-alert--error` is a semantic alias for `wp-alert--danger`; use whichever
  name best matches the application event.
- Use real buttons, links, headings, lists, and dialogs; the CSS supplies
  presentation, not semantics or dialog behavior.
- When implementing a dialog, add `role="dialog"`, `aria-modal="true"`, focus
  containment, Escape handling, and focus return in application JavaScript.

## Optional dialog controller

Load the controller near the end of the document, after the dialog markup:

```html
<script src="/warm-paper/js/dialog.js"></script>
```

Connect triggers, close controls, and a backdrop with data attributes:

```html
<button type="button" data-wp-dialog-open="delete-project">
  Delete project…
</button>

<div
  class="wp-dialog-backdrop"
  data-wp-dialog="delete-project"
  data-wp-dialog-inert=".wp-app"
  aria-hidden="true"
  hidden
>
  <section
    class="wp-dialog"
    role="alertdialog"
    aria-modal="true"
    aria-labelledby="delete-title"
  >
    <header class="wp-dialog__header">
      <h2 id="delete-title" class="wp-dialog__title">Delete project?</h2>
    </header>
    <div class="wp-dialog__actions">
      <button
        class="wp-button wp-button--secondary"
        type="button"
        data-wp-dialog-close="cancel"
        data-wp-dialog-initial-focus
      >
        Cancel
      </button>
      <button
        class="wp-button wp-button--danger"
        type="button"
        data-wp-dialog-close="confirm"
      >
        Delete project
      </button>
    </div>
  </section>
</div>

<script>
  const dialogs = WarmPaper.initDialogs();
</script>
```

`WarmPaper.initDialogs()` returns a `Map` keyed by each
`data-wp-dialog` value. Controllers expose `open()`, `close(reason)`,
`connectOpeners()`, and `destroy()`.

The backdrop emits bubbling `wp:dialog-open` and `wp:dialog-close` events.
The close event provides `event.detail.reason`, such as `confirm`, `cancel`,
`escape`, or `backdrop`.

## Optional flash-message controller

Add the region near the end of `<body>`, outside the application content and
dialog backdrops, then load and initialize the controller. The supplied styles
keep this region fixed at the top center above other interface layers:

```html
<div
  class="wp-flash-region"
  data-wp-flash-region="notifications"
  aria-label="Notifications"
></div>

<script src="/warm-paper/js/flash.js"></script>
<script>
  WarmPaper.initFlash();
</script>
```

Show feedback after a user interaction:

```js
WarmPaper.flash.success("Project saved.");
WarmPaper.flash.error("The project could not be saved.");
WarmPaper.flash.warning("Your session expires soon.", { timeout: 8000 });
```

Messages are dismissible by default. Pass `{ dismissible: false }` to keep the
dismiss control out, or `{ timeout: 5000 }` to dismiss after five seconds.
Info and success messages use `role="status"`; warnings and errors use
`role="alert"`. Text is inserted with `textContent`, so application-provided
messages are not interpreted as HTML.

`WarmPaper.initFlash()` returns a `Map` of named regions. A `FlashRegion`
controller exposes `show()`, `info()`, `success()`, `warning()`, `error()`,
`dismiss()`, and `clear()`. Regions emit bubbling `wp:flash-show` and
`wp:flash-dismiss` events.

## Specificity

The theme is scoped and namespaced instead of relying on high-specificity
selectors. It uses `!important` only in the reduced-motion accessibility
override, where animations from an adopting application must be suppressed.
