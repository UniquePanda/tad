<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Head, router } from '@inertiajs/vue3';

const logout = () => router.post(route('logout'));
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6">
        <div class="w-full max-w-sm space-y-4 text-center">
            <h1 class="text-2xl font-semibold">TAD – Tracker for Audio Dramas</h1>
            <p class="text-sm text-muted-foreground">{{ $t('dashboard.subtitle') }}</p>
            <Button variant="outline" @click="logout">{{ $t('dashboard.logout') }}</Button>
        </div>
    </div>
</template>
