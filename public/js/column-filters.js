/**
 * column-filters.js - Filtros tipo Excel para tablas HTML
 * 
 * Proporciona filtros desplegables por columna (icono ▼ en cabecera)
 * que permiten seleccionar/deseleccionar valores como en Excel.
 * 
 * Uso:
 *   1. Añadir data-filterable="true" a los <th> que quieras filtrar
 *   2. Llamar: window.ColumnFilters.init('#idDeLaTabla')
 * 
 * Dependencias: jQuery 3.x, Bootstrap 5.x
 */

(function() {
    'use strict';

    // ---- Configuración por defecto ----
    var defaults = {
        filterIcon: '<i class="bi bi-funnel" style="font-size:0.75rem; margin-left:4px; cursor:pointer; opacity:0.5; transition:opacity 0.2s;"></i>',
        filterIconActive: '<i class="bi bi-funnel-fill" style="font-size:0.75rem; margin-left:4px; cursor:pointer; opacity:1; color:#0d6efd; transition:opacity 0.2s;"></i>',
        filterMenuClass: 'col-filter-dropdown',
        filterActiveClass: 'col-filter-active',
        checkboxClass: 'col-filter-checkbox',
        searchInputClass: 'col-filter-search',
        zIndex: 1060
    };

    // ---- Estado interno ----
    var state = {
        table: null,
        tableId: null,
        filterStates: {},   // { columnIndex: { values: Set of selected values } }
        uniqueValues: {},   // { columnIndex: [] }
        menus: {},           // { columnIndex: DOM element reference }
        iconActive: {},      // { columnIndex: boolean } - estado del icono separado
        _closeHandler: null  // Referencia al closeHandler actual para removerlo
    };

    // ---- Utilidades ----
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&')
            .replace(/</g, '<')
            .replace(/>/g, '>')
            .replace(/"/g, '"')
            .replace(/'/g, '&#039;');
    }

    function getCellText(cell) {
        if (!cell) return '';
        var input = cell.querySelector('input, select');
        if (input) return (input.value || '').trim();
        return (cell.textContent || '').trim();
    }

    function getUniqueValuesFromColumn(columnIndex, table) {
        var values = new Set();
        var tbody = table.querySelector('tbody');
        if (!tbody) return values;

        var rows = tbody.querySelectorAll('tr');
        rows.forEach(function(row) {
            var cell = row.querySelectorAll('td')[columnIndex];
            if (cell) {
                var text = getCellText(cell);
                if (text !== '') {
                    values.add(text);
                }
            }
        });

        var sorted = Array.from(values).sort(function(a, b) {
            return a.localeCompare(b, 'es', { numeric: true });
        });
        return sorted;
    }

    function getFilterableColumns(table) {
        var columns = [];
        var thead = table.querySelector('thead');
        if (!thead) return columns;

        var headerRow = thead.querySelector('tr');
        if (!headerRow) return columns;

        var ths = headerRow.querySelectorAll('th');
        ths.forEach(function(th, index) {
            if (th.getAttribute('data-filterable') === 'true') {
                columns.push({
                    index: index,
                    element: th,
                    text: (th.textContent || '').trim().replace(/▼/g, '').replace(/[▼▲]/g, '').trim()
                });
            }
        });

        return columns;
    }

    // ---- Renderizar icono de filtro en cabecera ----
    function renderFilterIcons(table) {
        var columns = getFilterableColumns(table);

        columns.forEach(function(col) {
            var existing = col.element.querySelector('.col-filter-trigger');
            if (existing) existing.remove();

            var trigger = document.createElement('span');
            trigger.className = 'col-filter-trigger';
            trigger.setAttribute('data-column-index', col.index);
            trigger.innerHTML = defaults.filterIcon;
            trigger.style.cssText = 'display:inline-flex; align-items:center; user-select:none;';

            // Verificar icono activo desde iconActive (separado de filterStates)
            var isActive = state.iconActive[col.index] || false;
            if (isActive) {
                trigger.innerHTML = defaults.filterIconActive;
                trigger.classList.add(defaults.filterActiveClass);
            }

            col.element.style.cursor = 'pointer';
            col.element.appendChild(trigger);

            // Click en el th también abre el menú
            col.element.addEventListener('click', function(e) {
                if (e.target.closest('.col-filter-trigger') || e.target.closest('th')) {
                    e.stopPropagation();
                    e.preventDefault();
                    toggleFilterMenu(col.index, table);
                }
            });
        });
    }

    // ---- Construir menú de filtro ----
    function buildFilterMenuHTML(columnIndex, values, table) {
        var currentState = state.filterStates[columnIndex] || { values: new Set(values), active: false };
        var selectedValues = currentState.values || new Set(values);
        var allSelected = values.every(function(v) { return selectedValues.has(v); });

        var html = '<div class="' + defaults.filterMenuClass + '" data-column="' + columnIndex + '" style="position:fixed; z-index:' + defaults.zIndex + '; background:white; border:1px solid #ccc; border-radius:6px; box-shadow:0 4px 16px rgba(0,0,0,0.15); padding:8px; min-width:220px; max-width:350px; max-height:360px; display:none; font-size:13px;">';

        // Buscar
        html += '<div style="position:relative; margin-bottom:6px;">';
        html += '<input type="text" class="' + defaults.searchInputClass + '" placeholder="Buscar..." style="width:100%; padding:5px 8px; border:1px solid #ddd; border-radius:4px; font-size:13px; box-sizing:border-box;">';
        html += '</div>';

        // Botones rápidos
        html += '<div style="display:flex; gap:4px; margin-bottom:6px;">';
        html += '<button type="button" class="btn btn-sm btn-outline-secondary col-filter-select-all" style="font-size:11px; padding:2px 6px; flex:1;" data-column="' + columnIndex + '">Todo</button>';
        html += '<button type="button" class="btn btn-sm btn-outline-secondary col-filter-clear" style="font-size:11px; padding:2px 6px; flex:1;" data-column="' + columnIndex + '">Limpiar</button>';
        html += '</div>';

        // Lista de valores con checkboxes
        html += '<div class="col-filter-values-container" style="max-height:180px; overflow-y:auto; border-top:1px solid #eee; padding-top:4px;">';
        values.forEach(function(val) {
            var checked = selectedValues.has(val) ? 'checked' : '';
            var displayVal = val.length > 60 ? val.substring(0, 57) + '...' : val;
            html += '<label style="display:flex; align-items:center; gap:6px; padding:2px 4px; cursor:pointer; border-radius:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="' + escapeHtml(val) + '">';
            html += '<input type="checkbox" class="' + defaults.checkboxClass + '" value="' + escapeHtml(val) + '" ' + checked + ' data-column="' + columnIndex + '" style="margin:0;">';
            html += '<span style="overflow:hidden; text-overflow:ellipsis;">' + escapeHtml(displayVal) + '</span>';
            html += '</label>';
        });
        html += '</div>';

        // Footer con botones Confirmar / Cancelar
        html += '<div style="border-top:1px solid #eee; padding-top:6px; margin-top:4px; display:flex; gap:4px; justify-content:space-between; align-items:center;">';
        html += '<span style="font-size:11px; color:#888;">' + values.length + ' valores</span>';
        html += '<div style="display:flex; gap:4px;">';
        html += '<button type="button" class="btn btn-sm btn-secondary col-filter-cancel" style="font-size:11px; padding:3px 10px;" data-column="' + columnIndex + '">Cancelar</button>';
        html += '<button type="button" class="btn btn-sm btn-primary col-filter-apply" style="font-size:11px; padding:3px 10px;" data-column="' + columnIndex + '">Aplicar</button>';
        html += '</div></div>';

        html += '</div>';
        return html;
    }

    // ---- Mostrar/Ocultar menú de filtro ----
    function toggleFilterMenu(columnIndex, table) {
        // Si el menú de esta columna ya está visible, no hacer nada
        var existingMenu = state.menus[columnIndex];
        if (existingMenu && existingMenu.parentNode && existingMenu.style.display !== 'none') {
            return;
        }

        // Cerrar cualquier otro menú abierto
        closeAllMenus();

        if (existingMenu && existingMenu.parentNode) {
            existingMenu.style.display = 'block';
            positionMenu(existingMenu, columnIndex, table);
            return;
        }

        var values = state.uniqueValues[columnIndex];
        if (!values) {
            values = getUniqueValuesFromColumn(columnIndex, table);
            state.uniqueValues[columnIndex] = values;
        }

        if (values.length === 0) return;

        var html = buildFilterMenuHTML(columnIndex, values, table);
        var wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        var menu = wrapper.firstElementChild;

        document.body.appendChild(menu);
        state.menus[columnIndex] = menu;

        positionMenu(menu, columnIndex, table);
        menu.style.display = 'block';

        attachMenuEvents(menu, columnIndex, table);
    }

    function positionMenu(menu, columnIndex, table) {
        var columns = getFilterableColumns(table);
        var col = columns.find(function(c) { return c.index === columnIndex; });
        if (!col || !col.element) return;

        var rect = col.element.getBoundingClientRect();
        var menuWidth = menu.offsetWidth || 250;
        var left = rect.left;
        var right = left + menuWidth;

        if (right > window.innerWidth - 10) {
            left = window.innerWidth - menuWidth - 10;
        }
        if (left < 10) left = 10;

        menu.style.left = left + 'px';
        menu.style.top = (rect.bottom + 4) + 'px';
    }

    function closeAllMenus() {
        for (var key in state.menus) {
            if (state.menus.hasOwnProperty(key)) {
                var menu = state.menus[key];
                if (menu && menu.parentNode) {
                    menu.style.display = 'none';
                }
            }
        }
    }

    function attachMenuEvents(menu, columnIndex, table) {
        // Búsqueda
        var searchInput = menu.querySelector('.' + defaults.searchInputClass);
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                filterMenuItems(menu, this.value);
            });
            setTimeout(function() { searchInput.focus(); }, 50);
        }

        // Checkboxes - actualizar icono visual, SIN cerrar menú
        var checkboxes = menu.querySelectorAll('.' + defaults.checkboxClass);
        checkboxes.forEach(function(cb) {
            // Detener propagación de TODOS los eventos para evitar cierre del menú
            ['mousedown','mouseup','click','change'].forEach(function(evt) {
                cb.addEventListener(evt, function(e) {
                    if (evt === 'change') {
                        updateFilterIcon(columnIndex, table);
                    }
                    e.stopPropagation();
                });
            });
        });

        // Botón "Seleccionar todos" - solo marca visualmente los checkboxes
        var selectAllBtn = menu.querySelector('.col-filter-select-all');
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                e.stopImmediatePropagation();
                e.preventDefault();
                var cbs = menu.querySelectorAll('.' + defaults.checkboxClass);
                cbs.forEach(function(cb) { cb.checked = true; });
            });
        }

        // Botón "Limpiar" - solo desmarca visualmente los checkboxes
        var clearBtn = menu.querySelector('.col-filter-clear');
        if (clearBtn) {
            clearBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                e.stopImmediatePropagation();
                e.preventDefault();
                var cbs = menu.querySelectorAll('.' + defaults.checkboxClass);
                cbs.forEach(function(cb) { cb.checked = false; });
            });
        }

        // Botón "Aplicar" - notifica al servidor
        var applyBtn = menu.querySelector('.col-filter-apply');
        if (applyBtn) {
            applyBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                // Leer valores seleccionados directamente del menú
                var cbs = menu.querySelectorAll('.' + defaults.checkboxClass);
                var selectedValues = [];
                cbs.forEach(function(cb) {
                    if (cb.checked) selectedValues.push(cb.value);
                });
                var isActive = (selectedValues.length > 0 && selectedValues.length < cbs.length);
                // Guardar selección para que el menú muestre items marcados/desmarcados
                if (!state.filterStates[columnIndex]) {
                    state.filterStates[columnIndex] = { values: new Set(), active: false };
                }
                state.filterStates[columnIndex].values = new Set(selectedValues);
                state.filterStates[columnIndex].active = false; // No activar filtro local
                // Actualizar icono por separado
                state.iconActive[columnIndex] = isActive;
                updateFilterIcon(columnIndex, table);
                // Notificar a los callbacks con TODOS los filtros activos acumulados
                var filterData = {};
                for (var ci in state.iconActive) {
                    if (state.iconActive.hasOwnProperty(ci) && state.iconActive[ci]) {
                        var fs = state.filterStates[ci];
                        if (fs && fs.values && fs.values.size > 0) {
                            filterData[ci] = Array.from(fs.values);
                        }
                    }
                }
                _filterChangeCallbacks.forEach(function(cb) {
                    try { cb(filterData); } catch(e) { console.warn('[ColumnFilters] Error en callback:', e); }
                });
                closeAllMenus();
            });
        }

        // Botón "Cancelar" - restaura valores anteriores y cierra
        var cancelBtn = menu.querySelector('.col-filter-cancel');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function(e) {
                e.preventDefault();
                // Restaurar valores originales del estado actual
                var prevState = state._prevFilterStates && state._prevFilterStates[columnIndex];
                if (prevState && state.filterStates[columnIndex]) {
                    // Re-aplicar el estado anterior
                    var prevValues = prevState.values || new Set();
                    state.filterStates[columnIndex].values = prevValues;
                    state.filterStates[columnIndex].active = prevState.active;
                    // Actualizar checkboxes en el menú
                    var cbs = menu.querySelectorAll('.' + defaults.checkboxClass);
                    cbs.forEach(function(cb) {
                        cb.checked = prevValues.has(cb.value);
                    });
                    applyColumnFilter(columnIndex, table);
                }
                closeAllMenus();
            });
        }

        // Guardar estado anterior al abrir el menú (para poder cancelar)
        if (!state._prevFilterStates) state._prevFilterStates = {};
        state._prevFilterStates[columnIndex] = {
            values: state.filterStates[columnIndex] ? new Set(state.filterStates[columnIndex].values) : new Set(),
            active: state.filterStates[columnIndex] ? state.filterStates[columnIndex].active : false
        };

        // Evitar cierre al hacer clic dentro del menú
        menu.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Cerrar al hacer clic fuera
        setTimeout(function() {
            // Remover el closeHandler anterior si existe
            if (state._closeHandler) {
                document.removeEventListener('click', state._closeHandler, true);
            }
            function closeHandler(e) {
                if (!menu.contains(e.target) && !e.target.closest('.col-filter-trigger')) {
                    closeAllMenus();
                    document.removeEventListener('click', closeHandler, true);
                    state._closeHandler = null;
                }
            }
            state._closeHandler = closeHandler;
            document.addEventListener('click', closeHandler, true);
        }, 10);
    }

    function filterMenuItems(menu, searchTerm) {
        var container = menu.querySelector('.col-filter-values-container');
        if (!container) return;

        var labels = container.querySelectorAll('label');
        var term = searchTerm.toLowerCase().trim();

        labels.forEach(function(label) {
            var text = (label.textContent || '').toLowerCase();
            if (!term || text.indexOf(term) !== -1) {
                label.style.display = '';
            } else {
                label.style.display = 'none';
            }
        });
    }

    // ---- Aplicar filtro de columna ----
    function applyColumnFilter(columnIndex, table) {
        var menu = state.menus[columnIndex];
        if (!menu) return;

        var checkboxes = menu.querySelectorAll('.' + defaults.checkboxClass);
        var selectedValues = new Set();
        checkboxes.forEach(function(cb) {
            if (cb.checked) {
                selectedValues.add(cb.value);
            }
        });

        if (!state.filterStates[columnIndex]) {
            state.filterStates[columnIndex] = { values: new Set(), active: false };
        }
        state.filterStates[columnIndex].values = selectedValues;
        state.filterStates[columnIndex].active = (selectedValues.size > 0 && selectedValues.size < checkboxes.length);

        updateFilterIcon(columnIndex, table);
        applyAllFilters(table);
    }

    function updateFilterIcon(columnIndex, table) {
        var trigger = table.querySelector('.col-filter-trigger[data-column-index="' + columnIndex + '"]');
        if (!trigger) return;

        var isActive = state.iconActive[columnIndex] || false;
        if (isActive) {
            trigger.innerHTML = defaults.filterIconActive;
            trigger.classList.add(defaults.filterActiveClass);
        } else {
            trigger.innerHTML = defaults.filterIcon;
            trigger.classList.remove(defaults.filterActiveClass);
        }
    }

    function applyAllFilters(table) {
        var tbody = table.querySelector('tbody');
        if (!tbody) return;

        var rows = tbody.querySelectorAll('tr');

        var hasActiveFilter = false;
        for (var key in state.filterStates) {
            if (state.filterStates.hasOwnProperty(key) && state.filterStates[key].active) {
                hasActiveFilter = true;
                break;
            }
        }

        if (!hasActiveFilter) {
            rows.forEach(function(row) { row.style.display = ''; });
            updateVisibleCount(table, rows.length);
            return;
        }

        var visibleCount = 0;
        rows.forEach(function(row) {
            var show = true;
            for (var colIdx in state.filterStates) {
                if (!state.filterStates.hasOwnProperty(colIdx)) continue;
                var colState = state.filterStates[colIdx];
                if (!colState.active) continue;

                var cell = row.querySelectorAll('td')[parseInt(colIdx)];
                if (!cell) { show = false; break; }

                var cellText = getCellText(cell);
                if (!colState.values.has(cellText)) {
                    show = false;
                    break;
                }
            }
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        updateVisibleCount(table, visibleCount);
    }

    function updateVisibleCount(table, count) {
        var counter = table.parentNode.querySelector('.col-filter-counter');
        if (counter) {
            // Usar totalRegistros del data attribute (total real de la BD) o fallback al DOM
            var total = parseInt(table.getAttribute('data-total-registros')) || table.querySelectorAll('tbody tr').length;
            counter.textContent = count + ' de ' + total + ' registros';
        }
    }

    function refreshUniqueValues(table) {
        // Si hay valores únicos del servidor, no sobrescribir con DOM
        if (state._serverValuesSet) return;
        var columns = getFilterableColumns(table);
        columns.forEach(function(col) {
            state.uniqueValues[col.index] = getUniqueValuesFromColumn(col.index, table);
        });
    }

    // ---- Callbacks para filtros cambiados ----
    var _filterChangeCallbacks = [];

    function notifyFilterChange() {
        var activeFilters = getActiveFiltersStruct();
        _filterChangeCallbacks.forEach(function(cb) {
            try { cb(activeFilters); } catch(e) { console.warn('[ColumnFilters] Error en callback:', e); }
        });
    }

    function getActiveFiltersStruct() {
        var result = {};
        for (var colIdx in state.filterStates) {
            if (!state.filterStates.hasOwnProperty(colIdx)) continue;
            var colState = state.filterStates[colIdx];
            if (colState.active && colState.values && colState.values.size > 0) {
                result[colIdx] = Array.from(colState.values);
            }
        }
        return result;
    }

    // ---- API Pública ----
    window.ColumnFilters = {
        init: function(tableSelector) {
            var table = null;
            if (typeof tableSelector === 'string') {
                table = document.querySelector(tableSelector);
            } else if (tableSelector && tableSelector.tagName) {
                table = tableSelector;
            }

            if (!table || table.tagName !== 'TABLE') {
                console.warn('[ColumnFilters] No se encontró tabla con selector:', tableSelector);
                return;
            }

            state.table = table;
            state.tableId = table.id || 'table-' + Date.now();
            state.filterStates = {};
            state.uniqueValues = {};
            state.menus = {};
            state._serverValuesSet = false;

            refreshUniqueValues(table);
            renderFilterIcons(table);

            // Agregar contador de registros y botón de limpiar filtros
            var existingCounter = table.parentNode.querySelector('.col-filter-counter');
            if (!existingCounter) {
                var toolbar = document.createElement('div');
                toolbar.style.cssText = 'display:flex; justify-content:space-between; align-items:center; margin-top:4px;';
                
                var counter = document.createElement('span');
                counter.className = 'col-filter-counter';
                counter.style.cssText = 'font-size:12px; color:#888;';
                var totalRows = table.querySelectorAll('tbody tr').length;
                counter.textContent = totalRows + ' de ' + totalRows + ' registros';
                toolbar.appendChild(counter);
                
                var clearBtn = document.createElement('button');
                clearBtn.type = 'button';
                clearBtn.className = 'btn btn-sm btn-outline-secondary';
                clearBtn.textContent = 'Limpiar filtros';
                clearBtn.style.cssText = 'font-size:11px; padding:2px 8px;';
                clearBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.ColumnFilters.clearAllFilters();
                    // También notificar cambio para recargar
                    _filterChangeCallbacks.forEach(function(cb) {
                        try { cb({}); } catch(e) {}
                    });
                });
                toolbar.appendChild(clearBtn);
                
                table.parentNode.appendChild(toolbar);
            }

            console.log('[ColumnFilters] Filtros inicializados en #' + (table.id || 'sin-id'));
        },

        refresh: function() {
            if (!state.table) return;
            for (var key in state.menus) {
                if (state.menus.hasOwnProperty(key)) {
                    var menu = state.menus[key];
                    if (menu && menu.parentNode) menu.parentNode.removeChild(menu);
                }
            }
            state.menus = {};

            refreshUniqueValues(state.table);
            renderFilterIcons(state.table);
            applyAllFilters(state.table);
        },

        clearAllFilters: function() {
            for (var key in state.filterStates) {
                if (state.filterStates.hasOwnProperty(key)) {
                    state.filterStates[key].active = false;
                    state.filterStates[key].values = new Set();
                }
            }
            state.iconActive = {};
            if (state.table) {
                var triggers = state.table.querySelectorAll('.col-filter-trigger');
                triggers.forEach(function(t) {
                    t.innerHTML = defaults.filterIcon;
                    t.classList.remove(defaults.filterActiveClass);
                });
                applyAllFilters(state.table);
                notifyFilterChange();
            }
        },

        getFilterStates: function() {
            var result = {};
            for (var key in state.filterStates) {
                if (state.filterStates.hasOwnProperty(key)) {
                    var s = state.filterStates[key];
                    result[key] = {
                        active: s.active,
                        values: s.values ? Array.from(s.values) : []
                    };
                }
            }
            return result;
        },

        /**
         * Obtiene solo los filtros activos como { columnIndex: [selectedValues...] }
         */
        getActiveFilters: function() {
            return getActiveFiltersStruct();
        },

        /**
         * Registra un callback que se ejecuta cada vez que cambia un filtro de columna.
         * El callback recibe un argumento { columnIndex: [selectedValues...] }
         */
        onFilterChange: function(callback) {
            if (typeof callback === 'function') {
                _filterChangeCallbacks.push(callback);
            }
        },

        /**
         * Establece valores únicos desde el servidor para que los menús de filtro
         * muestren todos los valores disponibles (no solo los de la página actual).
         * Recibe { "NombreCampo": ["valor1", "valor2", ...] }
         */
        setServerUniqueValues: function(serverValues) {
            if (!state.table || !serverValues) return;
            state._serverValuesSet = true;
            // Mapear field names a column indices usando data-field
            var columns = getFilterableColumns(state.table);
            columns.forEach(function(col) {
                var field = col.element.getAttribute('data-field');
                if (field && serverValues[field] && Array.isArray(serverValues[field])) {
                    var vals = serverValues[field].slice().map(String);
                    // Formatear NVale a 6 dígitos (JSON_NUMERIC_CHECK elimina ceros izq.)
                    if (field === 'NVale') {
                        vals = vals.map(function(v) {
                            var digits = v.replace(/\D/g, '');
                            return digits ? digits.padStart(6, '0') : v;
                        });
                    }
                    state.uniqueValues[col.index] = vals.sort(function(a, b) {
                        return a.localeCompare(b, 'es', { numeric: true });
                    });
                }
            });
        },

        destroy: function() {
            closeAllMenus();
            for (var key in state.menus) {
                if (state.menus.hasOwnProperty(key)) {
                    var menu = state.menus[key];
                    if (menu && menu.parentNode) menu.parentNode.removeChild(menu);
                }
            }
            state = {
                table: null,
                tableId: null,
                filterStates: {},
                uniqueValues: {},
                menus: {}
            };
        }
    };

    // ---- Asegurar que printPreview.js esté cargado ----
    window._asegurarPrintPreview = function() {
        return new Promise(function(resolve) {
            // printPreview.js expone "printPreviewDE", no "printPreview"
            // Hacemos el mapeo para que los reportes puedan usar window.printPreview
            if (window.printPreviewDE && typeof window.printPreviewDE.showModalPreview === 'function') {
                window.printPreview = window.printPreviewDE;
                resolve();
                return;
            }
            var s = document.createElement('script');
            s.src = (window.BASE_URL || '') + '/js/printPreview.js?v=' + Date.now();
            s.onload = function() {
                if (window.printPreviewDE) {
                    window.printPreview = window.printPreviewDE;
                }
                resolve();
            };
            s.onerror = resolve;
            document.head.appendChild(s);
        });
    };
    // Precargar
    window._asegurarPrintPreview();

    // ---- Menú contextual con position:fixed (evita Popper.js) ----
    function _ctxClose(menu) {
        menu.classList.remove('show', 'fixed-contextual');
        menu.style.display = '';
        menu.style.position = '';
        menu.style.top = '';
        menu.style.left = '';
        menu.style.width = '';
    }
    
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('#grillaReporte .action-btn');
        var insideMenu = e.target.closest('#grillaReporte .dropdown-menu');
        var insideDropdown = e.target.closest('#grillaReporte .dropdown');
        
        // Clic en botón de 3 puntos: toggle menú
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            
            var menu = btn.parentNode.querySelector('.dropdown-menu');
            if (!menu) return;
            
            var isOpen = menu.classList.contains('show') && menu.classList.contains('fixed-contextual');
            
            // Cerrar cualquier otro menú abierto
            document.querySelectorAll('#grillaReporte .dropdown-menu.fixed-contextual').forEach(function(m) {
                if (m !== menu) _ctxClose(m);
            });
            
            if (isOpen) {
                _ctxClose(menu);
            } else {
                var rect = btn.getBoundingClientRect();
                menu.style.position = 'fixed';
                menu.style.top = (rect.bottom + 2) + 'px';
                menu.style.left = rect.left + 'px';
                menu.style.width = '';
                menu.style.display = 'block';
                menu.classList.add('show', 'fixed-contextual');
            }
            return;
        }
        
        // Clic dentro del menú: DEJAR que el evento se propague (para "Ver detalle", etc.)
        if (insideMenu) return;
        
        // Clic fuera del dropdown completo: cerrar
        if (!insideDropdown) {
            document.querySelectorAll('#grillaReporte .dropdown-menu.fixed-contextual').forEach(_ctxClose);
        }
    });

})();
