import * as THREE from 'three';
import { RoundedBoxGeometry } from 'three/addons/geometries/RoundedBoxGeometry.js';

const ORANGE = '#f08a2a';

type Tone = 'key' | 'plain' | 'string' | 'call';
type Role = 'plan' | 'design' | 'build' | 'develop' | 'deploy';

const CODE: readonly (readonly (readonly [string, Tone])[])[] = [
    [
        ['import', 'key'],
        [' { Racode } ', 'plain'],
        ['from', 'key'],
        [" '@/core'", 'string'],
    ],
    [
        ['const', 'key'],
        [' app = ', 'plain'],
        ['new', 'key'],
        [' Racode({', 'plain'],
    ],
    [
        ['  idea: ', 'plain'],
        ["'your vision'", 'string'],
        [',', 'plain'],
    ],
    [
        ['  stack: [', 'plain'],
        ["'Laravel'", 'string'],
        [', ', 'plain'],
        ["'React'", 'string'],
        [', ', 'plain'],
        ["'Flutter'", 'string'],
        ['],', 'plain'],
    ],
    [['})', 'plain']],
    [
        ['app', 'plain'],
        ['.build()', 'call'],
    ],
];

const GLYPHS = compile(CODE);
const STAMP = GLYPHS.map((glyph) => (glyph.ch === '\n' ? 0.3 : ' ,(){}[]'.includes(glyph.ch) ? 0.13 : glyph.ch === ' ' ? 0.02 : 0.038));
const STARTS: number[] = [];
let elapsed = 0;
STAMP.forEach((step) => {
    STARTS.push(elapsed);
    elapsed += step;
});
const TYPE_END = elapsed;
const HOLD = 1.55;
const CYCLE = TYPE_END + HOLD;

const CARDS: readonly { role: Role; title: string; note: string; x: number; y: number; z: number; ry: number; period: number }[] = [
    { role: 'plan', title: 'Plan', note: 'Strategy', x: 0, y: 2.2, z: 0.32, ry: 0, period: 5.6 },
    { role: 'design', title: 'Design', note: 'UI / UX', x: -2.46, y: 1.2, z: 0.3, ry: 0.03, period: 6.8 },
    { role: 'develop', title: 'Develop', note: 'Web · Mobile', x: 2.46, y: 1.2, z: 0.3, ry: -0.03, period: 5.4 },
    { role: 'build', title: 'Build', note: 'Products', x: -2.36, y: -0.52, z: 0.54, ry: 0.03, period: 6.2 },
    { role: 'deploy', title: 'Deploy', note: 'Cloud', x: 2.36, y: -0.52, z: 0.54, ry: -0.03, period: 7.1 },
];

export function mountCodeStage(element: HTMLElement): () => void {
    const lite = window.matchMedia('(max-width: 767px)').matches;
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const renderer = new THREE.WebGLRenderer({
        alpha: true,
        antialias: !lite,
        powerPreference: lite ? 'low-power' : 'high-performance',
    });

    if (!renderer.getContext()) {
        renderer.dispose();

        return () => {};
    }

    renderer.setClearColor(0x000000, 0);
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.12;
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, lite ? 1.25 : 2));

    const canvas = renderer.domElement;
    canvas.className = 'signal-canvas';
    element.appendChild(canvas);

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(30, 1, 0.1, 40);
    camera.position.set(0, 0.12, lite ? 13.4 : 11.8);

    const assembly = new THREE.Group();
    scene.add(assembly);
    const geometries: THREE.BufferGeometry[] = [];
    const materials: THREE.Material[] = [];
    const textures: THREE.Texture[] = [];
    const track = (geometry: THREE.BufferGeometry, material: THREE.Material, texture?: THREE.Texture) => {
        geometries.push(geometry);
        materials.push(material);
        if (texture) {
            textures.push(texture);
        }
    };

    scene.add(new THREE.AmbientLight(0xfff4ea, 0.55));
    const key = new THREE.DirectionalLight(0xfff7f0, 1.7);
    key.position.set(2.4, 4.6, 3.2);
    scene.add(key);
    const fill = new THREE.DirectionalLight(0x9eb6ff, 0.28);
    fill.position.set(-3.4, 1.2, 1.4);
    scene.add(fill);
    const glow = new THREE.PointLight(0xf08a2a, 4.5, 6.5, 2);
    glow.position.set(0.15, -1.2, 1.85);
    scene.add(glow);

    const display = new THREE.Group();
    display.position.set(-0.02, 0.34, -0.05);
    display.rotation.set(-0.03, 0, 0);
    assembly.add(display);

    const shellMaterial = new THREE.MeshStandardMaterial({
        color: 0x14110e,
        metalness: 0.62,
        roughness: 0.34,
    });
    const shell = new THREE.Mesh(new RoundedBoxGeometry(3.14, 1.98, 0.16, 4, 0.07), shellMaterial);
    track(shell.geometry, shellMaterial);
    shell.position.z = 0.02;
    display.add(shell);

    const screenState = makeSurface(1400, 860);
    textures.push(screenState.texture);
    const screenMaterial = new THREE.MeshBasicMaterial({
        map: screenState.texture,
        transparent: true,
        toneMapped: false,
    });
    const screen = new THREE.Mesh(new THREE.PlaneGeometry(3.0, 1.84), screenMaterial);
    track(screen.geometry, screenMaterial);
    screen.position.z = 0.112;
    display.add(screen);

    const pedestal = buildPedestal(track);
    display.add(pedestal);
    pedestal.position.set(0, -1.02, 0.05);
    const standYaw = new THREE.Quaternion().setFromEuler(new THREE.Euler(0, display.rotation.y, 0));
    pedestal.quaternion.copy(display.quaternion).invert().multiply(standYaw);

    const floaters: THREE.Group[] = [];
    const cardStates: Surface[] = [];
    CARDS.forEach((card, index) => {
        if (lite && index > 2) {
            return;
        }

        const state = makeSurface(1024, 1024, true);
        textures.push(state.texture);
        cardStates.push(state);
        const group = new THREE.Group();
        const edgeMaterial = new THREE.MeshStandardMaterial({
            color: 0x14110e,
            metalness: 0.4,
            roughness: 0.42,
        });
        const edge = new THREE.Mesh(new THREE.CylinderGeometry(0.68, 0.68, 0.05, 64), edgeMaterial);
        track(edge.geometry, edgeMaterial);
        edge.rotation.x = Math.PI / 2;
        group.add(edge);
        const rimMaterial = new THREE.MeshStandardMaterial({
            color: 0xf08a2a,
            emissive: 0xf08a2a,
            emissiveIntensity: 0.28,
            roughness: 0.35,
        });
        const rim = new THREE.Mesh(new THREE.TorusGeometry(0.68, 0.014, 12, 80), rimMaterial);
        track(rim.geometry, rimMaterial);
        rim.position.z = 0.012;
        group.add(rim);
        const faceMaterial = new THREE.MeshBasicMaterial({
            map: state.texture,
            toneMapped: false,
        });
        const face = new THREE.Mesh(new THREE.CircleGeometry(0.655, 64), faceMaterial);
        track(face.geometry, faceMaterial);
        face.position.z = 0.03;
        group.add(face);
        group.position.set(card.x * (lite ? 0.82 : 1), card.y, card.z);
        group.rotation.y = card.ry;
        group.userData.base = card.y;
        group.userData.phase = index * 1.15;
        group.userData.role = card.role;
        group.userData.period = card.period;
        group.userData.surface = state;
        assembly.add(group);
        floaters.push(group);
    });

    const wave = waveField(lite ? 42 : 78, lite ? 16 : 26);
    geometries.push(wave.geometry);
    materials.push(wave.material);
    textures.push(wave.texture);
    wave.points.position.set(0, -1.48, 0.35);
    assembly.add(wave.points);

    const stage = element.closest('.stage') ?? element;
    const pointer = { x: 0, y: 0 };
    let hovering = false;
    const onMove = (event: Event) => {
        if (!(event instanceof PointerEvent)) {
            return;
        }

        const rect = stage.getBoundingClientRect();
        if (rect.width === 0 || rect.height === 0) {
            return;
        }

        pointer.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
        pointer.y = ((event.clientY - rect.top) / rect.height) * 2 - 1;
        hovering = true;
    };
    const onLeave = () => {
        hovering = false;
    };

    if (!reduce) {
        stage.addEventListener('pointermove', onMove);
        stage.addEventListener('pointerleave', onLeave);
    }

    let frame = 0;
    let running = false;
    let lastDraw = 0;
    let poseX = 0;
    let poseY = 0;
    let screenKey = '';
    const cardKeys = new Map<Surface, string>();
    const clock = new THREE.Clock();

    const paint = (time: number) => {
        const typed = reduce ? { count: GLYPHS.length, cursor: false } : typedAt(time);
        const nextScreen = `${typed.count}:${typed.cursor ? 1 : 0}`;
        if (nextScreen !== screenKey) {
            screenKey = nextScreen;
            paintEditor(screenState.context, screenState.width, screenState.height, typed.count, typed.cursor);
            screenState.texture.needsUpdate = true;
        }

        floaters.forEach((group) => {
            const surface = group.userData.surface as Surface;
            const period = Number(group.userData.period);
            const progress = reduce ? 1 : (time % period) / period;
            const step = Math.round(progress * 28);
            const key = `${step}`;
            if (cardKeys.get(surface) !== key) {
                cardKeys.set(surface, key);
                paintPanel(
                    surface.context,
                    surface.width,
                    surface.height,
                    String(group.userData.role) as Role,
                    labelFor(group),
                    step / 28,
                );
                surface.texture.needsUpdate = true;
            }
        });
    };

    const draw = (time: number) => {
        const delta = Math.min(clock.getDelta(), 0.05);
        paint(time);
        const aimX = reduce ? 0 : hovering ? pointer.y * 0.045 : Math.sin(time * 0.22) * 0.012;
        const aimY = reduce ? 0 : hovering ? pointer.x * 0.07 : Math.sin(time * 0.13) * 0.02;
        poseX = THREE.MathUtils.damp(poseX, aimX, 2.2, delta);
        poseY = THREE.MathUtils.damp(poseY, aimY, 2.2, delta);
        assembly.rotation.set(poseX, poseY, 0);
        assembly.position.y = reduce ? 0 : Math.sin(time * 0.45) * 0.035;
        if (!reduce) {
            floaters.forEach((group) => {
                const phase = Number(group.userData.phase);
                group.position.y = Number(group.userData.base) + Math.sin(time * 0.8 + phase) * 0.035;
                group.rotation.z = Math.sin(time * 0.5 + phase) * 0.012;
            });
            wave.update(time);
            glow.intensity = 4.2 + Math.sin(time * 1.1) * 0.35;
        }
        camera.lookAt(0, 0.02, 0);
        renderer.render(scene, camera);
    };

    const loop = (now: number) => {
        if (!running) {
            return;
        }

        frame = window.requestAnimationFrame(loop);
        if (lite && now - lastDraw < 34) {
            return;
        }

        lastDraw = now;
        draw(now / 1000);
    };

    const start = () => {
        if (running) {
            return;
        }

        running = true;
        clock.getDelta();
        frame = window.requestAnimationFrame(loop);
    };

    const stop = () => {
        running = false;
        window.cancelAnimationFrame(frame);
    };

    const resize = () => {
        const width = element.clientWidth;
        const height = element.clientHeight;
        if (width < 2 || height < 2) {
            return;
        }

        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        const reach = (camera.position.z - 0.35) * Math.tan(THREE.MathUtils.degToRad(15)) * camera.aspect;
        assembly.scale.setScalar(Math.min(lite ? 0.64 : 1, (reach * 0.9) / 3.24));
        renderer.setSize(width, height, false);
        if (reduce) {
            draw(0);
        }
    };

    const observer = new ResizeObserver(resize);
    observer.observe(element);
    const visibility = new IntersectionObserver((entries) => {
        if (reduce) {
            resize();

            return;
        }

        if (entries.some((entry) => entry.isIntersecting)) {
            resize();
            start();
        } else {
            stop();
        }
    }, { threshold: 0.08 });
    visibility.observe(element);
    resize();
    if (!reduce && element.getBoundingClientRect().height > 0) {
        start();
    }

    const fonts = document.fonts;
    if (fonts) {
        void fonts.ready.then(() => {
            screenKey = '';
            cardKeys.clear();
            if (!running) {
                draw(0);
            }
        });
    }

    return () => {
        stop();
        observer.disconnect();
        visibility.disconnect();
        stage.removeEventListener('pointermove', onMove);
        stage.removeEventListener('pointerleave', onLeave);
        geometries.forEach((geometry) => geometry.dispose());
        materials.forEach((material) => material.dispose());
        textures.forEach((texture) => texture.dispose());
        renderer.dispose();
        canvas.remove();
    };
}

function labelFor(group: THREE.Group): { title: string; note: string } {
    const role = String(group.userData.role) as Role;
    const card = CARDS.find((item) => item.role === role);

    return { title: card?.title ?? role, note: card?.note ?? '' };
}

function buildPedestal(track: (geometry: THREE.BufferGeometry, material: THREE.Material, texture?: THREE.Texture) => void): THREE.Group {
    const group = new THREE.Group();
    const stone = new THREE.MeshStandardMaterial({
        color: 0x100e0c,
        metalness: 0.22,
        roughness: 0.72,
    });
    const metal = new THREE.MeshStandardMaterial({
        color: 0x1c1814,
        metalness: 0.78,
        roughness: 0.28,
    });
    materialsOnce(stone);
    track(new THREE.BufferGeometry(), metal);

    const chin = new THREE.Mesh(new RoundedBoxGeometry(0.7, 0.055, 0.16, 2, 0.02), metal);
    track(chin.geometry, metal);
    chin.position.set(0, 0.02, 0.03);
    group.add(chin);

    const neck = new THREE.Mesh(new RoundedBoxGeometry(0.16, 0.15, 0.11, 2, 0.028), metal);
    track(neck.geometry, metal);
    neck.position.set(0, -0.07, 0);
    group.add(neck);

    const slabs = [
        { w: 0.72, h: 0.065, d: 0.42, y: -0.17 },
        { w: 1.08, h: 0.07, d: 0.58, y: -0.255 },
        { w: 1.42, h: 0.075, d: 0.74, y: -0.345 },
    ];
    slabs.forEach((slab) => {
        const mesh = new THREE.Mesh(new RoundedBoxGeometry(slab.w, slab.h, slab.d, 3, 0.028), stone);
        geometriesOnce(mesh.geometry);
        mesh.position.set(0, slab.y, 0.02);
        group.add(mesh);
    });

    const disc = new THREE.Mesh(new THREE.CylinderGeometry(1.08, 1.14, 0.16, 64), stone);
    geometriesOnce(disc.geometry);
    disc.position.y = -0.48;
    group.add(disc);

    const ringMaterial = new THREE.MeshStandardMaterial({
        color: 0xf08a2a,
        emissive: 0xf08a2a,
        emissiveIntensity: 1.15,
        roughness: 0.32,
        metalness: 0.2,
    });
    const ring = new THREE.Mesh(new THREE.TorusGeometry(0.98, 0.016, 12, 80), ringMaterial);
    track(ring.geometry, ringMaterial);
    ring.rotation.x = Math.PI / 2;
    ring.position.y = -0.4;
    group.add(ring);

    const wash = glowDisc();
    track(wash.geometry, wash.material as THREE.Material, (wash.material as THREE.MeshBasicMaterial).map ?? undefined);
    wash.position.y = -0.57;
    group.add(wash);

    const markTexture = canvasTexture((context, width, height) => paintMark(context, width, height), 640, 360);
    const markMaterial = new THREE.MeshBasicMaterial({
        map: markTexture,
        transparent: true,
        toneMapped: false,
    });
    const mark = new THREE.Mesh(new THREE.PlaneGeometry(0.5, 0.28), markMaterial);
    track(mark.geometry, markMaterial, markTexture);
    mark.position.set(0, -0.22, 0.66);
    group.add(mark);

    return group;

    function geometriesOnce(geometry: THREE.BufferGeometry): void {
        track(geometry, stone);
    }

    function materialsOnce(material: THREE.Material): void {
        track(new THREE.BufferGeometry(), material);
    }
}

function glowDisc(): THREE.Mesh {
    const surface = document.createElement('canvas');
    surface.width = 256;
    surface.height = 256;
    const context = surface.getContext('2d');
    if (context) {
        const wash = context.createRadialGradient(128, 128, 40, 128, 128, 128);
        wash.addColorStop(0, 'rgba(240,138,42,0.0)');
        wash.addColorStop(0.55, 'rgba(240,138,42,0.28)');
        wash.addColorStop(0.78, 'rgba(240,138,42,0.12)');
        wash.addColorStop(1, 'rgba(240,138,42,0)');
        context.fillStyle = wash;
        context.fillRect(0, 0, 256, 256);
    }

    const texture = new THREE.CanvasTexture(surface);
    texture.colorSpace = THREE.SRGBColorSpace;
    const material = new THREE.MeshBasicMaterial({
        map: texture,
        transparent: true,
        depthWrite: false,
        toneMapped: false,
    });
    const mesh = new THREE.Mesh(new THREE.CircleGeometry(1.7, 64), material);
    mesh.rotation.x = -Math.PI / 2;

    return mesh;
}

type Surface = {
    context: CanvasRenderingContext2D;
    texture: THREE.CanvasTexture;
    width: number;
    height: number;
};

function makeSurface(width: number, height: number, sharp = false): Surface {
    const surface = document.createElement('canvas');
    surface.width = width;
    surface.height = height;
    const context = surface.getContext('2d', { alpha: !sharp });
    if (!context) {
        throw new Error('Canvas is unavailable');
    }

    const texture = new THREE.CanvasTexture(surface);
    texture.colorSpace = THREE.SRGBColorSpace;
    texture.anisotropy = 16;
    texture.wrapS = THREE.ClampToEdgeWrapping;
    texture.wrapT = THREE.ClampToEdgeWrapping;
    if (sharp) {
        texture.generateMipmaps = false;
        texture.minFilter = THREE.LinearFilter;
        texture.magFilter = THREE.LinearFilter;
        texture.premultiplyAlpha = false;
    }

    return { context, texture, width, height };
}

function canvasTexture(paint: (context: CanvasRenderingContext2D, width: number, height: number) => void, width: number, height: number): THREE.CanvasTexture {
    const surface = makeSurface(width, height);
    paint(surface.context, width, height);

    return surface.texture;
}

function typedAt(time: number): { count: number; cursor: boolean } {
    const local = time % CYCLE;
    if (local >= TYPE_END) {
        return { count: GLYPHS.length, cursor: Math.floor(local * 1.7) % 2 === 0 };
    }

    let low = 0;
    let high = STARTS.length;
    while (low < high) {
        const mid = (low + high) >> 1;
        if (STARTS[mid] <= local) {
            low = mid + 1;
        } else {
            high = mid;
        }
    }

    return { count: low, cursor: true };
}

function paintEditor(context: CanvasRenderingContext2D, width: number, height: number, count: number, cursor: boolean): void {
    context.clearRect(0, 0, width, height);
    context.direction = 'ltr';
    context.textAlign = 'left';
    context.textBaseline = 'alphabetic';
    roundedRect(context, 0, 0, width, height, 42);
    context.fillStyle = '#0c0e12';
    context.fill();
    roundedRect(context, 16, 16, width - 32, height - 32, 32);
    context.strokeStyle = ORANGE;
    context.lineWidth = 4;
    context.stroke();

    [0, 1, 2].forEach((dot) => {
        context.beginPath();
        context.fillStyle = dot === 0 ? '#ff5f57' : dot === 1 ? '#febc2e' : '#28c840';
        context.arc(64 + dot * 32, 58, 9, 0, Math.PI * 2);
        context.fill();
    });
    roundedRect(context, 188, 36, 176, 44, 12);
    context.fillStyle = 'rgba(240, 138, 42, 0.16)';
    context.fill();
    context.fillStyle = '#f7f4ee';
    context.font = '600 24px Consolas, ui-monospace, monospace';
    context.fillText('main.tsx', 214, 66);

    const colors: Record<Tone, string> = {
        key: '#ffb088',
        plain: '#f6f1ea',
        string: '#f3d2bc',
        call: ORANGE,
    };
    context.font = '500 44px Consolas, ui-monospace, monospace';
    let x = 108;
    let y = 176;
    let caretX = x;
    let caretY = y;
    GLYPHS.forEach((glyph, index) => {
        const written = index < count;
        if (glyph.ch === '\n') {
            if (written) {
                caretX = 108;
                caretY = y + 98;
            }
            x = 108;
            y += 98;

            return;
        }

        context.globalAlpha = written ? 1 : 0.2;
        context.fillStyle = colors[glyph.tone];
        context.fillText(glyph.ch, x, y);
        x += context.measureText(glyph.ch).width;
        if (written) {
            caretX = x;
            caretY = y;
        }
    });
    context.globalAlpha = 1;

    if (cursor) {
        context.fillStyle = ORANGE;
        context.fillRect(caretX + 6, caretY - 34, 3, 42);
    }
}

function paintPanel(context: CanvasRenderingContext2D, width: number, height: number, role: Role, copy: { title: string; note: string }, progress: number): void {
    const unit = 1024;
    const scale = width / unit;
    context.setTransform(scale, 0, 0, scale, 0, 0);
    context.clearRect(0, 0, unit, unit);
    const center = unit / 2;
    context.direction = 'ltr';
    context.textAlign = 'center';
    context.textBaseline = 'middle';
    context.fillStyle = '#101218';
    context.fillRect(0, 0, unit, unit);

    context.save();
    context.beginPath();
    context.arc(center, center, unit * 0.4, 0, Math.PI * 2);
    context.clip();

    context.save();
    context.translate(center, center - 210);
    context.strokeStyle = ORANGE;
    context.fillStyle = ORANGE;
    context.lineWidth = 11;
    context.lineCap = 'round';
    context.lineJoin = 'round';
    context.scale(2, 2);
    drawIcon(context, role);
    context.restore();

    context.fillStyle = '#f7f4ee';
    context.font = '700 124px Cairo, sans-serif';
    context.fillText(copy.title, center, center - 16);
    context.font = '600 50px Cairo, sans-serif';
    context.fillText(copy.note, center, center + 48);

    context.save();
    context.translate(center, center + 156);
    context.scale(0.78, 0.78);
    context.translate(-150, -24);
    context.strokeStyle = ORANGE;
    context.fillStyle = ORANGE;
    context.lineWidth = 9;
    context.lineCap = 'round';
    context.lineJoin = 'round';
    drawActivity(context, role, progress);
    context.restore();
    context.restore();
    context.setTransform(1, 0, 0, 1, 0, 0);
}

function drawActivity(context: CanvasRenderingContext2D, role: Role, progress: number): void {
    if (role === 'plan') {
        const nodes: [number, number][] = [[36, 112], [128, 36], [128, 188], [236, 112]];
        const links: [number, number][] = [[0, 1], [1, 3], [0, 2], [2, 3]];
        links.forEach((link, index) => {
            const amount = clamp((progress * links.length - index) * 1.15);
            const from = nodes[link[0]];
            const to = nodes[link[1]];
            context.globalAlpha = 0.85;
            context.beginPath();
            context.moveTo(from[0], from[1]);
            context.lineTo(from[0] + (to[0] - from[0]) * amount, from[1] + (to[1] - from[1]) * amount);
            context.stroke();
        });
        nodes.forEach((node, index) => {
            context.globalAlpha = clamp(progress * nodes.length - index);
            context.beginPath();
            context.arc(node[0], node[1], 14, 0, Math.PI * 2);
            context.fill();
        });
        context.globalAlpha = 1;

        return;
    }

    if (role === 'design') {
        roundedRect(context, 18, 18, 268, 196, 16);
        context.globalAlpha = 0.85;
        context.stroke();
        context.globalAlpha = 0.45;
        roundedRect(context, 34, 36, 236, 34, 8);
        context.stroke();
        roundedRect(context, 34, 86, 108, 108, 8);
        context.stroke();
        roundedRect(context, 156, 86, 114, 108, 8);
        context.stroke();
        const fill = clamp((progress - 0.2) / 0.6);
        if (fill > 0) {
            context.globalAlpha = fill;
            roundedRect(context, 34, 36, 236, 34, 8);
            context.fill();
            context.globalAlpha = fill * 0.45;
            roundedRect(context, 34, 86, 108, 108, 8);
            context.fill();
            roundedRect(context, 156, 86, 114, 108, 8);
            context.fill();
        }
        context.globalAlpha = 1;

        return;
    }

    if (role === 'build') {
        const pieces = [
            { from: [12, 16], to: [48, 58] },
            { from: [210, 12], to: [156, 58] },
            { from: [16, 196], to: [48, 146] },
            { from: [214, 200], to: [156, 146] },
        ];
        const amount = smooth(clamp(progress / 0.8));
        pieces.forEach((piece, index) => {
            const x = piece.from[0] + (piece.to[0] - piece.from[0]) * amount;
            const y = piece.from[1] + (piece.to[1] - piece.from[1]) * amount;
            context.globalAlpha = 0.95;
            context.fillStyle = index % 2 === 0 ? ORANGE : '#f6f1ea';
            roundedRect(context, x, y, 72, 56, 10);
            context.fill();
        });
        context.globalAlpha = 1;

        return;
    }

    if (role === 'develop') {
        const line = 'ship()';
        const shown = Math.max(0, Math.floor(clamp(progress * 1.15) * line.length));
        context.font = '700 92px Consolas, ui-monospace, monospace';
        context.textAlign = 'center';
        context.textBaseline = 'middle';
        context.globalAlpha = shown > 0 ? 1 : 0.4;
        context.fillStyle = '#f6f1ea';
        context.fillText(shown > 0 ? line.slice(0, shown) : line, 140, 78);
        const live = progress > 0.82;
        context.globalAlpha = 1;
        context.beginPath();
        context.arc(330, 150, 18, 0, Math.PI * 2);
        context.fillStyle = live ? '#8df5b0' : ORANGE;
        context.fill();

        return;
    }

    const filled = 248 * clamp(progress / 0.72);
    roundedRect(context, 24, 128, 248, 22, 11);
    context.globalAlpha = 0.4;
    context.stroke();
    context.globalAlpha = 1;
    if (filled > 2) {
        roundedRect(context, 24, 128, filled, 22, 11);
        context.fill();
    }
    if (progress > 0.8) {
        context.font = '700 36px Cairo, sans-serif';
        context.fillStyle = '#f7f4ee';
        context.textAlign = 'left';
        context.textBaseline = 'alphabetic';
        context.fillText('Live', 24, 96);
        context.beginPath();
        context.moveTo(148, 78);
        context.lineTo(166, 98);
        context.lineTo(198, 58);
        context.stroke();
    }
}

function drawIcon(context: CanvasRenderingContext2D, role: Role): void {
    context.beginPath();
    if (role === 'plan') {
        context.arc(0, -10, 26, Math.PI * 0.12, Math.PI * 0.88, true);
        context.stroke();
        context.beginPath();
        context.moveTo(-14, 18);
        context.lineTo(14, 18);
        context.moveTo(-9, 30);
        context.lineTo(9, 30);
        context.stroke();
    } else if (role === 'build') {
        context.moveTo(0, -28);
        context.lineTo(30, -12);
        context.lineTo(30, 16);
        context.lineTo(0, 32);
        context.lineTo(-30, 16);
        context.lineTo(-30, -12);
        context.closePath();
        context.moveTo(-30, -12);
        context.lineTo(0, 4);
        context.lineTo(30, -12);
        context.moveTo(0, 4);
        context.lineTo(0, 32);
        context.stroke();
    } else if (role === 'design') {
        [-18, 0, 18].forEach((y) => {
            context.beginPath();
            context.moveTo(-28, y);
            context.lineTo(0, y + 14);
            context.lineTo(28, y);
            context.lineTo(0, y - 14);
            context.closePath();
            context.stroke();
        });
    } else if (role === 'develop') {
        context.moveTo(-10, -22);
        context.lineTo(-28, 0);
        context.lineTo(-10, 22);
        context.moveTo(10, -22);
        context.lineTo(28, 0);
        context.lineTo(10, 22);
        context.stroke();
    } else {
        context.moveTo(0, 26);
        context.lineTo(0, -10);
        context.moveTo(0, -10);
        context.lineTo(-16, 6);
        context.moveTo(0, -10);
        context.lineTo(16, 6);
        context.moveTo(-12, 26);
        context.lineTo(12, 26);
        context.stroke();
    }
}

function paintMark(context: CanvasRenderingContext2D, width: number, height: number): void {
    context.clearRect(0, 0, width, height);
    context.strokeStyle = ORANGE;
    context.lineWidth = 22;
    context.lineCap = 'round';
    context.lineJoin = 'round';
    context.beginPath();
    context.moveTo(176, 64);
    context.lineTo(78, 180);
    context.lineTo(176, 296);
    context.moveTo(464, 64);
    context.lineTo(562, 180);
    context.lineTo(464, 296);
    context.moveTo(360, 40);
    context.lineTo(280, 320);
    context.stroke();
}

function waveField(columns: number, rows: number): { points: THREE.Points; geometry: THREE.BufferGeometry; material: THREE.PointsMaterial; texture: THREE.Texture; update: (time: number) => void } {
    const count = columns * rows;
    const positions = new Float32Array(count * 3);
    const seeds = new Float32Array(count);
    for (let column = 0; column < columns; column += 1) {
        for (let row = 0; row < rows; row += 1) {
            const index = column * rows + row;
            const x = (column / (columns - 1) - 0.5) * 11.5;
            const z = (row / (rows - 1)) * 4.2 - 0.6;
            positions[index * 3] = x;
            positions[index * 3 + 1] = 0;
            positions[index * 3 + 2] = z;
            seeds[index] = x * 0.9 + row * 0.15;
        }
    }

    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    const texture = sparkTexture();
    const material = new THREE.PointsMaterial({
        color: 0xf08a2a,
        size: 0.042,
        map: texture,
        transparent: true,
        opacity: 0.9,
        depthWrite: false,
        blending: THREE.AdditiveBlending,
        sizeAttenuation: true,
        toneMapped: false,
    });
    const points = new THREE.Points(geometry, material);
    const update = (time: number) => {
        const attribute = geometry.getAttribute('position');
        const array = attribute.array as Float32Array;
        for (let index = 0; index < count; index += 1) {
            array[index * 3 + 1] = Math.sin(seeds[index] + time * 0.65) * 0.045;
        }
        attribute.needsUpdate = true;
    };

    return { points, geometry, material, texture, update };
}

function sparkTexture(): THREE.CanvasTexture {
    const canvas = document.createElement('canvas');
    canvas.width = 32;
    canvas.height = 32;
    const context = canvas.getContext('2d');
    if (context) {
        const glow = context.createRadialGradient(16, 16, 1, 16, 16, 16);
        glow.addColorStop(0, 'rgba(255, 214, 170, 1)');
        glow.addColorStop(0.45, 'rgba(240, 138, 42, 0.9)');
        glow.addColorStop(1, 'rgba(240, 138, 42, 0)');
        context.fillStyle = glow;
        context.fillRect(0, 0, 32, 32);
    }

    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;

    return texture;
}

function compile(lines: readonly (readonly (readonly [string, Tone])[])[]): { ch: string; tone: Tone }[] {
    const glyphs: { ch: string; tone: Tone }[] = [];
    lines.forEach((line, lineIndex) => {
        line.forEach(([text, tone]) => {
            Array.from(text).forEach((ch) => {
                glyphs.push({ ch, tone });
            });
        });
        if (lineIndex < lines.length - 1) {
            glyphs.push({ ch: '\n', tone: 'plain' });
        }
    });

    return glyphs;
}

function roundedRect(context: CanvasRenderingContext2D, x: number, y: number, width: number, height: number, radius: number): void {
    const curve = Math.min(radius, width / 2, height / 2);
    context.beginPath();
    context.moveTo(x + curve, y);
    context.arcTo(x + width, y, x + width, y + height, curve);
    context.arcTo(x + width, y + height, x, y + height, curve);
    context.arcTo(x, y + height, x, y, curve);
    context.arcTo(x, y, x + width, y, curve);
    context.closePath();
}

function clamp(value: number): number {
    return Math.min(1, Math.max(0, value));
}

function smooth(value: number): number {
    return value * value * (3 - 2 * value);
}
