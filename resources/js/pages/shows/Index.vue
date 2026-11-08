<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { SharedData, WatchedShow, WatchlistStatus } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    shows: WatchedShow[];
    status?: WatchlistStatus | null;
    newReleaseCount?: number | null;
}>();

const { t, locale } = useI18n();
const page = usePage<SharedData>();

const spotifyConnected = computed(() => page.props.spotify.connected);

const statusMessage = computed(() => {
    switch (props.status) {
        case 'watchlist-show-added':
            return t('watchlist.status.added');
        case 'watchlist-show-removed':
            return t('watchlist.status.removed');
        case 'watchlist-refreshed':
            return t('watchlist.status.refreshed', props.newReleaseCount ?? 0);
        case 'watchlist-refresh-failed':
            return t('watchlist.status.refreshFailed');
        default:
            return null;
    }
});

const form = useForm({
    artist: '',
});

const addShow = () => {
    form.post(route('shows.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const refreshing = ref(false);

const refresh = () => {
    router.post(
        route('shows.refresh'),
        {},
        {
            preserveScroll: true,
            onStart: () => (refreshing.value = true),
            onFinish: () => (refreshing.value = false),
        },
    );
};

const removeShow = (show: WatchedShow) => {
    if (!window.confirm(t('watchlist.confirmRemove', { name: show.name }))) {
        return;
    }

    router.delete(route('shows.destroy', show.id), { preserveScroll: true });
};

const formatDateTime = (value: string) => new Intl.DateTimeFormat(locale.value, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
</script>

<template>
    <Head :title="$t('watchlist.title')" />

    <div class="min-h-svh bg-background p-4 sm:p-6">
        <div class="mx-auto w-full max-w-lg space-y-6">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-2xl font-semibold">{{ $t('watchlist.title') }}</h1>
                <TextLink :href="route('dashboard')" class="text-sm">{{ $t('watchlist.back') }}</TextLink>
            </div>

            <p v-if="statusMessage" class="text-sm text-muted-foreground">
                {{ statusMessage }}
                <TextLink v-if="status === 'watchlist-refreshed' && newReleaseCount" :href="route('releases.index')">
                    {{ $t('watchlist.newReleasesLink') }}
                </TextLink>
            </p>

            <p v-if="!spotifyConnected" class="text-sm text-muted-foreground">{{ $t('watchlist.notConnected') }}</p>

            <template v-else>
                <form class="grid gap-2" @submit.prevent="addShow">
                    <Label for="artist">{{ $t('watchlist.add.label') }}</Label>
                    <div class="flex gap-2">
                        <Input id="artist" v-model="form.artist" type="text" required :placeholder="$t('watchlist.add.placeholder')" />
                        <Button type="submit" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                            {{ $t('watchlist.add.submit') }}
                        </Button>
                    </div>
                    <p class="text-xs text-muted-foreground">{{ $t('watchlist.add.hint') }}</p>
                    <InputError :message="form.errors.artist" />
                </form>

                <Button v-if="shows.length" variant="outline" class="w-full" :disabled="refreshing" @click="refresh">
                    <LoaderCircle v-if="refreshing" class="h-4 w-4 animate-spin" />
                    {{ $t('watchlist.refresh') }}
                </Button>
            </template>

            <p v-if="!shows.length" class="text-sm text-muted-foreground">{{ $t('watchlist.empty') }}</p>

            <ul v-else class="divide-y rounded-lg border">
                <li v-for="show in shows" :key="show.id" data-test="show" class="flex items-center gap-3 p-3">
                    <img v-if="show.image_url" :src="show.image_url" :alt="show.name" class="h-12 w-12 shrink-0 rounded object-cover" />
                    <div v-else class="h-12 w-12 shrink-0 rounded bg-muted" />

                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium">{{ show.name }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{
                                show.last_checked_at
                                    ? $t('watchlist.lastChecked', { date: formatDateTime(show.last_checked_at) })
                                    : $t('watchlist.neverChecked')
                            }}
                        </p>
                    </div>

                    <span
                        v-if="show.unseen_releases_count"
                        class="shrink-0 rounded-full bg-primary px-2 py-0.5 text-xs font-medium text-primary-foreground"
                    >
                        {{ $t('watchlist.unseen', { count: show.unseen_releases_count }) }}
                    </span>

                    <Button variant="ghost" size="sm" @click="removeShow(show)">{{ $t('watchlist.remove') }}</Button>
                </li>
            </ul>
        </div>
    </div>
</template>
