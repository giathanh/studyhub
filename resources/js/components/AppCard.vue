<script setup lang="ts">
import type { Component } from 'vue';

withDefaults(
    defineProps<{
        as?: string | Component;
    }>(),
    { as: 'div' },
);
</script>

<template>
    <component :is="as" class="rounded-xl border border-line bg-surface">
        <slot />
    </component>
</template>
