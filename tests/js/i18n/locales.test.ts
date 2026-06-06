import de from '@/i18n/locales/de.json';
import en from '@/i18n/locales/en.json';
import { describe, expect, it } from 'vitest';

function keyPaths(obj: Record<string, unknown>, prefix = ''): string[] {
    return Object.entries(obj).flatMap(([key, value]) => {
        const path = prefix ? `${prefix}.${key}` : key;
        return value !== null && typeof value === 'object' ? keyPaths(value as Record<string, unknown>, path) : [path];
    });
}

describe('i18n locale files', () => {
    it('en and de define exactly the same keys', () => {
        expect(keyPaths(en).sort()).toEqual(keyPaths(de).sort());
    });
});
