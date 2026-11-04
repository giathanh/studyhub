<script setup lang="ts">
/** Vue port of Magic UI's Animated Grid Pattern (MIT): https://magicui.design/docs/components/animated-grid-pattern */
import { onMounted, onUnmounted, ref, useId, useTemplateRef, watch } from 'vue';

type Square = {
    id: number;
    column: number;
    row: number;
    iteration: number;
};

const props = withDefaults(
    defineProps<{
        width?: number;
        height?: number;
        numSquares?: number;
        maxOpacity?: number;
        duration?: number;
    }>(),
    {
        width: 40,
        height: 40,
        numSquares: 50,
        maxOpacity: 0.5,
        duration: 4,
    },
);

const patternId = useId();
const container = useTemplateRef<SVGSVGElement>('container');
const dimensions = ref({ width: 0, height: 0 });
const squares = ref<Square[]>([]);

function randomCell(): Pick<Square, 'column' | 'row'> {
    return {
        column: Math.floor((Math.random() * dimensions.value.width) / props.width),
        row: Math.floor((Math.random() * dimensions.value.height) / props.height),
    };
}

function moveSquare(square: Square): void {
    Object.assign(square, randomCell(), { iteration: square.iteration + 1 });
}

watch([dimensions, () => props.numSquares], () => {
    squares.value = Array.from({ length: props.numSquares }, (_, id) => ({
        id,
        iteration: 0,
        ...randomCell(),
    }));
});

const resizeObserver = new ResizeObserver(([entry]) => {
    const { width, height } = entry.contentRect;

    if (width !== dimensions.value.width || height !== dimensions.value.height) {
        dimensions.value = { width, height };
    }
});

onMounted(() => {
    if (container.value) {
        resizeObserver.observe(container.value);
    }
});

onUnmounted(() => resizeObserver.disconnect());
</script>

<template>
    <svg
        ref="container"
        aria-hidden="true"
        class="pointer-events-none absolute inset-0 h-full w-full stroke-gray-400/30"
        :style="{
            '--grid-square-duration': `${duration}s`,
            '--grid-square-max-opacity': maxOpacity,
        }"
    >
        <defs>
            <pattern
                :id="patternId"
                :width="width"
                :height="height"
                patternUnits="userSpaceOnUse"
                x="-1"
                y="-1"
            >
                <path :d="`M.5 ${height}V.5H${width}`" fill="none" />
            </pattern>
        </defs>
        <rect width="100%" height="100%" :fill="`url(#${patternId})`" />
        <svg x="-1" y="-1" class="overflow-visible motion-reduce:hidden">
            <rect
                v-for="square in squares"
                :key="`${square.id}-${square.iteration}`"
                class="animate-grid-square"
                :style="{ '--grid-square-delay': `${square.id * 0.1}s` }"
                :width="width - 1"
                :height="height - 1"
                :x="square.column * width + 1"
                :y="square.row * height + 1"
                fill="currentColor"
                stroke-width="0"
                @animationend="moveSquare(square)"
            />
        </svg>
    </svg>
</template>
