import { Link } from '@inertiajs/react';
import { AdminLayout, Empty } from '@/layouts/AdminLayout';
import { useI18n } from '@/lib/useI18n';

type Stat = { pages: number; services: number; projects: number; articles: number; new_inquiries: number };
type Activity = { id: number; action: string; description: string; user: string | null; created_at: string | null };

export default function Dashboard({ stats, activity }: { stats: Stat; activity: Activity[] }) {
    const { t } = useI18n();
    const rows = [
        [t.admin.pages, stats.pages, '/admin/pages'],
        [t.admin.services, stats.services, '/admin/services'],
        [t.admin.projects, stats.projects, '/admin/projects'],
        [t.admin.articles, stats.articles, '/admin/articles'],
        [t.admin.newCount, stats.new_inquiries, '/admin/inquiries'],
    ] as const;

    return (
        <AdminLayout title={t.admin.dashboard}>
            <div className="grid gap-px bg-midnight/15 sm:grid-cols-2 xl:grid-cols-5">
                {rows.map(([label, value, href]) => (
                    <Link key={label} href={href} className="bg-paper px-5 py-6 transition-colors duration-200 hover:bg-white">
                        <p className="display text-5xl tabular-nums">{value}</p>
                        <p className="quiet mt-3 text-sm">{label}</p>
                    </Link>
                ))}
            </div>
            <h2 className="display mb-4 mt-12 text-3xl">{t.admin.activity}</h2>
            {activity.length === 0 ? <Empty text={t.admin.empty} /> : (
                <ul className="border-t border-midnight/15">
                    {activity.map((item) => (
                        <li key={item.id} className="grid gap-1 border-b border-midnight/15 py-4 sm:grid-cols-[11rem_1fr] sm:items-baseline">
                            <span className="quiet text-sm tabular-nums">{item.created_at}</span>
                            <span>{item.description}{item.user ? ` — ${item.user}` : ''}</span>
                        </li>
                    ))}
                </ul>
            )}
        </AdminLayout>
    );
}
