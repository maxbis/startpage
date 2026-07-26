<!-- Category Link Test Modal -->
<div
    id="categoryLinkTestModal"
    class="wp-dialog-backdrop modal-backdrop"
    role="dialog"
    aria-modal="true"
    aria-labelledby="categoryLinkTestTitle"
    aria-hidden="true"
    aria-describedby="categoryLinkTestSummary"
    data-dialog-dismiss="categoryLinkTestClose"
    data-dialog-backdrop-dismiss="false"
>
    <div class="wp-dialog wp-dialog--wide modal-panel">
        <div class="wp-dialog__header dialog-header">
            <div class="dialog-heading-group">
                <h3 id="categoryLinkTestTitle" class="wp-dialog__title dialog-title">Test category links</h3>
                <p id="categoryLinkTestCategory" class="link-test-category-name"></p>
            </div>
            <button
                type="button"
                id="categoryLinkTestClose"
                class="wp-icon-button wp-dialog__close dialog-close-button"
                aria-label="Close link test results"
            >&times;</button>
        </div>

        <div class="wp-dialog__body dialog-body category-link-test-body">
            <div class="link-test-progress-panel">
                <div class="link-test-progress-heading">
                    <p id="categoryLinkTestSummary" aria-live="polite">Preparing link tests…</p>
                    <span id="categoryLinkTestCount"></span>
                </div>
                <progress
                    id="categoryLinkTestProgress"
                    value="0"
                    max="1"
                    aria-label="Category link test progress"
                    aria-valuetext="0 of 0 links tested"
                >0%</progress>
            </div>

            <div
                id="categoryLinkTestResults"
                class="link-test-results"
                role="list"
                aria-label="Link test results"
            ></div>
        </div>

        <div class="wp-dialog__actions dialog-actions category-link-test-actions">
            <span class="dialog-action-spacer"></span>
            <button
                type="button"
                id="categoryLinkTestCancel"
                class="wp-button wp-button--secondary dialog-button dialog-button-secondary"
            >Cancel testing</button>
        </div>
    </div>
</div>
