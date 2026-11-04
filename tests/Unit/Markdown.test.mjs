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
