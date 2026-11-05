<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import FormField from '@/components/FormField.vue';
import AppButton from '@/components/AppButton.vue';
import AppCard from '@/components/AppCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps<{
    user: {
        name: string;
        email: string;
    };
}>();
</script>

<template>
    <AppLayout title="Edit profile">
        <AppCard class="max-w-md p-6">
            <Form
                action="/user/profile-information"
                method="put"
                error-bag="updateProfileInformation"
                class="space-y-4"
                #default="{ errors, processing, recentlySuccessful }"
            >
                <FormField
                    label="Name"
                    name="name"
                    autocomplete="name"
                    :value="user.name"
                    :error="errors.name"
                />
                <FormField
                    label="Email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    :value="user.email"
                    :error="errors.email"
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
                        Profile updated.
                    </p>
                </div>
            </Form>
        </AppCard>
    </AppLayout>
</template>

