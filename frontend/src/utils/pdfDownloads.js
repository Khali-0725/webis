import { jsPDF } from 'jspdf';

const LEFT = 16;
const RIGHT = 194;
const BOTTOM = 279;
const LINE = 5;

const pdfValue = (value) => String(value ?? '—')
  .replaceAll('₱', 'PHP ')
  .replace(/[—–·]/g, '-');

const amount = (value) => `PHP ${Number(value || 0).toLocaleString('en-PH', {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
})}`;

function heading(doc, title, subtitle) {
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(16);
  doc.text(title, LEFT, 20);
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(10);
  doc.text(doc.splitTextToSize(pdfValue(subtitle), RIGHT - LEFT), LEFT, 28);
  doc.setDrawColor(190, 201, 219);
  doc.line(LEFT, 35, RIGHT, 35);
  return 43;
}

function row(doc, cells, widths, y, header = false) {
  doc.setFont('helvetica', header ? 'bold' : 'normal');
  doc.setFontSize(9);
  const lines = cells.map((cell, index) => doc.splitTextToSize(pdfValue(cell), widths[index] - 6));
  const height = Math.max(...lines.map((part) => part.length)) * LINE + 6;
  if (y + height > BOTTOM) {
    doc.addPage();
    y = 18;
  }
  if (header) {
    doc.setFillColor(238, 242, 249);
    doc.rect(LEFT, y, RIGHT - LEFT, height, 'F');
  }
  let x = LEFT;
  lines.forEach((part, index) => {
    doc.text(part, x + 3, y + 5);
    x += widths[index];
  });
  doc.setDrawColor(224, 230, 239);
  doc.line(LEFT, y + height, RIGHT, y + height);
  return y + height;
}

export function createReceiptPdf(receipt, fields, designation) {
  const doc = new jsPDF();
  let y = heading(doc, 'WEBIS Payment Receipt', receipt.receipt_id || 'Payment record');
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(11);
  const statusLines = doc.splitTextToSize(pdfValue(designation), RIGHT - LEFT);
  doc.text(statusLines, LEFT, y);
  y += statusLines.length * 6 + 5;
  if (receipt.status !== 'verified') {
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    doc.text('This record does not confirm that funds were received.', LEFT, y);
    y += 9;
  }
  y = row(doc, ['Field', 'Value'], [55, 123], y, true);
  for (const [label, value] of fields) {
    y = row(doc, [label, value], [55, 123], y);
  }
  return doc;
}

export function createEarningsPdf(summary, transactions, from, to) {
  const doc = new jsPDF();
  let y = heading(doc, 'WEBIS Earnings Report', `${summary.provider_name} | ${from || 'All dates'} to ${to || 'Present'}`);
  y = row(doc, ['Summary', 'Amount'], [89, 89], y, true);
  for (const [label, value] of [
    ['Confirmed earnings', summary.total_earnings],
    ['Pending / unverified', summary.pending_amount],
    ['Rejected', summary.rejected_amount],
    ['Refunded', summary.refunded_amount],
  ]) {
    y = row(doc, [label, amount(value)], [89, 89], y);
  }
  y += 8;
  const widths = [32, 70, 41, 35];
  const tableHeader = ['Booking', 'Service', 'Verified', 'Amount'];
  y = row(doc, tableHeader, widths, y, true);
  for (const transaction of transactions) {
    const verified = transaction.verified_at
      ? new Date(transaction.verified_at).toLocaleDateString('en-PH')
      : '—';
    const cells = [transaction.booking_code, transaction.service, verified, amount(transaction.amount)];
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    const rowHeight = Math.max(...cells.map((cell, index) => doc.splitTextToSize(pdfValue(cell), widths[index] - 6).length)) * LINE + 6;
    if (y + rowHeight > BOTTOM) {
      doc.addPage();
      y = row(doc, tableHeader, widths, 18, true);
    }
    y = row(doc, cells, widths, y);
  }
  if (transactions.length === 0) {
    row(doc, ['No confirmed transactions in this period.', '', '', ''], widths, y);
  }
  return doc;
}
