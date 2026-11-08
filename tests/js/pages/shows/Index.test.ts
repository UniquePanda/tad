import { createI18nInstance } from '@/i18n';
import ShowsIndex from '@/pages/shows/Index.vue';
import type { WatchedShow } from '@/types';
import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({
    page: { props: { spotify: { connected: true } } },
    form: null as null | Record<string, unknown>,
}));

const route = (name: string, params?: unknown) => (params === undefined ? `/${name}` : `/${name}/${params}`);

vi.mock('@inertiajs/vue3', async () => {
    const { reactive } = await import('vue');

    return {
        Head: { template: '<div />' },
        Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
        usePage: () => mocks.page,
        useForm: (data: Record<string, unknown>) => {
            mocks.form = reactive({ ...data, errors: {}, processing: false, post: vi.fn(), reset: vi.fn() });
            return mocks.form;
        },
        router: { post: vi.fn(), delete: vi.fn() },
    };
});

const show = (overrides: Partial<WatchedShow> = {}): WatchedShow => ({
    id: 1,
    name: 'Some Series',
    image_url: null,
    last_checked_at: '2026-11-08T10:00:00+00:00',
    unseen_releases_count: 0,
    ...overrides,
});

function mountPage(props: Record<string, unknown> = {}) {
    return mount(ShowsIndex, {
        props: { shows: [], ...props },
        global: {
            plugins: [createI18nInstance('en')],
            mocks: { route },
        },
    });
}

const button = (wrapper: ReturnType<typeof mountPage>, text: string) => wrapper.findAll('button').find((b) => b.text() === text);

describe('shows/Index.vue', () => {
    beforeEach(() => {
        mocks.page.props.spotify.connected = true;
        vi.clearAllMocks();
        vi.stubGlobal('route', route);
    });

    afterEach(() => {
        vi.unstubAllGlobals();
        vi.restoreAllMocks();
    });

    it('shows the empty state without a refresh button', () => {
        const wrapper = mountPage();

        expect(wrapper.text()).toContain('Your watchlist is empty.');
        expect(button(wrapper, 'Check for new episodes')).toBeUndefined();
    });

    it('lists shows with their unseen count and check state', () => {
        const wrapper = mountPage({
            shows: [show({ unseen_releases_count: 3 }), show({ id: 2, name: 'Other Series', last_checked_at: null })],
        });

        const items = wrapper.findAll('[data-test="show"]');
        expect(items).toHaveLength(2);
        expect(items[0].text()).toContain('Some Series');
        expect(items[0].text()).toContain('3 new');
        expect(items[0].text()).toContain('Last checked:');
        expect(items[1].text()).toContain('Not checked yet');
        expect(items[1].text()).not.toContain('new');
    });

    it('submits the add form to the store route', async () => {
        const wrapper = mountPage();

        await wrapper.find('input#artist').setValue('spotify:artist:abc');
        await wrapper.find('form').trigger('submit');

        expect(mocks.form?.artist).toBe('spotify:artist:abc');
        expect(mocks.form?.post).toHaveBeenCalledWith('/shows.store', expect.objectContaining({ preserveScroll: true }));
    });

    it('renders the validation error of the add form', async () => {
        const wrapper = mountPage();

        (mocks.form?.errors as Record<string, string>).artist = 'Please enter a Spotify artist link, URI or ID.';
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('Please enter a Spotify artist link, URI or ID.');
    });

    it('posts to the refresh route', async () => {
        const wrapper = mountPage({ shows: [show()] });

        await button(wrapper, 'Check for new episodes')?.trigger('click');

        expect(router.post).toHaveBeenCalledWith('/shows.refresh', {}, expect.objectContaining({ preserveScroll: true }));
    });

    it('removes a show after confirmation', async () => {
        const confirm = vi.fn(() => true);
        vi.stubGlobal('confirm', confirm);
        const wrapper = mountPage({ shows: [show({ id: 7 })] });

        await button(wrapper, 'Remove')?.trigger('click');

        expect(confirm).toHaveBeenCalledWith('Remove "Some Series" from your watchlist?');
        expect(router.delete).toHaveBeenCalledWith('/shows.destroy/7', { preserveScroll: true });
    });

    it('keeps the show when removal is not confirmed', async () => {
        vi.stubGlobal(
            'confirm',
            vi.fn(() => false),
        );
        const wrapper = mountPage({ shows: [show()] });

        await button(wrapper, 'Remove')?.trigger('click');

        expect(router.delete).not.toHaveBeenCalled();
    });

    it('renders the refresh result with a link to new episodes', () => {
        const wrapper = mountPage({ shows: [show()], status: 'watchlist-refreshed', newReleaseCount: 2 });

        expect(wrapper.text()).toContain('2 new episodes found.');
        expect(wrapper.find('a[href="/releases.index"]').exists()).toBe(true);
    });

    it('renders the matching message for each status', () => {
        expect(mountPage({ status: 'watchlist-show-added' }).text()).toContain('Show added to your watchlist.');
        expect(mountPage({ status: 'watchlist-show-removed' }).text()).toContain('Show removed from your watchlist.');
        expect(mountPage({ status: 'watchlist-refreshed', newReleaseCount: 0 }).text()).toContain('No new episodes found.');
        expect(mountPage({ status: 'watchlist-refresh-failed' }).text()).toContain('Checking for new episodes failed.');
    });

    it('hides the add form when Spotify is not connected', () => {
        mocks.page.props.spotify.connected = false;

        const wrapper = mountPage({ shows: [show()] });

        expect(wrapper.text()).toContain('Connect Spotify on the dashboard');
        expect(wrapper.find('form').exists()).toBe(false);
        expect(button(wrapper, 'Check for new episodes')).toBeUndefined();
    });
});
