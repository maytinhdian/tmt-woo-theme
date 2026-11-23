(() => {
// Chữ cạnh logo, hai kiểu cần JS (Tùy biến → Hiệu ứng → Chữ cạnh logo; port storefront-brand-fx.js của app):
// - Gõ chữ ([data-sf-bfx="type"]): gõ từng ký tự, có con trỏ nhấp nháy, gõ xong nghỉ rồi xóa và gõ lại.
// - Đổi câu ([data-sf-bfx="rotate"]): xoay vòng dòng công ty và các câu Anh 2 tự gõ, câu mới hiện dần từng chữ (CSS).
// Lúc chạy đọc từ data-bfx-run trên thẻ <a class="sf-brand">: loop (lặp), once (một lần khi mở trang), hover (khi rê chuột vào logo).
// Máy bật "giảm chuyển động": không chạy, chữ đứng yên như cũ. Chỉ nạp khi chọn một trong hai kiểu này.

const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function speedOf(link) {
    return Number(getComputedStyle(link).getPropertyValue('--bfx-k')) || 1;
}

function typeFx(box, link, run) {
    const tx = box.querySelector('.tx');
    const text = tx.dataset.text || '';
    const chars = [...text];
    const k = speedOf(link);
    let timer = null;

    const stop = () => { clearTimeout(timer); timer = null; tx.classList.remove('typing'); tx.textContent = text; };
    // Gõ ra hết; repeat = gõ xong nghỉ, xóa lùi rồi gõ lại.
    const play = (repeat) => {
        clearTimeout(timer);
        let i = 0;
        let back = false;
        tx.textContent = '';
        tx.classList.add('typing');
        const tick = () => {
            if (!back) {
                tx.textContent = chars.slice(0, ++i).join('');
                if (i >= chars.length) {
                    if (!repeat) { timer = setTimeout(() => tx.classList.remove('typing'), 1600); return; }
                    back = true;
                    timer = setTimeout(tick, 2400 * k);
                    return;
                }
                timer = setTimeout(tick, 70 * k);
            } else {
                tx.textContent = chars.slice(0, --i).join('');
                if (i <= 0) { back = false; timer = setTimeout(tick, 500 * k); return; }
                timer = setTimeout(tick, 28 * k);
            }
        };
        timer = setTimeout(tick, 300);
    };

    if (run === 'hover') {
        link.addEventListener('mouseenter', () => play(false));
        link.addEventListener('mouseleave', stop);
    } else {
        play(run === 'loop');
    }
}

function rotateFx(box, link, run) {
    const lines = [...box.querySelectorAll('.ln')];
    if (lines.length < 2) return;
    const k = speedOf(link);
    const wait = 3000 * k;
    let n = 0;
    let timer = null;

    const show = (i, animate) => {
        lines[n].classList.remove('on', 'run');
        n = i;
        lines[n].classList.add('on');
        if (animate) { void lines[n].offsetWidth; lines[n].classList.add('run'); }
    };
    const next = (onceRound) => {
        show((n + 1) % lines.length, true);
        if (onceRound && n === 0) return;
        timer = setTimeout(() => next(onceRound), wait);
    };

    if (run === 'hover') {
        link.addEventListener('mouseenter', () => { clearTimeout(timer); timer = setTimeout(() => next(false), 250); });
        link.addEventListener('mouseleave', () => { clearTimeout(timer); show(0, false); });
    } else {
        timer = setTimeout(() => next(run === 'once'), wait);
    }
}

if (!reduced) {
    document.querySelectorAll('[data-sf-bfx]').forEach((box) => {
        const link = box.closest('.sf-brand');
        if (!link) return;
        const run = link.dataset.bfxRun || 'loop';
        if (box.dataset.sfBfx === 'type') typeFx(box, link, run);
        else rotateFx(box, link, run);
    });
}

})();
