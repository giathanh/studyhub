<script setup lang="ts">
withDefaults(
    defineProps<{
        value: number;
        label: string;
        size?: 'sm' | 'md';
    }>(),
    { size: 'sm' },
);
</script>

<template>
    <div
        class="w-full overflow-hidden rounded-full bg-sunken"
        :class="size === 'md' ? 'h-2.5' : 'h-1.5'"
        role="progressbar"
        :aria-valuenow="value"
        aria-valuemin="0"
        aria-valuemax="100"
        :aria-label="label"
    >
        <div
            class="h-full rounded-full transition-[width] duration-300 motion-reduce:transition-none"
            :class="value >= 100 ? 'bg-success' : 'bg-brand'"
            :style="{ width: `${value}%` }"
        />
    </div>
</template>
