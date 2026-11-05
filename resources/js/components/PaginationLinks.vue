<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppButton from '@/components/AppButton.vue';
import type { Paginated } from '@/types/course';

defineProps<{
    pagination: Pick<Paginated<unknown>, 'links' | 'meta'>;
}>();
</script>

<template>
    <nav
        v-if="pagination.meta.last_page > 1"
        aria-label="Pagination"
        class="mt-6 flex items-center justify-between gap-3 text-sm"
    >
        <AppButton v-if="pagination.links.prev" :as="Link" :href="pagination.links.prev" variant="secondary" size="sm">
            Previous
        </AppButton>
        <span v-else />
        <span class="text-ink-soft">
            Page {{ pagination.meta.current_page }} of {{ pagination.meta.last_page }}
        </span>
        <AppButton v-if="pagination.links.next" :as="Link" :href="pagination.links.next" variant="secondary" size="sm">
            Next
        </AppButton>
        <span v-else />
    </nav>
</template>
