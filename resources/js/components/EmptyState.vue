<script setup lang="ts">
defineProps<{
    title: string;
    description: string;
}>();
</script>

<template>
    <div class="rounded-xl border border-dashed border-line-strong/50 px-6 py-12 text-center">
        <h2 class="font-serif text-xl font-medium text-ink">{{ title }}</h2>
        <p class="mx-auto mt-1 max-w-md text-sm text-ink-soft">{{ description }}</p>
        <div v-if="$slots.default" class="mt-5 flex flex-wrap justify-center gap-2">
            <slot />
        </div>
    </div>
</template>
