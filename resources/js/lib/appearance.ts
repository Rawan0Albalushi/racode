export type Appearance = 'light' | 'dark';

const STORAGE_KEY = 'racode-appearance';

export function readAppearance(): Appearance {
    if (typeof document === 'undefined') {
        return 'dark';
    }

    return document.documentElement.getAttribute('data-appearance') === 'light' ? 'light' : 'dark';
}

export function applyAppearance(mode: Appearance, persist = false): void {
    document.documentElement.setAttribute('data-appearance', mode);
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', mode === 'light' ? '#f3efe6' : '#07090f');

    if (persist) {
        localStorage.setItem(STORAGE_KEY, mode);
    }
}

export function appearanceFromSystem(): Appearance {
    if (typeof window === 'undefined') {
        return 'dark';
    }

    const stored = localStorage.getItem(STORAGE_KEY);

    if (stored === 'light' || stored === 'dark') {
        return stored;
    }

    return window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
}
