import type { ReleaseDatePrecision } from '@/types';

/**
 * Format a Spotify release date (`2024`, `2024-05` or `2024-05-17`) only as
 * precisely as Spotify knows it.
 */
export function formatReleaseDate(releaseDate: string, precision: ReleaseDatePrecision, locale: string): string {
    const [year, month = 1, day = 1] = releaseDate.split('-').map(Number);
    // Noon UTC avoids the date shifting by a day in far-off timezones.
    const date = new Date(Date.UTC(year, month - 1, day, 12));

    switch (precision) {
        case 'year':
            return String(year);
        case 'month':
            return new Intl.DateTimeFormat(locale, { year: 'numeric', month: 'long', timeZone: 'UTC' }).format(date);
        default:
            return new Intl.DateTimeFormat(locale, { dateStyle: 'medium', timeZone: 'UTC' }).format(date);
    }
}
