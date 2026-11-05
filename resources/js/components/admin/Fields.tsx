import { router, useForm } from '@inertiajs/react';
import { FormEvent, type ReactNode } from 'react';
import { useI18n } from '@/lib/useI18n';

type Pair = { ar: string; en: string };
type Block = { type: string; level?: number; text: Pair; items?: Pair[] };

export function Field({ label, children }: { label: string; children: ReactNode }) {
    return (
        <label className="grid gap-2 text-sm">
            <span>{label}</span>
            {children}
        </label>
    );
}

export const inputClass = 'field';

export function Bilingual({ label, value, onChange }: { label: string; value: Pair; onChange: (value: Pair) => void }) {
    const { t } = useI18n();

    return (
        <div className="grid gap-3 md:grid-cols-2">
            <Field label={`${label} · ${t.admin.arabic}`}>
                <input className={inputClass} value={value?.ar ?? ''} onChange={(event) => onChange({ ...value, ar: event.target.value })} />
            </Field>
            <Field label={`${label} · ${t.admin.english}`}>
                <input className={inputClass} dir="ltr" value={value?.en ?? ''} onChange={(event) => onChange({ ...value, en: event.target.value })} />
            </Field>
        </div>
    );
}

export function Area({ label, value, onChange }: { label: string; value: Pair; onChange: (value: Pair) => void }) {
    const { t } = useI18n();

    return (
        <div className="grid gap-3 md:grid-cols-2">
            <Field label={`${label} · ${t.admin.arabic}`}>
                <textarea className="field" value={value?.ar ?? ''} onChange={(event) => onChange({ ...value, ar: event.target.value })} />
            </Field>
            <Field label={`${label} · ${t.admin.english}`}>
                <textarea className="field" dir="ltr" value={value?.en ?? ''} onChange={(event) => onChange({ ...value, en: event.target.value })} />
            </Field>
        </div>
    );
}

export function BlockEditor({ blocks, onChange }: { blocks: Block[]; onChange: (blocks: Block[]) => void }) {
    const { t } = useI18n();

    function update(index: number, next: Block) {
        onChange(blocks.map((block, i) => (i === index ? next : block)));
    }

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap gap-2">
                {['paragraph', 'heading', 'list', 'quote'].map((type) => (
                    <button key={type} type="button" className="min-h-11 border border-midnight/15 px-3 text-sm" onClick={() => onChange([...blocks, { type, level: 2, text: { ar: '', en: '' }, items: [] }])}>
                        {t.admin.add} {type}
                    </button>
                ))}
            </div>
            {blocks.map((block, index) => (
                <div key={index} className="space-y-3 border border-midnight/10 p-4">
                    <div className="flex items-center justify-between">
                        <span className="text-sm">{block.type}</span>
                        <button type="button" className="text-sm text-rose" onClick={() => onChange(blocks.filter((_, i) => i !== index))}>{t.admin.delete}</button>
                    </div>
                    {block.type === 'list' ? (
                        <ListItems items={block.items ?? []} onChange={(items) => update(index, { ...block, items })} />
                    ) : (
                        <Area label={t.admin.body} value={block.text} onChange={(text) => update(index, { ...block, text })} />
                    )}
                </div>
            ))}
        </div>
    );
}

function ListItems({ items, onChange }: { items: Pair[]; onChange: (items: Pair[]) => void }) {
    const { t } = useI18n();

    return (
        <div className="space-y-3">
            {items.map((item, index) => (
                <Bilingual key={index} label={String(index + 1)} value={item} onChange={(value) => onChange(items.map((row, i) => (i === index ? value : row)))} />
            ))}
            <button type="button" className="min-h-11 text-sm text-rose" onClick={() => onChange([...items, { ar: '', en: '' }])}>{t.admin.add}</button>
        </div>
    );
}

export function PublishBar({ publishUrl, scheduleUrl, previewUrl, canPublish }: { publishUrl?: string; scheduleUrl?: string; previewUrl?: string; canPublish: boolean }) {
    const { t } = useI18n();
    const form = useForm({ scheduled_for: '' });

    function schedule(event: FormEvent) {
        event.preventDefault();

        if (scheduleUrl) {
            form.post(scheduleUrl);
        }
    }

    return (
        <div className="flex flex-wrap items-end gap-3 border-t border-midnight/10 pt-4">
            {canPublish && publishUrl ? <button type="button" className="min-h-12 bg-rose px-4 text-white" onClick={() => router.post(publishUrl)}>{t.admin.publish}</button> : null}
            {previewUrl ? <a className="inline-flex min-h-12 items-center px-3" href={previewUrl} target="_blank" rel="noreferrer">{t.admin.preview}</a> : null}
            {canPublish && scheduleUrl ? (
                <form onSubmit={schedule} className="flex flex-wrap items-end gap-2">
                    <Field label={t.admin.schedule}>
                        <input type="datetime-local" className={inputClass} value={form.data.scheduled_for} onChange={(event) => form.setData('scheduled_for', event.target.value)} />
                    </Field>
                    <button className="min-h-12 border border-midnight px-4">{t.admin.schedule}</button>
                </form>
            ) : null}
        </div>
    );
}

export function Revisions({ rows }: { rows: { id: number; note: string | null; created_at: string | null; user: string | null }[] }) {
    const { t } = useI18n();
    const can = true;

    if (!can || rows.length === 0) {
        return null;
    }

    return (
        <div className="mt-8">
            <h2 className="text-sm">{t.admin.restore}</h2>
            <ul className="mt-3 divide-y divide-midnight/10">
                {rows.map((row) => (
                    <li key={row.id} className="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                        <span>{row.note} — {row.user} — {row.created_at}</span>
                        <button type="button" className="min-h-11 px-3" onClick={() => router.post(`/admin/revisions/${row.id}/restore`)}>{t.admin.restore}</button>
                    </li>
                ))}
            </ul>
        </div>
    );
}

export function Errors({ errors }: { errors: Record<string, string | undefined> }) {
    const messages = Object.values(errors).filter(Boolean);

    if (messages.length === 0) {
        return null;
    }

    return (
        <div className="mb-4 border border-rose/40 bg-white px-4 py-3 text-sm text-rose" role="alert">
            {messages.map((message) => <p key={message}>{message}</p>)}
        </div>
    );
}
