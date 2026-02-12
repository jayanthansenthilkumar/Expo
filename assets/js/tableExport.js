/**
 * Table Export Utility – PDF & Excel + Column Filtering
 * Uses jsPDF + AutoTable for PDF, SheetJS for Excel
 */

/* ── Helper: extract clean text from a table cell ── */
function getCellText(cell) {
    if (!cell) return '';
    // If cell contains <li> elements, join each item with ", "
    const listItems = cell.querySelectorAll('li');
    if (listItems.length > 0) {
        return Array.from(listItems).map(li => li.textContent.trim().replace(/\s+/g, ' ')).join(', ');
    }
    // If cell contains <a> with href (links), prefer the text
    return cell.textContent.trim().replace(/\s+/g, ' ');
}

/* ── Collect visible table data for export ── */
function getTableData(tableEl) {
    const headers = [];
    const rows = [];
    const headerRow = tableEl.querySelector('thead tr:first-child');
    if (!headerRow) return { headers: [], rows: [] };
    const thElements = headerRow.querySelectorAll('th');

    thElements.forEach((th, idx) => {
        const text = th.textContent.trim();
        // Skip "Actions" columns
        if (text.toLowerCase() === 'actions' || text.toLowerCase() === 'action') return;
        headers.push({ text: text, index: idx });
    });

    const trElements = tableEl.querySelectorAll('tbody tr');
    trElements.forEach(tr => {
        // Skip empty-state rows
        if (tr.querySelector('.empty-table') || tr.querySelector('.empty-state')) return;
        // Skip hidden / filtered-out rows
        if (tr.style.display === 'none' || tr.classList.contains('filter-hidden')) return;
        const cells = tr.querySelectorAll('td');
        if (cells.length === 0) return;
        const row = [];
        headers.forEach(h => {
            row.push(getCellText(cells[h.index]));
        });
        rows.push(row);
    });

    return { headers: headers.map(h => h.text), rows: rows };
}

/* ── PDF Export ── */
function exportTableToPDF(tableEl, filename) {
    if (!tableEl) { alert('No table found to export.'); return; }
    const data = getTableData(tableEl);
    if (data.rows.length === 0) {
        Swal.fire({ icon: 'info', title: 'No Data', text: 'The table has no data to export.', confirmButtonColor: '#D97706' });
        return;
    }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'mm', 'a4'); // landscape for wide tables

    // Title
    doc.setFontSize(16);
    doc.setTextColor(37, 99, 235);
    doc.text(filename.replace(/_/g, ' '), 14, 18);

    // Date
    doc.setFontSize(9);
    doc.setTextColor(120, 120, 120);
    doc.text('Exported on: ' + new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }), 14, 24);

    // Calculate column widths proportionally
    const pageWidth = doc.internal.pageSize.getWidth() - 28; // minus margins
    const colCount = data.headers.length;
    const colWidths = data.headers.map(() => pageWidth / colCount);

    doc.autoTable({
        head: [data.headers],
        body: data.rows,
        startY: 30,
        theme: 'grid',
        headStyles: {
            fillColor: [37, 99, 235],
            textColor: 255,
            fontStyle: 'bold',
            fontSize: 9,
            halign: 'center'
        },
        bodyStyles: {
            fontSize: 8,
            cellPadding: 3
        },
        alternateRowStyles: {
            fillColor: [248, 250, 252]
        },
        columnStyles: data.headers.reduce((acc, _, i) => {
            acc[i] = { cellWidth: colWidths[i] };
            return acc;
        }, {}),
        styles: {
            overflow: 'linebreak',
            lineColor: [226, 232, 240],
            lineWidth: 0.25
        },
        margin: { top: 30, left: 14, right: 14 },
        didDrawPage: function (hookData) {
            // Footer
            doc.setFontSize(8);
            doc.setTextColor(150);
            doc.text('SPARK\'26 - Page ' + doc.internal.getNumberOfPages(), doc.internal.pageSize.getWidth() / 2, doc.internal.pageSize.getHeight() - 10, { align: 'center' });
        }
    });

    doc.save(filename + '.pdf');

    Swal.fire({ icon: 'success', title: 'PDF Downloaded!', text: filename + '.pdf has been saved.', confirmButtonColor: '#D97706', timer: 2000, timerProgressBar: true, showConfirmButton: false });
}

/* ── Excel Export ── */
function exportTableToExcel(tableEl, filename) {
    if (!tableEl) { alert('No table found to export.'); return; }
    const data = getTableData(tableEl);
    if (data.rows.length === 0) {
        Swal.fire({ icon: 'info', title: 'No Data', text: 'The table has no data to export.', confirmButtonColor: '#D97706' });
        return;
    }

    const wsData = [data.headers, ...data.rows];
    const ws = XLSX.utils.aoa_to_sheet(wsData);

    // Column widths
    ws['!cols'] = data.headers.map(h => ({ wch: Math.max(h.length + 5, 15) }));

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Data');
    XLSX.writeFile(wb, filename + '.xlsx');

    Swal.fire({ icon: 'success', title: 'Excel Downloaded!', text: filename + '.xlsx has been saved.', confirmButtonColor: '#D97706', timer: 2000, timerProgressBar: true, showConfirmButton: false });
}

/* ══════════════════════════════════════════════════════
   Column Filtering – auto-injected for every .data-table
   ══════════════════════════════════════════════════════ */

function initColumnFilters() {
    document.querySelectorAll('table.data-table').forEach(table => {
        const thead = table.querySelector('thead');
        if (!thead) return;
        const headerRow = thead.querySelector('tr:first-child');
        if (!headerRow) return;
        const ths = headerRow.querySelectorAll('th');
        if (ths.length === 0) return;

        // Don't add filter row twice
        if (thead.querySelector('.column-filter-row')) return;

        const filterRow = document.createElement('tr');
        filterRow.className = 'column-filter-row';

        ths.forEach((th, idx) => {
            const td = document.createElement('td');
            const text = th.textContent.trim().toLowerCase();
            // Skip filter for Actions columns
            if (text === 'actions' || text === 'action') {
                td.innerHTML = '';
                filterRow.appendChild(td);
                return;
            }
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'column-filter-input';
            input.placeholder = 'Filter...';
            input.setAttribute('data-col', idx);
            input.addEventListener('input', function () {
                applyColumnFilters(table);
            });
            td.appendChild(input);
            filterRow.appendChild(td);
        });

        thead.appendChild(filterRow);
    });
}

function applyColumnFilters(table) {
    const thead = table.querySelector('thead');
    const filterInputs = thead.querySelectorAll('.column-filter-input');
    const filters = [];
    filterInputs.forEach(input => {
        const col = parseInt(input.getAttribute('data-col'), 10);
        const val = input.value.trim().toLowerCase();
        if (val) filters.push({ col, val });
    });

    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(tr => {
        // Never hide empty-state rows via filter
        if (tr.querySelector('.empty-table') || tr.querySelector('.empty-state')) return;
        const cells = tr.querySelectorAll('td');
        if (cells.length === 0) return;

        let visible = true;
        for (const f of filters) {
            const cell = cells[f.col];
            if (!cell) { visible = false; break; }
            const cellText = getCellText(cell).toLowerCase();
            if (!cellText.includes(f.val)) {
                visible = false;
                break;
            }
        }
        tr.style.display = visible ? '' : 'none';
        if (visible) tr.classList.remove('filter-hidden');
        else tr.classList.add('filter-hidden');
    });
}

/* ── Clear all filters for a table ── */
function clearColumnFilters(table) {
    if (!table) return;
    const inputs = table.querySelectorAll('.column-filter-input');
    inputs.forEach(input => { input.value = ''; });
    applyColumnFilters(table);
}

/* ══════════════════════════════════════════════════════
   Initialization – robust timing for all load scenarios
   ══════════════════════════════════════════════════════ */

let _tableUtilsInitialized = false;

function initTableExport() {
    document.querySelectorAll('.export-btn-group').forEach(group => {
        if (group.dataset.bound) return; // prevent double-binding
        group.dataset.bound = '1';
        const tableId = group.dataset.table;
        const filename = group.dataset.filename || 'export';
        const tableEl = document.getElementById(tableId);

        const pdfBtn = group.querySelector('.export-pdf-btn');
        const excelBtn = group.querySelector('.export-excel-btn');

        if (pdfBtn && tableEl) {
            pdfBtn.addEventListener('click', () => exportTableToPDF(tableEl, filename));
        }
        if (excelBtn && tableEl) {
            excelBtn.addEventListener('click', () => exportTableToExcel(tableEl, filename));
        }
    });
}

function initAllTableUtils() {
    if (_tableUtilsInitialized) return;
    _tableUtilsInitialized = true;
    initTableExport();
    initColumnFilters();
}

// Run immediately if DOM already parsed (script at bottom of body)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAllTableUtils);
} else {
    initAllTableUtils();
}
// Safety fallback
document.addEventListener('DOMContentLoaded', initAllTableUtils);
