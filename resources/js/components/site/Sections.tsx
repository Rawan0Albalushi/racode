import { Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { TextButton } from '@/components/site/Chrome';
import { useI18n } from '@/lib/useI18n';
import { localPath, type Block, type HomeSection, type MediaCard } from '@/types';

type ServiceCard = { slug: string; name: string; summary: string; cover: MediaCard };
type ProjectCard = { slug: string; title: string; summary: string; is_demonstration: boolean; category: string | null };
type TechnologyCard = { slug: string; name: string; description: string };
type FaqCard = { question: string; answer: string };

export function CourseLine({ className = '' }: { className?: string }) {
    return (
        <svg viewBox="0 0 1200 72" className={`text-course h-16 w-full rtl:-scale-x-100 ${className}`} aria-hidden>
            <path className="course-path" pathLength="1" strokeWidth="2.5" d="M8 50 C 160 50, 220 18, 400 18 S 680 58, 920 34 1192 22 1192 22" />
        </svg>
    );
}

export function Blocks({ blocks }: { blocks: Block[] }) {
    return (
        <div className="max-w-[68ch] space-y-6 text-lg leading-8">
            {blocks.map((block, index) => {
                if (block.type === 'heading') {
                    return block.level === 3
                        ? <h3 key={index} className="display text-2xl">{block.text}</h3>
                        : <h2 key={index} className="display text-4xl">{block.text}</h2>;
                }

                if (block.type === 'list') {
                    return (
                        <ul key={index} className="space-y-2">
                            {(block.items ?? []).map((item) => (
                                <li key={item} className="grid grid-cols-[0.6rem_1fr] gap-3">
                                    <span className="mark mt-3 h-1.5 w-1.5" aria-hidden />
                                    <span>{item}</span>
                                </li>
                            ))}
                        </ul>
                    );
                }

                if (block.type === 'quote') {
                    return <blockquote key={index} className="display text-3xl leading-snug">{block.text}</blockquote>;
                }

                return <p key={index}>{block.text}</p>;
            })}
        </div>
    );
}

export function PageIntro({ title, excerpt }: { title: string; excerpt: string }) {
    return (
        <header className="stage border-b border-[color:var(--line)]">
            <div className="stage-grid" aria-hidden />
            <div className="relative mx-auto max-w-[90rem] px-4 pb-14 pt-[calc(var(--bar-clearance)+1.35rem)] sm:px-6 sm:pb-16 sm:pt-[calc(var(--bar-clearance)+1.75rem)]">
                <h1 className="display max-w-5xl text-[clamp(2.6rem,6vw,5.5rem)] leading-[1.05]">{title}</h1>
                {excerpt ? <p className="quiet mt-5 max-w-[68ch] text-lg leading-8">{excerpt}</p> : null}
            </div>
        </header>
    );
}

function SignalStage() {
    const host = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const element = host.current;
        if (!element) {
            return;
        }

        let release = () => {};
        let cancelled = false;

        void import('@/components/site/codeStage').then(({ mountCodeStage }) => {
            if (cancelled) {
                return;
            }

            release = mountCodeStage(element);
        });

        return () => {
            cancelled = true;
            release();
        };
    }, []);

    return <div ref={host} className="signal-stage" aria-hidden />;
}

export function HomeSections({
    sections,
    services,
    projects,
    technologies,
    faqs,
}: {
    sections: HomeSection[];
    services: ServiceCard[];
    projects: ProjectCard[];
    technologies: TechnologyCard[];
    faqs: FaqCard[];
}) {
    return (
        <>
            {sections.map((section) => {
                const body = renderSection(section, services, projects, technologies, faqs);

                return (
                    <section key={`${section.type}-${section.id}`} className={section.theme === 'dark' ? 'theme-dark' : 'theme-light'}>
                        {body}
                    </section>
                );
            })}
        </>
    );
}

function renderSection(section: HomeSection, services: ServiceCard[], projects: ProjectCard[], technologies: TechnologyCard[], faqs: FaqCard[]) {
    switch (section.type) {
        case 'hero':
            return <Hero section={section} />;
        case 'about':
        case 'engineering':
        case 'contact_cta':
            return <Statement section={section} />;
        case 'services':
            return <Services section={section} services={services} />;
        case 'selected_work':
            return <Work section={section} projects={projects} />;
        case 'process':
            return <Process section={section} />;
        case 'ai_innovation':
            return <Ai section={section} />;
        case 'technologies':
            return <Tech section={section} technologies={technologies} />;
        case 'faq':
            return <Faq section={section} faqs={faqs} />;
        default:
            return null;
    }
}

const plates = ['/brand/racode-plate-a.jpg', '/brand/racode-plate-b.jpg', '/brand/racode-plate-c.jpg'];

function splitHeadline(heading: string) {
    const match = heading.match(/^(.+?[.!?؟])\s+([\s\S]+)$/);

    return match ? { lead: match[1], rest: match[2] } : { lead: heading, rest: '' };
}

function Hero({ section }: { section: HomeSection }) {
    const { locale } = useI18n();
    const { lead, rest } = splitHeadline(section.content.heading);

    return (
        <div className="stage hero-night flex min-h-svh">
            <div className="stage-grid" aria-hidden />
            <div className="hero-grid relative mx-auto grid w-full max-w-[90rem] items-center gap-10 self-center px-4 sm:px-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:gap-12">
                <div className="min-w-0">
                    <h1 className="display max-w-[14ch] text-[clamp(2.8rem,6.2vw,6rem)] leading-[0.98]">
                        <span className="block">{lead}</span>
                        {rest ? <span className="hero-rest mt-2 block">{rest}</span> : null}
                    </h1>
                    <p className="quiet mt-7 max-w-[46ch] text-base leading-8 sm:text-lg">{section.content.description}</p>
                    <div className="mt-8 flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center">
                        {section.content.primary_button.label ? (
                            <TextButton href={localPath(locale, section.content.primary_button.url)} className="w-full sm:w-auto">
                                {section.content.primary_button.label}
                            </TextButton>
                        ) : null}
                        {section.content.secondary_button.label ? (
                            <Link href={localPath(locale, section.content.secondary_button.url)} className="pill-ghost w-full sm:w-auto">
                                {section.content.secondary_button.label}
                            </Link>
                        ) : null}
                    </div>
                </div>
                <SignalStage />
            </div>
        </div>
    );
}

function Statement({ section }: { section: HomeSection }) {
    const { locale } = useI18n();
    const plate = section.type === 'engineering' ? plates[0] : section.type === 'about' ? plates[1] : plates[2];
    const tone = section.theme === 'dark' || section.type === 'contact_cta' ? 'dark' : 'light';

    return (
        <div className={section.type === 'contact_cta' ? 'band-signal' : ''}>
            <div className="mx-auto grid max-w-[90rem] items-center gap-10 px-4 py-20 sm:px-6 lg:grid-cols-[1.15fr_0.85fr] lg:py-28">
                <div>
                    <h2 className="display max-w-4xl text-[clamp(2.2rem,5vw,4.75rem)] leading-[1.12]">{section.content.heading}</h2>
                    <p className="quiet mt-6 max-w-[62ch] text-lg leading-8">{section.content.description}</p>
                    {section.content.primary_button.label ? (
                        <div className="mt-8">
                            <TextButton href={localPath(locale, section.content.primary_button.url)} tone={tone}>
                                {section.content.primary_button.label}
                            </TextButton>
                        </div>
                    ) : null}
                </div>
                <div className="plate overflow-hidden rounded-[1.75rem]">
                    <img src={plate} alt="" className="aspect-[4/3] w-full object-cover" />
                </div>
            </div>
        </div>
    );
}

function Services({ section, services }: { section: HomeSection; services: ServiceCard[] }) {
    const { locale } = useI18n();

    return (
        <div id="services" className="mx-auto max-w-[90rem] px-4 py-20 sm:px-6">
            <div className="flex flex-col items-start justify-between gap-6 lg:flex-row lg:items-end">
                <h2 className="display max-w-4xl text-[clamp(2.2rem,5vw,4.75rem)] leading-[1.12]">{section.content.heading}</h2>
                {section.content.primary_button.label ? (
                    <TextButton href={localPath(locale, section.content.primary_button.url)} tone={section.theme === 'dark' ? 'dark' : 'light'}>
                        {section.content.primary_button.label}
                    </TextButton>
                ) : null}
            </div>
            {section.content.description ? <p className="quiet mt-6 max-w-[68ch] text-lg leading-8">{section.content.description}</p> : null}
            <div className="mt-12 border-t border-current/15">
                {services.map((service) => (
                    <Link key={service.slug} href={localPath(locale, `/services/${service.slug}`)} className="studio-row group grid gap-3 border-b border-[color:var(--line)] px-2 py-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                        <span className="display text-3xl sm:text-5xl">{service.name}</span>
                        <span className="quiet max-w-[48ch] leading-7">{service.summary}</span>
                        <span className="wake" />
                    </Link>
                ))}
            </div>
        </div>
    );
}

function Work({ section, projects }: { section: HomeSection; projects: ProjectCard[] }) {
    const { locale, t } = useI18n();

    if (projects.length === 0) {
        return null;
    }

    return (
        <div className="mx-auto max-w-[90rem] px-4 py-20 sm:px-6">
            <h2 className="display max-w-4xl text-[clamp(2.2rem,5vw,4.75rem)] leading-[1.12]">{section.content.heading}</h2>
            <p className="quiet mt-6 max-w-[68ch] text-lg leading-8">{section.content.description}</p>
            <div className="mt-14 space-y-16">
                {projects.map((project, index) => (
                    <Link key={project.slug} href={localPath(locale, `/work/${project.slug}`)} className="group relative block overflow-hidden rounded-[1.75rem]">
                        <span className="plate block">
                            <img src={plates[index % plates.length]} alt="" className="aspect-[16/9] w-full object-cover sm:aspect-[2/1]" />
                        </span>
                        <span className="frame-title absolute inset-x-0 bottom-0 p-6 sm:p-8">
                            <h3 className="display text-4xl leading-tight sm:text-5xl">{project.title}</h3>
                            <p className="mt-3 max-w-[46ch] text-sm leading-7">
                                {project.is_demonstration ? `${t.demonstration}. ` : ''}
                                {project.summary}
                            </p>
                        </span>
                    </Link>
                ))}
            </div>
            {section.content.primary_button.label ? (
                <div className="mt-12">
                    <TextButton href={localPath(locale, section.content.primary_button.url)} tone={section.theme === 'dark' ? 'dark' : 'light'}>
                        {section.content.primary_button.label}
                    </TextButton>
                </div>
            ) : null}
        </div>
    );
}

function Process({ section }: { section: HomeSection }) {
    const items = section.content.items.filter((item) => item.title);

    return (
        <div className="mx-auto max-w-[90rem] px-4 py-20 sm:px-6">
            <h2 className="display max-w-4xl text-[clamp(2.2rem,5vw,4.75rem)] leading-[1.12]">{section.content.heading}</h2>
            <p className="quiet mt-6 max-w-[68ch] text-lg leading-8">{section.content.description}</p>
            <ol className="relative mt-14 grid gap-10 lg:grid-cols-5">
                <span className="absolute inset-x-0 top-1 hidden h-px bg-current/20 lg:block" aria-hidden />
                {items.map((item) => (
                    <li key={item.title} className="relative">
                        <span className="mark relative z-10 grid h-3 w-3 place-items-center" aria-hidden />
                        <h3 className="display mt-5 text-2xl leading-snug">{item.title}</h3>
                        <p className="quiet mt-3 text-sm leading-6">{item.body}</p>
                    </li>
                ))}
            </ol>
        </div>
    );
}

function Ai({ section }: { section: HomeSection }) {
    const items = section.content.items.filter((item) => item.title);
    const [step, setStep] = useState(0);
    const current = items[step];
    const span = Math.max(items.length - 1, 1);

    return (
        <div className="mx-auto max-w-[90rem] px-4 py-20 sm:px-6">
            <h2 className="display max-w-4xl text-[clamp(2.2rem,5vw,4.75rem)] leading-[1.12]">{section.content.heading}</h2>
            <p className="quiet mt-6 max-w-[68ch] text-lg leading-8">{section.content.description}</p>
            <div className="relative mt-12">
                <div className="h-px bg-current/20" aria-hidden />
                <span
                    className="mark absolute top-0 h-2.5 w-2.5 -translate-y-1/2 transition-[inset-inline-start] duration-300"
                    style={{ insetInlineStart: `${(step / span) * 100}%` }}
                    aria-hidden
                />
            </div>
            <div className="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                {items.map((item, index) => (
                    <button key={item.title} type="button" className={`min-h-12 border px-3 text-start text-sm ${step === index ? 'border-[color:var(--mark)] text-course' : 'border-current/15'}`} aria-pressed={step === index} onClick={() => setStep(index)}>
                        {item.title}
                    </button>
                ))}
            </div>
            {current ? (
                <div className="mt-8 max-w-[62ch]">
                    <h3 className="display text-3xl">{current.title}</h3>
                    <p className="quiet mt-3 leading-8">{current.body}</p>
                </div>
            ) : null}
        </div>
    );
}

function Tech({ section, technologies }: { section: HomeSection; technologies: TechnologyCard[] }) {
    const { locale, t } = useI18n();
    const [paused, setPaused] = useState(false);
    const names = technologies.map((item) => item.name);

    return (
        <div className="py-16">
            <div className="mx-auto flex max-w-[90rem] flex-wrap items-end justify-between gap-6 px-4 sm:px-6">
                <h2 className="display text-[clamp(2.2rem,5vw,4.75rem)] leading-[1.12]">{section.content.heading}</h2>
                <div className="flex items-center gap-4">
                    <button type="button" className="inline-flex min-h-11 items-center border border-current/20 px-3 text-sm" aria-pressed={paused} onClick={() => setPaused((value) => !value)}>
                        {paused ? t.play : t.pause}
                    </button>
                    {section.content.primary_button.label ? (
                        <TextButton href={localPath(locale, section.content.primary_button.url)} tone={section.theme === 'dark' ? 'dark' : 'light'}>
                            {section.content.primary_button.label}
                        </TextButton>
                    ) : null}
                </div>
            </div>
            {section.content.description ? <p className="quiet mx-auto mt-4 max-w-[90rem] px-4 leading-8 sm:px-6">{section.content.description}</p> : null}
            <div className="mt-10 overflow-hidden" dir="ltr">
                <div className={`marquee-track flex w-max gap-12 pe-12 ${paused ? 'is-paused' : ''}`}>
                    {[...names, ...names].map((name, index) => (
                        <span key={`${name}-${index}`} className="display text-5xl sm:text-7xl">{name}</span>
                    ))}
                </div>
            </div>
        </div>
    );
}

function Faq({ section, faqs }: { section: HomeSection; faqs: FaqCard[] }) {
    const [open, setOpen] = useState<number | null>(0);

    return (
        <div className="mx-auto max-w-4xl px-4 py-20 sm:px-6">
            <h2 className="display text-[clamp(2.2rem,5vw,4.75rem)] leading-[1.12]">{section.content.heading}</h2>
            <p className="quiet mt-6 max-w-[68ch] leading-8">{section.content.description}</p>
            <div className="mt-10 border-t border-current/15">
                {faqs.map((faq, index) => (
                    <div key={faq.question} className="border-b border-current/15">
                        <button type="button" className="flex min-h-16 w-full items-center justify-between gap-4 py-4 text-start text-lg" aria-expanded={open === index} onClick={() => setOpen(open === index ? null : index)}>
                            <span>{faq.question}</span>
                            <span className="text-course text-2xl" aria-hidden>{open === index ? '–' : '+'}</span>
                        </button>
                        {open === index ? <p className="quiet pb-5 leading-8">{faq.answer}</p> : null}
                    </div>
                ))}
            </div>
        </div>
    );
}
