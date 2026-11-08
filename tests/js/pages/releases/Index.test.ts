import { createI18nInstance } from '@/i18n';
import ReleasesIndex from '@/pages/releases/Index.vue';
import type { NewRelease } from '@/types';
import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const route = (name: string, params?: unknown) => (params === undefined ? `/${name}` : `/${name}/${params}`);

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    router: { patch: vi.fn() },
}));

const release = (overrides: Partial<NewRelease> = {}): NewRelease => ({
    id: 1,
    spotify_album_id: 'album_1',
    name: 'Episode 1',
    image_url: null,
    release_date: '2020-03',
    release_date_precision: 'month',
    show: { id: 1, name: 'Some Series' },
    ...overrides,
});

function mountPage(releases: NewRelease[] = []) {
    return mount(ReleasesIndex, {
        props: { releases },
        global: {
            plugins: [createI18nInstance('en')],
            mocks: { route },
        },
    });
}

const button = (wrapper: ReturnType<typeof mountPage>, text: string) => wrapper.findAll('button').find((b) => b.text() === text);

describe('releases/Index.vue', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.stubGlobal('route', route);
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('shows the empty state without a mark-all button', () => {
        const wrapper = mountPage();

        expect(wrapper.text()).toContain("No new episodes — you're all caught up.");
        expect(button(wrapper, 'Mark all as seen')).toBeUndefined();
    });

    it('lists releases with show name, precision-aware date and Spotify link', () => {
        const wrapper = mountPage([release()]);

        const item = wrapper.find('[data-test="release"]');
        expect(item.text()).toContain('Episode 1');
        expect(item.text()).toContain('Some Series · March 2020');
        expect(item.find('a[href="https://open.spotify.com/album/album_1"]').exists()).toBe(true);
    });

    it('marks a single release as seen', async () => {
        const wrapper = mountPage([release({ id: 5 })]);

        await button(wrapper, 'Mark as seen')?.trigger('click');

        expect(router.patch).toHaveBeenCalledWith('/releases.seen/5', {}, { preserveScroll: true });
    });

    it('marks all releases as seen', async () => {
        const wrapper = mountPage([release(), release({ id: 2 })]);

        await button(wrapper, 'Mark all as seen')?.trigger('click');

        expect(router.patch).toHaveBeenCalledWith('/releases.seen-all', {}, { preserveScroll: true });
    });
});
