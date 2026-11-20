/**
 * Hiệu ứng cần JS: thẻ nghiêng 3D theo chuột và con trỏ chuột tùy biến (chép từ storefront-fx.js của app).
 * Chọn ở Tùy biến → Hiệu ứng; setup.php chỉ nạp file này khi có bật một trong hai.
 * <body data-fx-cursor="ring|glow|trail|spark"> và lớp sf-fx-p-tilt / sf-fx-a-tilt.
 * Không chạy trên máy cảm ứng và khi máy bật "giảm chuyển động".
 */

(() => {
const fine = () => window.matchMedia('(hover: hover) and (pointer: fine)').matches;
const reduced = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const LINKS = 'a, button, [role="button"], label, select, summary, input[type="submit"], input[type="checkbox"], input[type="radio"]';

function el(cls) {
    const d = document.createElement('div');
    d.className = 'sf-cur ' + cls;
    d.setAttribute('aria-hidden', 'true');
    document.body.appendChild(d);
    return d;
}

const place = (node, x, y, scale = 1) => {
    node.style.transform = `translate3d(${x}px, ${y}px, 0)` + (scale !== 1 ? ` scale(${scale})` : '');
};

/** Vòng lặp vẽ chỉ chạy khi còn chuyển động, dừng khi đứng yên để không tốn pin. */
function loop(step) {
    let running = false;
    const tick = () => {
        running = step();
        if (running) requestAnimationFrame(tick);
    };
    return () => {
        if (!running) {
            running = true;
            requestAnimationFrame(tick);
        }
    };
}

function cursorRing() {
    const dot = el('sf-cur-dot');
    const ring = el('sf-cur-ring');
    document.body.classList.add('sf-cur-hide');
    // Khách bật giảm chuyển động hoặc đổi sang cảm ứng khi trang đang mở: CSS ẩn vòng, ở đây trả lại con trỏ thật.
    const restore = () => document.body.classList.toggle('sf-cur-hide', fine() && !reduced());
    window.matchMedia('(prefers-reduced-motion: reduce)').addEventListener?.('change', restore);
    window.matchMedia('(hover: hover) and (pointer: fine)').addEventListener?.('change', restore);
    const m = { x: -100, y: -100 };
    const r = { x: -100, y: -100 };
    const kick = loop(() => {
        r.x += (m.x - r.x) * 0.2;
        r.y += (m.y - r.y) * 0.2;
        place(ring, r.x, r.y);
        return Math.abs(m.x - r.x) + Math.abs(m.y - r.y) > 0.3;
    });
    document.addEventListener('pointermove', (e) => {
        m.x = e.clientX;
        m.y = e.clientY;
        place(dot, m.x, m.y);
        ring.classList.toggle('is-link', !!e.target.closest?.(LINKS));
        kick();
    }, { passive: true });
    document.addEventListener('pointerdown', () => ring.classList.add('is-down'));
    document.addEventListener('pointerup', () => ring.classList.remove('is-down'));
    hideOnLeave([dot, ring]);
}

function cursorGlow() {
    const glow = el('sf-cur-glow');
    const m = { x: -400, y: -400 };
    const g = { x: -400, y: -400 };
    const kick = loop(() => {
        g.x += (m.x - g.x) * 0.12;
        g.y += (m.y - g.y) * 0.12;
        place(glow, g.x, g.y);
        return Math.abs(m.x - g.x) + Math.abs(m.y - g.y) > 0.3;
    });
    document.addEventListener('pointermove', (e) => {
        m.x = e.clientX;
        m.y = e.clientY;
        kick();
    }, { passive: true });
    hideOnLeave([glow]);
}

function cursorTrail() {
    const N = 10;
    const dots = Array.from({ length: N }, (_, i) => {
        const d = el('sf-cur-trail');
        d.style.opacity = String(0.85 - i * 0.07);
        return { node: d, x: -100, y: -100, s: 1 - i * 0.075 };
    });
    const m = { x: -100, y: -100 };
    const kick = loop(() => {
        let x = m.x;
        let y = m.y;
        let moving = false;
        for (const d of dots) {
            d.x += (x - d.x) * 0.38;
            d.y += (y - d.y) * 0.38;
            place(d.node, d.x, d.y, d.s);
            if (Math.abs(x - d.x) + Math.abs(y - d.y) > 0.3) moving = true;
            x = d.x;
            y = d.y;
        }
        return moving;
    });
    document.addEventListener('pointermove', (e) => {
        m.x = e.clientX;
        m.y = e.clientY;
        kick();
    }, { passive: true });
    hideOnLeave(dots.map((d) => d.node));
}

function cursorSpark() {
    const colors = [getVar('--sf-blue', '#3271E0'), getVar('--sf-yellow', '#FDDB4C'), getVar('--sf-navy', '#1E3A78')];
    document.addEventListener('pointerdown', (e) => {
        if (e.button !== 0) return;
        const wave = el('sf-cur-wave');
        place(wave, e.clientX, e.clientY);
        wave.animate([
            { transform: `translate3d(${e.clientX}px, ${e.clientY}px, 0) scale(.3)`, opacity: 0.8 },
            { transform: `translate3d(${e.clientX}px, ${e.clientY}px, 0) scale(1.6)`, opacity: 0 },
        ], { duration: 500, easing: 'ease-out' }).onfinish = () => wave.remove();
        for (let i = 0; i < 12; i++) {
            const p = el('sf-cur-spark');
            p.style.background = colors[i % colors.length];
            const a = (Math.PI * 2 * i) / 12 + Math.random() * 0.4;
            const dist = 28 + Math.random() * 30;
            const tx = e.clientX + Math.cos(a) * dist;
            const ty = e.clientY + Math.sin(a) * dist;
            p.animate([
                { transform: `translate3d(${e.clientX}px, ${e.clientY}px, 0) scale(1)`, opacity: 1 },
                { transform: `translate3d(${tx}px, ${ty}px, 0) scale(.2)`, opacity: 0 },
            ], { duration: 550 + Math.random() * 250, easing: 'cubic-bezier(.2,.7,.2,1)' }).onfinish = () => p.remove();
        }
    });
}

function getVar(name, fallback) {
    return getComputedStyle(document.body).getPropertyValue(name).trim() || fallback;
}

function hideOnLeave(nodes) {
    document.documentElement.addEventListener('mouseleave', () => nodes.forEach((n) => (n.style.opacity = '0')));
    document.documentElement.addEventListener('mouseenter', () => nodes.forEach((n) => (n.style.opacity = '')));
}

/** Thẻ nghiêng theo vị trí chuột (tối đa ~8 độ x cường độ). */
function tilt() {
    const body = document.body;
    const sel = [body.classList.contains('sf-fx-p-tilt') && '.sf-pcard', body.classList.contains('sf-fx-a-tilt') && '.sf-fx-post'].filter(Boolean).join(', ');
    if (!sel) return;
    const k = parseFloat(getComputedStyle(body).getPropertyValue('--fx-k')) || 1;
    let current = null;
    const reset = (card) => {
        card.style.removeProperty('--fx-rx');
        card.style.removeProperty('--fx-ry');
    };
    document.addEventListener('pointermove', (e) => {
        const card = e.target.closest?.(sel);
        if (current && current !== card) reset(current);
        current = card;
        if (!card) return;
        const r = card.getBoundingClientRect();
        const px = (e.clientX - r.left) / r.width;
        const py = (e.clientY - r.top) / r.height;
        card.style.setProperty('--fx-ry', `${((px - 0.5) * 10 * k).toFixed(2)}deg`);
        card.style.setProperty('--fx-rx', `${((0.5 - py) * 10 * k).toFixed(2)}deg`);
        card.style.setProperty('--fx-mx', `${(px * 100).toFixed(1)}%`);
        card.style.setProperty('--fx-my', `${(py * 100).toFixed(1)}%`);
    }, { passive: true });
    document.addEventListener('pointerleave', () => current && reset(current));
}

function initStorefrontFx() {
    if (!fine() || reduced() || window.__sfFx) return;
    window.__sfFx = true;
    tilt();
    const kind = document.body.dataset.fxCursor;
    ({ ring: cursorRing, glow: cursorGlow, trail: cursorTrail, spark: cursorSpark })[kind]?.();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStorefrontFx);
} else {
    initStorefrontFx();
}
})();
