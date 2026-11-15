import { jsPDF } from 'jspdf';

export function exportRowsToPdf({ filename, title, columns, rows }) {
  const doc = new jsPDF({ orientation: 'landscape' });
  doc.setFontSize(16);
  doc.text(title, 14, 16);
  doc.setFontSize(8);
  let y = 26;
  const widths = columns.map((column) => column.width ?? 40);
  const lineHeight = 5;
  doc.setFont(undefined, 'bold');
  columns.forEach((column, index) => doc.text(column.label, 14 + widths.slice(0, index).reduce((a, b) => a + b, 0), y));
  doc.setFont(undefined, 'normal');
  y += 6;
  rows.forEach((row) => {
    if (y > 195) { doc.addPage(); y = 16; }
    columns.forEach((column, index) => {
      const value = String(column.value(row) ?? '—').slice(0, 42);
      doc.text(value, 14 + widths.slice(0, index).reduce((a, b) => a + b, 0), y);
    });
    y += lineHeight;
  });
  doc.save(filename);
}
