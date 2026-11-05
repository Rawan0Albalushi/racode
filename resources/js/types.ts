export type Locale = 'ar' | 'en';

export type NavLink = {
    id: number;
    label: string;
    path: string;
    external: boolean;
};

export type Seo = {
    title: string;
    description: string;
    canonical: string;
    alternates: { ar: string; en: string; 'x-default': string };
    image: string;
    locale: string;
    json_ld: Record<string, unknown> | null;
};

export type SiteBrand = {
    name: string;
    name_ar: string;
    philosophy: string;
    tagline: string;
    logo_url: string;
    favicon_url: string;
    colors: Record<string, string>;
    company: { legal_name: string; country: string };
    contact: { email: string; phone: string; address: string };
    social: Record<string, string>;
    footer: string;
    seo: { title: string; description: string };
    analytics: { ga4: string | null; plausible: string | null };
    maintenance: boolean;
};

export type AuthUser = {
    id: number;
    name: string;
    email: string;
    locale: string;
    roles: string[];
    permissions: string[];
};

export type SharedProps = {
    locale: Locale;
    dir: 'rtl' | 'ltr';
    context: 'admin' | 'public';
    auth: { user: AuthUser | null };
    flash: { success: string | null; error: string | null };
    nav: { header: NavLink[]; footer: NavLink[] };
    site: SiteBrand;
    seo: Seo;
    unreadInquiries: number;
};

export type Block = {
    type: 'heading' | 'paragraph' | 'list' | 'quote';
    level?: number;
    text: string;
    items?: string[];
};

export type MediaCard = {
    url: string;
    alt: string;
    width: number | null;
    height: number | null;
} | null;

export type SectionContent = {
    heading: string;
    description: string;
    primary_button: { label: string; url: string | null };
    secondary_button: { label: string; url: string | null };
    image: MediaCard;
    items: { title: string; body: string }[];
};

export type HomeSection = {
    id: number | null;
    type: string;
    layout: string;
    theme: 'light' | 'dark';
    content: SectionContent;
};

export type Paginator<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    total: number;
};

export function localPath(locale: string, path?: string | null): string {
    if (!path) {
        return `/${locale}`;
    }

    if (path.startsWith('https://')) {
        return path;
    }

    if (!path.startsWith('/') || path.startsWith('//')) {
        return `/${locale}`;
    }

    return path === '/' ? `/${locale}` : `/${locale}${path}`;
}
