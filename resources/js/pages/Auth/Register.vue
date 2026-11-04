<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import FormField from '@/components/FormField.vue';
import ShimmerButton from '@/components/ShimmerButton.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
</script>

<template>
    <AuthLayout title="Register">
        <Form
            action="/register"
            method="post"
            :reset-on-error="['password', 'password_confirmation']"
            class="space-y-4"
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

            <ShimmerButton type="submit" :disabled="processing" class="w-full">
                {{ processing ? 'Creating account…' : 'Register' }}
            </ShimmerButton>
        </Form>

        <p class="mt-4 text-center text-sm text-gray-600">
            Already registered?
            <Link href="/login" class="font-medium text-brand underline underline-offset-4">
                Log in
            </Link>
        </p>
    </AuthLayout>
</template>
