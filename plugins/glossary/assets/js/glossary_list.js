/**
 * Glossary List - JSTable enhancement for the glossary item list
 *
 * Turns the shared item-list table into a sortable, instantly searchable, client-side paginated
 * table, which a glossary of several hundred phrases needs and the shared component does not
 * provide. Binds to the shared component's own container hook, so it only ever finds a table in
 * 'list' view mode and no-ops everywhere else - it is loaded site-wide with the plugin. A facet or
 * view-mode click re-renders the list body in place, so a MutationObserver rebinds the fresh table.
 * The same swap leaves the sidebar training box behind, so its train and export links are copied
 * from the [data-glossary-selection] hook the fresh list body carries.
 *
 * Loaded in:  base.html.twig via Plugin::getJavascripts() (all pages, active plugin only)
 * Used by:    [data-item-list="glossary"] table (plugins/glossary/templates/item/list_body.html.twig),
 *             [data-glossary-training] box (plugins/glossary/templates/item/_training_box.html.twig)
 * Depends on: js/vendor/jstable.min.js (JSTable), loaded by the glossary index page
 */

function glossaryListBind() {
    const table = document.querySelector('[data-item-list="glossary"] table');
    if (!table || typeof JSTable === 'undefined' || table.dataset.jstable === '1') {
        return;
    }

    table.dataset.jstable = '1';
    new JSTable(table, {
        sortable: true,
        searchable: true,
        perPage: 25,
        perPageSelect: [25, 50, 100, 250, 500, 2500]
    });
}

function glossaryTrainingSync() {
    const hook = document.querySelector('[data-item-list-scope="glossary"] [data-glossary-selection]');
    const box = document.querySelector('[data-glossary-training]');
    if (!hook || !box) {
        return;
    }

    const empty = hook.dataset.count === '0';
    const train = box.querySelector('[data-glossary-train]');
    if (train) {
        train.href = hook.dataset.trainHref;
        train.querySelector('[data-glossary-train-label]').textContent = hook.dataset.trainLabel;
        train.classList.toggle('is-hidden', empty);
    }

    const exportLink = box.querySelector('[data-glossary-export]');
    if (exportLink) {
        exportLink.href = hook.dataset.exportHref;
        exportLink.classList.toggle('is-hidden', empty);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    glossaryListBind();

    const region = document.querySelector('[data-item-list-scope="glossary"] [data-item-list-body]');
    if (region) {
        new MutationObserver(function () {
            glossaryListBind();
            glossaryTrainingSync();
        }).observe(region, {childList: true});
    }
});
