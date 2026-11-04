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
                <li
                    v-for="item in block.items"
                    :key="item.id"
                    :class="item.checked !== undefined ? 'flex list-none items-start gap-2' : undefined"
                >
                    <input
                        v-if="item.checked !== undefined"
                        type="checkbox"
                        :checked="item.checked"
                        disabled
                        :aria-label="item.tokens.map((token) => token.text).join('') || 'Checklist item'"
                        class="mt-1 size-4 shrink-0 accent-brand"
                    />
                    <span><MarkdownInline :tokens="item.tokens" /></span>
                </li>
            </ul>

            <ol
                v-else-if="block.type === 'ol'"
                class="list-decimal space-y-1.5 pl-5 text-gray-800"
            >
                <li
                    v-for="item in block.items"
                    :key="item.id"
                    :class="item.checked !== undefined ? 'flex list-none items-start gap-2' : undefined"
                >
                    <input
                        v-if="item.checked !== undefined"
                        type="checkbox"
                        :checked="item.checked"
                        disabled
                        :aria-label="item.tokens.map((token) => token.text).join('') || 'Checklist item'"
                        class="mt-1 size-4 shrink-0 accent-brand"
                    />
                    <span><MarkdownInline :tokens="item.tokens" /></span>
                </li>
            </ol>

            <div v-else-if="block.type === 'table'" class="max-w-full overflow-x-auto rounded-lg border border-gray-200">
                <table class="w-full border-collapse text-left text-sm">
                    <thead class="bg-gray-50 text-gray-900">
                        <tr>
                            <th v-for="column in block.columns" :key="column.id" scope="col"
                                :style="{ textAlign: column.align }"
                                class="border-b border-gray-200 px-4 py-3 font-semibold">
                                <MarkdownInline :tokens="column.tokens" />
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="row in block.rows" :key="row.id">
                            <td v-for="(cell, index) in row.cells" :key="cell.id"
                                :style="{ textAlign: block.columns?.[index]?.align }"
                                class="min-w-32 px-4 py-3 align-top">
                                <MarkdownInline :tokens="cell.tokens" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <hr v-else-if="block.type === 'hr'" class="border-gray-200" />

            <p v-else class="whitespace-pre-line">
                <MarkdownInline :tokens="block.tokens ?? []" />
            </p>
        </template>
    </div>
</template>
