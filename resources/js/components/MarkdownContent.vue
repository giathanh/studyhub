<script setup lang="ts">
import { computed } from 'vue';
import MarkdownInline from '@/components/MarkdownInline.vue';
import { parseMarkdownBlocks } from '@/composables/useMarkdown';

const props = defineProps<{
    content: string;
}>();

const blocks = computed(() => parseMarkdownBlocks(props.content));

const headingClasses: Record<number, string> = {
    1: 'pt-2 text-3xl leading-tight font-medium tracking-tight text-ink',
    2: 'pt-2 text-2xl leading-snug font-medium tracking-tight text-ink',
    3: 'pt-1 text-xl leading-snug font-medium text-ink',
    4: 'text-lg font-semibold text-ink',
    5: 'text-base font-semibold text-ink',
    6: 'font-sans text-xs font-medium tracking-wide text-ink-soft uppercase',
};
</script>

<template>
    <div class="space-y-4 font-serif text-lg leading-relaxed wrap-anywhere text-ink">
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
                class="overflow-x-auto rounded-lg bg-ink p-4 font-mono text-[0.8125rem] leading-relaxed text-paper"
            ><code>{{ block.code }}</code></pre>

            <blockquote
                v-else-if="block.type === 'blockquote'"
                class="border-l-4 border-highlight pl-4 whitespace-pre-line text-ink-soft italic"
            >
                <MarkdownInline :tokens="block.tokens ?? []" />
            </blockquote>

            <ul
                v-else-if="block.type === 'ul'"
                class="list-disc space-y-1.5 pl-5 text-ink"
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
                class="list-decimal space-y-1.5 pl-5 text-ink"
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

            <div v-else-if="block.type === 'table'" class="max-w-full overflow-x-auto rounded-lg border border-line">
                <table class="w-full border-collapse text-left font-sans text-sm">
                    <thead class="bg-paper text-ink">
                        <tr>
                            <th v-for="column in block.columns" :key="column.id" scope="col"
                                :style="{ textAlign: column.align }"
                                class="border-b border-line px-4 py-3 font-semibold">
                                <MarkdownInline :tokens="column.tokens" />
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
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

            <hr v-else-if="block.type === 'hr'" class="border-line" />

            <p v-else class="whitespace-pre-line">
                <MarkdownInline :tokens="block.tokens ?? []" />
            </p>
        </template>
    </div>
</template>
