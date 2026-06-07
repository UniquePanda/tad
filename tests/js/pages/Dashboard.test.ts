import { createI18nInstance } from '@/i18n';
import Dashboard from '@/pages/Dashboard.vue';
import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({
    page: { props: { spotify: { connected: false } } },
}));

// Ziggy exposes `route()` as a global at runtime; mirror that for components
// that call it inside <script setup> (not just in templates).
const route = (name: string) => `/${name}`;

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    usePage: () => mocks.page,
    router: { post: vi.fn(), delete: vi.fn() },
}));

function mountDashboard(props: { status?: string } = {}) {
    return mount(Dashboard, {
        props,
        global: {
            plugins: [createI18nInstance('en')],
            mocks: { route },
        },
    });
}

describe('Dashboard.vue', () => {
    beforeEach(() => {
        mocks.page.props.spotify.connected = false;
        vi.clearAllMocks();
        vi.stubGlobal('route', route);
    });

    afterEach(() => {
        vi.unstubAllGlobals();
        vi.restoreAllMocks();
    });

    it('navigates to the connect route when Spotify is not connected', async () => {
        const assign = vi.spyOn(window.location, 'assign').mockImplementation(() => {});
        const wrapper = mountDashboard();

        expect(wrapper.text()).toContain('Spotify is not connected');

        const connect = wrapper.findAll('button').find((button) => button.text() === 'Connect Spotify');
        expect(connect).toBeTruthy();

        await connect?.trigger('click');

        expect(assign).toHaveBeenCalledWith('/spotify.connect');
    });

    it('shows the disconnect action and triggers a delete when connected', async () => {
        mocks.page.props.spotify.connected = true;

        const wrapper = mountDashboard();

        expect(wrapper.text()).toContain('Spotify connected');

        const disconnect = wrapper.findAll('button').find((button) => button.text() === 'Disconnect Spotify');
        expect(disconnect).toBeTruthy();

        await disconnect?.trigger('click');

        expect(router.delete).toHaveBeenCalledWith('/spotify.disconnect');
    });

    it('renders the matching message for each connection status', () => {
        expect(mountDashboard({ status: 'spotify-connected' }).text()).toContain('Spotify was connected successfully.');
        expect(mountDashboard({ status: 'spotify-disconnected' }).text()).toContain('Spotify was disconnected.');
        expect(mountDashboard({ status: 'spotify-connect-failed' }).text()).toContain('Connecting to Spotify failed. Please try again.');
    });

    it('renders no status message when no status is given', () => {
        expect(mountDashboard().text()).not.toContain('Spotify was connected successfully.');
    });
});
