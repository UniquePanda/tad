import { createI18n } from 'vue-i18n';
import de from './locales/de.json';
import en from './locales/en.json';

export const messages = { en, de };

export type AppLocale = keyof typeof messages;

/**
 * Create the vue-i18n instance. The active locale comes from the user account
 * (shared by the backend via Inertia); English is the fallback.
 */
export function createI18nInstance(locale: string) {
    return createI18n({
        legacy: false,
        globalInjection: true,
        locale,
        fallbackLocale: 'en',
        messages,
    });
}
