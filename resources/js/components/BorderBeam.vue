<script setup lang="ts">
/** Vue port of Magic UI's Border Beam (MIT): https://magicui.design/docs/components/border-beam */
withDefaults(
    defineProps<{
        size?: number;
        duration?: number;
        colorFrom?: string;
        colorTo?: string;
        borderWidth?: number;
    }>(),
    {
        size: 50,
        duration: 6,
        colorFrom: 'var(--color-brand-accent)',
        colorTo: 'var(--color-brand)',
        borderWidth: 1,
    },
);
</script>

<template>
    <div
        aria-hidden="true"
        class="pointer-events-none absolute inset-0 rounded-[inherit] border-(length:--border-beam-width) border-transparent mask-[linear-gradient(transparent,transparent),linear-gradient(#000,#000)] mask-intersect [mask-clip:padding-box,border-box] motion-reduce:hidden"
        :style="{ '--border-beam-width': `${borderWidth}px` }"
    >
        <div
            class="absolute aspect-square animate-border-beam bg-linear-to-l from-(--color-from) via-(--color-to) to-transparent"
            :style="{
                width: `${size}px`,
                offsetPath: `rect(0 auto auto 0 round ${size}px)`,
                '--border-beam-duration': `${duration}s`,
                '--color-from': colorFrom,
                '--color-to': colorTo,
            }"
        />
    </div>
</template>
