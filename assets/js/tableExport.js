/**
 * Table Export Utility – PDF & Excel
 * Uses jsPDF + AutoTable for PDF, SheetJS for Excel
 */

function getTableData(tableEl) {
    const headers = [];
    const rows = [];
    const thElements = tableEl.querySelectorAll('thead th');

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
        const cells = tr.querySelectorAll('td');
        if (cells.length === 0) return;
        const row = [];
        headers.forEach(h => {
            const cell = cells[h.index];
            if (cell) {
                // Get clean text content
                row.push(cell.textContent.trim().replace(/\s+/g, ' '));
            } else {
                row.push('');
            }
        });
        rows.push(row);
    });

    return { headers: headers.map(h => h.text), rows: rows };
}

function exportTableToPDF(tableEl, filename) {
    if (!tableEl) { alert('No table found to export.'); return; }
    const data = getTableData(tableEl);
    if (data.rows.length === 0) {
        Swal.fire({ icon: 'info', title: 'No Data', text: 'The table has no data to export.', confirmButtonColor: '#2563eb' });
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
        styles: {
            overflow: 'linebreak',
            lineColor: [226, 232, 240],
            lineWidth: 0.25
        },
        margin: { top: 30, left: 14, right: 14 },
        didDrawPage: function (data) {
            // Footer
            doc.setFontSize(8);
            doc.setTextColor(150);
            doc.text('SPARK\'26 - Page ' + doc.internal.getNumberOfPages(), doc.internal.pageSize.getWidth() / 2, doc.internal.pageSize.getHeight() - 10, { align: 'center' });
        }
    });

    doc.save(filename + '.pdf');

    Swal.fire({ icon: 'success', title: 'PDF Downloaded!', text: filename + '.pdf has been saved.', confirmButtonColor: '#2563eb', timer: 2000, timerProgressBar: true, showConfirmButton: false });
}

function exportTableToExcel(tableEl, filename) {
    if (!tableEl) { alert('No table found to export.'); return; }
    const data = getTableData(tableEl);
    if (data.rows.length === 0) {
        Swal.fire({ icon: 'info', title: 'No Data', text: 'The table has no data to export.', confirmButtonColor: '#2563eb' });
        return;
    }

    const wsData = [data.headers, ...data.rows];
    const ws = XLSX.utils.aoa_to_sheet(wsData);

    // Column widths
    ws['!cols'] = data.headers.map(h => ({ wch: Math.max(h.length + 5, 15) }));

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Data');
    XLSX.writeFile(wb, filename + '.xlsx');

    Swal.fire({ icon: 'success', title: 'Excel Downloaded!', text: filename + '.xlsx has been saved.', confirmButtonColor: '#2563eb', timer: 2000, timerProgressBar: true, showConfirmButton: false });
}

/**
 * Initialize export buttons.
 * Call this after DOM is loaded. It finds all .export-btn-group containers
 * and wires up the buttons to the corresponding table.
 */
function initTableExport() {
    document.querySelectorAll('.export-btn-group').forEach(group => {
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

document.addEventListener('DOMContentLoaded', initTableExport);
