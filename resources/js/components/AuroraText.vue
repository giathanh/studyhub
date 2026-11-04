<script setup lang="ts">
/** Vue port of Magic UI's Aurora Text (MIT): https://magicui.design/docs/components/aurora-text */
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        colors?: string[];
    }>(),
    {
        colors: () => [
            'var(--color-brand)',
            'var(--color-brand-accent)',
            '#2563eb',
            '#0891b2',
        ],
    },
);

const backgroundImage = computed(
    () =>
        `linear-gradient(135deg, ${props.colors.join(', ')}, ${props.colors[0]})`,
);
</script>

<template>
    <span class="relative inline-block">
        <span class="sr-only"><slot /></span>
        <span
            aria-hidden="true"
            class="relative animate-aurora bg-size-[200%_auto] bg-clip-text text-transparent motion-reduce:animate-none"
            :style="{ backgroundImage }"
        >
            <slot />
        </span>
    </span>
</template>
