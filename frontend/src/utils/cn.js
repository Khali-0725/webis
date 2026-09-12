import clsx from 'clsx';
import { twMerge } from 'tailwind-merge';

/**
 * Merges conditional class names and resolves Tailwind conflicts, so a
 * caller-supplied `className` always wins over a component's default.
 */
export function cn(...inputs) {
  return twMerge(clsx(inputs));
}
