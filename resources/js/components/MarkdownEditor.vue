<script setup lang="ts">
import { defineAsyncComponent, nextTick, ref, useTemplateRef, watch } from 'vue';
import MarkdownContent from '@/components/MarkdownContent.vue';

type EditorMode = 'visual' | 'markdown' | 'preview';

interface VisualEditorHandle {
    keepsContent: (markdown: string) => boolean;
    load: (markdown: string) => void;
    focus: () => void;
}

const props = withDefaults(
    defineProps<{
        label: string;
        name: string;
        rows?: number;
        value?: string | null;
        hint?: string;
        error?: string;
        required?: boolean;
    }>(),
    {
        rows: 16,
        value: undefined,
        hint: undefined,
        error: undefined,
        required: false,
    },
);

const modes: { value: EditorMode; label: string }[] = [
    { value: 'visual', label: 'Visual' },
    { value: 'markdown', label: 'Markdown' },
    { value: 'preview', label: 'Preview' },
];

const activeMode = ref<EditorMode>('visual');
const notice = ref<string | null>(null);
const isVisualReady = ref(false);
const hasVisualFailed = ref(false);
const draft = ref(props.value ?? '');
const sourceField = useTemplateRef<HTMLTextAreaElement>('sourceField');
const visualEditor = useTemplateRef<VisualEditorHandle>('visualEditor');

function fallBackToMarkdown(message: string): void {
    activeMode.value = 'markdown';
    notice.value = message;
}

function showVisualFailure(): void {
    fallBackToMarkdown('The visual editor could not load. Edit here, or reload the page to try again.');
}

const MarkdownVisualEditor = defineAsyncComponent({
    loader: () => import('@/components/MarkdownVisualEditor.vue'),
    onError: (_error, _retry, fail) => {
        hasVisualFailed.value = true;
        showVisualFailure();
        fail();
    },
});

function canShowVisualEditor(): boolean {
    if (hasVisualFailed.value) {
        showVisualFailure();

        return false;
    }

    if (visualEditor.value && !visualEditor.value.keepsContent(draft.value)) {
        fallBackToMarkdown(
            'This content has formatting the visual editor cannot keep exactly, such as images or code inside lists. Edit it here so nothing is lost.',
        );

        return false;
    }

    visualEditor.value?.load(draft.value);

    return true;
}

function selectMode(mode: EditorMode): void {
    notice.value = null;

    if (mode !== 'visual' || canShowVisualEditor()) {
        activeMode.value = mode;
    }
}

function handleVisualReady(): void {
    isVisualReady.value = true;

    if (activeMode.value === 'visual') {
        canShowVisualEditor();
    }
}

async function handleVisualUpdate(markdown: string): Promise<void> {
    draft.value = markdown;

    await nextTick();
    sourceField.value?.dispatchEvent(new Event('input', { bubbles: true }));
}

function focusVisualEditor(): void {
    if (activeMode.value === 'visual') {
        visualEditor.value?.focus();
    }
}

watch(
    () => props.value,
    (newValue) => {
        draft.value = newValue ?? '';
        visualEditor.value?.load(draft.value);
    },
);

const tabButtonClass =
    'cursor-pointer rounded-md px-3 py-1 text-xs font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand';
</script>

<template>
    <div>
        <div class="flex flex-wrap items-center justify-between gap-2">
            <label
                :id="`${name}-label`"
                :for="name"
                class="block text-sm font-medium text-gray-900"
                @click="focusVisualEditor"
            >
                {{ label }}
            </label>
            <div role="tablist" aria-label="Editor mode" class="inline-flex rounded-lg bg-gray-100 p-0.5">
                <button
                    v-for="mode in modes"
                    :key="mode.value"
                    type="button"
                    role="tab"
                    :aria-selected="activeMode === mode.value"
                    :class="[
                        tabButtonClass,
                        activeMode === mode.value
                            ? 'bg-white text-gray-900 shadow-2xs'
                            : 'text-gray-600 hover:text-gray-900',
                    ]"
                    @click="selectMode(mode.value)"
                >
                    {{ mode.label }}
                </button>
            </div>
        </div>

        <p
            v-if="notice"
            role="status"
            class="mt-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900"
        >
            {{ notice }}
        </p>

        <div
            v-if="!hasVisualFailed"
            v-show="activeMode === 'visual'"
            class="mt-1.5 overflow-hidden rounded-lg border bg-white shadow-xs transition-shadow focus-within:border-brand focus-within:ring-3 focus-within:ring-brand/20"
            :class="error ? 'border-red-500' : 'border-gray-300'"
        >
            <div
                v-if="!isVisualReady"
                aria-hidden="true"
                class="animate-pulse"
                :style="{ minHeight: `${rows * 1.5}rem` }"
            >
                <div class="h-11 border-b border-gray-200 bg-gray-50" />
                <div class="space-y-3 p-4">
                    <div class="h-4 w-1/3 rounded bg-gray-200" />
                    <div class="h-3 w-5/6 rounded bg-gray-100" />
                    <div class="h-3 w-2/3 rounded bg-gray-100" />
                </div>
            </div>
            <MarkdownVisualEditor
                ref="visualEditor"
                :class="{ hidden: !isVisualReady }"
                :initial-markdown="draft"
                :label-id="`${name}-label`"
                :rows="rows"
                :error-id="error ? `${name}-error` : undefined"
                @ready="handleVisualReady"
                @update="handleVisualUpdate"
            />
        </div>

        <textarea
            v-show="activeMode === 'markdown'"
            :id="name"
            ref="sourceField"
            v-model="draft"
            :name="name"
            :rows="rows"
            :required="required && activeMode === 'markdown'"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="error ? `${name}-error` : undefined"
            class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm shadow-xs transition-shadow focus:border-brand focus:ring-3 focus:ring-brand/20 focus:outline-none aria-invalid:border-red-500"
        />

        <div
            v-if="activeMode === 'preview'"
            class="mt-1.5 min-h-48 rounded-lg border border-gray-200 bg-gray-50/60 p-4"
        >
            <MarkdownContent v-if="draft.trim() !== ''" :content="draft" />
            <p v-else class="text-sm text-gray-500">Nothing to preview yet.</p>
        </div>

        <p v-if="hint" class="mt-1 text-sm text-gray-500">{{ hint }}</p>
        <p v-if="error" :id="`${name}-error`" class="mt-1 text-sm text-red-600">
            {{ error }}
        </p>
    </div>
</template>
