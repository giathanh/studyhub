<script setup lang="ts">
/** Vue port of Magic UI's Magic Card (MIT): https://magicui.design/docs/components/magic-card */
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        gradientSize?: number;
        gradientColor?: string;
        gradientOpacity?: number;
        gradientFrom?: string;
        gradientTo?: string;
    }>(),
    {
        gradientSize: 200,
        gradientColor: '#d9d9d955',
        gradientOpacity: 0.8,
        gradientFrom: 'var(--color-brand)',
        gradientTo: 'var(--color-brand-accent)',
    },
);

const offscreenPosition = computed(() => `${-props.gradientSize}px`);

const spotlight = computed(
    () =>
        `${props.gradientSize}px circle at var(--mouse-x) var(--mouse-y)`,
);

const cardStyle = computed(() => ({
    '--mouse-x': offscreenPosition.value,
    '--mouse-y': offscreenPosition.value,
    background: `linear-gradient(#fff 0 0) padding-box, radial-gradient(${spotlight.value}, ${props.gradientFrom}, ${props.gradientTo}, var(--color-gray-200) 100%) border-box`,
}));

function trackPointer(event: PointerEvent): void {
    const card = event.currentTarget as HTMLElement;
    const { left, top } = card.getBoundingClientRect();

    card.style.setProperty('--mouse-x', `${event.clientX - left}px`);
    card.style.setProperty('--mouse-y', `${event.clientY - top}px`);
}

function resetPointer(event: PointerEvent): void {
    const card = event.currentTarget as HTMLElement;

    card.style.setProperty('--mouse-x', offscreenPosition.value);
    card.style.setProperty('--mouse-y', offscreenPosition.value);
}
</script>

<template>
    <div
        class="relative isolate overflow-hidden rounded-xl border border-transparent shadow-sm"
        :style="cardStyle"
        @pointermove="trackPointer"
        @pointerleave="resetPointer"
    >
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-px rounded-[inherit]"
            :style="{
                background: `radial-gradient(${spotlight}, ${gradientColor}, transparent 100%)`,
                opacity: gradientOpacity,
            }"
        />
        <div class="relative">
            <slot />
        </div>
    </div>
</template>
