import { cn } from '@/lib/utils';
import { describe, expect, it } from 'vitest';

describe('cn', () => {
    it('merges class names', () => {
        expect(cn('a', 'b')).toBe('a b');
    });

    it('lets later tailwind classes override earlier conflicting ones', () => {
        expect(cn('p-2', 'p-4')).toBe('p-4');
    });

    it('ignores falsy values', () => {
        expect(cn('a', false, null, undefined, 'b')).toBe('a b');
    });
});
