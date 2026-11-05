<script setup lang="ts">
import type { Editor } from '@tiptap/vue-3';
import { computed, nextTick, ref, useTemplateRef } from 'vue';
import { sanitizeMarkdownUrl } from '@/composables/useMarkdown';

const props = defineProps<{
    editor: Editor;
}>();

interface ToolbarAction {
    label: string;
    icon: string;
    run: () => void;
    isActive?: () => boolean;
    isDisabled?: () => boolean;
}

type HeadingLevel = 1 | 2 | 3 | 4;

const headingLevels: HeadingLevel[] = [1, 2, 3, 4];

const chain = () => props.editor.chain().focus();

const actionGroups: { name: string; actions: ToolbarAction[] }[] = [
    {
        name: 'history',
        actions: [
            {
                label: 'Undo',
                icon: 'M3 7v6h6 M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13',
                run: () => chain().undo().run(),
                isDisabled: () => !props.editor.can().undo(),
            },
            {
                label: 'Redo',
                icon: 'M21 7v6h-6 M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 2.7',
                run: () => chain().redo().run(),
                isDisabled: () => !props.editor.can().redo(),
            },
        ],
    },
    {
        name: 'inline',
        actions: [
            {
                label: 'Bold',
                icon: 'M6 12h9a4 4 0 0 1 0 8H7a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h7a4 4 0 0 1 0 8',
                run: () => chain().toggleBold().run(),
                isActive: () => props.editor.isActive('bold'),
            },
            {
                label: 'Italic',
                icon: 'M19 4h-9 M14 20H5 M15 4 9 20',
                run: () => chain().toggleItalic().run(),
                isActive: () => props.editor.isActive('italic'),
            },
            {
                label: 'Inline code',
                icon: 'm16 18 6-6-6-6 M8 6l-6 6 6 6',
                run: () => chain().toggleCode().run(),
                isActive: () => props.editor.isActive('code'),
            },
            {
                label: 'Link',
                icon: 'M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71 M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71',
                run: () => toggleLinkForm(),
                isActive: () => props.editor.isActive('link'),
            },
        ],
    },
    {
        name: 'lists',
        actions: [
            {
                label: 'Bulleted list',
                icon: 'M3 5h.01 M3 12h.01 M3 19h.01 M8 5h13 M8 12h13 M8 19h13',
                run: () => chain().toggleBulletList().run(),
                isActive: () => props.editor.isActive('bulletList'),
            },
            {
                label: 'Numbered list',
                icon: 'M10 6h11 M10 12h11 M10 18h11 M4 6h1v4 M4 10h2 M6 18H4c0-1 2-2 2-3s-1-1.5-2-1',
                run: () => chain().toggleOrderedList().run(),
                isActive: () => props.editor.isActive('orderedList'),
            },
            {
                label: 'Checklist',
                icon: 'm3 7 2 2 4-4 M3 17l2 2 4-4 M13 6h8 M13 12h8 M13 18h8',
                run: () => chain().toggleTaskList().run(),
                isActive: () => props.editor.isActive('taskList'),
            },
        ],
    },
    {
        name: 'blocks',
        actions: [
            {
                label: 'Quote',
                icon: 'M3 6h18 M9 12h12 M9 18h12 M4 11v8',
                run: () => chain().toggleBlockquote().run(),
                isActive: () => props.editor.isActive('blockquote'),
            },
            {
                label: 'Code block',
                icon: 'M10 9.5 8 12l2 2.5 M14 9.5l2 2.5-2 2.5 M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z',
                run: () => chain().toggleCodeBlock().run(),
                isActive: () => props.editor.isActive('codeBlock'),
            },
            {
                label: 'Divider',
                icon: 'M5 12h14',
                run: () => chain().setHorizontalRule().run(),
            },
            {
                label: 'Table',
                icon: 'M12 3v18 M3 9h18 M3 15h18 M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z',
                run: () => chain().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
                isDisabled: () => props.editor.isActive('table'),
            },
        ],
    },
];

const tableActions: { label: string; run: () => void }[] = [
    { label: 'Add row', run: () => chain().addRowAfter().run() },
    { label: 'Add column', run: () => chain().addColumnAfter().run() },
    { label: 'Delete row', run: () => chain().deleteRow().run() },
    { label: 'Delete column', run: () => chain().deleteColumn().run() },
    { label: 'Delete table', run: () => chain().deleteTable().run() },
];

const blockStyle = computed(() => {
    const level = headingLevels.find((candidate) => props.editor.isActive('heading', { level: candidate }));

    return level ? String(level) : 'paragraph';
});

function applyBlockStyle(event: Event): void {
    const { value } = event.target as HTMLSelectElement;

    if (value === 'paragraph') {
        chain().setParagraph().run();
    } else {
        chain().setHeading({ level: Number(value) as HeadingLevel }).run();
    }
}

const isLinkFormOpen = ref(false);
const linkUrl = ref('');
const linkError = ref('');
const linkField = useTemplateRef<HTMLInputElement>('linkField');

async function toggleLinkForm(): Promise<void> {
    isLinkFormOpen.value = !isLinkFormOpen.value;
    linkError.value = '';

    if (isLinkFormOpen.value) {
        linkUrl.value = props.editor.getAttributes('link').href ?? '';
        await nextTick();
        linkField.value?.focus();
    }
}

function applyLink(): void {
    const href = sanitizeMarkdownUrl(linkUrl.value);

    if (href === null) {
        linkError.value = 'Enter a full link, such as https://example.com.';

        return;
    }

    if (props.editor.state.selection.empty && !props.editor.isActive('link')) {
        chain()
            .insertContent({ type: 'text', text: href, marks: [{ type: 'link', attrs: { href } }] })
            .run();
    } else {
        chain().extendMarkRange('link').setLink({ href }).run();
    }

    isLinkFormOpen.value = false;
}

function removeLink(): void {
    chain().extendMarkRange('link').unsetLink().run();
    isLinkFormOpen.value = false;
}

const toolButtonClass =
    'inline-flex size-8 cursor-pointer items-center justify-center rounded-md transition-colors focus-visible:outline-2 focus-visible:outline-brand disabled:cursor-not-allowed disabled:opacity-40';
const textButtonClass =
    'cursor-pointer rounded-md px-2 py-1 text-xs font-medium text-ink-soft transition-colors hover:bg-sunken focus-visible:outline-2 focus-visible:outline-brand';
</script>

<template>
    <div class="border-b border-line bg-paper">
        <div role="toolbar" aria-label="Formatting" class="flex flex-wrap items-center gap-x-2 gap-y-1 p-1.5">
            <select
                aria-label="Text style"
                :value="blockStyle"
                class="h-8 cursor-pointer rounded-md border border-line-strong bg-surface px-2 text-xs text-ink focus-visible:outline-2 focus-visible:outline-brand"
                @change="applyBlockStyle"
            >
                <option value="paragraph">Paragraph</option>
                <option v-for="level in headingLevels" :key="level" :value="String(level)">
                    Heading {{ level }}
                </option>
            </select>

            <div v-for="group in actionGroups" :key="group.name" class="flex items-center gap-0.5">
                <button
                    v-for="action in group.actions"
                    :key="action.label"
                    type="button"
                    :title="action.label"
                    :aria-label="action.label"
                    :aria-pressed="action.isActive ? action.isActive() : undefined"
                    :disabled="action.isDisabled?.()"
                    :class="[
                        toolButtonClass,
                        action.isActive?.()
                            ? 'bg-brand/15 text-brand'
                            : 'text-ink-soft hover:bg-sunken',
                    ]"
                    @mousedown.prevent
                    @click="action.run"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                        class="size-4"
                    >
                        <path :d="action.icon" />
                    </svg>
                </button>
            </div>
        </div>

        <div
            v-if="editor.isActive('table')"
            class="flex flex-wrap items-center gap-1 border-t border-line px-1.5 py-1"
        >
            <button
                v-for="action in tableActions"
                :key="action.label"
                type="button"
                :class="textButtonClass"
                @mousedown.prevent
                @click="action.run"
            >
                {{ action.label }}
            </button>
        </div>

        <div v-if="isLinkFormOpen" class="border-t border-line p-1.5">
            <div class="flex flex-wrap items-center gap-1">
                <input
                    ref="linkField"
                    v-model="linkUrl"
                    type="url"
                    aria-label="Link address"
                    placeholder="https://example.com"
                    :aria-invalid="linkError ? 'true' : undefined"
                    class="h-8 min-w-48 flex-1 rounded-md border border-line-strong bg-surface px-2 text-sm focus:border-brand focus:outline-none aria-invalid:border-danger"
                    @keydown.enter.prevent="applyLink"
                    @keydown.esc.prevent="toggleLinkForm"
                />
                <button type="button" :class="textButtonClass" @click="applyLink">Apply</button>
                <button v-if="editor.isActive('link')" type="button" :class="textButtonClass" @click="removeLink">
                    Remove
                </button>
                <button type="button" :class="textButtonClass" @click="toggleLinkForm">Cancel</button>
            </div>
            <p v-if="linkError" role="alert" class="mt-1 text-xs text-danger">{{ linkError }}</p>
        </div>
    </div>
</template>
