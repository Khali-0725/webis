import { describe, expect, it } from 'vitest';
import { createEarningsPdf, createReceiptPdf } from '../src/utils/pdfDownloads';

const pdfText = (document) => new TextDecoder('latin1').decode(document.output('arraybuffer'));

describe('direct PDF downloads', () => {
  it('builds a receipt PDF with status and wrapped record fields', () => {
    const fields = [
      ['Receipt identifier', 'REC-204'],
      ['Service', 'A very long service description '.repeat(12)],
      ['Amount due', 'PHP 800.00'],
    ];
    const document = createReceiptPdf({ receipt_id: 'REC-204' }, fields, 'Payment Submitted - Not Yet Verified');
    const output = pdfText(document);

    expect(output.startsWith('%PDF-')).toBe(true);
    expect(output).toContain('WEBIS Payment Receipt');
    expect(output).toContain('Not Yet Verified');
    expect(output).toContain('REC-204');
    expect(output).toContain('Amount due');
  });

  it('keeps unverified status clear when receipt fields are missing', () => {
    const document = createReceiptPdf(
      { receipt_id: 'REC-205', status: 'proof_submitted' },
      [['Payment reference', null], ['Provider', ''], ['Amount due', '₱500.00']],
      'Payment Submitted — Not Yet Verified',
    );
    const output = pdfText(document);

    expect(output).toContain('does not confirm that funds were received');
    expect(output).toContain('PHP 500.00');
    expect(output).toContain('Payment reference');
  });

  it('builds a filtered earnings PDF and continues transactions across pages', () => {
    const transactions = Array.from({ length: 75 }, (_, index) => ({
      id: index + 1,
      booking_code: `BOOK-${index + 1}`,
      service: `Long service name ${index + 1} `.repeat(5),
      verified_at: '2026-11-02T10:00:00Z',
      amount: 250,
    }));
    const document = createEarningsPdf({
      provider_name: 'Sample Provider',
      total_earnings: 18750,
      pending_amount: 100,
      rejected_amount: 0,
      refunded_amount: 0,
    }, transactions, '2026-09-01', '2026-11-04');
    const output = pdfText(document);

    expect(output.startsWith('%PDF-')).toBe(true);
    expect(output).toContain('WEBIS Earnings Report');
    expect(output).toContain('Sample Provider');
    expect(output).toContain('2026-09-01');
    expect(output).toContain('2026-11-04');
    expect(output).toContain('BOOK-1');
    expect(output).toContain('BOOK-75');
    expect(document.getNumberOfPages()).toBeGreaterThan(1);
  });
});
