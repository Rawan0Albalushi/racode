import { useEffect, useRef } from 'react';

export function CodeMark({ className = '' }: { className?: string }) {
    const root = useRef<SVGSVGElement>(null);

    useEffect(() => {
        const svg = root.current;
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (!svg || reduce) {
            return;
        }

        const paths = svg.querySelectorAll<SVGPathElement>('[data-draw]');
        let revert: (() => void) | undefined;
        let cancelled = false;

        void import('gsap').then(({ default: gsap }) => {
            if (cancelled) {
                return;
            }

            paths.forEach((path) => {
                const length = path.getTotalLength();
                path.style.strokeDasharray = `${length}`;
                path.style.strokeDashoffset = `${length}`;
            });

            const tween = gsap.to(paths, {
                strokeDashoffset: 0,
                duration: 1.6,
                stagger: 0.12,
                ease: 'power2.out',
            });

            revert = () => tween.kill();
        });

        return () => {
            cancelled = true;
            revert?.();
        };
    }, []);

    return (
        <svg ref={root} viewBox="0 0 420 420" className={className} role="img" aria-label="</>">
            <circle cx="210" cy="210" r="188" className="sigil-path" strokeWidth="1" opacity="0.45" data-draw />
            <path d="M70 118 L168 210 L70 302" className="sigil-path" data-draw />
            <path d="M198 328 L236 92" className="sigil-path" data-draw />
            <path d="M350 118 L252 210 L350 302" className="sigil-path" data-draw />
            <path d="M40 40 H92 M40 40 V92 M380 40 H328 M380 40 V92 M40 380 H92 M40 380 V328 M380 380 H328 M380 380 V328" className="sigil-path" strokeWidth="2" data-draw />
        </svg>
    );
}
