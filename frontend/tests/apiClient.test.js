import { describe, it, expect } from 'vitest';
import { unwrap, unwrapMeta, fieldError } from '@/services/api/client';

describe('API envelope helpers', () => {
  it('unwraps the data payload', () => {
    expect(unwrap({ data: { success: true, data: { id: 7 } } })).toEqual({ id: 7 });
  });

  it('returns undefined rather than throwing on a malformed response', () => {
    expect(unwrap(undefined)).toBeUndefined();
    expect(unwrap({})).toBeUndefined();
  });

  it('extracts pagination meta', () => {
    const meta = { page: 2, per_page: 15, total: 40 };

    expect(unwrapMeta({ data: { success: true, data: [], meta } })).toEqual(meta);
    expect(unwrapMeta({ data: { success: true, data: [] } })).toBeNull();
  });

  it('reads the first validation message for a field', () => {
    const error = { errors: { email: ['Already taken.', 'Second message.'] } };

    expect(fieldError(error, 'email')).toBe('Already taken.');
    expect(fieldError(error, 'password')).toBeUndefined();
    expect(fieldError(undefined, 'email')).toBeUndefined();
  });
});
