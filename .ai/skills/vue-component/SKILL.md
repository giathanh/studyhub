---
name: vue-component
description: "Writes Vue 3 single-file components, composables, and layouts to StudyHub conventions: script setup, typed props, emits, slots, and when to extract a component. Use when creating or refactoring anything in resources/js, including when the user asks for a UI element, modal, table, or shared logic. Complements inertia-vue-development, which covers forms, links, and Inertia data loading."
---

# Vue Component

Inertia APIs (`<Form>`, `<Link>`, `useForm`, deferred props) are in `inertia-vue-development`. This skill covers plain Vue structure.

## Page or component

- **Page** (`resources/js/pages`): receives props from Laravel and arranges components. Keep it free of reusable markup.
- **Component** (`resources/js/components`): receives data through props and reports through emits. It does not read `usePage()`, so it stays reusable.
- Extract a component when markup repeats or a file passes about 150 lines. Do not extract single-use fragments.

## Component shape

Order the file as `<script setup>`, then `<template>`, then `<style>` only if needed.

```vue
<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        course: { id: number; title: string };
        compact?: boolean;
    }>(),
    { compact: false },
);

const emit = defineEmits<{
    enroll: [courseId: number];
}>();

const title = computed(() => props.course.title.trim());
</script>

<template>
    <article :class="compact ? 'p-2' : 'p-4'">
        <h2 class="font-semibold">{{ title }}</h2>
        <slot name="meta" />
        <button type="button" @click="emit('enroll', course.id)">Enroll</button>
    </article>
</template>
```

## Rules that prevent bugs

- Never mutate a prop. Emit the change, or use `defineModel()` for two-way binding.
- Use `computed` for derived values, not `watch` that writes to a `ref`.
- Do not destructure `props` into plain variables outside `computed`, unless you have checked it stays reactive.
- Give every `v-for` a stable `:key` from the data, never the index.
- Do not put `v-if` and `v-for` on the same element. Filter in a `computed`.
- Clean up in `onUnmounted` whatever you add in `onMounted`, such as timers and listeners.
- Set `type="button"` on buttons inside forms that do not submit.

## Composables

Put logic used by two or more components in `resources/js/composables/useThing.ts`. It exports one `useThing()` function that returns refs and functions. Do not keep module-level state: with Inertia it persists across page visits and leaks between pages.

## Accessibility

Use semantic elements (`button`, `nav`, `label`). Every input has a label, every icon-only button has `aria-label`, and interactive elements work from the keyboard.

## Text

Every page sets a `<Head title="...">`. Handle loading, empty, and error states, not only the populated case.
