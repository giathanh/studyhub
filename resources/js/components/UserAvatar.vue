<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        name: string;
        size?: 'sm' | 'md';
    }>(),
    { size: 'sm' },
);

const initials = computed(() => {
    const words = props.name.trim().split(/\s+/).filter(Boolean);
    const firstAndLastWords = words.length > 1 ? [words[0], words[words.length - 1]] : words;

    return firstAndLastWords
        .map((word) => Array.from(word)[0])
        .join('')
        .toLocaleUpperCase();
});
</script>

<template>
    <span
        aria-hidden="true"
        class="flex shrink-0 items-center justify-center rounded-full bg-linear-to-br from-brand to-brand-accent font-semibold text-white"
        :class="size === 'md' ? 'size-12 text-base' : 'size-8 text-xs'"
    >
        {{ initials }}
    </span>
</template>
