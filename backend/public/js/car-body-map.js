(function () {
    'use strict';

    var STATUS_COLORS = {
        original: '#e2e8f0',
        local_paint: '#f97316',
        painted: '#3b82f6',
        replaced: '#ef4444'
    };

    function parseJson(value, fallback) {
        if (!value) {
            return fallback;
        }
        try {
            return JSON.parse(value);
        } catch (e) {
            return fallback;
        }
    }

    function partLabel(labels, partId) {
        var part = labels.parts && labels.parts[partId];
        return part || partId;
    }

    function statusLabel(labels, status) {
        return (labels.statuses && labels.statuses[status]) || status;
    }

    function buildSummary(parts, labels) {
        var order = ['replaced', 'painted', 'local_paint'];
        var groups = { replaced: [], painted: [], local_paint: [], original: [] };
        var partIds = Object.keys(parts);

        partIds.forEach(function (partId) {
            var status = parts[partId] || 'original';
            if (groups[status]) {
                groups[status].push(partId);
            }
        });

        var hasDamage = groups.replaced.length || groups.painted.length || groups.local_paint.length;
        if (!hasDamage) {
            return labels.allOriginalSummary || '';
        }

        var lines = [];
        order.forEach(function (status) {
            if (!groups[status].length) {
                return;
            }
            var names = groups[status].map(function (partId) {
                return partLabel(labels, partId);
            });
            var title = (labels.groupTitles && labels.groupTitles[status]) || status;
            lines.push(title + ': ' + names.join(labels.listSeparator || '، '));
        });

        return lines.join('\n');
    }

    function countNonOriginal(parts) {
        var count = 0;
        Object.keys(parts).forEach(function (partId) {
            if (parts[partId] !== 'original') {
                count++;
            }
        });
        return count;
    }

    function CarBodyMapWidget(root) {
        this.root = root;
        this.fieldId = root.getAttribute('data-field-id');
        this.namePrefix = root.getAttribute('data-name-prefix') || ('custom_fields[' + this.fieldId + ']');
        this.labels = parseJson(root.getAttribute('data-labels'), {});
        this.parts = parseJson(root.getAttribute('data-initial-parts'), {});
        this.menu = root.querySelector('[data-car-body-menu]');
        this.summaryEl = root.querySelector('[data-car-body-summary]');
        this.progressEl = root.querySelector('[data-car-body-progress]');
        this.allOriginalCheckbox = root.querySelector('[data-car-body-all-original]');
        this.hiddenContainer = root.querySelector('[data-car-body-hidden]');
        this.activePartId = null;
        this.bindEvents();
        this.render();
    }

    CarBodyMapWidget.prototype.bindEvents = function () {
        var self = this;

        this.root.querySelectorAll('[data-part-id]').forEach(function (el) {
            el.addEventListener('click', function (event) {
                event.stopPropagation();
                self.openMenu(el.getAttribute('data-part-id'), el, event);
            });
        });

        if (this.menu) {
            this.menu.querySelectorAll('[data-status]').forEach(function (btn) {
                btn.addEventListener('click', function (event) {
                    event.stopPropagation();
                    if (!self.activePartId) {
                        return;
                    }
                    self.setPartStatus(self.activePartId, btn.getAttribute('data-status'));
                    self.closeMenu();
                });
            });
        }

        document.addEventListener('click', function () {
            self.closeMenu();
        });

        if (this.allOriginalCheckbox) {
            this.allOriginalCheckbox.addEventListener('change', function () {
                if (self.allOriginalCheckbox.checked) {
                    Object.keys(self.parts).forEach(function (partId) {
                        self.parts[partId] = 'original';
                    });
                    self.render();
                }
            });
        }
    };

    CarBodyMapWidget.prototype.openMenu = function (partId, anchor, event) {
        if (!this.menu) {
            return;
        }
        this.activePartId = partId;
        var rect = anchor.getBoundingClientRect();
        var rootRect = this.root.getBoundingClientRect();
        this.menu.style.left = (rect.left - rootRect.left + rect.width / 2) + 'px';
        this.menu.style.top = (rect.top - rootRect.top + rect.height / 2) + 'px';
        this.menu.classList.remove('hidden');
    };

    CarBodyMapWidget.prototype.closeMenu = function () {
        if (this.menu) {
            this.menu.classList.add('hidden');
        }
        this.activePartId = null;
    };

    CarBodyMapWidget.prototype.setPartStatus = function (partId, status) {
        if (!STATUS_COLORS[status]) {
            return;
        }
        this.parts[partId] = status;
        if (this.allOriginalCheckbox) {
            this.allOriginalCheckbox.checked = false;
        }
        this.render();
    };

    CarBodyMapWidget.prototype.render = function () {
        var self = this;
        var total = Object.keys(this.parts).length;
        var changed = countNonOriginal(this.parts);
        var allOriginal = changed === 0;

        this.root.querySelectorAll('[data-part-id]').forEach(function (el) {
            var partId = el.getAttribute('data-part-id');
            var status = self.parts[partId] || 'original';
            var color = STATUS_COLORS[status] || STATUS_COLORS.original;
            var isOriginal = status === 'original';

            el.setAttribute('fill', color);
            el.setAttribute('fill-opacity', isOriginal ? '0' : '0.55');
            el.setAttribute('stroke', isOriginal ? 'transparent' : color);
            el.setAttribute('stroke-width', '1');
            el.setAttribute('data-status', status);
        });

        if (this.progressEl) {
            var template = this.labels.progressTemplate || ':count/:total';
            this.progressEl.textContent = template
                .replace(':count', String(changed))
                .replace(':total', String(total));
        }

        if (this.summaryEl) {
            this.summaryEl.textContent = buildSummary(this.parts, this.labels);
        }

        if (this.allOriginalCheckbox) {
            this.allOriginalCheckbox.checked = allOriginal;
        }

        this.syncHiddenInputs(allOriginal);
    };

    CarBodyMapWidget.prototype.syncHiddenInputs = function (allOriginal) {
        if (!this.hiddenContainer) {
            return;
        }

        this.hiddenContainer.innerHTML = '';
        var fieldPrefix = this.namePrefix;

        Object.keys(this.parts).forEach(function (partId) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = fieldPrefix + '[parts][' + partId + ']';
            input.value = this.parts[partId] || 'original';
            this.hiddenContainer.appendChild(input);
        }, this);

        var allOriginalInput = document.createElement('input');
        allOriginalInput.type = 'hidden';
        allOriginalInput.name = fieldPrefix + '[all_original]';
        allOriginalInput.value = allOriginal ? '1' : '0';
        this.hiddenContainer.appendChild(allOriginalInput);

        var summary = buildSummary(this.parts, this.labels);
        ['ar', 'en', 'tr'].forEach(function (locale) {
            var summaryInput = document.createElement('input');
            summaryInput.type = 'hidden';
            summaryInput.name = fieldPrefix + '[summary][' + locale + ']';
            summaryInput.value = summary;
            this.hiddenContainer.appendChild(summaryInput);
        }, this);
    };

    function initCarBodyMaps() {
        document.querySelectorAll('[data-car-body-map]').forEach(function (root) {
            if (root._carBodyMapInitialized) {
                return;
            }
            root._carBodyMapInitialized = true;
            new CarBodyMapWidget(root);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCarBodyMaps);
    } else {
        initCarBodyMaps();
    }

    window.initCarBodyMaps = initCarBodyMaps;
})();
