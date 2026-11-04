<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Paginated } from '@/types/course';

defineProps<{
    pagination: Pick<Paginated<unknown>, 'links' | 'meta'>;
}>();

const linkClass =
    'rounded-lg border border-gray-200 bg-white px-3 py-1.5 font-medium text-gray-700 transition-colors hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand';
</script>

<template>
    <nav
        v-if="pagination.meta.last_page > 1"
        aria-label="Pagination"
        class="mt-6 flex items-center justify-between gap-3 text-sm"
    >
        <Link v-if="pagination.links.prev" :href="pagination.links.prev" :class="linkClass">
            Previous
        </Link>
        <span v-else />
        <span class="text-gray-600">
            Page {{ pagination.meta.current_page }} of {{ pagination.meta.last_page }}
        </span>
        <Link v-if="pagination.links.next" :href="pagination.links.next" :class="linkClass">
            Next
        </Link>
        <span v-else />
    </nav>
</template>
