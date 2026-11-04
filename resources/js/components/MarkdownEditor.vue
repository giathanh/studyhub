<script setup lang="ts">
import { ref, watch } from 'vue';
import MarkdownContent from '@/components/MarkdownContent.vue';

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
        hint: 'Write in Markdown. Raw HTML is disabled and unsafe links are blocked.',
        error: undefined,
        required: false,
    },
);

const activeTab = ref<'write' | 'preview'>('write');
const draft = ref(props.value ?? '');

watch(
    () => props.value,
    (newValue) => {
        draft.value = newValue ?? '';
    },
);

const tabButtonClass =
    'cursor-pointer rounded-md px-3 py-1 text-xs font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand';
</script>

<template>
    <div>
        <div class="flex items-center justify-between gap-2">
            <label :for="name" class="block text-sm font-medium text-gray-900">
                {{ label }}
            </label>
            <div role="tablist" aria-label="Markdown mode" class="inline-flex rounded-lg bg-gray-100 p-0.5">
                <button
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === 'write'"
                    :class="[
                        tabButtonClass,
                        activeTab === 'write'
                            ? 'bg-white text-gray-900 shadow-2xs'
                            : 'text-gray-600 hover:text-gray-900',
                    ]"
                    @click="activeTab = 'write'"
                >
                    Write
                </button>
                <button
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === 'preview'"
                    :class="[
                        tabButtonClass,
                        activeTab === 'preview'
                            ? 'bg-white text-gray-900 shadow-2xs'
                            : 'text-gray-600 hover:text-gray-900',
                    ]"
                    @click="activeTab = 'preview'"
                >
                    Preview
                </button>
            </div>
        </div>

        <textarea
            v-show="activeTab === 'write'"
            :id="name"
            v-model="draft"
            :name="name"
            :rows="rows"
            :required="required"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="error ? `${name}-error` : undefined"
            class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm shadow-xs transition-shadow focus:border-brand focus:ring-3 focus:ring-brand/20 focus:outline-none aria-invalid:border-red-500"
        />

        <div
            v-if="activeTab === 'preview'"
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
