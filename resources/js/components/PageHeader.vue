<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        backHref?: string;
        backLabel?: string;
    }>(),
    {
        description: undefined,
        backHref: undefined,
        backLabel: undefined,
    },
);
</script>

<template>
    <header class="mb-8">
        <Link
            v-if="backHref"
            :href="backHref"
            class="mb-3 inline-block max-w-full truncate rounded text-sm font-medium text-ink-soft transition-colors hover:text-brand"
        >
            ← {{ backLabel }}
        </Link>
        <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-4">
            <div class="min-w-0 flex-1 basis-80">
                <p v-if="$slots.eyebrow" class="mb-1 text-sm font-medium text-ink-faint">
                    <slot name="eyebrow" />
                </p>
                <h1 class="font-serif text-3xl leading-tight font-medium tracking-tight wrap-anywhere text-ink sm:text-4xl">
                    {{ title }}
                </h1>
                <div v-if="$slots.meta || description" class="mt-2 text-sm text-ink-soft">
                    <slot name="meta">{{ description }}</slot>
                </div>
            </div>
            <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
                <slot name="actions" />
            </div>
        </div>
    </header>
</template>
