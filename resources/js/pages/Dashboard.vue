<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { SharedData, SpotifyConnectionStatus } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    status?: SpotifyConnectionStatus | null;
}>();

const { t } = useI18n();
const page = usePage<SharedData>();

const spotifyConnected = computed(() => page.props.spotify.connected);

const statusMessage = computed(() => {
    switch (props.status) {
        case 'spotify-connected':
            return t('dashboard.spotify.status.connected');
        case 'spotify-disconnected':
            return t('dashboard.spotify.status.disconnected');
        case 'spotify-connect-failed':
            return t('dashboard.spotify.status.failed');
        default:
            return null;
    }
});

const logout = () => router.post(route('logout'));

// Full-page navigation on purpose: the connect route redirects to Spotify (an
// external URL), which Inertia's XHR visits cannot follow.
const connectSpotify = () => window.location.assign(route('spotify.connect'));
const disconnectSpotify = () => router.delete(route('spotify.disconnect'));
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6">
        <div class="w-full max-w-sm space-y-4 text-center">
            <h1 class="text-2xl font-semibold">TAD – Tracker for Audio Dramas</h1>
            <p class="text-sm text-muted-foreground">{{ $t('dashboard.subtitle') }}</p>

            <p v-if="statusMessage" class="text-sm text-muted-foreground">{{ statusMessage }}</p>

            <div class="space-y-2">
                <template v-if="spotifyConnected">
                    <p class="text-sm font-medium">{{ $t('dashboard.spotify.connected') }}</p>
                    <Button variant="outline" @click="disconnectSpotify">{{ $t('dashboard.spotify.disconnect') }}</Button>
                </template>
                <template v-else>
                    <p class="text-sm text-muted-foreground">{{ $t('dashboard.spotify.notConnected') }}</p>
                    <Button @click="connectSpotify">{{ $t('dashboard.spotify.connect') }}</Button>
                </template>
            </div>

            <Button variant="outline" @click="logout">{{ $t('dashboard.logout') }}</Button>
        </div>
    </div>
</template>
