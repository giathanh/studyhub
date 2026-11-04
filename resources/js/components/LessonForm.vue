<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import FormField from '@/components/FormField.vue';
import FormTextarea from '@/components/FormTextarea.vue';
import MagicCard from '@/components/MagicCard.vue';
import ShimmerButton from '@/components/ShimmerButton.vue';
import type { Lesson } from '@/types/course';

withDefaults(
    defineProps<{
        action: string;
        method: 'post' | 'put';
        submitLabel: string;
        lesson?: Lesson;
    }>(),
    {
        lesson: undefined,
    },
);
</script>

<template>
    <MagicCard class="p-6">
        <Form
            :action="action"
            :method="method"
            class="space-y-4"
            #default="{ errors, processing }"
        >
            <FormField label="Title" name="title" :value="lesson?.title" :error="errors.title" />
            <FormField
                label="Section"
                name="section"
                :required="false"
                :value="lesson?.section"
                :error="errors.section"
            />
            <FormTextarea
                label="Content"
                name="content"
                hint="Write in Markdown."
                monospace
                :rows="18"
                :value="lesson?.content"
                :error="errors.content"
            />

            <ShimmerButton type="submit" :disabled="processing">
                {{ processing ? 'Saving…' : submitLabel }}
            </ShimmerButton>
        </Form>
    </MagicCard>
</template>
