<script setup lang="ts">
import type { FormComponentRef } from '@inertiajs/core';
import { Form } from '@inertiajs/vue3';
import { computed, ref, useTemplateRef } from 'vue';
import FormField from '@/components/FormField.vue';
import MarkdownEditor from '@/components/MarkdownEditor.vue';
import AppButton from '@/components/AppButton.vue';
import AppCard from '@/components/AppCard.vue';
import { useUnsavedChanges } from '@/composables/useUnsavedChanges';
import type { CourseOption, Note } from '@/types/course';

const props = withDefaults(
    defineProps<{
        action: string;
        method: 'post' | 'put';
        courses: CourseOption[];
        submitLabel: string;
        note?: Note;
        initialCourseId?: number | null;
        initialLessonId?: number | null;
    }>(),
    {
        note: undefined,
        initialCourseId: null,
        initialLessonId: null,
    },
);

const formRef = useTemplateRef<FormComponentRef>('formRef');

useUnsavedChanges(() =>
    Boolean(formRef.value?.isDirty && !formRef.value?.processing && !formRef.value?.wasSuccessful),
);

const selectedCourseId = ref<string>(
    props.note?.course_id
        ? String(props.note.course_id)
        : props.initialCourseId
          ? String(props.initialCourseId)
          : '',
);

const selectedLessonId = ref<string>(
    props.note?.lesson_id
        ? String(props.note.lesson_id)
        : props.initialLessonId
          ? String(props.initialLessonId)
          : '',
);

const availableLessons = computed(() => {
    if (selectedCourseId.value === '') {
        return [];
    }

    const course = props.courses.find((item) => String(item.id) === selectedCourseId.value);

    return course?.lessons ?? [];
});

function handleCourseChange(event: Event): void {
    selectedCourseId.value = (event.target as HTMLSelectElement).value;

    const lessonStillValid = availableLessons.value.some(
        (lesson) => String(lesson.id) === selectedLessonId.value,
    );

    if (!lessonStillValid) {
        selectedLessonId.value = '';
    }
}

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
            <FormField label="Title" name="title" :value="note?.title" :error="errors.title" />

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="course_id" class="block text-sm font-medium text-ink">
                        Course (optional)
                    </label>
                    <select
                        id="course_id"
                        name="course_id"
                        :value="selectedCourseId"
                        :aria-invalid="errors.course_id ? 'true' : undefined"
                        :aria-describedby="errors.course_id ? 'course_id-error' : undefined"
                        class="control mt-1.5"
                        @change="handleCourseChange"
                    >
                        <option value="">Standalone note</option>
                        <option v-for="course in courses" :key="course.id" :value="String(course.id)">
                            {{ course.title }}
                        </option>
                    </select>
                    <p v-if="errors.course_id" id="course_id-error" class="mt-1 text-sm text-danger">
                        {{ errors.course_id }}
                    </p>
                </div>

                <div>
                    <label for="lesson_id" class="block text-sm font-medium text-ink">
                        Lesson (optional)
                    </label>
                    <select
                        id="lesson_id"
                        v-model="selectedLessonId"
                        name="lesson_id"
                        :disabled="selectedCourseId === ''"
                        :aria-invalid="errors.lesson_id ? 'true' : undefined"
                        :aria-describedby="errors.lesson_id ? 'lesson_id-error' : undefined"
                        class="control mt-1.5"
                    >
                        <option value="">
                            {{ selectedCourseId === '' ? 'Choose a course first' : 'No specific lesson' }}
                        </option>
                        <option
                            v-for="lesson in availableLessons"
                            :key="lesson.id"
                            :value="String(lesson.id)"
                        >
                            {{ lesson.title }}
                        </option>
                    </select>
                    <p v-if="errors.lesson_id" id="lesson_id-error" class="mt-1 text-sm text-danger">
                        {{ errors.lesson_id }}
                    </p>
                </div>
            </div>

            <MarkdownEditor
                label="Body"
                name="body"
                :rows="16"
                required
                :value="note?.body"
                :error="errors.body"
            />

            <AppButton type="submit" :disabled="processing">
                {{ processing ? 'Saving…' : submitLabel }}
            </AppButton>
        </Form>
    </AppCard>
</template>
