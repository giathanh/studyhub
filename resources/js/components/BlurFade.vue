<script setup lang="ts">
/** Vue port of Magic UI's Blur Fade (MIT): https://magicui.design/docs/components/blur-fade */
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        duration?: number;
        delay?: number;
        offset?: number;
        direction?: 'up' | 'down' | 'left' | 'right';
        blur?: string;
    }>(),
    {
        duration: 0.4,
        delay: 0,
        offset: 6,
        direction: 'down',
        blur: '6px',
    },
);

const animationStyle = computed(() => {
    const isHorizontal =
        props.direction === 'left' || props.direction === 'right';
    const startsNegative =
        props.direction === 'right' || props.direction === 'down';
    const distance = `${startsNegative ? -props.offset : props.offset}px`;

    return {
        '--blur-fade-duration': `${props.duration}s`,
        '--blur-fade-delay': `${0.04 + props.delay}s`,
        '--blur-fade-blur': props.blur,
        '--blur-fade-x': isHorizontal ? distance : '0px',
        '--blur-fade-y': isHorizontal ? '0px' : distance,
    };
});
</script>

<template>
    <div class="animate-blur-fade motion-reduce:animate-none" :style="animationStyle">
        <slot />
    </div>
</template>
