// Public website behaviour. Small and dependency-free; every feature works (or degrades) without it.
document.documentElement.classList.add('js');

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// Sticky header: add a shadow once the page scrolls.
const header = document.querySelector('[data-site-header]');
if (header) {
    const update = () => header.setAttribute('data-scrolled', window.scrollY > 8 ? 'true' : 'false');
    update();
    window.addEventListener('scroll', update, { passive: true });
}

// Disclosure menus (Treatments dropdown, mobile menu): a button controls the element named in aria-controls.
const closeAll = (except) => {
    document.querySelectorAll('[data-disclosure][aria-expanded="true"]').forEach((button) => {
        if (button !== except) toggle(button, false);
    });
};

function toggle(button, open) {
    const panel = document.getElementById(button.getAttribute('aria-controls'));
    if (!panel) return;
    button.setAttribute('aria-expanded', String(open));
    panel.hidden = !open;
    if (button.dataset.disclosure === 'modal') {
        document.documentElement.classList.toggle('overflow-hidden', open);
        if (open) panel.querySelector('a, button')?.focus();
    }
}

document.querySelectorAll('[data-disclosure]').forEach((button) => {
    button.addEventListener('click', (event) => {
        event.stopPropagation();
        const open = button.getAttribute('aria-expanded') !== 'true';
        if (button.dataset.disclosure !== 'inline') closeAll(button);
        toggle(button, open);
    });
});

document.querySelectorAll('[data-close-disclosure]').forEach((el) => {
    el.addEventListener('click', () => {
        const button = document.querySelector(`[aria-controls="${el.dataset.closeDisclosure}"]`);
        if (button) {
            toggle(button, false);
            button.focus();
        }
    });
});

document.addEventListener('click', (event) => {
    document.querySelectorAll('[data-disclosure="menu"][aria-expanded="true"]').forEach((button) => {
        const panel = document.getElementById(button.getAttribute('aria-controls'));
        if (panel && !panel.contains(event.target)) toggle(button, false);
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const open = document.querySelector('[data-disclosure][aria-expanded="true"]:not([data-disclosure="inline"])');
    if (open) {
        toggle(open, false);
        open.focus();
    }
});

// Reveal sections as they scroll into view.
const revealed = document.querySelectorAll('[data-reveal]');
if (revealed.length && 'IntersectionObserver' in window && !reduceMotion) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -10% 0px' });
    revealed.forEach((el) => observer.observe(el));
} else {
    revealed.forEach((el) => el.classList.add('is-visible'));
}

// Before/after comparison: a native range input drives the split, so keyboard and touch work out of the box.
document.querySelectorAll('[data-compare]').forEach((root) => {
    const range = root.querySelector('input[type="range"]');
    if (!range) return;
    const set = () => root.style.setProperty('--split', `${range.value}%`);
    range.addEventListener('input', set);
    set();
});

// Carousels (testimonials): scroll-snap track with previous/next buttons.
document.querySelectorAll('[data-carousel]').forEach((root) => {
    const track = root.querySelector('[data-carousel-track]');
    if (!track) return;
    const rtl = getComputedStyle(track).direction === 'rtl';
    const step = (dir) => {
        const slide = track.firstElementChild;
        const width = slide ? slide.getBoundingClientRect().width + 24 : track.clientWidth;
        track.scrollBy({ left: dir * width * (rtl ? -1 : 1), behavior: reduceMotion ? 'auto' : 'smooth' });
    };
    root.querySelector('[data-carousel-prev]')?.addEventListener('click', () => step(-1));
    root.querySelector('[data-carousel-next]')?.addEventListener('click', () => step(1));
});

// Smile Gallery: treatment filter.
document.querySelectorAll('[data-gallery-filters]').forEach((group) => {
    const items = document.querySelectorAll('[data-gallery] > li');
    group.addEventListener('click', (event) => {
        const button = event.target.closest('[data-filter]');
        if (!button) return;
        group.querySelectorAll('[data-filter]').forEach((b) => b.setAttribute('aria-pressed', String(b === button)));
        items.forEach((li) => { li.hidden = button.dataset.filter !== 'all' && li.dataset.category !== button.dataset.filter; });
    });
});

// Smile Gallery: full-screen viewer with previous/next and arrow keys.
const viewer = document.querySelector('[data-case-viewer]');
if (viewer && typeof viewer.showModal === 'function') {
    const cases = viewer.querySelectorAll('[data-case]');
    const counter = viewer.querySelector('[data-case-counter]');
    let current = 0;
    const show = (index) => {
        const visible = [...document.querySelectorAll('[data-gallery] > li')].map((li, i) => (li.hidden ? null : i)).filter((i) => i !== null);
        if (!visible.length) return;
        if (!visible.includes(index)) index = visible[0];
        current = index;
        cases.forEach((c, i) => { c.hidden = i !== index; });
        counter.textContent = `${visible.indexOf(index) + 1} / ${visible.length}`;
    };
    const step = (dir) => {
        const visible = [...document.querySelectorAll('[data-gallery] > li')].map((li, i) => (li.hidden ? null : i)).filter((i) => i !== null);
        const pos = visible.indexOf(current);
        show(visible[(pos + dir + visible.length) % visible.length]);
    };
    document.querySelectorAll('[data-open-case]').forEach((button) => {
        button.addEventListener('click', () => { show(Number(button.dataset.openCase)); viewer.showModal(); });
    });
    const rtl = document.documentElement.dir === 'rtl';
    viewer.querySelector('[data-case-prev]').addEventListener('click', () => step(-1));
    viewer.querySelector('[data-case-next]').addEventListener('click', () => step(1));
    viewer.addEventListener('keydown', (event) => {
        if (event.target.matches('input[type="range"]')) return; // arrows move the comparison handle there
        if (event.key === 'ArrowRight') step(rtl ? -1 : 1);
        if (event.key === 'ArrowLeft') step(rtl ? 1 : -1);
    });
} else if (viewer) {
    document.querySelectorAll('[data-open-case]').forEach((b) => { b.hidden = true; });
}
