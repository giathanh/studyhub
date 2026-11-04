export interface InlineToken {
    id: string;
    type: 'text' | 'code' | 'strong' | 'em' | 'link' | 'unsafe_link';
    text: string;
    href?: string;
}

export interface MarkdownListItem {
    id: string;
    tokens: InlineToken[];
    checked?: boolean;
}

export interface MarkdownBlock {
    id: string;
    type: 'heading' | 'paragraph' | 'code' | 'blockquote' | 'ul' | 'ol' | 'hr' | 'table';
    level?: 1 | 2 | 3 | 4 | 5 | 6;
    language?: string;
    code?: string;
    tokens?: InlineToken[];
    items?: MarkdownListItem[];
    columns?: { id: string; tokens: InlineToken[]; align: 'left' | 'center' | 'right' | undefined }[];
    rows?: { id: string; cells: { id: string; tokens: InlineToken[] }[] }[];
}

const allowedProtocols = new Set(['http:', 'https:', 'mailto:']);

export function sanitizeMarkdownUrl(rawUrl: string): string | null {
    const cleaned = rawUrl.replace(/[\u0000-\u001F\u007F]/g, '').trim();

    if (cleaned === '' || cleaned.startsWith('//')) {
        return null;
    }

    if (
        cleaned.startsWith('#') ||
        cleaned.startsWith('/') ||
        cleaned.startsWith('./') ||
        cleaned.startsWith('../')
    ) {
        return cleaned;
    }

    try {
        const parsed = new URL(cleaned);

        return allowedProtocols.has(parsed.protocol) ? parsed.href : null;
    } catch {
        return null;
    }
}

export function parseInlineMarkdown(text: string, prefix: string): InlineToken[] {
    const tokens: InlineToken[] = [];
    const pattern = /(`[^`]+`)|(\[([^\]]+)\]\(([^)]+)\))|(\*\*([^*]+)\*\*)|(\*([^*]+)\*)/g;
    let lastIndex = 0;
    let partCounter = 0;
    let match: RegExpExecArray | null = pattern.exec(text);

    while (match !== null) {
        if (match.index > lastIndex) {
            tokens.push({
                id: `${prefix}-t-${partCounter++}`,
                type: 'text',
                text: text.slice(lastIndex, match.index),
            });
        }

        if (match[1] !== undefined) {
            tokens.push({
                id: `${prefix}-c-${partCounter++}`,
                type: 'code',
                text: match[1].slice(1, -1),
            });
        } else if (match[2] !== undefined) {
            const label = match[3];
            const safeHref = sanitizeMarkdownUrl(match[4]);

            tokens.push({
                id: `${prefix}-l-${partCounter++}`,
                type: safeHref ? 'link' : 'unsafe_link',
                text: label,
                href: safeHref ?? undefined,
            });
        } else if (match[5] !== undefined) {
            tokens.push({
                id: `${prefix}-b-${partCounter++}`,
                type: 'strong',
                text: match[6],
            });
        } else if (match[7] !== undefined) {
            tokens.push({
                id: `${prefix}-i-${partCounter++}`,
                type: 'em',
                text: match[8],
            });
        }

        lastIndex = pattern.lastIndex;
        match = pattern.exec(text);
    }

    if (lastIndex < text.length) {
        tokens.push({
            id: `${prefix}-t-${partCounter++}`,
            type: 'text',
            text: text.slice(lastIndex),
        });
    }

    return tokens;
}

function splitTableRow(line: string): string[] {
    const cells: string[] = [];
    let cell = '';
    const trimmed = line.trim();

    for (let index = 0; index < trimmed.length; index++) {
        if (trimmed[index] === '\\' && index + 1 < trimmed.length) {
            const next = trimmed[++index];
            cell += next === '|' ? '|' : `\\${next}`;
        } else if (trimmed[index] === '|') {
            cells.push(cell.trim());
            cell = '';
        } else {
            cell += trimmed[index];
        }
    }
    cells.push(cell.trim());
    if (trimmed.startsWith('|')) cells.shift();
    if (trimmed.endsWith('|') && cells.at(-1) === '') cells.pop();

    return cells;
}

function tableColumns(lines: string[], index: number): string[] | null {
    if (!lines[index]?.includes('|') || !lines[index + 1]?.includes('|')) return null;
    const headers = splitTableRow(lines[index]);
    const delimiters = splitTableRow(lines[index + 1]);
    return headers.length === delimiters.length && delimiters.every((cell) => /^:?-{3,}:?$/.test(cell))
        ? delimiters : null;
}

function parseListItem(text: string, id: string): MarkdownListItem {
    const task = text.match(/^\[([ xX])\](?:\s+|$)(.*)$/);
    return {
        id,
        tokens: parseInlineMarkdown(task ? task[2] : text, id),
        ...(task ? { checked: task[1].toLowerCase() === 'x' } : {}),
    };
}

export function parseMarkdownBlocks(markdown: string): MarkdownBlock[] {
    const normalized = markdown.replace(/\r\n?/g, '\n');
    const lines = normalized.split('\n');
    const blocks: MarkdownBlock[] = [];
    let lineIndex = 0;
    let blockCounter = 0;

    while (lineIndex < lines.length) {
        const line = lines[lineIndex];

        if (line.trim() === '') {
            lineIndex++;
            continue;
        }

        const fenceMatch = line.match(/^\s*(`{3,}|~{3,})\s*([^\s`~]*).*$/);

        if (fenceMatch) {
            const fenceMarker = fenceMatch[1];
            const language = fenceMatch[2] || undefined;
            const codeLines: string[] = [];
            lineIndex++;

            while (lineIndex < lines.length) {
                const candidate = lines[lineIndex];
                const closeMatch = candidate.match(/^\s*(`{3,}|~{3,})\s*$/);

                if (
                    closeMatch &&
                    closeMatch[1][0] === fenceMarker[0] &&
                    closeMatch[1].length >= fenceMarker.length
                ) {
                    lineIndex++;
                    break;
                }

                codeLines.push(candidate);
                lineIndex++;
            }

            blocks.push({
                id: `block-${blockCounter++}`,
                type: 'code',
                language,
                code: codeLines.join('\n'),
            });
            continue;
        }

        const delimiters = tableColumns(lines, lineIndex);
        if (delimiters) {
            const blockId = `block-${blockCounter++}`;
            const columns = splitTableRow(line).map((text, index) => ({
                id: `${blockId}-header-${index}`,
                tokens: parseInlineMarkdown(text, `${blockId}-header-${index}`),
                align: delimiters[index].endsWith(':')
                    ? delimiters[index].startsWith(':') ? 'center' as const : 'right' as const
                    : delimiters[index].startsWith(':') ? 'left' as const : undefined,
            }));
            const rows: NonNullable<MarkdownBlock['rows']> = [];
            lineIndex += 2;
            while (lineIndex < lines.length && lines[lineIndex].trim() !== '' && lines[lineIndex].includes('|')) {
                const rowId = `${blockId}-row-${rows.length}`;
                const cells = splitTableRow(lines[lineIndex++]);
                rows.push({
                    id: rowId,
                    cells: columns.map((_, index) => ({
                        id: `${rowId}-cell-${index}`,
                        tokens: parseInlineMarkdown(cells[index] ?? '', `${rowId}-cell-${index}`),
                    })),
                });
            }
            blocks.push({ id: blockId, type: 'table', columns, rows });
            continue;
        }

        const headingMatch = line.match(/^(#{1,6})\s+(.+?)\s*#*\s*$/);

        if (headingMatch) {
            const blockId = `block-${blockCounter++}`;
            blocks.push({
                id: blockId,
                type: 'heading',
                level: headingMatch[1].length as 1 | 2 | 3 | 4 | 5 | 6,
                tokens: parseInlineMarkdown(headingMatch[2], blockId),
            });
            lineIndex++;
            continue;
        }

        if (/^\s*(?:-\s*){3,}$|^\s*(?:\*\s*){3,}$|^\s*(?:_\s*){3,}$/.test(line)) {
            blocks.push({
                id: `block-${blockCounter++}`,
                type: 'hr',
            });
            lineIndex++;
            continue;
        }

        if (/^\s*>\s?/.test(line)) {
            const quoteLines: string[] = [];

            while (lineIndex < lines.length && /^\s*>\s?/.test(lines[lineIndex])) {
                quoteLines.push(lines[lineIndex].replace(/^\s*>\s?/, ''));
                lineIndex++;
            }

            const blockId = `block-${blockCounter++}`;
            blocks.push({
                id: blockId,
                type: 'blockquote',
                tokens: parseInlineMarkdown(quoteLines.join('\n'), blockId),
            });
            continue;
        }

        if (/^\s*[-*+]\s+/.test(line)) {
            const items: MarkdownListItem[] = [];
            const blockId = `block-${blockCounter++}`;
            let itemCounter = 0;

            while (lineIndex < lines.length && /^\s*[-*+]\s+/.test(lines[lineIndex])) {
                const itemId = `${blockId}-item-${itemCounter++}`;
                const itemText = lines[lineIndex].replace(/^\s*[-*+]\s+/, '');
                items.push(parseListItem(itemText, itemId));
                lineIndex++;
            }

            blocks.push({
                id: blockId,
                type: 'ul',
                items,
            });
            continue;
        }

        if (/^\s*\d+\.\s+/.test(line)) {
            const items: MarkdownListItem[] = [];
            const blockId = `block-${blockCounter++}`;
            let itemCounter = 0;

            while (lineIndex < lines.length && /^\s*\d+\.\s+/.test(lines[lineIndex])) {
                const itemId = `${blockId}-item-${itemCounter++}`;
                const itemText = lines[lineIndex].replace(/^\s*\d+\.\s+/, '');
                items.push(parseListItem(itemText, itemId));
                lineIndex++;
            }

            blocks.push({
                id: blockId,
                type: 'ol',
                items,
            });
            continue;
        }

        const paragraphLines: string[] = [];

        while (
            lineIndex < lines.length &&
            lines[lineIndex].trim() !== '' &&
            !tableColumns(lines, lineIndex) &&
            !/^\s*(`{3,}|~{3,})/.test(lines[lineIndex]) &&
            !/^(#{1,6})\s+(.+?)\s*#*\s*$/.test(lines[lineIndex]) &&
            !/^\s*>\s?/.test(lines[lineIndex]) &&
            !/^\s*[-*+]\s+/.test(lines[lineIndex]) &&
            !/^\s*\d+\.\s+/.test(lines[lineIndex])
        ) {
            paragraphLines.push(lines[lineIndex]);
            lineIndex++;
        }

        const blockId = `block-${blockCounter++}`;
        blocks.push({
            id: blockId,
            type: 'paragraph',
            tokens: parseInlineMarkdown(paragraphLines.join('\n'), blockId),
        });
    }

    return blocks;
}
