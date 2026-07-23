# Warm Paper and Ink UI style guide

## Purpose

This guide defines a reusable visual system for calm, information-dense productivity applications. It combines warm paper-like surfaces, charcoal text, muted ink-blue interaction colors, restrained shadows, and compact controls.

Use it for dashboards, internal tools, note applications, content managers, administration interfaces, and other products where clarity and spatial memory matter more than decoration.

The intended character is:

- Warm rather than clinical.
- Compact without feeling cramped.
- Quiet at rest and clear during interaction.
- Application-like rather than website-like.
- Accessible without relying on excessive contrast or saturated color.

## Location

The implemented reference lives in:

- `assets/css/main.css`
- `assets/css/bookmark-colors.css`
- `assets/css/responsive.css`
- `includes/templates/modals/*.php`
- `app/index.php`

This reusable guide lives at:

- `docs/assets/css/warm-paper-ink-style-guide.md`

## Inputs/Outputs

Inputs:

- Semantic design tokens for surfaces, borders, text, accents, danger states, focus rings, and shadows.
- A system sans-serif typeface.
- A four-pixel spacing rhythm.
- Content density appropriate for productivity software.
- Interaction states for pointer, keyboard, touch, loading, selection, and destructive actions.

Outputs:

- Consistent application shells, cards, lists, controls, menus, dialogs, and responsive layouts.
- A predictable visual hierarchy.
- Reusable CSS variables and component rules.
- Accessible focus and text contrast.

## Design principles

- When an element is at rest, then keep its styling quiet.
- When an element is interactive, then reveal that through hover, focus, selection, or a clear control shape.
- When multiple surfaces are adjacent, then separate them with subtle temperature and border differences before adding shadow.
- When color is decorative rather than meaningful, then prefer a neutral.
- When a semantic state needs attention, then use one restrained color plus text or an icon.
- When information is dense, then use spacing and alignment to create hierarchy instead of large typography.
- When a temporary layer opens, then keep the underlying application recognizable.
- When users arrange content spatially, then avoid unexpected movement.

## Color system

### Core tokens

Use these values as the default light theme:

```css
:root {
  --surface-page: #f5f2ec;
  --surface-panel: #fffefb;
  --surface-input: #fbfaf7;
  --surface-subtle: #f0ece5;
  --surface-hover: #eaf0f6;
  --surface-selected: #dfeaf6;

  --border-subtle: #e3ddd3;
  --border-strong: #cdc4b7;

  --text-primary: #252525;
  --text-secondary: #72757c;
  --text-heading-muted: #61748a;
  --control-muted: #9298a1;

  --accent: #315f8d;
  --accent-hover: #264b70;
  --accent-soft: #e4edf7;
  --focus-ring: rgba(49, 95, 141, 0.2);

  --danger: #c94f49;
  --danger-hover: #ad403b;
  --danger-soft: #f8e9e7;

  --shadow-raised: 0 2px 8px rgba(61, 49, 31, 0.07);
  --shadow-floating: 0 12px 30px rgba(61, 49, 31, 0.16);
}
```

### Token usage

- `--surface-page` is the application canvas.
- `--surface-panel` is used for cards, dialogs, menus, and the header.
- `--surface-input` distinguishes editable controls from their surrounding panel.
- `--surface-subtle` is used for quiet grouped content, neutral buttons, and supporting panels.
- `--surface-hover` is the default hover and focus-within background.
- `--surface-selected` marks the current or keyboard-selected item.
- `--border-subtle` separates rows and nearby surfaces.
- `--border-strong` outlines form controls and stronger structural boundaries.
- `--text-primary` is used for body copy, item titles, and important labels.
- `--text-secondary` is used for descriptions, metadata, timestamps, and supporting copy.
- `--text-heading-muted` is used for section and category headings.
- `--control-muted` is used for inactive icons, drag handles, and non-text indicators.
- `--accent` is used for primary actions, links, active indicators, and keyboard focus.
- `--accent-soft` is used behind accent-colored text.
- `--danger` is reserved for destructive actions and irreversible warnings.

### Color rules

- When an icon or favicon carries identity, then preserve its native color.
- When creating a hover state, then change the background before increasing text saturation.
- When indicating selection, then use `--surface-selected` plus an accent border or marker.
- When creating secondary text, then do not go lighter than `--text-secondary` for normal-sized essential copy.
- When adding a new semantic color, then provide a dark foreground, a soft background, and a hover value.
- Do not use bright royal blue as the default accent.
- Do not use pure white for every surface.
- Do not use pure black for normal text.

## Typography

Use the platform system sans-serif stack unless the product has an established typeface:

```css
font-family:
  ui-sans-serif,
  system-ui,
  -apple-system,
  BlinkMacSystemFont,
  "Segoe UI",
  sans-serif;
```

Recommended hierarchy:

- Application or page title: `1.5rem`, weight `600`, line height `1.2`.
- Dialog title: `1.125rem`, weight `600`, line height `1.3`.
- Section or category title: `1rem`, weight `600`, line height `1.25`.
- Primary item text: `0.875rem`, weight `500`.
- Standard body and control text: `0.875rem`, weight `400–500`.
- Supporting description: `0.8125rem`, weight `400`, line height `1rem`.
- Metadata and hints: `0.75rem`.
- Keyboard hints and tertiary labels: `0.6875rem`.

Typography rules:

- When text identifies an item, then use primary color and medium weight.
- When text explains an item, then use secondary color and regular weight.
- When a title is truncated, then provide the complete value through an accessible tooltip or detail view.
- Avoid using weight `700` throughout an information-dense interface.
- Avoid oversized headings inside compact tools.

## Spacing

Use a four-pixel base rhythm with these preferred values:

- `4px`: tight internal separation.
- `6px`: compact control padding.
- `8px`: icon-to-label and row padding.
- `10px`: compact component gaps.
- `12px`: card, column, and field gaps.
- `16px`: standard section spacing.
- `20px`: dialog and major component horizontal padding.
- `24px`: generous container padding.
- `32px`: separation between major page regions.

Spacing rules:

- When elements belong to the same control, then use `4–8px`.
- When elements are peers in a component, then use `8–12px`.
- When separating components, then use `12–20px`.
- Avoid arbitrary spacing values when a value from the scale works.

## Shape and elevation

Preferred corner radii:

- List row and compact item: `7px`.
- Icon button and standard button: `8px`.
- Input, menu, and search field: `10px`.
- Card and dialog: `12px`.
- Status dot or pill: `999px`.

Elevation rules:

- Cards use `--shadow-raised`.
- Menus, tooltips, overlays, and dialogs use `--shadow-floating`.
- The fixed application header normally uses a border without a shadow.
- When a border provides enough separation, then do not add another shadow.
- Avoid strong black shadows and glass-like transparency.

## Application shell

The application shell should contain:

- A warm-white header separated by a subtle bottom border.
- A warm page canvas.
- A compact central search control.
- Navigation and creation controls placed at predictable edges.
- User and application-level actions in an account menu.

Header rules:

- Keep the header between `52px` and `60px` tall.
- Use one primary page title.
- Use icon buttons with at least a `32px` square hit area.
- Use a soft background for hover and expanded states.
- Put infrequent actions such as About, password changes, and administration in the user menu.

## Cards and panels

Default card treatment:

```css
.card {
  background: var(--surface-panel);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  box-shadow: var(--shadow-raised);
}
```

Card rules:

- Use a quiet heading color such as `--text-heading-muted`.
- Keep internal rows borderless and separate them with inset dividers.
- Let content determine card height unless a fixed viewport is essential.
- When content is collapsed, then show the exact hidden count.
- When temporary expansion would disrupt spatial layout, then expand above nearby content as a clearly elevated overlay.

## Lists and rows

Recommended row dimensions:

- Standard row minimum height: `40px`.
- Row with description minimum height: `48px`.
- Icon slot: `32px`.
- Visible icon: approximately `24px`.
- Horizontal row padding: `8px`.
- Row gap: `8px`.

Row rules:

- Use transparent backgrounds at rest.
- Use `--surface-hover` for uncolored hover and focus-within states.
- Use a divider beginning after the icon where possible.
- Keep the primary title on one line in dense navigation lists.
- Keep descriptions to one line unless reading the full description is the main task.
- Put repeated metadata or actions in a fixed-width trailing slot to preserve alignment.

## Buttons and icon controls

Primary buttons:

- Use `--accent` with white text.
- Use `--accent-hover` on hover.
- Use an accent outline on keyboard focus.

Secondary buttons:

- Use `--surface-subtle`.
- Use primary text and a subtle border.
- Use `--border-subtle` as the hover background.

Destructive buttons:

- Use a filled `--danger` button only when confirming destruction.
- Use a transparent, outlined danger button when Delete is one action within an edit dialog.
- Keep destructive actions visually separated from Save.

Icon buttons:

- Use transparent backgrounds at rest.
- Use `--surface-hover` and accent text on hover.
- Provide an accessible name through visible text, `aria-label`, or both.
- Use a minimum hit area of `32px`; use `36px` in the application header.

## Inputs and search

Default form control treatment:

```css
input,
textarea,
select {
  border: 1px solid var(--border-strong);
  border-radius: 10px;
  background: var(--surface-input);
  color: var(--text-primary);
}

input:focus,
textarea:focus,
select:focus {
  border-color: var(--accent);
  outline: 2px solid var(--focus-ring);
  outline-offset: 1px;
}
```

Form rules:

- Put labels above fields.
- Use medium-weight primary text for labels.
- Keep optional status in the label instead of relying on placeholder text.
- Use placeholder text only as an example, not as the field name.
- Keep empty textareas compact; expand them only when the task requires longer writing.

## Menus, tooltips, and popovers

- Use the panel surface, subtle border, `10px` radius, and floating shadow.
- Use `6px` outer padding around menu items.
- Use a `7px` item radius.
- Use `38px` as the target minimum height for menu items.
- Separate related groups with a subtle border.
- Use hover background plus accent text for standard actions.
- Use danger text plus a danger-soft hover for destructive actions.
- Keep tooltips concise and do not repeat information already visible in full.

## Dialogs

Dialog anatomy:

1. A fixed header containing a title and close button.
2. A scrollable body or form.
3. A sticky action row when the dialog contains actions.

Dialog surface:

- Warm-white panel.
- `12px` radius.
- Subtle border.
- Floating shadow.
- Maximum height of `100dvh - 32px`.
- Backdrop using charcoal at approximately `34%` opacity.

Dialog header:

- Minimum height `58px`.
- Horizontal padding `20px`.
- Title size `1.125rem`.
- Close button in the upper-right corner.

Dialog actions:

- Place Cancel immediately before the primary action.
- Place Save, Add, Apply, or Confirm on the far right.
- Place a subdued Delete action on the far left of edit dialogs.
- In destructive confirmation dialogs, place Cancel before the filled Delete button.
- Keep the action row visible when the form body scrolls.
- On narrow screens, allow buttons to wrap; put a subdued Delete action on its own row.

Dialog behavior:

- When the close button is activated, then run the same cleanup as Cancel.
- When `Escape` is pressed, then close the topmost dismissible dialog.
- When the backdrop is clicked, then close a non-blocking dialog.
- When a dialog closes, then return focus to the control that opened it.
- When an irreversible operation is in progress, then prevent accidental backdrop dismissal.

## Status and activity indicators

- Use small indicators rather than borders around entire rows.
- Encode degree through both shape or length and color.
- Keep inactive tracks neutral.
- Pair indicators with text in legends and accessible names.
- Do not rely on color alone.
- Use saturated semantic color only for current, important, or actionable states.

## Responsive behavior

- At desktop widths, preserve spatial organization and stable content placement.
- At narrower widths, reduce the number of columns deterministically.
- At mobile widths, use a single content flow.
- Disable drag interactions where touch precision or responsive folding makes placement ambiguous.
- Keep interactive targets at least `32px`; prefer `40px` for primary touch controls.
- Let dialog buttons wrap rather than shrink labels below a readable width.
- Use `100dvh` rather than `100vh` for mobile dialog constraints where supported.

## Motion

- Use `150ms` for hover, focus, and color transitions.
- Use `200ms` for icon rotation or small disclosure transitions.
- Use animation to clarify state change, not as decoration.
- Avoid animating large layout reflows during direct manipulation.
- Respect `prefers-reduced-motion` in projects with more than simple color transitions.

Suggested baseline:

```css
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    scroll-behavior: auto !important;
    transition-duration: 0.01ms !important;
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
  }
}
```

## Accessibility

- Maintain at least `4.5:1` contrast for normal essential text.
- Maintain at least `3:1` contrast for large text, icons, control boundaries, and meaningful graphics.
- Use visible `:focus-visible` outlines.
- Provide keyboard access for menus, dialogs, disclosures, and reordered content.
- Give dialogs `role="dialog"` or `role="alertdialog"`, `aria-modal="true"`, and a valid accessible label.
- Give icon-only buttons an accessible name.
- Preserve native control semantics whenever possible.
- Do not communicate recency, status, or danger through color alone.
- When content is truncated, then provide access to the complete value.

## Implementation procedure

1. Add the core tokens before component styles.
2. Apply page, panel, input, and text tokens to the application shell.
3. Build buttons, inputs, cards, menus, and dialogs from semantic classes.
4. Replace framework color utilities with component-level semantic tokens.
5. Add hover, focus-visible, selected, disabled, and danger states.
6. Add responsive dialog and layout behavior.
7. Check text and non-text contrast.
8. Test keyboard navigation and focus return.
9. Test at desktop, narrow desktop, tablet, and mobile widths.
10. Review the interface at rest; remove any border, shadow, or accent that does not communicate structure or state.

## Edge cases/Failure modes

- Framework utility classes can override semantic tokens when they have equal or greater specificity.
- Hard-coded bright blues or cool grays can make an otherwise warm interface look inconsistent.
- Excessively light secondary text can fail contrast even when it appears visually elegant.
- A fixed-height dialog can become unusable with browser zoom, translation, validation messages, or a mobile keyboard.
- Sticky dialog actions need a solid background so scrolled content does not show through.
- Multiple equal-emphasis actions make destructive operations easier to trigger accidentally.
- Dense cards with variable heights can create unexpected movement when an automatic packing algorithm reflows them.
- Native icon and favicon colors may look vivid against the restrained palette; keep them because they communicate identity.
- Warm surfaces should remain distinguishable. Do not make the page, panel, input, and subtle surfaces identical.

## Related Files

- `assets/css/main.css`
- `assets/css/bookmark-colors.css`
- `assets/css/responsive.css`
- `app/index.php`
- `assets/js/modules/modal-management.js`
- `assets/js/modules/account-menu.js`
- `includes/templates/modals/*.php`
- [Client modules](../js/client-modules.md)
