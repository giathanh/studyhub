<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { useTemplateRef } from 'vue';
import FormField from '@/components/FormField.vue';
import AppButton from '@/components/AppButton.vue';
import TurnstileWidget from '@/components/TurnstileWidget.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';

defineProps<{
    turnstileSiteKey: string | null;
}>();

const turnstile = useTemplateRef<InstanceType<typeof TurnstileWidget>>('turnstile');
</script>

<template>
    <AuthLayout title="Register">
        <Form
            action="/register"
            method="post"
            :reset-on-error="['password', 'password_confirmation']"
            class="space-y-4"
            @error="turnstile?.reset()"
            #default="{ errors, processing }"
        >
            <FormField
                label="Name"
                name="name"
                autocomplete="name"
                autofocus
                :error="errors.name"
            />
            <FormField
                label="Email"
                name="email"
                type="email"
                autocomplete="username"
                :error="errors.email"
            />
            <FormField
                label="Password"
                name="password"
                type="password"
                autocomplete="new-password"
                :error="errors.password"
            />
            <FormField
                label="Confirm password"
                name="password_confirmation"
                type="password"
                autocomplete="new-password"
                :error="errors.password_confirmation"
            />

            <TurnstileWidget
                v-if="turnstileSiteKey"
                ref="turnstile"
                :site-key="turnstileSiteKey"
                :error="errors['cf-turnstile-response']"
            />

            <AppButton type="submit" :disabled="processing" class="w-full">
                {{ processing ? 'Creating account…' : 'Register' }}
            </AppButton>
        </Form>

        <p class="mt-6 text-sm text-ink-soft">
            Already registered?
            <Link href="/login" class="font-medium text-brand underline underline-offset-4">
                Log in
            </Link>
        </p>
    </AuthLayout>
</template>
