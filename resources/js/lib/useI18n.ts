import { usePage } from '@inertiajs/react';
import { copy, type Copy } from '@/i18n';
import type { SharedProps } from '@/types';

export function useI18n(): { locale: SharedProps['locale']; dir: SharedProps['dir']; t: Copy } {
    const { locale, dir } = usePage<SharedProps>().props;

    return { locale, dir, t: copy(locale) };
}

export function useCan(permission: string): boolean {
    const permissions = usePage<SharedProps>().props.auth.user?.permissions ?? [];

    return permissions.includes(permission);
}
