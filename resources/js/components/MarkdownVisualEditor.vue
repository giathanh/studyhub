<script setup lang="ts">
import { TaskItem, TaskList } from '@tiptap/extension-list';
import { TableKit } from '@tiptap/extension-table';
import { Markdown } from '@tiptap/markdown';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { watch } from 'vue';
import MarkdownEditorToolbar from '@/components/MarkdownEditorToolbar.vue';
import { rendersSameContent } from '@/composables/useMarkdown';

const props = defineProps<{
    initialMarkdown: string;
    labelId: string;
    rows: number;
    errorId?: string;
}>();

const emit = defineEmits<{
    ready: [];
    update: [markdown: string];
}>();

let loadedMarkdown = props.initialMarkdown;

function editorAttributes(): Record<string, string> {
    return {
        role: 'textbox',
        'aria-multiline': 'true',
        'aria-labelledby': props.labelId,
        ...(props.errorId ? { 'aria-invalid': 'true', 'aria-describedby': props.errorId } : {}),
    };
}

const editor = useEditor({
    content: props.initialMarkdown,
    contentType: 'markdown',
    extensions: [
        StarterKit.configure({
            strike: false,
            underline: false,
            link: { openOnClick: false, defaultProtocol: 'https' },
        }),
        TableKit.configure({ table: { resizable: false } }),
        TaskList,
        TaskItem.configure({ nested: true }),
        Markdown,
    ],
    editorProps: { attributes: editorAttributes() },
    onCreate: () => emit('ready'),
    onUpdate: ({ editor: instance }) => {
        loadedMarkdown = instance.isEmpty ? '' : instance.getMarkdown().trim();
        emit('update', loadedMarkdown);
    },
});

function keepsContent(markdown: string): boolean {
    const manager = editor.value?.markdown;

    return manager !== undefined && rendersSameContent(markdown, manager.serialize(manager.parse(markdown)));
}

function load(markdown: string): void {
    if (markdown !== loadedMarkdown) {
        editor.value?.commands.setContent(markdown, { contentType: 'markdown', emitUpdate: false });
        loadedMarkdown = markdown;
    }
}

function focus(): void {
    editor.value?.commands.focus();
}

watch(
    () => props.errorId,
    () => {
        editor.value?.setOptions({ editorProps: { attributes: editorAttributes() } });
    },
);

defineExpose({ keepsContent, load, focus });
</script>

<template>
    <div>
        <MarkdownEditorToolbar v-if="editor" :editor="editor" />
        <EditorContent
            :editor="editor"
            class="visual-editor max-h-[70vh] overflow-y-auto"
            :style="{ minHeight: `${rows * 1.5}rem` }"
        />
    </div>
</template>

<style>
@reference '../../css/app.css';

.visual-editor .tiptap {
    @apply min-h-[inherit] space-y-4 px-4 py-3 text-sm leading-relaxed wrap-anywhere text-gray-800 outline-none;
}

.visual-editor h1 {
    @apply text-xl font-semibold text-gray-900;
}

.visual-editor h2 {
    @apply text-lg font-semibold text-gray-900;
}

.visual-editor h3 {
    @apply text-base font-semibold text-gray-900;
}

.visual-editor h4 {
    @apply text-sm font-semibold text-gray-900;
}

.visual-editor h5 {
    @apply text-sm font-medium text-gray-900;
}

.visual-editor h6 {
    @apply text-xs font-medium tracking-wide text-gray-600 uppercase;
}

.visual-editor strong {
    @apply font-semibold text-gray-900;
}

.visual-editor a {
    @apply font-medium text-brand underline underline-offset-2;
}

.visual-editor :not(pre) > code {
    @apply rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-gray-900;
}

.visual-editor pre {
    @apply overflow-x-auto rounded-xl border border-gray-200 bg-gray-900 p-4 font-mono text-xs leading-relaxed text-gray-100;
}

.visual-editor blockquote {
    @apply border-l-4 border-brand/40 pl-4 text-gray-600 italic;
}

.visual-editor ul {
    @apply list-disc space-y-1.5 pl-5;
}

.visual-editor ol {
    @apply list-decimal space-y-1.5 pl-5;
}

.visual-editor ul[data-type='taskList'] {
    @apply list-none pl-0;
}

.visual-editor ul[data-type='taskList'] li {
    @apply flex items-start gap-2;
}

.visual-editor ul[data-type='taskList'] li > label input {
    @apply mt-1 size-4 accent-brand;
}

.visual-editor ul[data-type='taskList'] li > div {
    @apply min-w-0 flex-1;
}

.visual-editor hr {
    @apply border-gray-200;
}

.visual-editor hr.ProseMirror-selectednode {
    @apply border-brand;
}

.visual-editor table {
    @apply w-full border-collapse text-left text-sm;
}

.visual-editor th,
.visual-editor td {
    @apply relative min-w-32 border border-gray-200 px-4 py-3 align-top;
}

.visual-editor th {
    @apply bg-gray-50 font-semibold text-gray-900;
}

.visual-editor .selectedCell {
    @apply bg-brand/10;
}
</style>
