/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 *
 * Fills the Platform Overview panels.
 *
 * Every tab is fetched on its own, all at once: one unresponsive backend then
 * costs one slow panel instead of a page that will not paint. Content is built
 * with DOM nodes rather than innerHTML, because these strings are version
 * banners, queue names and error messages that came back from services outside
 * Magento and must never be parsed as markup.
 */
define([], function () {
    'use strict';

    var STATUS_LABEL = {
        ok: 'OK',
        info: '',
        warn: 'Attention',
        unavailable: 'Unavailable',
        error: 'Problem'
    };

    /**
     * @param {String} tag
     * @param {String} className
     * @param {String} [text]
     * @returns {HTMLElement}
     */
    function el(tag, className, text) {
        var node = document.createElement(tag);

        if (className) {
            node.className = className;
        }

        if (text !== undefined && text !== null) {
            node.textContent = String(text);
        }

        return node;
    }

    /**
     * @param {Object} row
     * @returns {HTMLElement}
     */
    function renderRow(row) {
        var item = el('div', 'magenx-platform-row _status-' + (row.status || 'info')),
            label = el('div', 'magenx-platform-row-label', row.label),
            value = el('div', 'magenx-platform-row-value', row.value);

        if (row.hint) {
            label.appendChild(el('span', 'magenx-platform-row-hint', row.hint));
        }

        item.appendChild(label);
        item.appendChild(value);

        return item;
    }

    /**
     * @param {Object} section
     * @returns {HTMLElement}
     */
    function renderSection(section) {
        var card = el('div', 'magenx-platform-card _status-' + (section.status || 'info'));

        card.appendChild(el('h3', 'magenx-platform-card-title', section.label));
        (section.rows || []).forEach(function (row) {
            card.appendChild(renderRow(row));
        });

        return card;
    }

    /**
     * @param {HTMLElement} panel
     * @param {Object} payload
     */
    function renderPanel(panel, payload) {
        var summary = el('div', 'magenx-platform-summary _status-' + (payload.status || 'info')),
            grid = el('div', 'magenx-platform-grid'),
            badge = STATUS_LABEL[payload.status] || '';

        panel.textContent = '';

        if (badge) {
            summary.appendChild(el('span', 'magenx-platform-badge', badge));
        }
        summary.appendChild(el('span', 'magenx-platform-summary-text', payload.summary || ''));
        panel.appendChild(summary);

        (payload.sections || []).forEach(function (section) {
            grid.appendChild(renderSection(section));
        });
        panel.appendChild(grid);
    }

    /**
     * @param {HTMLElement} root
     * @param {String} code
     * @param {String} status
     */
    function paintDot(root, code, status) {
        var tab = root.querySelector('.magenx-platform-tab[data-collector="' + code + '"] .magenx-platform-dot');

        if (tab) {
            tab.setAttribute('data-status', status);
        }
    }

    return function (config, element) {
        var root = element,
            meta = root.querySelector('[data-role="meta"]'),
            timer = null;

        /**
         * @param {Object} tab
         * @returns {Promise}
         */
        function load(tab) {
            var panel = root.querySelector('.magenx-platform-panel[data-panel="' + tab.code + '"]');

            paintDot(root, tab.code, 'pending');

            return fetch(tab.url, {
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                return response.json();
            }).then(function (payload) {
                renderPanel(panel, payload);
                paintDot(root, tab.code, payload.status || 'info');

                return payload;
            }).catch(function (error) {
                renderPanel(panel, {
                    status: 'unavailable',
                    summary: 'This tab could not be loaded: ' + error.message,
                    sections: []
                });
                paintDot(root, tab.code, 'unavailable');
            });
        }

        /**
         * Fires every tab at once — the whole point of splitting the endpoint.
         */
        function loadAll() {
            if (meta) {
                meta.textContent = 'Collecting…';
            }

            return Promise.all((config.tabs || []).map(load)).then(function () {
                if (meta) {
                    meta.textContent = 'Updated ' + new Date().toLocaleTimeString();
                }
            });
        }

        root.addEventListener('click', function (event) {
            var target = event.target,
                tabButton,
                refresh,
                code;

            if (!target || typeof target.closest !== 'function') {
                return;
            }

            tabButton = target.closest('.magenx-platform-tab');
            refresh = target.closest('[data-role="refresh"]');

            if (refresh) {
                loadAll();

                return;
            }

            if (!tabButton) {
                return;
            }

            code = tabButton.getAttribute('data-collector');

            Array.prototype.forEach.call(root.querySelectorAll('.magenx-platform-tab'), function (node) {
                var active = node === tabButton;

                node.classList.toggle('_active', active);
                node.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            Array.prototype.forEach.call(root.querySelectorAll('.magenx-platform-panel'), function (node) {
                node.classList.toggle('_active', node.getAttribute('data-panel') === code);
            });
        });

        loadAll();

        if (config.autoRefresh > 0) {
            timer = window.setInterval(loadAll, config.autoRefresh * 1000);
            window.addEventListener('beforeunload', function () {
                window.clearInterval(timer);
            });
        }
    };
});
