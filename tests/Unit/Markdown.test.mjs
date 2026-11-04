import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import ts from 'typescript';

const source = readFileSync(new URL('../../resources/js/composables/useMarkdown.ts', import.meta.url), 'utf8');
const { outputText } = ts.transpileModule(source, {
    compilerOptions: { module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022 },
});
const { parseMarkdownBlocks, parseInlineMarkdown, sanitizeMarkdownUrl } = await import(
    `data:text/javascript;base64,${Buffer.from(outputText).toString('base64')}`
);

test('unfinished headings remain text and parsing continues', () => {
    for (const heading of ['# ', '## ', '######\t']) {
        const blocks = parseMarkdownBlocks(`${heading}\n\n# Valid heading`);
        assert.equal(blocks[0].type, 'paragraph');
        assert.equal(blocks[0].tokens[0].text, heading);
        assert.equal(blocks[1].type, 'heading');
        assert.equal(blocks[1].tokens[0].text, 'Valid heading');
    }
});

test('script URLs are rejected while safe links and literal HTML remain data', () => {
    for (const url of ['javascript:alert(1)', 'java\nscript:alert(1)', 'data:text/html,test', '//example.com']) {
        assert.equal(sanitizeMarkdownUrl(url), null);
    }
    assert.equal(sanitizeMarkdownUrl('https://example.com/docs'), 'https://example.com/docs');
    const tokens = parseInlineMarkdown('<script>alert(1)</script> [bad](javascript:alert)', 'test');
    assert.equal(tokens[0].type, 'text');
    assert.equal(tokens[0].text, '<script>alert(1)</script> ');
    assert.equal(tokens[1].type, 'unsafe_link');
    assert.equal(tokens[1].href, undefined);
});

test('code fences preserve code without interpreting inline markup', () => {
    const blocks = parseMarkdownBlocks('```html\n<script>alert(1)</script>\n```');
    assert.equal(blocks.length, 1);
    assert.equal(blocks[0].type, 'code');
    assert.equal(blocks[0].code, '<script>alert(1)</script>');
});

test('tables render headers, alignment, inline tokens and normalized rows', () => {
    const blocks = parseMarkdownBlocks('Intro\n| Variable | Meaning | Example |\n|:---|:---:|---:|\n| `REGION` | **Region** | ap-southeast-1 |\n| short |\n| a | b | c | ignored |\n\nAfter');
    assert.deepEqual(blocks.map((block) => block.type), ['paragraph', 'table', 'paragraph']);
    const table = blocks[1];
    assert.deepEqual(table.columns.map((column) => column.align), ['left', 'center', 'right']);
    assert.equal(table.columns[0].tokens[0].text, 'Variable');
    assert.equal(table.rows[0].cells[0].tokens[0].type, 'code');
    assert.equal(table.rows[0].cells[1].tokens[0].type, 'strong');
    assert.deepEqual(table.rows[1].cells[1].tokens, []);
    assert.equal(table.rows[2].cells.length, 3);
});

test('tables support omitted outer pipes, escaped pipes and adjacent headings', () => {
    const blocks = parseMarkdownBlocks('Name | Value\n--- | ---\n`a\\|b` | [Docs](https://example.com)\n# Next');
    assert.deepEqual(blocks.map((block) => block.type), ['table', 'heading']);
    assert.equal(blocks[0].rows[0].cells[0].tokens[0].text, 'a|b');
    assert.equal(blocks[0].rows[0].cells[1].tokens[0].type, 'link');
});

test('invalid tables remain paragraphs and fenced tables stay code', () => {
    assert.equal(parseMarkdownBlocks('| a | b |\n| --- |')[0].type, 'paragraph');
    assert.equal(parseMarkdownBlocks('| a | b |\n| --- | invalid |')[0].type, 'paragraph');
    assert.equal(parseMarkdownBlocks('```md\n| a | b |\n| --- | --- |\n```')[0].type, 'code');
});

test('checklists distinguish unchecked, checked and ordinary list items', () => {
    const blocks = parseMarkdownBlocks('- [ ] Read **guide**\n- [x] Done\n- [X] Also done\n- Ordinary\n- [maybe] text\n\n1. [ ] Ordered task');
    assert.deepEqual(blocks[0].items.map((item) => item.checked), [false, true, true, undefined, undefined]);
    assert.equal(blocks[0].items[0].tokens[0].text, 'Read ');
    assert.equal(blocks[0].items[0].tokens[1].type, 'strong');
    assert.equal(blocks[1].items[0].checked, false);
});

test('tables and tasks keep HTML as data and block unsafe links', () => {
    const table = parseMarkdownBlocks('| Name |\n| --- |\n| <img src=x onerror=alert(1)> |')[0];
    assert.equal(table.rows[0].cells[0].tokens[0].type, 'text');
    const task = parseMarkdownBlocks('- [ ] [bad](javascript:alert)')[0].items[0];
    assert.equal(task.tokens[0].type, 'unsafe_link');
    assert.equal(task.tokens[0].href, undefined);
});

async function loadMarkdownComponent(name, imports) {
    const { parse, compileScript } = await import('@vue/compiler-sfc');
    const { descriptor } = parse(readFileSync(new URL(`../../resources/js/components/${name}.vue`, import.meta.url), 'utf8'));
    const compiled = compileScript(descriptor, { id: name, inlineTemplate: true });
    let { outputText } = ts.transpileModule(compiled.content, {
        compilerOptions: { module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022 },
    });
    outputText = outputText.replaceAll('from "vue"', `from ${JSON.stringify(import.meta.resolve('vue'))}`)
        .replaceAll("from 'vue'", `from ${JSON.stringify(import.meta.resolve('vue'))}`);
    for (const [path, url] of Object.entries(imports)) {
        outputText = outputText.replaceAll(`'${path}'`, JSON.stringify(url));
    }
    return `data:text/javascript;base64,${Buffer.from(outputText).toString('base64')}`;
}

test('Markdown component renders semantic tables and read-only task checkboxes', async () => {
    const { createSSRApp } = await import('vue');
    const { renderToString } = await import('@vue/server-renderer');
    const inline = await loadMarkdownComponent('MarkdownInline', {});
    const componentUrl = await loadMarkdownComponent('MarkdownContent', {
        '@/components/MarkdownInline.vue': inline,
        '@/composables/useMarkdown': `data:text/javascript;base64,${Buffer.from(outputText).toString('base64')}`,
    });
    const { default: component } = await import(componentUrl);
    const html = await renderToString(createSSRApp(component, {
        content: '| Variable | Meaning |\n| --- | --- |\n| `REGION` | <script>alert(1)</script> |\n\n- [ ] Pending\n- [x] Complete',
    }));
    assert.match(html, /<table\b/);
    assert.match(html, /<th[^>]*scope="col"/);
    assert.match(html.replace(/<!--.*?-->/gs, ''), /<td[^>]*><code[^>]*>REGION<\/code>/);
    assert.match(html, /&lt;script&gt;/);
    assert.doesNotMatch(html, /<script>/);
    assert.match(html, /<input[^>]*type="checkbox"[^>]*disabled[^>]*aria-label="Pending"/);
    assert.match(html, /<input[^>]*type="checkbox"[^>]*checked[^>]*disabled[^>]*aria-label="Complete"/);
    assert.doesNotMatch(html, /\[ \]|\[x\]/);
});
