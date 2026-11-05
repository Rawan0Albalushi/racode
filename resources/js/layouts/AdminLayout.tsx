import { Link, router, usePage } from '@inertiajs/react';
import { FormEvent, useEffect, useState, type ReactNode } from 'react';
import { useCan, useI18n } from '@/lib/useI18n';
import type { SharedProps } from '@/types';

export function AdminLayout({ title, children }: { title: string; children: ReactNode }) {
    const { t, locale, dir } = useI18n();
    const page = usePage<SharedProps>();
    const { auth, flash, unreadInquiries } = page.props;
    const [open, setOpen] = useState(false);
    const path = page.url.split('?')[0];
    const items = [
        ['/admin', 'dashboard.view', t.admin.dashboard],
        ['/admin/pages', 'pages.view', t.admin.pages],
        ['/admin/services', 'services.view', t.admin.services],
        ['/admin/projects', 'projects.view', t.admin.projects],
        ['/admin/articles', 'articles.view', t.admin.articles],
        ['/admin/faqs', 'faqs.view', t.admin.faqs],
        ['/admin/technologies', 'technologies.view', t.admin.technologies],
        ['/admin/media', 'media.view', t.admin.media],
        ['/admin/inquiries', 'inquiries.view', t.admin.inquiries],
        ['/admin/navigation', 'pages.view', t.admin.navigation],
        ['/admin/settings', 'settings.update', t.admin.settings],
        ['/admin/users', 'users.view', t.admin.users],
        ['/admin/activity', 'activity.view', t.admin.activity],
    ] as const;

    useEffect(() => {
        document.documentElement.lang = locale;
        document.documentElement.dir = dir;
    }, [locale, dir]);

    return (
        <div className="min-h-screen bg-ivory text-midnight lg:grid lg:grid-cols-[16.5rem_1fr]">
            <aside className={`${open ? 'fixed inset-0 z-40 overflow-y-auto' : 'hidden'} bg-midnight text-ivory lg:static lg:block`}>
                <div className="flex items-center justify-between border-b border-white/10 px-5 py-5">
                    <Link href="/admin" className="display text-xl tracking-normal text-ivory">RACODE</Link>
                    <button type="button" className="min-h-11 px-2 text-sm lg:hidden" onClick={() => setOpen(false)}>{t.close}</button>
                </div>
                <nav className="space-y-0.5 px-3 py-4">
                    {items.filter((item) => auth.user?.permissions.includes(item[1])).map((item) => {
                        const active = item[0] === '/admin' ? path === '/admin' : path.startsWith(item[0]);

                        return (
                            <Link key={item[0]} href={item[0]} aria-current={active ? 'page' : undefined} className={`flex min-h-11 items-center justify-between px-3 text-sm ${active ? 'bg-white/10 text-ivory' : 'text-blush hover:bg-white/5 hover:text-ivory'}`} onClick={() => setOpen(false)}>
                                <span>{item[2]}</span>
                                {item[0] === '/admin/inquiries' && unreadInquiries > 0 ? <span className="text-ivory">{unreadInquiries}</span> : null}
                            </Link>
                        );
                    })}
                </nav>
            </aside>
            <div className="min-w-0">
                <header className="sticky top-0 z-20 flex items-center justify-between gap-3 border-b border-midnight/15 bg-ivory px-4 py-3 sm:px-6">
                    <div className="flex items-center gap-3">
                        <button type="button" className="inline-flex min-h-11 items-center border border-midnight/20 px-3 lg:hidden" onClick={() => setOpen(true)}>{t.menu}</button>
                        <h1 className="display text-2xl">{title}</h1>
                    </div>
                    <div className="flex items-center gap-3 text-sm">
                        <button type="button" className="inline-flex min-h-11 items-center border border-midnight/20 px-3" onClick={() => router.post('/admin/locale', { locale: locale === 'ar' ? 'en' : 'ar' })}>
                            {locale === 'ar' ? 'EN' : 'عربي'}
                        </button>
                        <span className="hidden sm:inline">{auth.user?.name}</span>
                        <Link href="/admin/logout" method="post" as="button" className="inline-flex min-h-11 items-center">{t.admin.logout}</Link>
                    </div>
                </header>
                {flash.success ? <p className="bg-midnight px-6 py-3 text-sm text-ivory" role="status">{flash.success}</p> : null}
                {flash.error ? <p className="bg-rose px-6 py-3 text-sm text-white" role="alert">{flash.error}</p> : null}
                <div className="chart-lines px-4 py-8 sm:px-6">{children}</div>
            </div>
        </div>
    );
}

export function usePermission(permission: string): boolean {
    return useCan(permission);
}

export function SearchForm({ value }: { value?: string }) {
    const { t } = useI18n();

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        const q = String(data.get('q') ?? '');
        router.get(window.location.pathname, { q }, { preserveState: true, replace: true });
    }

    return (
        <form onSubmit={submit} className="flex min-w-0 flex-1 gap-2">
            <input name="q" defaultValue={value} aria-label={t.admin.search} className="field" />
            <button className="slip">{t.admin.search}</button>
        </form>
    );
}

export function Empty({ text }: { text: string }) {
    return <p className="border border-dashed border-midnight/25 px-4 py-12 text-center text-sm">{text}</p>;
}

export function DeleteButton({ href }: { href: string }) {
    const { t } = useI18n();
    const [armed, setArmed] = useState(false);

    if (!armed) {
        return <button type="button" className="inline-flex min-h-11 items-center px-3 text-rose" onClick={() => setArmed(true)}>{t.admin.delete}</button>;
    }

    return (
        <span className="inline-flex items-center gap-2">
            <button type="button" className="inline-flex min-h-11 items-center bg-rose px-3 text-white" onClick={() => router.delete(href)}>{t.admin.confirmDelete}</button>
            <button type="button" className="inline-flex min-h-11 items-center px-3" onClick={() => setArmed(false)}>{t.close}</button>
        </span>
    );
}
