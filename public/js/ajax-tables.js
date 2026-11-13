/**
 * Universal AJAX Table, Partial Rendering, Skeleton Loading & Server-Side Pagination
 * LSP PrO Indo System
 */
(function () {
    'use strict';

    function getSkeletonHtml(container) {
        const cols = parseInt(container.getAttribute('data-skeleton-cols') || '5', 10);
        const rows = parseInt(container.getAttribute('data-skeleton-rows') || '6', 10);

        let cardsHtml = '';
        for (let i = 0; i < rows; i++) {
            cardsHtml += `
                <div class="p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center space-x-3 w-full">
                            <div class="w-9 h-9 rounded-full bg-slate-200 shrink-0"></div>
                            <div class="space-y-1.5 flex-1 min-w-0">
                                <div class="h-3.5 bg-slate-200 rounded w-3/4"></div>
                                <div class="h-2.5 bg-slate-100 rounded w-1/2"></div>
                            </div>
                        </div>
                        <div class="w-16 h-5 bg-slate-100 rounded shrink-0"></div>
                    </div>
                    <div class="space-y-1.5 pt-1">
                        <div class="h-2.5 bg-slate-100 rounded w-5/6"></div>
                        <div class="h-2.5 bg-slate-100 rounded w-2/3"></div>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                        <div class="w-20 h-3 bg-slate-100 rounded"></div>
                        <div class="flex gap-2">
                            <div class="w-14 h-6 bg-slate-200 rounded"></div>
                            <div class="w-14 h-6 bg-slate-200 rounded"></div>
                        </div>
                    </div>
                </div>
            `;
        }

        let tableHeaderCols = '';
        for (let c = 0; c < cols; c++) {
            tableHeaderCols += `<div class="h-3 bg-slate-200 rounded w-2/3"></div>`;
        }

        let tableRowsHtml = '';
        for (let r = 0; r < rows; r++) {
            let colsHtml = '';
            for (let c = 0; c < cols; c++) {
                colsHtml += `
                    <div class="space-y-1.5">
                        <div class="h-3.5 bg-slate-200 rounded ${c === 0 ? 'w-4/5' : (c === cols - 1 ? 'w-1/2 ml-auto' : 'w-3/5')}"></div>
                        ${c === 0 ? '<div class="h-2.5 bg-slate-100 rounded w-1/2"></div>' : ''}
                    </div>
                `;
            }
            tableRowsHtml += `
                <div class="px-6 py-4 grid gap-4 items-center" style="grid-template-columns: repeat(${cols}, minmax(0, 1fr));">
                    ${colsHtml}
                </div>
            `;
        }

        return `
            <div class="animate-pulse space-y-4" aria-busy="true" aria-label="Memuat data...">
                <div class="block md:hidden divide-y divide-slate-100 bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
                    ${cardsHtml}
                </div>
                <div class="hidden md:block bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
                    <div class="min-w-full">
                        <div class="bg-slate-50 border-b border-slate-200 px-6 py-3.5 grid gap-4 items-center" style="grid-template-columns: repeat(${cols}, minmax(0, 1fr));">
                            ${tableHeaderCols}
                        </div>
                        <div class="divide-y divide-slate-100">
                            ${tableRowsHtml}
                        </div>
                        <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                            <div class="h-3 bg-slate-200 rounded w-32"></div>
                            <div class="flex gap-1.5">
                                <div class="w-7 h-7 bg-slate-200 rounded"></div>
                                <div class="w-7 h-7 bg-slate-200 rounded"></div>
                                <div class="w-7 h-7 bg-slate-200 rounded"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    const activeControllers = {};

    function loadAjaxTable(targetContainer, url, updateHistory = true) {
        if (!targetContainer) return;
        const containerId = targetContainer.id || targetContainer.getAttribute('data-ajax-container');

        if (activeControllers[containerId]) {
            activeControllers[containerId].abort();
        }

        const controller = new AbortController();
        activeControllers[containerId] = controller;

        targetContainer.innerHTML = getSkeletonHtml(targetContainer);

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            },
            signal: controller.signal
        })
        .then(function (response) {
            if (!response.ok) throw new Error('Network response was not ok: ' + response.status);
            return response.text();
        })
        .then(function (html) {
            targetContainer.innerHTML = html;
            if (updateHistory && window.history && window.history.pushState) {
                window.history.pushState({ ajaxTable: true, containerId: containerId, url: url }, '', url);
            }
        })
        .catch(function (err) {
            if (err.name === 'AbortError') return;
            console.error('AJAX table load error:', err);
            window.location.href = url;
        })
        .finally(function () {
            delete activeControllers[containerId];
        });
    }

    // Intercept clicks on pagination links inside data-ajax-container or reset buttons
    document.addEventListener('click', function (e) {
        const link = e.target.closest('a');
        if (!link) return;

        // Check for reset buttons targeting a container
        const resetTarget = link.getAttribute('data-ajax-reset');
        if (resetTarget) {
            const target = document.getElementById(resetTarget) || document.querySelector(`[data-ajax-container="${resetTarget}"]`);
            if (target) {
                e.preventDefault();
                const form = document.querySelector(`form[data-ajax-filter="${resetTarget}"]`);
                if (form) {
                    form.querySelectorAll('input[type="text"], input[type="search"]').forEach(function (inp) {
                        inp.value = '';
                    });
                    form.querySelectorAll('select').forEach(function (sel) {
                        sel.selectedIndex = 0;
                    });
                }
                loadAjaxTable(target, link.href);
            }
            return;
        }

        // Check if inside an ajax table container
        const container = link.closest('[data-ajax-container]');
        if (!container) return;

        const isPagination = link.closest('.pagination') || 
                             link.classList.contains('page-link') || 
                             link.hasAttribute('data-ajax-page') ||
                             (link.href && (link.href.includes('page=') || link.href.includes('sort=')));

        if (isPagination && !link.hasAttribute('data-no-ajax')) {
            e.preventDefault();
            loadAjaxTable(container, link.href);
            container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });

    // Intercept form submissions for data-ajax-filter forms
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form || !form.hasAttribute('data-ajax-filter')) return;

        const targetId = form.getAttribute('data-ajax-filter');
        const targetContainer = document.getElementById(targetId) || document.querySelector(`[data-ajax-container="${targetId}"]`);
        if (!targetContainer) return;

        e.preventDefault();

        const formData = new FormData(form);
        const actionUrl = new URL(form.action || window.location.href, window.location.origin);

        for (const [key, value] of formData.entries()) {
            if (value !== '' && value !== null) {
                actionUrl.searchParams.set(key, value);
            } else {
                actionUrl.searchParams.delete(key);
            }
        }
        actionUrl.searchParams.delete('page');

        loadAjaxTable(targetContainer, actionUrl.toString());
    });

    // Debounced search on input & immediate on select change inside data-ajax-filter forms
    let debounceTimer = null;
    document.addEventListener('input', function (e) {
        const input = e.target;
        const form = input.closest('form[data-ajax-filter]');
        if (!form) return;
        if (input.type === 'text' || input.type === 'search') {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                form.requestSubmit();
            }, 350);
        }
    });

    document.addEventListener('change', function (e) {
        const select = e.target;
        const form = select.closest('form[data-ajax-filter]');
        if (!form) return;
        if (select.tagName === 'SELECT' || select.type === 'checkbox' || select.type === 'radio' || select.type === 'date') {
            form.requestSubmit();
        }
    });

    // Browser back/forward navigation support
    window.addEventListener('popstate', function () {
        document.querySelectorAll('[data-ajax-container]').forEach(function (container) {
            loadAjaxTable(container, window.location.href, false);
        });
    });

    // Expose utility globally
    window.LspAjaxTable = {
        load: loadAjaxTable
    };
})();
