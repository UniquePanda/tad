import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type SpotifyConnectionStatus = 'spotify-connected' | 'spotify-disconnected' | 'spotify-connect-failed';

export type WatchlistStatus = 'watchlist-show-added' | 'watchlist-show-removed' | 'watchlist-refreshed' | 'watchlist-refresh-failed';

export type ReleaseDatePrecision = 'year' | 'month' | 'day';

export interface WatchedShow {
    id: number;
    name: string;
    image_url: string | null;
    last_checked_at: string | null;
    unseen_releases_count: number;
}

export interface NewRelease {
    id: number;
    spotify_album_id: string;
    name: string;
    image_url: string | null;
    release_date: string;
    release_date_precision: ReleaseDatePrecision;
    show: {
        id: number;
        name: string;
    };
}

export interface SharedData {
    name: string;
    locale: string;
    availableLocales: string[];
    auth: Auth;
    spotify: {
        connected: boolean;
    };
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
}

export interface User {
    id: number;
    username: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
