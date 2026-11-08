import { formatReleaseDate } from '@/lib/releaseDate';
import { describe, expect, it } from 'vitest';

describe('formatReleaseDate', () => {
    it('shows only the year for year precision', () => {
        expect(formatReleaseDate('2019', 'year', 'en')).toBe('2019');
    });

    it('shows month and year for month precision', () => {
        expect(formatReleaseDate('2020-03', 'month', 'en')).toBe('March 2020');
        expect(formatReleaseDate('2020-03', 'month', 'de')).toBe('März 2020');
    });

    it('shows the full date for day precision', () => {
        expect(formatReleaseDate('2021-04-05', 'day', 'en')).toBe('Apr 5, 2021');
        expect(formatReleaseDate('2021-04-05', 'day', 'de')).toBe('05.04.2021');
    });
});
