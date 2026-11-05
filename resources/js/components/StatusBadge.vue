<script setup lang="ts">
defineProps<{
    status: string;
    label: string;
}>();

const statusClasses: Record<string, string> = {
    in_progress: 'bg-highlight-soft text-ink',
    completed: 'bg-success-soft text-success',
};
</script>

<template>
    <span
        class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium"
        :class="statusClasses[status] ?? 'bg-sunken text-ink-soft'"
    >
        {{ label }}
    </span>
</template>
