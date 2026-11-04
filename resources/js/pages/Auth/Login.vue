<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import FormField from '@/components/FormField.vue';
import ShimmerButton from '@/components/ShimmerButton.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
</script>

<template>
    <AuthLayout title="Log in">
        <Form
            action="/login"
            method="post"
            :reset-on-error="['password']"
            class="space-y-4"
            #default="{ errors, processing }"
        >
            <FormField
                label="Email"
                name="email"
                type="email"
                autocomplete="username"
                autofocus
                :error="errors.email"
            />
            <FormField
                label="Password"
                name="password"
                type="password"
                autocomplete="current-password"
                :error="errors.password"
            />

            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="remember" class="size-4 accent-brand" />
                Remember me
            </label>

            <ShimmerButton type="submit" :disabled="processing" class="w-full">
                {{ processing ? 'Logging in…' : 'Log in' }}
            </ShimmerButton>
        </Form>

        <p class="mt-4 text-center text-sm text-gray-600">
            No account yet?
            <Link href="/register" class="font-medium text-brand underline underline-offset-4">
                Register
            </Link>
        </p>
    </AuthLayout>
</template>
