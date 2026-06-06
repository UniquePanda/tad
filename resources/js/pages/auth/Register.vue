<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const form = useForm({
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthBase :title="$t('auth.register.title')" :description="$t('auth.register.description')">
        <Head :title="$t('auth.register.submit')" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="username">{{ $t('auth.register.username') }}</Label>
                    <Input
                        id="username"
                        type="text"
                        required
                        autofocus
                        tabindex="1"
                        autocomplete="username"
                        v-model="form.username"
                        :placeholder="$t('auth.register.username')"
                    />
                    <InputError :message="form.errors.username" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">{{ $t('auth.register.email') }}</Label>
                    <Input id="email" type="email" required tabindex="2" autocomplete="email" v-model="form.email" placeholder="email@example.com" />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">{{ $t('auth.register.password') }}</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        tabindex="3"
                        autocomplete="new-password"
                        v-model="form.password"
                        :placeholder="$t('auth.register.password')"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">{{ $t('auth.register.confirmPassword') }}</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        tabindex="4"
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        :placeholder="$t('auth.register.confirmPassword')"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <Button type="submit" class="mt-2 w-full" tabindex="5" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    {{ $t('auth.register.submit') }}
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                {{ $t('auth.register.haveAccount') }}
                <TextLink :href="route('login')" class="underline underline-offset-4" tabindex="6">{{ $t('auth.register.logIn') }}</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
