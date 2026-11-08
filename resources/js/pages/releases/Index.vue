<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { formatReleaseDate } from '@/lib/releaseDate';
import type { NewRelease } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

defineProps<{
    releases: NewRelease[];
}>();

const { locale } = useI18n();

const markSeen = (release: NewRelease) => router.patch(route('releases.seen', release.id), {}, { preserveScroll: true });
const markAllSeen = () => router.patch(route('releases.seen-all'), {}, { preserveScroll: true });

const spotifyAlbumUrl = (release: NewRelease) => `https://open.spotify.com/album/${release.spotify_album_id}`;
</script>

<template>
    <Head :title="$t('releases.title')" />

    <div class="min-h-svh bg-background p-4 sm:p-6">
        <div class="mx-auto w-full max-w-lg space-y-6">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-2xl font-semibold">{{ $t('releases.title') }}</h1>
                <div class="flex gap-4 text-sm">
                    <TextLink :href="route('shows.index')">{{ $t('releases.watchlistLink') }}</TextLink>
                    <TextLink :href="route('dashboard')">{{ $t('releases.back') }}</TextLink>
                </div>
            </div>

            <p v-if="!releases.length" class="text-sm text-muted-foreground">{{ $t('releases.empty') }}</p>

            <template v-else>
                <Button variant="outline" class="w-full" @click="markAllSeen">{{ $t('releases.markAllSeen') }}</Button>

                <ul class="divide-y rounded-lg border">
                    <li v-for="release in releases" :key="release.id" data-test="release" class="flex items-center gap-3 p-3">
                        <img v-if="release.image_url" :src="release.image_url" :alt="release.name" class="h-14 w-14 shrink-0 rounded object-cover" />
                        <div v-else class="h-14 w-14 shrink-0 rounded bg-muted" />

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">{{ release.name }}</p>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ release.show.name }} · {{ formatReleaseDate(release.release_date, release.release_date_precision, locale) }}
                            </p>
                            <a :href="spotifyAlbumUrl(release)" target="_blank" rel="noopener" class="text-xs underline underline-offset-4">
                                {{ $t('releases.openInSpotify') }}
                            </a>
                        </div>

                        <Button variant="ghost" size="sm" @click="markSeen(release)">{{ $t('releases.markSeen') }}</Button>
                    </li>
                </ul>
            </template>
        </div>
    </div>
</template>
