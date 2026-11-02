import { useLayoutEffect, useRef } from 'react';
import { cn } from '@/utils/cn';

/** Keep one table's content and actions while presenting labeled cards on phones. */
export function ResponsiveTable({ className, children, ...props }) {
  const tableRef = useRef(null);

  useLayoutEffect(() => {
    const table = tableRef.current;
    if (!table) return;

    const labels = [...table.querySelectorAll('thead th')]
      .map((heading) => heading.textContent.replace(/\s+/g, ' ').trim());

    table.querySelectorAll('tbody tr').forEach((row) => {
      [...row.cells].forEach((cell, index) => {
        cell.dataset.label = labels[index] ?? '';
      });
    });
  });

  return <table ref={tableRef} className={cn('webis-responsive-table', className)} {...props}>{children}</table>;
}
