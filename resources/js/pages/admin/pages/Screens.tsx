import { Link, router } from '@inertiajs/react';
import { DndContext, PointerSensor, closestCenter, useSensor, useSensors, type DragEndEvent } from '@dnd-kit/core';
import { SortableContext, arrayMove, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { Area, Bilingual, BlockEditor, Errors, PublishBar, Revisions, inputClass } from '@/components/admin/Fields';
import { AdminLayout, DeleteButton, Empty, SearchForm, usePermission } from '@/layouts/AdminLayout';
import { useI18n } from '@/lib/useI18n';
import { localPath, type Paginator } from '@/types';

type PageRow = {
    id: number;
    slug: string;
    template: string;
    title: { ar: string; en: string };
    status: string;
    is_system: boolean;
    has_unpublished_changes: boolean;
};

type PageForm = {
    id: number;
    slug: string;
    public_slug: string;
    template: string;
    is_system: boolean;
    status: string;
    title: { ar: string; en: string };
    excerpt: { ar: string; en: string };
    body: { type: string; text: { ar: string; en: string }; items?: { ar: string; en: string }[] }[];
    seo: { title: { ar: string; en: string }; description: { ar: string; en: string } };
    has_unpublished_changes: boolean;
} | null;

export default function Index({ pages, filters }: { pages: Paginator<PageRow>; filters: { q: string } }) {
    const { t, locale } = useI18n();
    const canCreate = usePermission('pages.create');

    return (
        <AdminLayout title={t.admin.pages}>
            <div className="mb-4 flex justify-between gap-3">
                <SearchForm value={filters.q} />
                {canCreate ? <Link href="/admin/pages/create" className="inline-flex min-h-12 items-center bg-midnight px-4 text-ivory">{t.admin.create}</Link> : null}
            </div>
            {pages.data.length === 0 ? <Empty text={t.admin.empty} /> : (
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[40rem] text-start text-sm">
                        <tbody>
                            {pages.data.map((page) => (
                                <tr key={page.id} className="border-b border-midnight/10">
                                    <td className="py-4">{page.title[locale] || page.title.ar}</td>
                                    <td>{page.status}</td>
                                    <td>{page.has_unpublished_changes ? t.admin.unpublished : ''}</td>
                                    <td className="text-end"><Link href={`/admin/pages/${page.id}/edit`} className="min-h-11 inline-flex items-center">{t.admin.edit}</Link></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </AdminLayout>
    );
}

function previewPath(page: NonNullable<PageForm>): string {
    const map: Record<string, string> = {
        homepage: '/',
        services_index: '/services',
        work_index: '/work',
        technologies_index: '/technologies',
        insights_index: '/insights',
        contact: '/contact',
    };

    if (map[page.template]) {
        return map[page.template];
    }

    return page.is_system ? `/${page.public_slug}` : `/p/${page.public_slug}`;
}

export function PageForm({ page, revisions }: { page: PageForm; revisions: { id: number; note: string | null; created_at: string | null; user: string | null }[] }) {
    const { t, locale } = useI18n();
    const canPublish = usePermission('pages.publish');
    const form = useForm({
        slug: page?.slug ?? '',
        title: page?.title ?? { ar: '', en: '' },
        excerpt: page?.excerpt ?? { ar: '', en: '' },
        body: page?.body ?? [],
        seo: page?.seo ?? { title: { ar: '', en: '' }, description: { ar: '', en: '' } },
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        if (page) {
            form.put(`/admin/pages/${page.id}`);
        } else {
            form.post('/admin/pages');
        }
    }

    return (
        <AdminLayout title={page ? t.admin.edit : t.admin.create}>
            <form onSubmit={submit} className="max-w-4xl space-y-6">
                <Errors errors={form.errors} />
                <label className="grid gap-2 text-sm">{t.admin.slug}<input className={inputClass} dir="ltr" value={form.data.slug} onChange={(event) => form.setData('slug', event.target.value)} /></label>
                <Bilingual label={t.admin.title} value={form.data.title} onChange={(title) => form.setData('title', title)} />
                <Area label={t.admin.excerpt} value={form.data.excerpt} onChange={(excerpt) => form.setData('excerpt', excerpt)} />
                <Bilingual label={t.admin.seoTitle} value={form.data.seo.title} onChange={(title) => form.setData('seo', { ...form.data.seo, title })} />
                <Area label={t.admin.seoDescription} value={form.data.seo.description} onChange={(description) => form.setData('seo', { ...form.data.seo, description })} />
                <BlockEditor blocks={form.data.body} onChange={(body) => form.setData('body', body)} />
                <div className="flex gap-3">
                    <button disabled={form.processing} className="min-h-12 bg-midnight px-4 text-ivory">{t.admin.save}</button>
                    {page && !page.is_system ? <DeleteButton href={`/admin/pages/${page.id}`} /> : null}
                </div>
                {page ? <PublishBar canPublish={canPublish} publishUrl={`/admin/pages/${page.id}/publish`} scheduleUrl={`/admin/pages/${page.id}/schedule`} previewUrl={`${localPath(locale, previewPath(page))}?preview=1`} /> : null}
            </form>
            <Revisions rows={revisions} />
        </AdminLayout>
    );
}

type SectionRow = {
    id: number;
    type: string;
    layout: string;
    theme: string;
    is_visible: boolean;
    sort_order: number;
    content: {
        heading: { ar: string; en: string };
        description: { ar: string; en: string };
        primary_button: { label: { ar: string; en: string }; url: string | null };
        secondary_button: { label: { ar: string; en: string }; url: string | null };
        image_id: number | null;
        items: { title: { ar: string; en: string }; body: { ar: string; en: string } }[];
    };
};

export function Builder({ page, sections, catalog, revisions, media }: {
    page: { id: number; public_slug: string; has_unpublished_changes: boolean };
    sections: SectionRow[];
    catalog: Record<string, { layouts: string[]; themes: string[] }>;
    revisions: { id: number; note: string | null; created_at: string | null; user: string | null }[];
    media: { id: number; filename: string }[];
}) {
    const { t, locale } = useI18n();
    const canPublish = usePermission('pages.publish');
    const [rows, setRows] = useState(sections);
    const [type, setType] = useState(Object.keys(catalog)[0] ?? 'hero');
    const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 8 } }));

    function persist(next: SectionRow[]) {
        setRows(next);
        router.post(`/admin/pages/${page.id}/sections/reorder`, { ids: next.map((row) => row.id) }, { preserveScroll: true });
    }

    function onDragEnd(event: DragEndEvent) {
        const { active, over } = event;

        if (!over || active.id === over.id) {
            return;
        }

        const oldIndex = rows.findIndex((row) => row.id === active.id);
        const newIndex = rows.findIndex((row) => row.id === over.id);
        persist(arrayMove(rows, oldIndex, newIndex));
    }

    return (
        <AdminLayout title={t.admin.pages}>
            {page.has_unpublished_changes ? <p className="mb-4 text-sm text-rose">{t.admin.unpublished}</p> : null}
            <form className="mb-6 flex flex-wrap gap-2" onSubmit={(event) => { event.preventDefault(); router.post(`/admin/pages/${page.id}/sections`, { type }); }}>
                <select className={inputClass + ' max-w-xs'} value={type} onChange={(event) => setType(event.target.value)}>
                    {Object.keys(catalog).map((item) => <option key={item} value={item}>{item}</option>)}
                </select>
                <button className="min-h-12 bg-midnight px-4 text-ivory">{t.admin.add}</button>
            </form>
            <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={onDragEnd}>
                <SortableContext items={rows.map((row) => row.id)} strategy={verticalListSortingStrategy}>
                    <div className="space-y-4">
                        {rows.map((section, index) => (
                            <SortableSection key={section.id} section={section} pageId={page.id} catalog={catalog[section.type]} media={media} index={index} total={rows.length} onMove={(direction) => {
                                const next = arrayMove(rows, index, index + direction);
                                persist(next);
                            }} />
                        ))}
                    </div>
                </SortableContext>
            </DndContext>
            <div className="mt-8">
                <PublishBar canPublish={canPublish} publishUrl={`/admin/pages/${page.id}/publish`} scheduleUrl={`/admin/pages/${page.id}/schedule`} previewUrl={`${localPath(locale)}?preview=1`} />
            </div>
            <Revisions rows={revisions} />
        </AdminLayout>
    );
}

function SortableSection({ section, pageId, catalog, media, index, total, onMove }: {
    section: SectionRow;
    pageId: number;
    catalog?: { layouts: string[]; themes: string[] };
    media: { id: number; filename: string }[];
    index: number;
    total: number;
    onMove: (direction: number) => void;
}) {
    const { attributes, listeners, setNodeRef, transform, transition } = useSortable({ id: section.id });
    const { t } = useI18n();
    const content = {
        heading: section.content?.heading ?? { ar: '', en: '' },
        description: section.content?.description ?? { ar: '', en: '' },
        primary_button: { label: section.content?.primary_button?.label ?? { ar: '', en: '' }, url: section.content?.primary_button?.url ?? '' },
        secondary_button: { label: section.content?.secondary_button?.label ?? { ar: '', en: '' }, url: section.content?.secondary_button?.url ?? '' },
        image_id: section.content?.image_id ?? null,
        items: section.content?.items ?? [],
    };
    const form = useForm({
        layout: section.layout,
        theme: section.theme,
        is_visible: section.is_visible,
        content,
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.put(`/admin/pages/${pageId}/sections/${section.id}`, { preserveScroll: true });
    }

    return (
        <form ref={setNodeRef} style={{ transform: CSS.Transform.toString(transform), transition }} onSubmit={submit} className="space-y-4 border border-midnight/10 bg-white p-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <button type="button" className="min-h-11 cursor-grab px-2 text-sm" {...attributes} {...listeners}>{t.admin.reorder}</button>
                <strong>{section.type}</strong>
                <span className="flex gap-2">
                    <button type="button" className="min-h-11 px-2" disabled={index === 0} onClick={() => onMove(-1)}>{t.admin.up}</button>
                    <button type="button" className="min-h-11 px-2" disabled={index === total - 1} onClick={() => onMove(1)}>{t.admin.down}</button>
                </span>
            </div>
            <div className="grid gap-3 md:grid-cols-3">
                <select className={inputClass} value={form.data.layout} onChange={(event) => form.setData('layout', event.target.value)}>
                    {(catalog?.layouts ?? [section.layout]).map((layout) => <option key={layout}>{layout}</option>)}
                </select>
                <select className={inputClass} value={form.data.theme} onChange={(event) => form.setData('theme', event.target.value)}>
                    {(catalog?.themes ?? [section.theme]).map((theme) => <option key={theme}>{theme}</option>)}
                </select>
                <label className="flex min-h-12 items-center gap-2 text-sm">
                    <input type="checkbox" checked={form.data.is_visible} onChange={(event) => form.setData('is_visible', event.target.checked)} />
                    {t.admin.isVisible}
                </label>
            </div>
            <Bilingual label={t.admin.heading} value={form.data.content.heading} onChange={(heading) => form.setData('content', { ...form.data.content, heading })} />
            <Area label={t.admin.description} value={form.data.content.description} onChange={(description) => form.setData('content', { ...form.data.content, description })} />
            <Bilingual label={t.admin.button} value={form.data.content.primary_button.label} onChange={(label) => form.setData('content', { ...form.data.content, primary_button: { ...form.data.content.primary_button, label } })} />
            <label className="grid gap-2 text-sm">{t.admin.url}<input className={inputClass} dir="ltr" value={form.data.content.primary_button.url ?? ''} onChange={(event) => form.setData('content', { ...form.data.content, primary_button: { ...form.data.content.primary_button, url: event.target.value } })} /></label>
            <Bilingual label={t.admin.button} value={form.data.content.secondary_button.label} onChange={(label) => form.setData('content', { ...form.data.content, secondary_button: { ...form.data.content.secondary_button, label } })} />
            <label className="grid gap-2 text-sm">{t.admin.url}<input className={inputClass} dir="ltr" value={form.data.content.secondary_button.url ?? ''} onChange={(event) => form.setData('content', { ...form.data.content, secondary_button: { ...form.data.content.secondary_button, url: event.target.value } })} /></label>
            <label className="grid gap-2 text-sm">{t.admin.cover}
                <select className={inputClass} value={form.data.content.image_id ?? ''} onChange={(event) => form.setData('content', { ...form.data.content, image_id: event.target.value ? Number(event.target.value) : null })}>
                    <option value="">{t.admin.none}</option>
                    {media.map((item) => <option key={item.id} value={item.id}>{item.filename}</option>)}
                </select>
            </label>
            <div className="space-y-3">
                <div className="flex items-center justify-between">
                    <span className="text-sm">{t.admin.items}</span>
                    <button type="button" className="min-h-11 text-sm text-rose" onClick={() => form.setData('content', { ...form.data.content, items: [...form.data.content.items, { title: { ar: '', en: '' }, body: { ar: '', en: '' } }] })}>{t.admin.add}</button>
                </div>
                {form.data.content.items.map((item, itemIndex) => (
                    <div key={itemIndex} className="space-y-3 border border-midnight/10 p-3">
                        <Bilingual label={t.admin.title} value={item.title} onChange={(title) => form.setData('content', { ...form.data.content, items: form.data.content.items.map((row, i) => i === itemIndex ? { ...row, title } : row) })} />
                        <Area label={t.admin.body} value={item.body} onChange={(body) => form.setData('content', { ...form.data.content, items: form.data.content.items.map((row, i) => i === itemIndex ? { ...row, body } : row) })} />
                        <button type="button" className="text-sm text-rose" onClick={() => form.setData('content', { ...form.data.content, items: form.data.content.items.filter((_, i) => i !== itemIndex) })}>{t.admin.delete}</button>
                    </div>
                ))}
            </div>
            <div className="flex gap-3">
                <button className="min-h-12 bg-midnight px-4 text-ivory">{t.admin.save}</button>
                <DeleteButton href={`/admin/pages/${pageId}/sections/${section.id}`} />
            </div>
        </form>
    );
}
