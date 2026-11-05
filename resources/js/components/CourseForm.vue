<script setup lang="ts">
import type { FormComponentRef } from '@inertiajs/core';
import { Form } from '@inertiajs/vue3';
import { useTemplateRef } from 'vue';
import FormField from '@/components/FormField.vue';
import FormSelect from '@/components/FormSelect.vue';
import FormTextarea from '@/components/FormTextarea.vue';
import AppButton from '@/components/AppButton.vue';
import AppCard from '@/components/AppCard.vue';
import { useUnsavedChanges } from '@/composables/useUnsavedChanges';
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

const formRef = useTemplateRef<FormComponentRef>('formRef');

useUnsavedChanges(() =>
    Boolean(formRef.value?.isDirty && !formRef.value?.processing && !formRef.value?.wasSuccessful),
);
</script>

<template>
    <AppCard class="max-w-2xl p-6">
        <Form
            ref="formRef"
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

            <AppButton type="submit" :disabled="processing">
                {{ processing ? 'Saving…' : submitLabel }}
            </AppButton>
        </Form>
    </AppCard>
</template>
