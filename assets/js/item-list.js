/**
 * Item List -- facet and view-mode clicks re-render the list region instead of the whole page
 *
 * Intercepts every [data-item-facet] link inside an item-list layout, fetches the core fragment
 * route for the same selection and writes the returned filter box and list body back into their
 * hooks, then pushes the link's own URL onto the history. Every link stays a real GET to a real
 * page, so a rejected request, a missing hook or disabled JavaScript falls back to a normal
 * navigation. Also owns the two progressive-enhancement collapses of the filter box - the mobile
 * "Filters" bar and the "Show all" chip trigger - which the markup leaves expanded so they never
 * hide anything without JavaScript. Once the visitor has opened the chip list it stays open across
 * every following swap, so narrowing by a chip below the fold does not fold it away again.
 *
 * Loaded in:  templates/base.html.twig (all pages)
 * Used by:    [data-item-list-scope] (templates/_components/item/list_layout.html.twig),
 *             [data-item-facet] chips and view-switcher buttons,
 *             [data-item-filter] / [data-item-list-body] swap regions
 * Depends on: ma-fetch.js (maFetch)
 */

function itemListScopeOf(element) {
    return element.closest('[data-item-list-scope]');
}

function itemListChipsExpanded(scope) {
    const extra = scope.querySelector('.item-facet-extra');

    return extra !== null && !extra.classList.contains('is-hidden');
}

function itemListInitFilter(scope, keepExpanded) {
    const toggle = scope.querySelector('[data-item-facet-toggle]');
    const panel = scope.querySelector('[data-item-facet-panel]');
    if (toggle && panel) {
        toggle.classList.remove('is-hidden');
        panel.classList.add('is-hidden-mobile');
    }

    if (keepExpanded) {
        return;
    }

    scope.querySelectorAll('[data-item-facet-more]').forEach((trigger) => {
        const axis = trigger.closest('.field');
        if (!axis) {
            return;
        }
        axis.querySelectorAll('.item-facet-extra').forEach((chip) => chip.classList.add('is-hidden'));
        trigger.classList.remove('is-hidden');
    });
}

function itemListApply(scope, payload) {
    const expanded = itemListChipsExpanded(scope);

    const filter = scope.querySelector('[data-item-filter]');
    if (filter && typeof payload.filter === 'string') {
        filter.innerHTML = payload.filter;
    }

    const body = scope.querySelector('[data-item-list-body]');
    if (body && typeof payload.body === 'string') {
        body.innerHTML = payload.body;
    }

    itemListInitFilter(scope, expanded);
}

function itemListMarkMode(link) {
    const mode = link.dataset.itemMode;
    if (!mode || !link.parentElement) {
        return;
    }

    link.parentElement.querySelectorAll('[data-item-mode]').forEach((sibling) => {
        sibling.classList.toggle('is-link', sibling === link);
    });
}

function itemListFragmentUrl(scope, link) {
    const mode = link.dataset.itemMode;
    const source = mode ? window.location.search : new URL(link.href, window.location.origin).search;
    const params = new URLSearchParams(source);
    if (mode) {
        params.set('mode', mode);
    }
    const query = params.toString();

    return scope.dataset.itemListFragment + (query ? '?' + query : '');
}

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }

    const link = event.target.closest('[data-item-facet]');
    if (!link || event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) {
        return;
    }

    const scope = itemListScopeOf(link);
    if (!scope || !scope.dataset.itemListFragment || scope.dataset.itemListBusy === '1') {
        return;
    }

    event.preventDefault();
    scope.dataset.itemListBusy = '1';
    scope.classList.add('item-list-busy');

    maFetch(itemListFragmentUrl(scope, link), true)
        .then((payload) => {
            itemListApply(scope, payload);
            itemListMarkMode(link);
            window.history.pushState({}, '', payload.url || link.href);
            scope.dataset.itemListBusy = '';
            scope.classList.remove('item-list-busy');
        })
        .catch(() => {
            window.location.href = link.href;
        });
});

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }

    const toggle = event.target.closest('[data-item-facet-toggle]');
    if (toggle) {
        const scope = itemListScopeOf(toggle);
        const panel = scope ? scope.querySelector('[data-item-facet-panel]') : null;
        if (panel) {
            panel.classList.toggle('is-hidden-mobile');
        }
        return;
    }

    const more = event.target.closest('[data-item-facet-more]');
    const axis = more ? more.closest('.field') : null;
    if (!axis) {
        return;
    }
    axis.querySelectorAll('.item-facet-extra').forEach((chip) => chip.classList.remove('is-hidden'));
    more.classList.add('is-hidden');
});

window.addEventListener('popstate', () => {
    const scope = document.querySelector('[data-item-list-scope]');
    if (!scope || !scope.dataset.itemListFragment) {
        return;
    }

    maFetch(scope.dataset.itemListFragment + window.location.search, true)
        .then((payload) => itemListApply(scope, payload))
        .catch(() => window.location.reload());
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-item-list-scope]').forEach((scope) => itemListInitFilter(scope));
});
