<script setup lang="ts">
import { computed } from 'vue';
import MarkdownInline from '@/components/MarkdownInline.vue';
import { parseMarkdownBlocks } from '@/composables/useMarkdown';

const props = defineProps<{
    content: string;
}>();

const blocks = computed(() => parseMarkdownBlocks(props.content));

const headingClasses: Record<number, string> = {
    1: 'text-xl font-semibold text-gray-900',
    2: 'text-lg font-semibold text-gray-900',
    3: 'text-base font-semibold text-gray-900',
    4: 'text-sm font-semibold text-gray-900',
    5: 'text-sm font-medium text-gray-900',
    6: 'text-xs font-medium uppercase tracking-wide text-gray-600',
};
</script>

<template>
    <div class="space-y-4 text-sm leading-relaxed wrap-anywhere text-gray-800">
        <template v-for="block in blocks" :key="block.id">
            <component
                :is="`h${block.level ?? 2}`"
                v-if="block.type === 'heading'"
                :class="headingClasses[block.level ?? 2]"
            >
                <MarkdownInline :tokens="block.tokens ?? []" />
            </component>

            <pre
                v-else-if="block.type === 'code'"
                class="overflow-x-auto rounded-xl border border-gray-200 bg-gray-900 p-4 font-mono text-xs leading-relaxed text-gray-100"
            ><code>{{ block.code }}</code></pre>

            <blockquote
                v-else-if="block.type === 'blockquote'"
                class="border-l-4 border-brand/40 pl-4 whitespace-pre-line text-gray-600 italic"
            >
                <MarkdownInline :tokens="block.tokens ?? []" />
            </blockquote>

            <ul
                v-else-if="block.type === 'ul'"
                class="list-disc space-y-1.5 pl-5 text-gray-800"
            >
                <li v-for="item in block.items" :key="item.id">
                    <MarkdownInline :tokens="item.tokens" />
                </li>
            </ul>

            <ol
                v-else-if="block.type === 'ol'"
                class="list-decimal space-y-1.5 pl-5 text-gray-800"
            >
                <li v-for="item in block.items" :key="item.id">
                    <MarkdownInline :tokens="item.tokens" />
                </li>
            </ol>

            <hr v-else-if="block.type === 'hr'" class="border-gray-200" />

            <p v-else class="whitespace-pre-line">
                <MarkdownInline :tokens="block.tokens ?? []" />
            </p>
        </template>
    </div>
</template>
