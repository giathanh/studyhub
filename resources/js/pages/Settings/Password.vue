<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import FormField from '@/components/FormField.vue';
import AppButton from '@/components/AppButton.vue';
import AppCard from '@/components/AppCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
</script>

<template>
    <AppLayout title="Change password">
        <AppCard class="max-w-md p-6">
            <Form
                action="/user/password"
                method="put"
                error-bag="updatePassword"
                reset-on-success
                :reset-on-error="[
                    'current_password',
                    'password',
                    'password_confirmation',
                ]"
                class="space-y-4"
                #default="{ errors, processing, recentlySuccessful }"
            >
                <FormField
                    label="Current password"
                    name="current_password"
                    type="password"
                    autocomplete="current-password"
                    :error="errors.current_password"
                />
                <FormField
                    label="New password"
                    name="password"
                    type="password"
                    autocomplete="new-password"
                    :error="errors.password"
                />
                <FormField
                    label="Confirm new password"
                    name="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    :error="errors.password_confirmation"
                />

                <div class="flex items-center gap-3">
                    <AppButton type="submit" :disabled="processing">
                        {{ processing ? 'Saving…' : 'Save' }}
                    </AppButton>
                    <p
                        v-if="recentlySuccessful"
                        role="status"
                        class="text-sm text-success"
                    >
                        Password updated.
                    </p>
                </div>
            </Form>
        </AppCard>
    </AppLayout>
</template>
