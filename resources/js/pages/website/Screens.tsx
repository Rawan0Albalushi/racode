import { useForm, usePage } from '@inertiajs/react';
import { FormEvent } from 'react';
import { SiteLayout } from '@/components/site/Chrome';
import { Blocks, HomeSections, PageIntro } from '@/components/site/Sections';
import { useI18n } from '@/lib/useI18n';
import type { Block, HomeSection, MediaCard, Seo, SharedProps } from '@/types';

type ServiceCard = { slug: string; name: string; summary: string; cover: MediaCard; features?: string[] };
type TechnologyCard = { slug: string; name: string; description: string };
type FaqCard = { id: number; question: string; answer: string };

type ProjectCard = { slug: string; title: string; summary: string; is_demonstration: boolean; category: string | null; cover: MediaCard; category_slug?: string | null };
type ArticleCard = { slug: string; title: string; excerpt: string; cover: MediaCard; is_featured: boolean; published_at: string | null; category: string | null; category_slug?: string | null };

export default function Home({ seo, sections, services, projects, technologies, faqs, available, previewing }: {
    seo: Seo;
    sections: HomeSection[];
    services: ServiceCard[];
    projects: ProjectCard[];
    technologies: TechnologyCard[];
    faqs: FaqCard[];
    available: boolean;
    previewing: boolean;
}) {
    const { t } = useI18n();

    return (
        <SiteLayout seo={seo} previewing={previewing}>
            {available ? (
                <HomeSections sections={sections} services={services} projects={projects} technologies={technologies} faqs={faqs} />
            ) : (
                <div className="stage mx-auto flex min-h-svh max-w-[90rem] flex-col justify-end px-6 pt-[var(--bar-clearance)] pb-24">
                    <h1 className="display text-6xl">RACODE</h1>
                    <p className="quiet mt-4 max-w-[62ch] text-lg">{t.emptyHome}</p>
                </div>
            )}
        </SiteLayout>
    );
}

export function DocumentPage({ seo, page, previewing }: { seo: Seo; page: { title: string; excerpt: string; body: Block[] }; previewing: boolean }) {
    return (
        <SiteLayout seo={seo} previewing={previewing}>
            <PageIntro title={page.title} excerpt={page.excerpt} />
            <div className="mx-auto max-w-[90rem] px-4 py-16 sm:px-6">
                <Blocks blocks={page.body} />
            </div>
        </SiteLayout>
    );
}

export function ServicesIndex({ seo, intro, services, previewing }: { seo: Seo; intro: { title: string; excerpt: string }; services: ServiceCard[]; previewing: boolean }) {
    const { locale } = useI18n();

    return (
        <SiteLayout seo={seo} previewing={previewing}>
            <PageIntro title={intro.title} excerpt={intro.excerpt} />
            <div className="mx-auto max-w-[90rem] border-t border-[color:var(--line)] px-4 sm:px-6">
                {services.map((service) => (
                    <a key={service.slug} href={`/${locale}/services/${service.slug}`} className="studio-row grid gap-3 border-b border-[color:var(--line)] px-2 py-8 lg:grid-cols-[1fr_1.1fr] lg:items-center">
                        <h2 className="display text-4xl">{service.name}</h2>
                        <p className="quiet max-w-[52ch] leading-8">{service.summary}</p>
                        <span className="wake" />
                    </a>
                ))}
            </div>
        </SiteLayout>
    );
}

export function ServiceShow({ seo, service, previewing }: {
    seo: Seo;
    previewing: boolean;
    service: { name: string; summary: string; description: Block[]; features: string[]; cover: MediaCard; technologies: TechnologyCard[] };
}) {
    const { t } = useI18n();

    return (
        <SiteLayout seo={seo} previewing={previewing}>
            <PageIntro title={service.name} excerpt={service.summary} />
            <div className="mx-auto grid max-w-[90rem] gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[1.4fr_0.6fr]">
                <Blocks blocks={service.description} />
                <aside className="space-y-8">
                    {service.cover ? <img src={service.cover.url} alt={service.cover.alt} width={service.cover.width ?? undefined} height={service.cover.height ?? undefined} className="w-full" /> : null}
                    <div>
                        <h2 className="display text-2xl">{t.features}</h2>
                        <ul className="mt-4 space-y-2">{service.features.map((feature) => <li key={feature}>{feature}</li>)}</ul>
                    </div>
                    <div>
                        <h2 className="display text-2xl">{t.technologies}</h2>
                        <ul className="mt-4 space-y-2">{service.technologies.map((item) => <li key={item.slug}>{item.name}</li>)}</ul>
                    </div>
                </aside>
            </div>
        </SiteLayout>
    );
}

export function WorkIndex({ seo, intro, projects, categories, category, previewing }: {
    seo: Seo;
    intro: { title: string; excerpt: string };
    projects: ProjectCard[];
    categories: { slug: string; name: string }[];
    category: string | null;
    previewing: boolean;
}) {
    const { locale, t } = useI18n();

    return (
        <SiteLayout seo={seo} previewing={previewing}>
            <PageIntro title={intro.title} excerpt={intro.excerpt} />
            <div className="mx-auto max-w-[90rem] px-4 py-10 sm:px-6">
                <div className="flex flex-wrap gap-2">
                    <a href={`/${locale}/work`} className={`inline-flex min-h-11 items-center border px-3 ${!category ? 'chip is-on' : 'chip'}`}>{t.all}</a>
                    {categories.map((item) => (
                        <a key={item.slug} href={`/${locale}/work?category=${item.slug}`} className={`inline-flex min-h-11 items-center border px-3 ${category === item.slug ? 'chip is-on' : 'chip'}`}>{item.name}</a>
                    ))}
                </div>
                {projects.length === 0 ? <p className="mt-10">{t.noProjects}</p> : null}
                <div className="mt-8 grid gap-4 md:grid-cols-2">
                    {projects.map((project) => (
                        <a key={project.slug} href={`/${locale}/work/${project.slug}`} className="group relative block overflow-hidden rounded-[1.5rem]">
                            {project.cover ? (
                                <img src={project.cover.url} alt="" className="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-[1.03]" />
                            ) : (
                                <span className="plate grid aspect-[4/3] place-items-center">
                                    <span className="display text-4xl">{project.title}</span>
                                </span>
                            )}
                            <span className="frame-title absolute inset-x-0 bottom-0 p-5">
                                <span className="display block text-3xl">{project.title}</span>
                                <span className="mt-2 block text-sm leading-6">
                                    {project.is_demonstration ? `${t.demonstration}. ` : ''}
                                    {project.summary}
                                </span>
                            </span>
                        </a>
                    ))}
                </div>
            </div>
        </SiteLayout>
    );
}

export function WorkShow({ seo, project, previewing }: {
    seo: Seo;
    previewing: boolean;
    project: {
        title: string;
        summary: string;
        description: Block[];
        challenges: { title: string; body: string }[];
        solutions: { title: string; body: string }[];
        features: string[];
        results: { title: string; body: string }[];
        results_verified: boolean;
        is_demonstration: boolean;
        cover: MediaCard;
        gallery: MediaCard[];
        technologies: TechnologyCard[];
        category: { name: string } | null;
    };
}) {
    const { t } = useI18n();

    return (
        <SiteLayout seo={seo} previewing={previewing}>
            <PageIntro title={project.title} excerpt={project.summary} />
            {project.is_demonstration ? <p className="notice px-4 py-3 text-center text-sm">{t.demonstration}</p> : null}
            <div className="mx-auto max-w-[90rem] space-y-12 px-4 py-16 sm:px-6">
                {project.cover ? <img src={project.cover.url} alt={project.cover.alt} className="max-h-[36rem] w-full object-cover" /> : <div className="plate grid min-h-64 place-items-center"><span className="display text-6xl">RACODE</span></div>}
                <Blocks blocks={project.description} />
                <PairList label={t.challenge} items={project.challenges} />
                <PairList label={t.solution} items={project.solutions} />
                <div>
                    <h2 className="display text-3xl">{t.features}</h2>
                    <ul className="mt-4 space-y-2">{project.features.map((feature) => <li key={feature}>{feature}</li>)}</ul>
                </div>
                <div>
                    <h2 className="display text-3xl">{t.results}</h2>
                    {project.results_verified && project.results.length > 0 ? <PairList label="" items={project.results} /> : <p className="mt-4">{t.noResults}</p>}
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    {project.gallery.filter((image): image is NonNullable<MediaCard> => image !== null).map((image) => (
                        <img key={image.url} src={image.url} alt={image.alt} loading="lazy" className="w-full" />
                    ))}
                </div>
                <ul className="quiet flex flex-wrap gap-x-4 gap-y-2 text-sm">
                    {project.technologies.map((item) => <li key={item.slug}>{item.name}</li>)}
                </ul>
            </div>
        </SiteLayout>
    );
}

function PairList({ label, items }: { label: string; items: { title: string; body: string }[] }) {
    if (items.length === 0) {
        return null;
    }

    return (
        <div>
            {label ? <h2 className="display text-3xl">{label}</h2> : null}
            <div className="mt-4 space-y-6">
                {items.map((item) => (
                    <div key={item.title}>
                        <h3 className="text-xl">{item.title}</h3>
                        <p className="mt-2 leading-8">{item.body}</p>
                    </div>
                ))}
            </div>
        </div>
    );
}

export function TechnologiesPage({ seo, intro, technologies, previewing }: {
    seo: Seo;
    intro: { title: string; excerpt: string };
    technologies: TechnologyCard[];
    previewing: boolean;
}) {
    return (
        <SiteLayout seo={seo} previewing={previewing}>
            <PageIntro title={intro.title} excerpt={intro.excerpt} />
            <div className="mx-auto max-w-[90rem] px-4 py-12 sm:px-6">
                <ul className="border-t border-[color:var(--line)]">
                    {technologies.map((item) => (
                        <li key={item.slug} className="grid gap-2 border-b border-[color:var(--line)] py-6 sm:grid-cols-[14rem_1fr] sm:items-baseline">
                            <span className="display text-3xl" dir="ltr">{item.name}</span>
                            <span className="quiet leading-7">{item.description}</span>
                        </li>
                    ))}
                </ul>
            </div>
        </SiteLayout>
    );
}

export function InsightsIndex({ seo, intro, articles, categories, category, previewing }: {
    seo: Seo;
    intro: { title: string; excerpt: string };
    articles: ArticleCard[];
    categories: { slug: string; name: string }[];
    category: string | null;
    previewing: boolean;
}) {
    const { locale, t } = useI18n();

    return (
        <SiteLayout seo={seo} previewing={previewing}>
            <PageIntro title={intro.title} excerpt={intro.excerpt} />
            <div className="mx-auto max-w-[90rem] px-4 py-10 sm:px-6">
                <div className="flex flex-wrap gap-2">
                    <a href={`/${locale}/insights`} className={`inline-flex min-h-11 items-center border px-3 ${!category ? 'chip is-on' : 'chip'}`}>{t.all}</a>
                    {categories.map((item) => (
                        <a key={item.slug} href={`/${locale}/insights?category=${item.slug}`} className={`inline-flex min-h-11 items-center border px-3 ${category === item.slug ? 'chip is-on' : 'chip'}`}>{item.name}</a>
                    ))}
                </div>
                {articles.length === 0 ? <p className="mt-10">{t.noArticles}</p> : null}
                <div className="mt-10 border-t border-[color:var(--line)]">
                    {articles.map((article) => (
                        <a key={article.slug} href={`/${locale}/insights/${article.slug}`} className="sounding block border-b border-[color:var(--line)] py-8">
                            <h2 className="display max-w-3xl text-4xl">{article.title}</h2>
                            <p className="quiet mt-3 max-w-[62ch] leading-8">{article.excerpt}</p>
                            {article.category ? <p className="text-course mt-3 text-sm">{article.category}</p> : null}
                            <span className="wake" />
                        </a>
                    ))}
                </div>
            </div>
        </SiteLayout>
    );
}

export function InsightShow({ seo, article, previewing }: {
    seo: Seo;
    previewing: boolean;
    article: { title: string; excerpt: string; body: Block[]; cover: MediaCard; published_at: string | null; category: { name: string } | null };
}) {
    const { t } = useI18n();

    return (
        <SiteLayout seo={seo} previewing={previewing}>
            <PageIntro title={article.title} excerpt={article.excerpt} />
            <div className="mx-auto max-w-[90rem] px-4 py-16 sm:px-6">
                <p className="quiet mb-8 text-sm">{article.category?.name} {article.published_at ? `· ${t.published} ${article.published_at}` : ''}</p>
                {article.cover ? <img src={article.cover.url} alt={article.cover.alt} className="mb-10 w-full max-w-4xl" /> : null}
                <Blocks blocks={article.body} />
            </div>
        </SiteLayout>
    );
}

export function ContactPage({ seo, intro, project_types, started_at, previewing }: {
    seo: Seo;
    intro: { title: string; excerpt: string };
    project_types: string[];
    started_at: number;
    previewing: boolean;
}) {
    const { locale, t } = useI18n();
    const { errors } = usePage<SharedProps & { errors: Record<string, string> }>().props;
    const form = useForm({
        name: '',
        email: '',
        phone: '',
        company: '',
        project_type: project_types[0] ?? 'web',
        message: '',
        consent: false,
        started_at,
        company_website: '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post(`/${locale}/contact`, { preserveScroll: true });
    }

    return (
        <SiteLayout seo={seo} previewing={previewing}>
            <PageIntro title={intro.title} excerpt={intro.excerpt} />
            <form onSubmit={submit} className="mx-auto grid max-w-[42rem] gap-5 px-4 py-16 sm:px-6" noValidate>
                <Field label={t.contact.name} error={errors.name} value={form.data.name} onChange={(value) => form.setData('name', value)} />
                <Field label={t.contact.email} error={errors.email} type="email" value={form.data.email} onChange={(value) => form.setData('email', value)} />
                <Field label={t.contact.phone} error={errors.phone} value={form.data.phone} onChange={(value) => form.setData('phone', value)} />
                <Field label={t.contact.company} error={errors.company} value={form.data.company} onChange={(value) => form.setData('company', value)} />
                <label className="grid gap-2 text-sm">
                    {t.contact.type}
                    <select className="field" value={form.data.project_type} onChange={(event) => form.setData('project_type', event.target.value)}>
                        {project_types.map((type) => <option key={type} value={type}>{t.types[type] ?? type}</option>)}
                    </select>
                    {errors.project_type ? <span className="text-rose">{errors.project_type}</span> : null}
                </label>
                <label className="grid gap-2 text-sm">
                    {t.contact.message}
                    <textarea className="field" value={form.data.message} onChange={(event) => form.setData('message', event.target.value)} />
                    {errors.message ? <span className="text-rose">{errors.message}</span> : null}
                </label>
                <label className="flex items-start gap-3 text-sm leading-6">
                    <input type="checkbox" className="mt-1 h-5 w-5" checked={form.data.consent} onChange={(event) => form.setData('consent', event.target.checked)} />
                    <span>{t.contact.consent}</span>
                </label>
                {errors.consent ? <span className="text-rose">{errors.consent}</span> : null}
                <div className="sr-only" aria-hidden>
                    <label>
                        {t.contact.honeypot}
                        <input tabIndex={-1} autoComplete="off" value={form.data.company_website} onChange={(event) => form.setData('company_website', event.target.value)} />
                    </label>
                </div>
                <button type="submit" disabled={form.processing} className="slip w-full sm:w-fit">
                    {form.processing ? t.contact.sending : t.contact.submit}
                </button>
            </form>
        </SiteLayout>
    );
}

function Field({ label, value, onChange, error, type = 'text' }: { label: string; value: string; onChange: (value: string) => void; error?: string; type?: string }) {
    return (
        <label className="grid gap-2 text-sm">
            {label}
            <input type={type} className="field" value={value} onChange={(event) => onChange(event.target.value)} />
            {error ? <span className="text-rose">{error}</span> : null}
        </label>
    );
}

export function ErrorPage({ status }: { status: number }) {
    const { t, locale } = useI18n();
    const shared = usePage<SharedProps>().props;
    const message = status === 403 ? t.forbidden : status === 503 ? t.maintenance : status === 500 ? t.server : t.notFoundBody;

    return (
        <SiteLayout seo={{ ...shared.seo, title: `${status} — RACODE` }}>
            <div className="stage mx-auto flex min-h-svh max-w-[90rem] flex-col justify-end px-6 pt-[var(--bar-clearance)] pb-20">
                <p className="text-course display text-[clamp(3.5rem,10vw,6rem)] leading-none">{status}</p>
                <h1 className="display mt-4 max-w-3xl text-5xl">{status === 404 ? t.notFoundTitle : 'RACODE'}</h1>
                <p className="quiet mt-6 max-w-[62ch] text-lg leading-8">{message}</p>
                <a href={`/${locale}`} className="slip mt-8 w-fit">{t.backHome}</a>
            </div>
        </SiteLayout>
    );
}
