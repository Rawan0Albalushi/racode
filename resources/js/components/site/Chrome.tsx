import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useState, type ReactNode } from 'react';
import { CodeMark } from '@/components/site/CodeMark';
import { applyAppearance, appearanceFromSystem, readAppearance, type Appearance } from '@/lib/appearance';
import { useI18n } from '@/lib/useI18n';
import { localPath, type Seo, type SharedProps } from '@/types';

export function DocumentMeta({ seo }: { seo: Seo }) {
    const json = seo.json_ld ? JSON.stringify(seo.json_ld) : null;

    return (
        <Head title={seo.title}>
            <meta name="description" content={seo.description} head-key="description" />
            <link rel="canonical" href={seo.canonical} head-key="canonical" />
            <link rel="alternate" hrefLang="ar" href={seo.alternates.ar} head-key="alt-ar" />
            <link rel="alternate" hrefLang="en" href={seo.alternates.en} head-key="alt-en" />
            <link rel="alternate" hrefLang="x-default" href={seo.alternates['x-default']} head-key="alt-default" />
            <meta property="og:title" content={seo.title} head-key="og-title" />
            <meta property="og:description" content={seo.description} head-key="og-description" />
            <meta property="og:url" content={seo.canonical} head-key="og-url" />
            <meta property="og:image" content={seo.image} head-key="og-image" />
            {json ? <script type="application/ld+json" head-key="jsonld" dangerouslySetInnerHTML={{ __html: json }} /> : null}
        </Head>
    );
}

function Direction() {
    const { locale, dir } = usePage<SharedProps>().props;

    useEffect(() => {
        document.documentElement.lang = locale;
        document.documentElement.dir = dir;
    }, [locale, dir]);

    return null;
}

function ArrowIcon() {
    return (
        <svg viewBox="0 0 24 24" className="h-4 w-4 rtl:-scale-x-100" aria-hidden>
            <path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" strokeWidth="1.6" />
        </svg>
    );
}

function SunIcon() {
    return (
        <svg viewBox="0 0 24 24" className="h-4 w-4" aria-hidden>
            <circle cx="12" cy="12" r="3.25" fill="none" stroke="currentColor" strokeWidth="1.6" />
            <path d="M12 3.5v2.2M12 18.3v2.2M3.5 12h2.2M18.3 12h2.2M6 6l1.6 1.6M16.4 16.4 18 18M18 6l-1.6 1.6M7.6 16.4 6 18" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
        </svg>
    );
}

function MoonIcon() {
    return (
        <svg viewBox="0 0 24 24" className="h-4 w-4" aria-hidden>
            <path d="M15.5 3.8A7.8 7.8 0 1 0 20.2 15 6.4 6.4 0 0 1 15.5 3.8Z" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinejoin="round" />
        </svg>
    );
}

function useAppearanceMode() {
    const [mode, setMode] = useState<Appearance>('dark');

    useEffect(() => {
        const sync = () => setMode(readAppearance());
        applyAppearance(appearanceFromSystem());
        sync();

        const onChange = () => sync();
        const onStorage = (event: StorageEvent) => {
            if (event.key !== 'racode-appearance') {
                return;
            }

            const next = event.newValue === 'light' || event.newValue === 'dark' ? event.newValue : appearanceFromSystem();
            applyAppearance(next);
            setMode(next);
        };
        const onSystem = () => {
            if (localStorage.getItem('racode-appearance')) {
                return;
            }

            applyAppearance(window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
            sync();
        };
        const media = window.matchMedia('(prefers-color-scheme: light)');

        window.addEventListener('racode-appearance', onChange);
        window.addEventListener('storage', onStorage);
        media.addEventListener('change', onSystem);

        return () => {
            window.removeEventListener('racode-appearance', onChange);
            window.removeEventListener('storage', onStorage);
            media.removeEventListener('change', onSystem);
        };
    }, []);

    function choose(next: Appearance) {
        applyAppearance(next, true);
        window.dispatchEvent(new Event('racode-appearance'));
    }

    return { mode, choose };
}

export function ThemeSwitch({ labeled = false }: { labeled?: boolean }) {
    const { t } = useI18n();
    const { mode, choose } = useAppearanceMode();
    const next = mode === 'dark' ? 'light' : 'dark';

    return (
        <button type="button" className="theme-switch" aria-label={`${mode === 'dark' ? t.darkMode : t.lightMode}. ${next === 'light' ? t.useLight : t.useDark}`} onClick={() => choose(next)}>
            {mode === 'dark' ? <MoonIcon /> : <SunIcon />}
            <span className={labeled ? undefined : 'hidden xl:inline'}>{mode === 'dark' ? t.darkMode : t.lightMode}</span>
        </button>
    );
}

export function SiteHeader() {
    const { locale, t } = useI18n();
    const page = usePage<SharedProps>();
    const { nav, site, seo, auth } = page.props;
    const [open, setOpen] = useState(false);
    const other = locale === 'ar' ? 'en' : 'ar';
    const path = page.url.split('?')[0];

    return (
        <>
            <a href="#content" className="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-[80] focus:bg-[#f08a2a] focus:px-3 focus:py-2 focus:text-[#1a1208]">
                {t.skip}
            </a>
            <header className="site-bar">
                <div className="site-bar-shell">
                    <Link href={localPath(locale, '/')} className="flex min-w-0 items-center gap-2.5" onClick={() => setOpen(false)}>
                        <img src={site.logo_url} alt="" className="h-8 w-8 shrink-0 sm:h-9 sm:w-9" width={36} height={36} />
                        <span className="min-w-0 leading-none">
                            <span className="display block text-base tracking-normal sm:text-lg">RACODE</span>
                            <span className="quiet mt-0.5 block truncate text-[0.7rem] sm:text-xs">راكود</span>
                        </span>
                    </Link>
                    <nav className="hidden min-w-0 items-center gap-x-5 whitespace-nowrap lg:flex xl:gap-x-7" aria-label="Primary">
                        {nav.header.map((item) => {
                            const href = item.external ? item.path : localPath(locale, item.path);
                            const current = !item.external && (path === href || (item.path !== '/' && path.startsWith(href)));

                            return (
                                <Link key={item.id} href={href} aria-current={current ? 'page' : undefined} className={`link-draw inline-flex items-center py-3 text-sm ${current ? 'text-course' : ''}`}>
                                    {item.label}
                                </Link>
                            );
                        })}
                    </nav>
                    <div className="flex shrink-0 items-center gap-1 sm:gap-1.5">
                        <ThemeSwitch />
                        <Link href={seo.alternates[other]} hrefLang={other} className="pill-ghost px-2.5 text-sm font-medium sm:px-3" aria-label={t.language}>
                            {other === 'ar' ? 'عربي' : 'EN'}
                        </Link>
                        <Link href={localPath(locale, '/contact')} className="pill hidden sm:inline-flex">
                            {t.start}
                        </Link>
                        {auth.user ? (
                            <Link href="/admin" className="hidden min-h-11 items-center px-2 text-sm xl:inline-flex">
                                {t.admin.dashboard}
                            </Link>
                        ) : null}
                        <button type="button" className="site-bar-menu lg:hidden" aria-expanded={open} aria-controls="mobile-nav" onClick={() => setOpen((value) => !value)}>
                            <span className="sr-only">{open ? t.close : t.menu}</span>
                            <span aria-hidden className="flex w-4 flex-col gap-1.5">
                                <span className="h-px bg-current" />
                                <span className="h-px bg-current" />
                                <span className="h-px w-2/3 bg-current" />
                            </span>
                        </button>
                    </div>
                </div>
            </header>
            {open ? (
                <nav id="mobile-nav" className="site-menu fixed inset-0 z-[70] flex flex-col px-5 pb-10 pt-6 lg:hidden">
                    <div className="mb-6 flex items-center justify-between gap-3">
                        <ThemeSwitch labeled />
                        <button type="button" className="pill-ghost" onClick={() => setOpen(false)}>
                            {t.close}
                        </button>
                    </div>
                    <ul>
                        {nav.header.map((item) => (
                            <li key={item.id} className="border-b border-[color:var(--line)]">
                                <Link href={item.external ? item.path : localPath(locale, item.path)} className="display flex min-h-16 items-center text-4xl" onClick={() => setOpen(false)}>
                                    {item.label}
                                </Link>
                            </li>
                        ))}
                        {auth.user ? (
                            <li className="border-b border-[color:var(--line)]">
                                <Link href="/admin" className="display flex min-h-16 items-center text-4xl" onClick={() => setOpen(false)}>{t.admin.dashboard}</Link>
                            </li>
                        ) : null}
                    </ul>
                    <Link href={localPath(locale, '/contact')} className="pill mt-8 w-fit" onClick={() => setOpen(false)}>
                        {t.start}
                    </Link>
                </nav>
            ) : null}
        </>
    );
}

export function SiteFooter() {
    const { locale, t } = useI18n();
    const { nav, site } = usePage<SharedProps>().props;

    return (
        <footer className="site-foot">
            <div className="mx-auto grid max-w-[90rem] gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[1.3fr_0.7fr_0.9fr]">
                <div>
                    <CodeMark className="text-course mb-6 h-14 w-14" />
                    <p className="display text-5xl">RACODE</p>
                    <p className="text-course mt-2 text-3xl">راكود</p>
                    <p className="quiet mt-6 max-w-sm text-sm leading-7">{site.footer || t.philosophy}</p>
                </div>
                <ul className="space-y-3 text-sm">
                    {nav.footer.map((item) => (
                        <li key={item.id}>
                            <Link href={item.external ? item.path : localPath(locale, item.path)} className="link-draw">
                                {item.label}
                            </Link>
                        </li>
                    ))}
                </ul>
                <div className="space-y-2 text-sm leading-7">
                    <p className="quiet">{site.company.country}</p>
                    {site.contact.email ? <p><a className="link-draw" href={`mailto:${site.contact.email}`}>{site.contact.email}</a></p> : null}
                    {site.contact.phone ? <p dir="ltr">{site.contact.phone}</p> : null}
                    <p className="quiet">{t.philosophy}</p>
                    <ul className="flex flex-wrap gap-4 pt-3">
                        {Object.entries(site.social).map(([name, url]) => (
                            <li key={name}><a href={url} className="link-draw" rel="noreferrer">{name}</a></li>
                        ))}
                    </ul>
                </div>
            </div>
        </footer>
    );
}

export function SiteLayout({ seo, previewing = false, children }: { seo: Seo; previewing?: boolean; children: ReactNode }) {
    const { locale, t } = useI18n();
    const { flash } = usePage<SharedProps>().props;

    return (
        <div className="studio min-h-screen">
            <Direction />
            <DocumentMeta seo={seo} />
            <SiteHeader />
            {previewing || flash.success || flash.error ? (
                <div className="site-banners">
                    {previewing ? (
                        <div className="notice px-4 py-3 text-center text-sm">
                            {t.preview}
                            {' '}
                            <Link href={localPath(locale, '/')} className="underline">{t.exitPreview}</Link>
                        </div>
                    ) : null}
                    {flash.success ? <p className="notice px-4 py-3 text-center text-sm" role="status">{flash.success}</p> : null}
                    {flash.error ? <p className="notice-alert px-4 py-3 text-center text-sm" role="alert">{flash.error}</p> : null}
                </div>
            ) : null}
            <main id="content">{children}</main>
            <SiteFooter />
        </div>
    );
}

export function TextButton({ href, children, tone = 'light', className = '' }: { href: string; children: ReactNode; tone?: 'light' | 'dark'; className?: string }) {
    return (
        <Link href={href} className={`${tone === 'dark' ? 'slip slip-light' : 'slip'} ${className}`}>
            {children}
            <ArrowIcon />
        </Link>
    );
}
