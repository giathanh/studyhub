<script setup lang="ts">
import type { InlineToken } from '@/composables/useMarkdown';

defineProps<{
    tokens: InlineToken[];
}>();
</script>

<template>
    <template v-for="token in tokens" :key="token.id">
        <code
            v-if="token.type === 'code'"
            class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-gray-900 not-italic"
        >{{ token.text }}</code>
        <a
            v-else-if="token.type === 'link'"
            :href="token.href"
            target="_blank"
            rel="noopener noreferrer"
            class="font-medium text-brand underline underline-offset-2 hover:text-brand-accent"
        ><MarkdownInline :tokens="token.children ?? []" /></a>
        <span v-else-if="token.type === 'unsafe_link'" class="text-gray-500">{{ token.text }}</span>
        <strong v-else-if="token.type === 'strong'" class="font-semibold text-gray-900">
            <MarkdownInline :tokens="token.children ?? []" />
        </strong>
        <em v-else-if="token.type === 'em'" class="italic">
            <MarkdownInline :tokens="token.children ?? []" />
        </em>
        <template v-else>{{ token.text }}</template>
    </template>
</template>
