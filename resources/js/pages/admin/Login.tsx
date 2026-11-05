import { useForm } from '@inertiajs/react';
import { FormEvent, useEffect } from 'react';
import { ThemeSwitch } from '@/components/site/Chrome';
import { useI18n } from '@/lib/useI18n';

export default function Login() {
    const { t, locale, dir } = useI18n();
    const form = useForm({ email: '', password: '', remember: false });

    useEffect(() => {
        document.documentElement.lang = locale;
        document.documentElement.dir = dir;
    }, [locale, dir]);

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post('/admin/login');
    }

    return (
        <main className="studio stage flex min-h-screen flex-col px-4 py-6 sm:px-6">
            <div className="flex w-full justify-end">
                <ThemeSwitch labeled />
            </div>
            <form onSubmit={submit} className="mx-auto my-auto w-full max-w-xl py-10">
                <h1 className="display text-6xl">RACODE</h1>
                <p className="mt-3 text-lg">{t.admin.signIn}</p>
                <div className="mt-8 space-y-5">
                    <label className="grid gap-2 text-sm">
                        {t.admin.email}
                        <input type="email" className="field" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} autoComplete="username" />
                        {form.errors.email ? <span className="text-rose">{form.errors.email}</span> : null}
                    </label>
                    <label className="grid gap-2 text-sm">
                        {t.admin.password}
                        <input type="password" className="field" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} autoComplete="current-password" />
                        {form.errors.password ? <span className="text-rose">{form.errors.password}</span> : null}
                    </label>
                    <label className="flex min-h-11 items-center gap-3 text-sm">
                        <input type="checkbox" className="h-5 w-5" checked={form.data.remember} onChange={(event) => form.setData('remember', event.target.checked)} />
                        {t.admin.remember}
                    </label>
                    <button disabled={form.processing} className="slip w-full">{t.admin.login}</button>
                </div>
            </form>
        </main>
    );
}
