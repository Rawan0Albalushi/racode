import { Link, router, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { Area, Bilingual, BlockEditor, Errors, PublishBar, Revisions, inputClass } from '@/components/admin/Fields';
import { AdminLayout, DeleteButton, Empty, SearchForm, usePermission } from '@/layouts/AdminLayout';
import { useI18n } from '@/lib/useI18n';
import { localPath, type Paginator } from '@/types';

type Pair = { ar: string; en: string };
type Block = { type: string; level?: number; text: Pair; items?: Pair[] };
type Titled = { title: Pair; body: Pair };
type MediaOpt = { id: number; filename: string; url: string };
type Revision = { id: number; note: string | null; created_at: string | null; user: string | null };
type Named = { id: number; slug?: string; name: Pair | string };

const blank = (): Pair => ({ ar: '', en: '' });
const seoBlank = () => ({ title: blank(), description: blank() });

function textOf(value: Pair | string | null | undefined, locale: 'ar' | 'en'): string {
    if (!value) {
        return '';
    }

    return typeof value === 'string' ? value : (value[locale] || value.ar || value.en);
}

function Pages({ links }: { links: Paginator<unknown>['links'] }) {
    if (!links || links.length <= 3) {
        return null;
    }

    return (
        <nav className="mt-6 flex flex-wrap gap-2 text-sm">
            {links.map((link, index) => {
                const label = link.label.replace(/<[^>]*>/g, '').replace(/&laquo;|&raquo;/g, '').trim();

                return link.url
                    ? <Link key={index} href={link.url} className={`inline-flex min-h-11 items-center px-3 ${link.active ? 'bg-midnight text-ivory' : ''}`}>{label}</Link>
                    : <span key={index} className="inline-flex min-h-11 items-center px-3 text-muted">{label}</span>;
            })}
        </nav>
    );
}

function Check({ label, checked, onChange }: { label: string; checked: boolean; onChange: (value: boolean) => void }) {
    return (
        <label className="flex min-h-12 items-center gap-3 text-sm">
            <input type="checkbox" className="h-5 w-5" checked={checked} onChange={(event) => onChange(event.target.checked)} />
            {label}
        </label>
    );
}

function PairList({ label, items, onChange }: { label: string; items: Pair[]; onChange: (items: Pair[]) => void }) {
    const { t } = useI18n();

    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between"><span className="text-sm">{label}</span><button type="button" className="min-h-11 text-sm text-rose" onClick={() => onChange([...items, blank()])}>{t.admin.add}</button></div>
            {items.map((item, index) => (
                <div key={index} className="space-y-2">
                    <Bilingual label={String(index + 1)} value={item} onChange={(value) => onChange(items.map((row, i) => (i === index ? value : row)))} />
                    <button type="button" className="text-sm text-rose" onClick={() => onChange(items.filter((_, i) => i !== index))}>{t.admin.delete}</button>
                </div>
            ))}
        </div>
    );
}

function TitledList({ label, items, onChange }: { label: string; items: Titled[]; onChange: (items: Titled[]) => void }) {
    const { t } = useI18n();

    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between"><span className="text-sm">{label}</span><button type="button" className="min-h-11 text-sm text-rose" onClick={() => onChange([...items, { title: blank(), body: blank() }])}>{t.admin.add}</button></div>
            {items.map((item, index) => (
                <div key={index} className="space-y-3 border border-midnight/10 p-3">
                    <Bilingual label={t.admin.title} value={item.title} onChange={(title) => onChange(items.map((row, i) => (i === index ? { ...row, title } : row)))} />
                    <Area label={t.admin.body} value={item.body} onChange={(body) => onChange(items.map((row, i) => (i === index ? { ...row, body } : row)))} />
                    <button type="button" className="text-sm text-rose" onClick={() => onChange(items.filter((_, i) => i !== index))}>{t.admin.delete}</button>
                </div>
            ))}
        </div>
    );
}

function MediaPick({ label, value, media, onChange }: { label: string; value: number | null; media: MediaOpt[]; onChange: (id: number | null) => void }) {
    const { t } = useI18n();

    return (
        <label className="grid gap-2 text-sm">{label}
            <select className={inputClass} value={value ?? ''} onChange={(event) => onChange(event.target.value ? Number(event.target.value) : null)}>
                <option value="">{t.admin.none}</option>
                {media.map((item) => <option key={item.id} value={item.id}>{item.filename}</option>)}
            </select>
        </label>
    );
}

function IdChecks({ label, options, selected, onChange }: { label: string; options: Named[]; selected: number[]; onChange: (ids: number[]) => void }) {
    const { locale } = useI18n();

    return (
        <fieldset className="space-y-2">
            <legend className="text-sm">{label}</legend>
            <div className="flex flex-wrap gap-3">
                {options.map((option) => (
                    <label key={option.id} className="flex min-h-11 items-center gap-2 text-sm">
                        <input type="checkbox" className="h-5 w-5" checked={selected.includes(option.id)} onChange={(event) => onChange(event.target.checked ? [...selected, option.id] : selected.filter((id) => id !== option.id))} />
                        {textOf(option.name, locale)}
                    </label>
                ))}
            </div>
        </fieldset>
    );
}

export function ServicesIndex({ services, filters }: { services: Paginator<{ id: number; name: Pair; status: string; has_unpublished_changes: boolean }>; filters: { q: string } }) {
    const { t, locale } = useI18n();
    const canCreate = usePermission('services.create');

    return (
        <AdminLayout title={t.admin.services}>
            <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
                <SearchForm value={filters.q} />
                {canCreate ? <Link href="/admin/services/create" className="inline-flex min-h-12 items-center bg-midnight px-4 text-ivory">{t.admin.create}</Link> : null}
            </div>
            {services.data.length === 0 ? <Empty text={t.admin.empty} /> : (
                <ul className="divide-y divide-midnight/10 border-y border-midnight/10">
                    {services.data.map((service) => (
                        <li key={service.id} className="flex flex-wrap items-center justify-between gap-3 py-4">
                            <span>{textOf(service.name, locale)}</span>
                            <span className="text-sm text-muted">{service.status}{service.has_unpublished_changes ? ` — ${t.admin.unpublished}` : ''}</span>
                            <Link href={`/admin/services/${service.id}/edit`} className="inline-flex min-h-11 items-center">{t.admin.edit}</Link>
                        </li>
                    ))}
                </ul>
            )}
            <Pages links={services.links} />
        </AdminLayout>
    );
}

type ServiceRecord = {
    id: number; slug: string; public_slug: string; name: Pair; summary: Pair; description: Block[]; features: Pair[];
    cover_media_id: number | null; technology_ids: number[]; seo: { title: Pair; description: Pair };
} | null;

export function ServiceForm({ service, technologies, media, revisions }: { service: ServiceRecord; technologies: Named[]; media: MediaOpt[]; revisions: Revision[] }) {
    const { t, locale } = useI18n();
    const form = useForm({
        slug: service?.slug ?? '',
        name: service?.name ?? blank(),
        summary: service?.summary ?? blank(),
        description: service?.description ?? [],
        features: service?.features ?? [],
        cover_media_id: service?.cover_media_id ?? null,
        technology_ids: service?.technology_ids ?? [],
        seo: service?.seo ?? seoBlank(),
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        service ? form.put(`/admin/services/${service.id}`) : form.post('/admin/services');
    }

    return (
        <AdminLayout title={service ? t.admin.edit : t.admin.create}>
            <form onSubmit={submit} className="max-w-4xl space-y-6">
                <Errors errors={form.errors} />
                <label className="grid gap-2 text-sm">{t.admin.slug}<input className={inputClass} dir="ltr" value={form.data.slug} onChange={(event) => form.setData('slug', event.target.value)} /></label>
                <Bilingual label={t.admin.name} value={form.data.name} onChange={(name) => form.setData('name', name)} />
                <Area label={t.admin.summary} value={form.data.summary} onChange={(summary) => form.setData('summary', summary)} />
                <BlockEditor blocks={form.data.description} onChange={(description) => form.setData('description', description)} />
                <PairList label={t.admin.features} items={form.data.features} onChange={(features) => form.setData('features', features)} />
                <MediaPick label={t.admin.cover} value={form.data.cover_media_id} media={media} onChange={(cover_media_id) => form.setData('cover_media_id', cover_media_id)} />
                <IdChecks label={t.admin.technologies} options={technologies} selected={form.data.technology_ids} onChange={(technology_ids) => form.setData('technology_ids', technology_ids)} />
                <Bilingual label={t.admin.seoTitle} value={form.data.seo.title} onChange={(title) => form.setData('seo', { ...form.data.seo, title })} />
                <Area label={t.admin.seoDescription} value={form.data.seo.description} onChange={(description) => form.setData('seo', { ...form.data.seo, description })} />
                <div className="flex gap-3">
                    <button disabled={form.processing} className="min-h-12 bg-midnight px-4 text-ivory">{t.admin.save}</button>
                    {service ? <DeleteButton href={`/admin/services/${service.id}`} /> : null}
                </div>
                {service ? <PublishBar canPublish={usePermission('services.publish')} publishUrl={`/admin/services/${service.id}/publish`} scheduleUrl={`/admin/services/${service.id}/schedule`} previewUrl={`${localPath(locale, `/services/${service.public_slug}`)}?preview=1`} /> : null}
            </form>
            <Revisions rows={revisions} />
        </AdminLayout>
    );
}

export function ProjectsIndex({ projects, categories, filters }: {
    projects: Paginator<{ id: number; title: Pair; status: string; is_demonstration: boolean; category: Pair | null; has_unpublished_changes: boolean }>;
    categories: Named[];
    filters: { q: string };
}) {
    const { t, locale } = useI18n();
    const category = useForm({ slug: '', name: blank() });

    return (
        <AdminLayout title={t.admin.projects}>
            <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
                <SearchForm value={filters.q} />
                {usePermission('projects.create') ? <Link href="/admin/projects/create" className="inline-flex min-h-12 items-center bg-midnight px-4 text-ivory">{t.admin.create}</Link> : null}
            </div>
            {projects.data.length === 0 ? <Empty text={t.admin.empty} /> : (
                <ul className="divide-y divide-midnight/10 border-y border-midnight/10">
                    {projects.data.map((project) => (
                        <li key={project.id} className="flex flex-wrap items-center justify-between gap-3 py-4">
                            <span>{textOf(project.title, locale)}{project.is_demonstration ? ` — ${t.demonstration}` : ''}</span>
                            <span className="text-sm text-muted">{textOf(project.category, locale)} {project.status}</span>
                            <Link href={`/admin/projects/${project.id}/edit`} className="inline-flex min-h-11 items-center">{t.admin.edit}</Link>
                        </li>
                    ))}
                </ul>
            )}
            <Pages links={projects.links} />
            {usePermission('projects.update') ? (
                <form className="mt-8 max-w-3xl space-y-4" onSubmit={(event) => { event.preventDefault(); category.post('/admin/project-categories'); }}>
                    <h2 className="text-sm">{t.admin.category}</h2>
                    <label className="grid gap-2 text-sm">{t.admin.slug}<input className={inputClass} dir="ltr" value={category.data.slug} onChange={(event) => category.setData('slug', event.target.value)} /></label>
                    <Bilingual label={t.admin.name} value={category.data.name} onChange={(name) => category.setData('name', name)} />
                    <button className="min-h-12 bg-midnight px-4 text-ivory">{t.admin.add}</button>
                    <ul className="divide-y divide-midnight/10">
                        {categories.map((item) => (
                            <li key={item.id} className="flex items-center justify-between py-3 text-sm">
                                <span>{textOf(item.name, locale)}</span>
                                {usePermission('projects.delete') ? <DeleteButton href={`/admin/project-categories/${item.id}`} /> : null}
                            </li>
                        ))}
                    </ul>
                </form>
            ) : null}
        </AdminLayout>
    );
}

type ProjectRecord = {
    id: number; slug: string; public_slug: string; project_category_id: number | null; title: Pair; summary: Pair; description: Block[];
    challenges: Titled[]; solutions: Titled[]; features: Pair[]; results: Titled[]; results_verified: boolean; is_demonstration: boolean;
    is_featured: boolean; cover_media_id: number | null; gallery_ids: number[]; technology_ids: number[]; seo: { title: Pair; description: Pair };
} | null;

export function ProjectForm({ project, categories, technologies, media, revisions }: { project: ProjectRecord; categories: Named[]; technologies: Named[]; media: MediaOpt[]; revisions: Revision[] }) {
    const { t, locale } = useI18n();
    const form = useForm({
        slug: project?.slug ?? '',
        project_category_id: project?.project_category_id ?? null,
        title: project?.title ?? blank(),
        summary: project?.summary ?? blank(),
        description: project?.description ?? [],
        challenges: project?.challenges ?? [],
        solutions: project?.solutions ?? [],
        features: project?.features ?? [],
        results: project?.results ?? [],
        results_verified: project?.results_verified ?? false,
        is_demonstration: project?.is_demonstration ?? true,
        is_featured: project?.is_featured ?? false,
        cover_media_id: project?.cover_media_id ?? null,
        gallery_ids: project?.gallery_ids ?? [],
        technology_ids: project?.technology_ids ?? [],
        seo: project?.seo ?? seoBlank(),
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        project ? form.put(`/admin/projects/${project.id}`) : form.post('/admin/projects');
    }

    return (
        <AdminLayout title={project ? t.admin.edit : t.admin.create}>
            <form onSubmit={submit} className="max-w-4xl space-y-6">
                <Errors errors={form.errors} />
                <label className="grid gap-2 text-sm">{t.admin.slug}<input className={inputClass} dir="ltr" value={form.data.slug} onChange={(event) => form.setData('slug', event.target.value)} /></label>
                <label className="grid gap-2 text-sm">{t.admin.category}
                    <select className={inputClass} value={form.data.project_category_id ?? ''} onChange={(event) => form.setData('project_category_id', event.target.value ? Number(event.target.value) : null)}>
                        <option value="">{t.admin.none}</option>
                        {categories.map((item) => <option key={item.id} value={item.id}>{textOf(item.name, locale)}</option>)}
                    </select>
                </label>
                <Bilingual label={t.admin.title} value={form.data.title} onChange={(title) => form.setData('title', title)} />
                <Area label={t.admin.summary} value={form.data.summary} onChange={(summary) => form.setData('summary', summary)} />
                <BlockEditor blocks={form.data.description} onChange={(description) => form.setData('description', description)} />
                <TitledList label={t.admin.challenges} items={form.data.challenges} onChange={(challenges) => form.setData('challenges', challenges)} />
                <TitledList label={t.admin.solutions} items={form.data.solutions} onChange={(solutions) => form.setData('solutions', solutions)} />
                <PairList label={t.admin.features} items={form.data.features} onChange={(features) => form.setData('features', features)} />
                <Check label={t.admin.verified} checked={form.data.results_verified} onChange={(results_verified) => form.setData('results_verified', results_verified)} />
                <TitledList label={t.admin.results} items={form.data.results} onChange={(results) => form.setData('results', results)} />
                <Check label={t.admin.demonstration} checked={form.data.is_demonstration} onChange={(is_demonstration) => form.setData('is_demonstration', is_demonstration)} />
                <Check label={t.admin.featured} checked={form.data.is_featured} onChange={(is_featured) => form.setData('is_featured', is_featured)} />
                <MediaPick label={t.admin.cover} value={form.data.cover_media_id} media={media} onChange={(cover_media_id) => form.setData('cover_media_id', cover_media_id)} />
                <IdChecks label={t.admin.gallery} options={media.map((item) => ({ id: item.id, name: item.filename }))} selected={form.data.gallery_ids} onChange={(gallery_ids) => form.setData('gallery_ids', gallery_ids)} />
                <IdChecks label={t.admin.technologies} options={technologies} selected={form.data.technology_ids} onChange={(technology_ids) => form.setData('technology_ids', technology_ids)} />
                <Bilingual label={t.admin.seoTitle} value={form.data.seo.title} onChange={(title) => form.setData('seo', { ...form.data.seo, title })} />
                <Area label={t.admin.seoDescription} value={form.data.seo.description} onChange={(description) => form.setData('seo', { ...form.data.seo, description })} />
                <div className="flex gap-3">
                    <button disabled={form.processing} className="min-h-12 bg-midnight px-4 text-ivory">{t.admin.save}</button>
                    {project ? <DeleteButton href={`/admin/projects/${project.id}`} /> : null}
                </div>
                {project ? <PublishBar canPublish={usePermission('projects.publish')} publishUrl={`/admin/projects/${project.id}/publish`} scheduleUrl={`/admin/projects/${project.id}/schedule`} previewUrl={`${localPath(locale, `/work/${project.public_slug}`)}?preview=1`} /> : null}
            </form>
            <Revisions rows={revisions} />
        </AdminLayout>
    );
}

export function ArticlesIndex({ articles, categories, tags, filters }: {
    articles: Paginator<{ id: number; title: Pair; status: string; category: Pair | null; has_unpublished_changes: boolean }>;
    categories: Named[];
    tags: Named[];
    filters: { q: string };
}) {
    const { t, locale } = useI18n();
    const category = useForm({ slug: '', name: blank() });
    const tag = useForm({ slug: '', name: blank() });

    return (
        <AdminLayout title={t.admin.articles}>
            <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
                <SearchForm value={filters.q} />
                {usePermission('articles.create') ? <Link href="/admin/articles/create" className="inline-flex min-h-12 items-center bg-midnight px-4 text-ivory">{t.admin.create}</Link> : null}
            </div>
            {articles.data.length === 0 ? <Empty text={t.admin.empty} /> : (
                <ul className="divide-y divide-midnight/10 border-y border-midnight/10">
                    {articles.data.map((article) => (
                        <li key={article.id} className="flex flex-wrap items-center justify-between gap-3 py-4">
                            <span>{textOf(article.title, locale)}</span>
                            <span className="text-sm text-muted">{textOf(article.category, locale)} {article.status}</span>
                            <Link href={`/admin/articles/${article.id}/edit`} className="inline-flex min-h-11 items-center">{t.admin.edit}</Link>
                        </li>
                    ))}
                </ul>
            )}
            <Pages links={articles.links} />
            {usePermission('articles.update') ? (
                <div className="mt-8 grid gap-8 lg:grid-cols-2">
                    <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); category.post('/admin/article-categories'); }}>
                        <h2 className="text-sm">{t.admin.category}</h2>
                        <label className="grid gap-2 text-sm">{t.admin.slug}<input className={inputClass} dir="ltr" value={category.data.slug} onChange={(event) => category.setData('slug', event.target.value)} /></label>
                        <Bilingual label={t.admin.name} value={category.data.name} onChange={(name) => category.setData('name', name)} />
                        <button className="min-h-12 bg-midnight px-4 text-ivory">{t.admin.add}</button>
                        <ul>{categories.map((item) => <li key={item.id} className="py-2 text-sm">{textOf(item.name, locale)}</li>)}</ul>
                    </form>
                    <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); tag.post('/admin/tags'); }}>
                        <h2 className="text-sm">{t.admin.tags}</h2>
                        <label className="grid gap-2 text-sm">{t.admin.slug}<input className={inputClass} dir="ltr" value={tag.data.slug} onChange={(event) => tag.setData('slug', event.target.value)} /></label>
                        <Bilingual label={t.admin.name} value={tag.data.name} onChange={(name) => tag.setData('name', name)} />
                        <button className="min-h-12 bg-midnight px-4 text-ivory">{t.admin.add}</button>
                        <ul>{tags.map((item) => <li key={item.id} className="py-2 text-sm">{textOf(item.name, locale)}</li>)}</ul>
                    </form>
                </div>
            ) : null}
        </AdminLayout>
    );
}

type ArticleRecord = {
    id: number; slug: string; public_slug: string; article_category_id: number | null; title: Pair; excerpt: Pair; body: Block[];
    cover_media_id: number | null; is_featured: boolean; tag_ids: number[]; seo: { title: Pair; description: Pair };
} | null;

export function ArticleForm({ article, categories, tags, media, revisions }: { article: ArticleRecord; categories: Named[]; tags: Named[]; media: MediaOpt[]; revisions: Revision[] }) {
    const { t, locale } = useI18n();
    const form = useForm({
        slug: article?.slug ?? '',
        article_category_id: article?.article_category_id ?? null,
        title: article?.title ?? blank(),
        excerpt: article?.excerpt ?? blank(),
        body: article?.body ?? [],
        cover_media_id: article?.cover_media_id ?? null,
        is_featured: article?.is_featured ?? false,
        tag_ids: article?.tag_ids ?? [],
        seo: article?.seo ?? seoBlank(),
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        article ? form.put(`/admin/articles/${article.id}`) : form.post('/admin/articles');
    }

    return (
        <AdminLayout title={article ? t.admin.edit : t.admin.create}>
            <form onSubmit={submit} className="max-w-4xl space-y-6">
                <Errors errors={form.errors} />
                <label className="grid gap-2 text-sm">{t.admin.slug}<input className={inputClass} dir="ltr" value={form.data.slug} onChange={(event) => form.setData('slug', event.target.value)} /></label>
                <label className="grid gap-2 text-sm">{t.admin.category}
                    <select className={inputClass} value={form.data.article_category_id ?? ''} onChange={(event) => form.setData('article_category_id', event.target.value ? Number(event.target.value) : null)}>
                        <option value="">{t.admin.none}</option>
                        {categories.map((item) => <option key={item.id} value={item.id}>{textOf(item.name, locale)}</option>)}
                    </select>
                </label>
                <Bilingual label={t.admin.title} value={form.data.title} onChange={(title) => form.setData('title', title)} />
                <Area label={t.admin.excerpt} value={form.data.excerpt} onChange={(excerpt) => form.setData('excerpt', excerpt)} />
                <BlockEditor blocks={form.data.body} onChange={(body) => form.setData('body', body)} />
                <MediaPick label={t.admin.cover} value={form.data.cover_media_id} media={media} onChange={(cover_media_id) => form.setData('cover_media_id', cover_media_id)} />
                <Check label={t.admin.featured} checked={form.data.is_featured} onChange={(is_featured) => form.setData('is_featured', is_featured)} />
                <IdChecks label={t.admin.tags} options={tags} selected={form.data.tag_ids} onChange={(tag_ids) => form.setData('tag_ids', tag_ids)} />
                <Bilingual label={t.admin.seoTitle} value={form.data.seo.title} onChange={(title) => form.setData('seo', { ...form.data.seo, title })} />
                <Area label={t.admin.seoDescription} value={form.data.seo.description} onChange={(description) => form.setData('seo', { ...form.data.seo, description })} />
                <div className="flex gap-3">
                    <button disabled={form.processing} className="min-h-12 bg-midnight px-4 text-ivory">{t.admin.save}</button>
                    {article ? <DeleteButton href={`/admin/articles/${article.id}`} /> : null}
                </div>
                {article ? <PublishBar canPublish={usePermission('articles.publish')} publishUrl={`/admin/articles/${article.id}/publish`} scheduleUrl={`/admin/articles/${article.id}/schedule`} previewUrl={`${localPath(locale, `/insights/${article.public_slug}`)}?preview=1`} /> : null}
            </form>
            <Revisions rows={revisions} />
        </AdminLayout>
    );
}

export function FaqsIndex({ faqs }: { faqs: Paginator<{ id: number; question: Pair; placement: string; status: string; is_visible: boolean }> }) {
    const { t, locale } = useI18n();
    const canUpdate = usePermission('faqs.update');

    return (
        <AdminLayout title={t.admin.faqs}>
            {usePermission('faqs.create') ? <Link href="/admin/faqs/create" className="mb-4 inline-flex min-h-12 items-center bg-midnight px-4 text-ivory">{t.admin.create}</Link> : null}
            {faqs.data.length === 0 ? <Empty text={t.admin.empty} /> : (
                <ul className="divide-y divide-midnight/10 border-y border-midnight/10">
                    {faqs.data.map((faq, index) => (
                        <li key={faq.id} className="flex flex-wrap items-center justify-between gap-3 py-4">
                            <span>{textOf(faq.question, locale)}</span>
                            <span className="text-sm text-muted">{faq.placement} {faq.status}</span>
                            <span className="flex gap-2">
                                {canUpdate ? (
                                    <>
                                        <button type="button" className="min-h-11 px-2" disabled={index === 0} onClick={() => router.post('/admin/faqs/reorder', { ids: faqs.data.map((row) => row.id).map((id, i, ids) => i === index - 1 ? ids[index] : i === index ? ids[index - 1] : id) })}>{t.admin.up}</button>
                                        <button type="button" className="min-h-11 px-2" disabled={index === faqs.data.length - 1} onClick={() => router.post('/admin/faqs/reorder', { ids: faqs.data.map((row) => row.id).map((id, i, ids) => i === index ? ids[index + 1] : i === index + 1 ? ids[index] : id) })}>{t.admin.down}</button>
                                    </>
                                ) : null}
                                <Link href={`/admin/faqs/${faq.id}/edit`} className="inline-flex min-h-11 items-center">{t.admin.edit}</Link>
                            </span>
                        </li>
                    ))}
                </ul>
            )}
        </AdminLayout>
    );
}

export function FaqForm({ faq, revisions }: { faq: { id: number; question: Pair; answer: Pair; placement: string; is_visible: boolean } | null; revisions: Revision[] }) {
    const { t, locale } = useI18n();
    const form = useForm({
        question: faq?.question ?? blank(),
        answer: faq?.answer ?? blank(),
        placement: faq?.placement ?? 'general',
        is_visible: faq?.is_visible ?? true,
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        faq ? form.put(`/admin/faqs/${faq.id}`) : form.post('/admin/faqs');
    }

    return (
        <AdminLayout title={faq ? t.admin.edit : t.admin.create}>
            <form onSubmit={submit} className="max-w-4xl space-y-6">
                <Errors errors={form.errors} />
                <Bilingual label={t.admin.question} value={form.data.question} onChange={(question) => form.setData('question', question)} />
                <Area label={t.admin.answer} value={form.data.answer} onChange={(answer) => form.setData('answer', answer)} />
                <label className="grid gap-2 text-sm">{t.admin.placement}
                    <select className={inputClass} value={form.data.placement} onChange={(event) => form.setData('placement', event.target.value)}>
                        <option value="home">home</option>
                        <option value="general">general</option>
                    </select>
                </label>
                <Check label={t.admin.isVisible} checked={form.data.is_visible} onChange={(is_visible) => form.setData('is_visible', is_visible)} />
                <div className="flex gap-3">
                    <button disabled={form.processing} className="min-h-12 bg-midnight px-4 text-ivory">{t.admin.save}</button>
                    {faq ? <DeleteButton href={`/admin/faqs/${faq.id}`} /> : null}
                </div>
                {faq ? <PublishBar canPublish={usePermission('faqs.publish')} publishUrl={`/admin/faqs/${faq.id}/publish`} previewUrl={`${localPath(locale)}?preview=1`} /> : null}
            </form>
            <Revisions rows={revisions} />
        </AdminLayout>
    );
}

export function MediaIndex({ media, filters }: { media: Paginator<{ id: number; url: string; filename: string; alt: Pair; usages: number }>; filters: { q: string } }) {
    const { t } = useI18n();
    const upload = useForm<{ file: File | null; alt: Pair }>({ file: null, alt: blank() });

    return (
        <AdminLayout title={t.admin.media}>
            <SearchForm value={filters.q} />
            {usePermission('media.upload') ? (
                <form className="mb-8 grid gap-3 md:grid-cols-[1fr_1fr_auto]" onSubmit={(event) => { event.preventDefault(); upload.post('/admin/media', { forceFormData: true }); }}>
                    <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" className="min-h-12" onChange={(event) => upload.setData('file', event.target.files?.[0] ?? null)} />
                    <Bilingual label={t.admin.alt} value={upload.data.alt} onChange={(alt) => upload.setData('alt', alt)} />
                    <button className="min-h-12 bg-midnight px-4 text-ivory">{t.admin.upload}</button>
                    <Errors errors={upload.errors} />
                </form>
            ) : null}
            {media.data.length === 0 ? <Empty text={t.admin.empty} /> : (
                <ul className="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    {media.data.map((item) => <MediaCard key={item.id} item={item} />)}
                </ul>
            )}
            <Pages links={media.links} />
        </AdminLayout>
    );
}

function MediaCard({ item }: { item: { id: number; url: string; filename: string; alt: Pair; usages: number } }) {
    const { t } = useI18n();
    const alt = useForm({ alt: item.alt ?? blank() });
    const replace = useForm<{ file: File | null }>({ file: null });

    return (
        <li className="space-y-3 border border-midnight/10 bg-white p-3">
            <img src={item.url} alt={item.alt?.ar || item.filename} className="aspect-[4/3] w-full object-cover" />
            <p className="truncate text-sm">{item.filename}</p>
            <p className="text-sm text-muted">{item.usages}</p>
            {usePermission('media.upload') ? (
                <>
                    <form className="space-y-2" onSubmit={(event) => { event.preventDefault(); alt.put(`/admin/media/${item.id}`); }}>
                        <Bilingual label={t.admin.alt} value={alt.data.alt} onChange={(value) => alt.setData('alt', value)} />
                        <button className="min-h-11 text-sm">{t.admin.save}</button>
                    </form>
                    <form className="space-y-2" onSubmit={(event) => { event.preventDefault(); replace.post(`/admin/media/${item.id}/replace`, { forceFormData: true }); }}>
                        <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" onChange={(event) => replace.setData('file', event.target.files?.[0] ?? null)} />
                        <button className="min-h-11 text-sm">{t.admin.replace}</button>
                    </form>
                </>
            ) : null}
            {usePermission('media.delete') && item.usages === 0 ? <DeleteButton href={`/admin/media/${item.id}`} /> : null}
        </li>
    );
}

export function InquiriesIndex({ inquiries, filters }: { inquiries: Paginator<{ id: number; name: string; email: string; company: string | null; project_type: string; status: string; created_at: string }>; filters: { q: string; status: string } }) {
    const { t } = useI18n();

    return (
        <AdminLayout title={t.admin.inquiries}>
            <form className="mb-4 flex flex-wrap gap-2" onSubmit={(event) => { event.preventDefault(); const data = new FormData(event.currentTarget); router.get('/admin/inquiries', { q: String(data.get('q') ?? ''), status: String(data.get('status') ?? '') }, { preserveState: true, replace: true }); }}>
                <input name="q" defaultValue={filters.q} aria-label={t.admin.search} className="min-h-12 flex-1 border border-midnight/15 bg-white px-3" />
                <select name="status" defaultValue={filters.status} aria-label={t.admin.status} className="min-h-12 border border-midnight/15 bg-white px-3">
                    <option value="">{t.admin.filter}</option>
                    {['new', 'in_review', 'replied', 'archived'].map((status) => <option key={status} value={status}>{status}</option>)}
                </select>
                <button className="min-h-12 bg-midnight px-4 text-ivory">{t.admin.search}</button>
            </form>
            {usePermission('inquiries.export') ? <a className="mb-4 inline-flex min-h-11 items-center" href="/admin/inquiries-export">{t.admin.export}</a> : null}
            {inquiries.data.length === 0 ? <Empty text={t.admin.empty} /> : (
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[40rem] text-start text-sm">
                        <tbody>
                            {inquiries.data.map((inquiry) => (
                                <tr key={inquiry.id} className="border-b border-midnight/10">
                                    <td className="py-4">{inquiry.name}</td>
                                    <td>{inquiry.email}</td>
                                    <td>{inquiry.project_type}</td>
                                    <td>{inquiry.status}</td>
                                    <td className="text-end"><Link href={`/admin/inquiries/${inquiry.id}`} className="inline-flex min-h-11 items-center">{t.admin.edit}</Link></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
            <Pages links={inquiries.links} />
        </AdminLayout>
    );
}

type InquiryShow = {
    id: number; name: string; email: string; phone: string | null; company: string | null; project_type: string; message: string; status: string; assigned_to: number | null; locale: string; created_at: string;
    notes: { id: number; body: string; user?: { name: string } | null; created_at: string }[];
};

export function InquiryShowPage({ inquiry, assignees }: { inquiry: InquiryShow; assignees: { id: number; name: string }[] }) {
    const { t } = useI18n();
    const form = useForm({ status: inquiry.status, assigned_to: inquiry.assigned_to });
    const note = useForm({ body: '' });

    return (
        <AdminLayout title={inquiry.name}>
            <Link href="/admin/inquiries" className="mb-4 inline-flex min-h-11 items-center">{t.admin.back}</Link>
            <dl className="grid gap-4 text-sm sm:grid-cols-2">
                <div><dt className="text-muted">{t.admin.email}</dt><dd>{inquiry.email}</dd></div>
                <div><dt className="text-muted">{t.admin.phone}</dt><dd>{inquiry.phone || '—'}</dd></div>
                <div><dt className="text-muted">{t.admin.company}</dt><dd>{inquiry.company || '—'}</dd></div>
                <div><dt className="text-muted">{t.types[inquiry.project_type as keyof typeof t.types] ?? inquiry.project_type}</dt><dd>{inquiry.locale}</dd></div>
            </dl>
            <p className="mt-6 max-w-3xl whitespace-pre-wrap">{inquiry.message}</p>
            {usePermission('inquiries.update') ? (
                <form className="mt-8 flex flex-wrap items-end gap-3" onSubmit={(event) => { event.preventDefault(); form.put(`/admin/inquiries/${inquiry.id}`); }}>
                    <label className="grid gap-2 text-sm">{t.admin.status}
                        <select className={inputClass} value={form.data.status} onChange={(event) => form.setData('status', event.target.value)}>
                            {['new', 'in_review', 'replied', 'archived'].map((status) => <option key={status}>{status}</option>)}
                        </select>
                    </label>
                    <label className="grid gap-2 text-sm">{t.admin.assign}
                        <select className={inputClass} value={form.data.assigned_to ?? ''} onChange={(event) => form.setData('assigned_to', event.target.value ? Number(event.target.value) : null)}>
                            <option value="">{t.admin.none}</option>
                            {assignees.map((user) => <option key={user.id} value={user.id}>{user.name}</option>)}
                        </select>
                    </label>
                    <button className="min-h-12 bg-midnight px-4 text-ivory">{t.admin.save}</button>
                </form>
            ) : null}
            <h2 className="mb-3 mt-10 text-sm">{t.admin.notes}</h2>
            <ul className="space-y-3">
                {inquiry.notes.map((item) => <li key={item.id} className="border border-midnight/10 p-3 text-sm"><p className="whitespace-pre-wrap">{item.body}</p><p className="mt-2 text-muted">{item.user?.name}</p></li>)}
            </ul>
            {usePermission('inquiries.update') ? (
                <form className="mt-4 space-y-3" onSubmit={(event) => { event.preventDefault(); note.post(`/admin/inquiries/${inquiry.id}/notes`); }}>
                    <textarea className="min-h-28 w-full border border-midnight/15 bg-white px-3 py-3" value={note.data.body} onChange={(event) => note.setData('body', event.target.value)} aria-label={t.admin.notes} />
                    <button className="min-h-12 bg-midnight px-4 text-ivory">{t.admin.add}</button>
                </form>
            ) : null}
        </AdminLayout>
    );
}

type NavItem = { location: 'header' | 'footer'; label: Pair; path: string; is_visible: boolean; open_in_new_tab: boolean };

export function NavigationIndex({ items }: { items: NavItem[] }) {
    const { t } = useI18n();
    const form = useForm<{ items: NavItem[] }>({
        items: items.map((item) => ({
            location: item.location,
            label: item.label ?? blank(),
            path: item.path,
            is_visible: item.is_visible,
            open_in_new_tab: item.open_in_new_tab,
        })),
    });

    function update(index: number, next: NavItem) {
        form.setData('items', form.data.items.map((item, i) => (i === index ? next : item)));
    }

    return (
        <AdminLayout title={t.admin.navigation}>
            <form className="max-w-4xl space-y-4" onSubmit={(event) => { event.preventDefault(); form.put('/admin/navigation'); }}>
                <Errors errors={form.errors} />
                {form.data.items.map((item, index) => (
                    <div key={index} className="space-y-3 border border-midnight/10 bg-white p-4">
                        <div className="grid gap-3 md:grid-cols-2">
                            <select className={inputClass} value={item.location} onChange={(event) => update(index, { ...item, location: event.target.value as 'header' | 'footer' })}>
                                <option value="header">header</option>
                                <option value="footer">footer</option>
                            </select>
                            <input className={inputClass} dir="ltr" value={item.path} onChange={(event) => update(index, { ...item, path: event.target.value })} aria-label={t.admin.url} />
                        </div>
                        <Bilingual label={t.admin.title} value={item.label} onChange={(label) => update(index, { ...item, label })} />
                        <Check label={t.admin.isVisible} checked={item.is_visible} onChange={(is_visible) => update(index, { ...item, is_visible })} />
                        <Check label={t.admin.url} checked={item.open_in_new_tab} onChange={(open_in_new_tab) => update(index, { ...item, open_in_new_tab })} />
                        <button type="button" className="text-sm text-rose" onClick={() => form.setData('items', form.data.items.filter((_, i) => i !== index))}>{t.admin.delete}</button>
                    </div>
                ))}
                <button type="button" className="min-h-12 border border-midnight px-4" onClick={() => form.setData('items', [...form.data.items, { location: 'header', label: blank(), path: '/', is_visible: true, open_in_new_tab: false }])}>{t.admin.add}</button>
                {usePermission('navigation.update') ? <button className="min-h-12 bg-midnight px-4 text-ivory">{t.admin.save}</button> : null}
            </form>
        </AdminLayout>
    );
}

type SettingsPayload = {
    brand: Record<string, string>;
    company: { legal_name?: string };
    contact: { email?: string; phone?: string; notify_email?: string; address?: Pair };
    social: Record<string, string>;
    footer: Pair;
    seo: { title: Pair; description: Pair };
    maintenance: { enabled: boolean };
    analytics: { measurement_id?: string; plausible_domain?: string };
    logo: { media_id: number | null };
    favicon: { media_id: number | null };
};

export function SettingsEdit({ settings, media }: { settings: SettingsPayload; media: MediaOpt[] }) {
    const { t } = useI18n();
    const form = useForm({
        brand: { midnight: '#210C17', burgundy: '#5B2038', rose: '#A83F67', blush: '#E8A3BB', ivory: '#F8F0F3', ...settings.brand },
        company: { legal_name: settings.company?.legal_name ?? 'RACODE' },
        contact: { email: settings.contact?.email ?? '', phone: settings.contact?.phone ?? '', notify_email: settings.contact?.notify_email ?? '', address: settings.contact?.address ?? blank() },
        social: { linkedin: '', instagram: '', x: '', github: '', youtube: '', ...settings.social },
        footer: settings.footer ?? blank(),
        seo: settings.seo ?? seoBlank(),
        maintenance: { enabled: settings.maintenance?.enabled ?? false },
        analytics: { measurement_id: settings.analytics?.measurement_id ?? '', plausible_domain: settings.analytics?.plausible_domain ?? '' },
        logo: { media_id: settings.logo?.media_id ?? null },
        favicon: { media_id: settings.favicon?.media_id ?? null },
    });

    return (
        <AdminLayout title={t.admin.settings}>
            <form className="max-w-4xl space-y-6" onSubmit={(event) => { event.preventDefault(); form.put('/admin/settings'); }}>
                <Errors errors={form.errors} />
                <fieldset className="grid gap-3 sm:grid-cols-2">
                    <legend className="mb-2 text-sm">{t.admin.colors}</legend>
                    {(['midnight', 'burgundy', 'rose', 'blush', 'ivory'] as const).map((key) => (
                        <label key={key} className="grid gap-2 text-sm">{key}
                            <input className={inputClass} dir="ltr" value={form.data.brand[key]} onChange={(event) => form.setData('brand', { ...form.data.brand, [key]: event.target.value })} />
                        </label>
                    ))}
                </fieldset>
                <label className="grid gap-2 text-sm">{t.admin.legalName}<input className={inputClass} value={form.data.company.legal_name} onChange={(event) => form.setData('company', { legal_name: event.target.value })} /></label>
                <label className="grid gap-2 text-sm">{t.admin.email}<input className={inputClass} dir="ltr" value={form.data.contact.email} onChange={(event) => form.setData('contact', { ...form.data.contact, email: event.target.value })} /></label>
                <label className="grid gap-2 text-sm">{t.admin.phone}<input className={inputClass} dir="ltr" value={form.data.contact.phone} onChange={(event) => form.setData('contact', { ...form.data.contact, phone: event.target.value })} /></label>
                <label className="grid gap-2 text-sm">{t.admin.notifyEmail}<input className={inputClass} dir="ltr" value={form.data.contact.notify_email} onChange={(event) => form.setData('contact', { ...form.data.contact, notify_email: event.target.value })} /></label>
                <Bilingual label={t.admin.address} value={form.data.contact.address} onChange={(address) => form.setData('contact', { ...form.data.contact, address })} />
                {(['linkedin', 'instagram', 'x', 'github', 'youtube'] as const).map((network) => (
                    <label key={network} className="grid gap-2 text-sm">{network}<input className={inputClass} dir="ltr" value={form.data.social[network] ?? ''} onChange={(event) => form.setData('social', { ...form.data.social, [network]: event.target.value })} /></label>
                ))}
                <Area label={t.admin.footer} value={form.data.footer} onChange={(footer) => form.setData('footer', footer)} />
                <Bilingual label={t.admin.seoTitle} value={form.data.seo.title} onChange={(title) => form.setData('seo', { ...form.data.seo, title })} />
                <Area label={t.admin.seoDescription} value={form.data.seo.description} onChange={(description) => form.setData('seo', { ...form.data.seo, description })} />
                <Check label={t.admin.maintenance} checked={form.data.maintenance.enabled} onChange={(enabled) => form.setData('maintenance', { enabled })} />
                <label className="grid gap-2 text-sm">{t.admin.analytics}<input className={inputClass} dir="ltr" value={form.data.analytics.measurement_id} onChange={(event) => form.setData('analytics', { ...form.data.analytics, measurement_id: event.target.value })} placeholder="G-" /></label>
                <label className="grid gap-2 text-sm">Plausible<input className={inputClass} dir="ltr" value={form.data.analytics.plausible_domain} onChange={(event) => form.setData('analytics', { ...form.data.analytics, plausible_domain: event.target.value })} /></label>
                <MediaPick label={t.admin.logo} value={form.data.logo.media_id} media={media} onChange={(media_id) => form.setData('logo', { media_id })} />
                <MediaPick label={t.admin.favicon} value={form.data.favicon.media_id} media={media} onChange={(media_id) => form.setData('favicon', { media_id })} />
                <button disabled={form.processing} className="min-h-12 bg-midnight px-4 text-ivory">{t.admin.save}</button>
            </form>
        </AdminLayout>
    );
}

export function UsersIndex({ users }: { users: Paginator<{ id: number; name: string; email: string; role: string | null; is_active: boolean }> }) {
    const { t } = useI18n();

    return (
        <AdminLayout title={t.admin.users}>
            {usePermission('users.create') ? <Link href="/admin/users/create" className="mb-4 inline-flex min-h-12 items-center bg-midnight px-4 text-ivory">{t.admin.create}</Link> : null}
            <ul className="divide-y divide-midnight/10 border-y border-midnight/10">
                {users.data.map((user) => (
                    <li key={user.id} className="flex flex-wrap items-center justify-between gap-3 py-4">
                        <span>{user.name}</span>
                        <span className="text-sm text-muted" dir="ltr">{user.email}</span>
                        <span className="text-sm">{user.role}{user.is_active ? '' : ` ${t.admin.hidden}`}</span>
                        <Link href={`/admin/users/${user.id}/edit`} className="inline-flex min-h-11 items-center">{t.admin.edit}</Link>
                    </li>
                ))}
            </ul>
            <Pages links={users.links} />
        </AdminLayout>
    );
}

export function UserForm({ user, roles }: { user: { id: number; name: string; email: string; locale: string; is_active: boolean; role: string } | null; roles: string[] }) {
    const { t } = useI18n();
    const form = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
        locale: user?.locale ?? 'ar',
        is_active: user?.is_active ?? true,
        role: user?.role ?? 'viewer',
        password: '',
        password_confirmation: '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        user ? form.put(`/admin/users/${user.id}`) : form.post('/admin/users');
    }

    return (
        <AdminLayout title={user ? t.admin.edit : t.admin.create}>
            <form onSubmit={submit} className="max-w-xl space-y-5">
                <Errors errors={form.errors} />
                <label className="grid gap-2 text-sm">{t.admin.name}<input className={inputClass} value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} /></label>
                <label className="grid gap-2 text-sm">{t.admin.email}<input type="email" className={inputClass} dir="ltr" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} /></label>
                <label className="grid gap-2 text-sm">{t.admin.password}<input type="password" className={inputClass} dir="ltr" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} autoComplete="new-password" /></label>
                <label className="grid gap-2 text-sm">{t.admin.passwordConfirm}<input type="password" className={inputClass} dir="ltr" value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)} autoComplete="new-password" /></label>
                <label className="grid gap-2 text-sm">{t.language}
                    <select className={inputClass} value={form.data.locale} onChange={(event) => form.setData('locale', event.target.value)}>
                        <option value="ar">العربية</option>
                        <option value="en">English</option>
                    </select>
                </label>
                <label className="grid gap-2 text-sm">{t.admin.role}
                    <select className={inputClass} value={form.data.role} onChange={(event) => form.setData('role', event.target.value)}>
                        {roles.map((role) => <option key={role} value={role}>{role}</option>)}
                    </select>
                </label>
                <Check label={t.admin.active} checked={form.data.is_active} onChange={(is_active) => form.setData('is_active', is_active)} />
                <div className="flex gap-3">
                    <button disabled={form.processing} className="min-h-12 bg-midnight px-4 text-ivory">{t.admin.save}</button>
                    {user ? <DeleteButton href={`/admin/users/${user.id}`} /> : null}
                </div>
            </form>
        </AdminLayout>
    );
}

type TechnologyRow = { id: number; slug: string; name: string; description: Pair; is_visible: boolean };

export function TechnologiesIndex({ technologies }: { technologies: TechnologyRow[] }) {
    const { t } = useI18n();
    const create = useForm({ slug: '', name: '', description: blank(), is_visible: true });

    return (
        <AdminLayout title={t.admin.technologies}>
            {usePermission('technologies.update') ? (
                <form className="mb-8 max-w-3xl space-y-4" onSubmit={(event) => { event.preventDefault(); create.post('/admin/technologies'); }}>
                    <Errors errors={create.errors} />
                    <label className="grid gap-2 text-sm">{t.admin.slug}<input className={inputClass} dir="ltr" value={create.data.slug} onChange={(event) => create.setData('slug', event.target.value)} /></label>
                    <label className="grid gap-2 text-sm">{t.admin.name}<input className={inputClass} value={create.data.name} onChange={(event) => create.setData('name', event.target.value)} /></label>
                    <Area label={t.admin.description} value={create.data.description} onChange={(description) => create.setData('description', description)} />
                    <Check label={t.admin.isVisible} checked={create.data.is_visible} onChange={(is_visible) => create.setData('is_visible', is_visible)} />
                    <button className="min-h-12 bg-midnight px-4 text-ivory">{t.admin.add}</button>
                </form>
            ) : null}
            <div className="space-y-4">
                {technologies.map((technology, index) => <TechnologyEditor key={technology.id} technology={technology} index={index} total={technologies.length} ids={technologies.map((row) => row.id)} />)}
            </div>
        </AdminLayout>
    );
}

function TechnologyEditor({ technology, index, total, ids }: { technology: TechnologyRow; index: number; total: number; ids: number[] }) {
    const { t } = useI18n();
    const form = useForm({ slug: technology.slug, name: technology.name, description: technology.description ?? blank(), is_visible: technology.is_visible });

    return (
        <form className="space-y-3 border border-midnight/10 bg-white p-4" onSubmit={(event) => { event.preventDefault(); form.put(`/admin/technologies/${technology.id}`); }}>
            <div className="flex gap-2">
                <button type="button" className="min-h-11 px-2" disabled={index === 0} onClick={() => router.post('/admin/technologies/reorder', { ids: ids.map((id, i) => i === index - 1 ? ids[index] : i === index ? ids[index - 1] : id) })}>{t.admin.up}</button>
                <button type="button" className="min-h-11 px-2" disabled={index === total - 1} onClick={() => router.post('/admin/technologies/reorder', { ids: ids.map((id, i) => i === index ? ids[index + 1] : i === index + 1 ? ids[index] : id) })}>{t.admin.down}</button>
            </div>
            <label className="grid gap-2 text-sm">{t.admin.slug}<input className={inputClass} dir="ltr" value={form.data.slug} onChange={(event) => form.setData('slug', event.target.value)} /></label>
            <label className="grid gap-2 text-sm">{t.admin.name}<input className={inputClass} value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} /></label>
            <Area label={t.admin.description} value={form.data.description} onChange={(description) => form.setData('description', description)} />
            <Check label={t.admin.isVisible} checked={form.data.is_visible} onChange={(is_visible) => form.setData('is_visible', is_visible)} />
            <div className="flex gap-3">
                {usePermission('technologies.update') ? <button className="min-h-12 bg-midnight px-4 text-ivory">{t.admin.save}</button> : null}
                {usePermission('technologies.update') ? <DeleteButton href={`/admin/technologies/${technology.id}`} /> : null}
            </div>
        </form>
    );
}

export function ActivityIndex({ logs, filters }: { logs: Paginator<{ id: number; action: string; description: string; user: string | null; created_at: string | null }>; filters: { q: string } }) {
    const { t } = useI18n();

    return (
        <AdminLayout title={t.admin.activity}>
            <SearchForm value={filters.q} />
            {logs.data.length === 0 ? <Empty text={t.admin.empty} /> : (
                <ul className="divide-y divide-midnight/10 border-y border-midnight/10">
                    {logs.data.map((log) => (
                        <li key={log.id} className="grid gap-1 py-4 sm:grid-cols-[12rem_1fr]">
                            <span className="text-sm text-muted">{log.created_at}</span>
                            <span>{log.description}{log.user ? ` — ${log.user}` : ''}<span className="ms-3 text-sm text-muted" dir="ltr">{log.action}</span></span>
                        </li>
                    ))}
                </ul>
            )}
            <Pages links={logs.links} />
        </AdminLayout>
    );
}
