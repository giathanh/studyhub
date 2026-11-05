<script setup lang="ts">
import type { FormComponentRef } from '@inertiajs/core';
import { Form } from '@inertiajs/vue3';
import { useTemplateRef } from 'vue';
import FormField from '@/components/FormField.vue';
import FormSelect from '@/components/FormSelect.vue';
import MarkdownEditor from '@/components/MarkdownEditor.vue';
import AppButton from '@/components/AppButton.vue';
import AppCard from '@/components/AppCard.vue';
import { useUnsavedChanges } from '@/composables/useUnsavedChanges';
import type { Lesson, StatusOption } from '@/types/course';

withDefaults(
    defineProps<{
        action: string;
        method: 'post' | 'put';
        statuses: StatusOption[];
        submitLabel: string;
        lesson?: Lesson;
    }>(),
    {
        lesson: undefined,
    },
);

const formRef = useTemplateRef<FormComponentRef>('formRef');

useUnsavedChanges(() =>
    Boolean(formRef.value?.isDirty && !formRef.value?.processing && !formRef.value?.wasSuccessful),
);
</script>

<template>
    <AppCard class="p-6">
        <Form
            ref="formRef"
            :action="action"
            :method="method"
            class="space-y-4"
            #default="{ errors, processing }"
        >
            <FormField label="Title" name="title" :value="lesson?.title" :error="errors.title" />
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField
                    label="Section"
                    name="section"
                    :required="false"
                    :value="lesson?.section"
                    :error="errors.section"
                />
                <FormSelect
                    label="Status"
                    name="status"
                    :options="statuses"
                    :value="lesson?.status"
                    :error="errors.status"
                />
            </div>
            <MarkdownEditor
                label="Content"
                name="content"
                :rows="18"
                :value="lesson?.content"
                :error="errors.content"
            />

            <AppButton type="submit" :disabled="processing">
                {{ processing ? 'Saving…' : submitLabel }}
            </AppButton>
        </Form>
    </AppCard>
</template>
