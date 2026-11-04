<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';

const visibleAfterScrollY = 400;
const isVisible = ref(false);

function updateVisibility(): void {
    isVisible.value = window.scrollY > visibleAfterScrollY;
}

function scrollToTop(): void {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    window.scrollTo({ top: 0, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
}

onMounted(() => {
    updateVisibility();
    window.addEventListener('scroll', updateVisibility, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('scroll', updateVisibility);
});
</script>

<template>
    <Transition
        enter-active-class="transition-opacity duration-200 motion-reduce:transition-none"
        enter-from-class="opacity-0"
        leave-active-class="transition-opacity duration-200 motion-reduce:transition-none"
        leave-to-class="opacity-0"
    >
        <button
            v-if="isVisible"
            type="button"
            aria-label="Scroll to top"
            class="fixed right-4 bottom-4 z-10 flex size-11 cursor-pointer items-center justify-center rounded-full border border-gray-200 bg-white text-gray-700 shadow-md transition-colors hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand sm:right-6 sm:bottom-6"
            @click="scrollToTop"
        >
            <svg
                aria-hidden="true"
                class="size-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.75"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M12 19V5m-6 6 6-6 6 6" />
            </svg>
        </button>
    </Transition>
</template>
