<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import FormField from '@/components/FormField.vue';
import FormSelect from '@/components/FormSelect.vue';
import FormTextarea from '@/components/FormTextarea.vue';
import MagicCard from '@/components/MagicCard.vue';
import ShimmerButton from '@/components/ShimmerButton.vue';
import type { Course, StatusOption } from '@/types/course';

withDefaults(
    defineProps<{
        action: string;
        method: 'post' | 'put';
        statuses: StatusOption[];
        submitLabel: string;
        course?: Course;
    }>(),
    {
        course: undefined,
    },
);
</script>

<template>
    <MagicCard class="max-w-2xl p-6">
        <Form
            :action="action"
            :method="method"
            class="space-y-4"
            #default="{ errors, processing }"
        >
            <FormField label="Title" name="title" :value="course?.title" :error="errors.title" />
            <FormTextarea
                label="Description"
                name="description"
                :value="course?.description"
                :error="errors.description"
            />
            <div class="grid gap-4 sm:grid-cols-2">
                <FormSelect
                    label="Status"
                    name="status"
                    :options="statuses"
                    :value="course?.status"
                    :error="errors.status"
                />
                <FormField
                    label="Target date"
                    name="target_date"
                    type="date"
                    :required="false"
                    :value="course?.target_date"
                    :error="errors.target_date"
                />
            </div>

            <ShimmerButton type="submit" :disabled="processing">
                {{ processing ? 'Saving…' : submitLabel }}
            </ShimmerButton>
        </Form>
    </MagicCard>
</template>
