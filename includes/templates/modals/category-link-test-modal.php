<!-- Category Link Test Modal -->
<div
    id="categoryLinkTestModal"
    class="modal-backdrop hidden fixed inset-0 flex items-center justify-center z-50"
    role="dialog"
    aria-modal="true"
    aria-labelledby="categoryLinkTestTitle"
    aria-describedby="categoryLinkTestSummary"
    data-dialog-dismiss="categoryLinkTestClose"
    data-dialog-backdrop-dismiss="false"
>
    <div class="modal-panel w-full max-w-2xl mx-4">
        <div class="dialog-header">
            <div class="min-w-0">
                <h3 id="categoryLinkTestTitle" class="dialog-title">Test category links</h3>
                <p id="categoryLinkTestCategory" class="link-test-category-name"></p>
            </div>
            <button
                type="button"
                id="categoryLinkTestClose"
                class="dialog-close-button"
                aria-label="Close link test results"
            >&times;</button>
        </div>

        <div class="dialog-body category-link-test-body">
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

        <div class="dialog-actions category-link-test-actions">
            <span class="dialog-action-spacer"></span>
            <button
                type="button"
                id="categoryLinkTestCancel"
                class="dialog-button dialog-button-secondary"
            >Cancel testing</button>
        </div>
    </div>
</div>
