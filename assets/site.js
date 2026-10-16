/* ORWO Studios — progressive enhancement only. Every text, image, link and form works without JavaScript. */
(() => {
  const root = document.documentElement;
  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const desktop = matchMedia('(min-width: 900px)');
  const phone = matchMedia('(max-width: 640px)');
  root.classList.add('js');
  const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];
  const raf = fn => { let q = false; return () => { if (!q) { q = true; requestAnimationFrame(() => { q = false; fn(); }); } }; };

  /* Header: solid glass once the page scrolls; current section marked in the menu. */
  const header = document.querySelector('.site-header');
  if (header) {
    const mark = raf(() => header.classList.toggle('is-scrolled', scrollY > 30));
    addEventListener('scroll', mark, {passive: true}); mark();
    if (!document.querySelector('.orwost-hero.has-photo')) header.classList.add('is-solid');
  }
  const here = location.pathname.replace(/\/+$/, '') || '/';
  $$('.orwost-nav a').forEach(a => {
    let url; try { url = new URL(a.href); } catch { return; }
    if (url.origin !== location.origin) return;
    const path = url.pathname.replace(/\/+$/, '') || '/';
    if (path === here || (path === '/blog' && here.startsWith('/blog/'))) a.setAttribute('aria-current', 'page');
  });

  /* Hero headline: words rise in one after another. */
  if (!reduced) $$('.orwost-hero .hero-title').forEach(title => {
    const walker = document.createTreeWalker(title, NodeFilter.SHOW_TEXT), nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    let i = 0;
    nodes.forEach(node => {
      const frag = document.createDocumentFragment();
      node.textContent.split(/(\s+)/).forEach(part => {
        if (!part) return;
        if (/^\s+$/.test(part)) { frag.append(part); return; }
        const outer = document.createElement('span'), inner = document.createElement('span');
        outer.className = 'w'; inner.textContent = part; inner.style.setProperty('--i', i++);
        outer.append(inner); frag.append(outer);
      });
      node.replaceWith(frag);
    });
    title.style.animation = 'none';
  });

  /* Home hero: the original muted background showreel starts a few seconds after the page loads
     (phones included; not with reduced motion or data saver). The poster stays until it really plays. */
  const heroVideo = document.querySelector('.orwost-hero[class*="ytbg-"]');
  if (heroVideo && !reduced && !(navigator.connection && navigator.connection.saveData)) {
    const cls = heroVideo.className;
    const id = (cls.match(/(?:^|\s)ytbg-([A-Za-z0-9_-]{11})(?=\s|$)/) || [])[1];
    const scale = Math.min(2, Math.max(1, ((cls.match(/ytscale-(\d+)/) || [])[1] || 1000) / 1000));
    if (id) {
      const start = () => setTimeout(() => {
        const box = document.createElement('div'); box.className = 'hero-video'; box.setAttribute('aria-hidden', 'true');
        box.style.setProperty('--yt-scale', scale);
        const frame = document.createElement('iframe');
        frame.src = `https://www.youtube-nocookie.com/embed/${id}?autoplay=1&mute=1&loop=1&playlist=${id}&controls=0&modestbranding=1&playsinline=1&rel=0&disablekb=1&iv_load_policy=3&enablejsapi=1&origin=${encodeURIComponent(location.origin)}`;
        frame.allow = 'autoplay; encrypted-media'; frame.tabIndex = -1; frame.title = '';
        // Fade the video in only once YouTube reports it is really playing; if autoplay is blocked the poster simply stays.
        addEventListener('message', e => {
          if (e.source !== frame.contentWindow || !/youtube(-nocookie)?\.com$/.test(new URL(e.origin).hostname)) return;
          let msg; try { msg = typeof e.data === 'string' ? JSON.parse(e.data) : e.data; } catch { return; }
          if (msg && msg.info && msg.info.playerState === 1) setTimeout(() => box.classList.add('is-ready'), 400);
        });
        frame.addEventListener('load', () => frame.contentWindow.postMessage(JSON.stringify({event: 'listening', id: 1, channel: 'widget'}), '*'));
        box.append(frame);
        heroVideo.querySelector('.hero-photo')?.after(box);
      }, desktop.matches ? 600 : 2500);
      if (document.readyState === 'complete') start(); else addEventListener('load', start);
    }
  }

  /* Seamless marquees: clone the original items (hidden from assistive tech) until the loop is long enough. */
  const loop = (container, track, speed) => {
    if (reduced || !track || !track.children.length) return;
    const items = [...track.children];
    const fill = () => {
      while (track.scrollWidth < container.clientWidth * 2.2) {
        items.forEach(item => { const c = item.cloneNode(true); c.setAttribute('aria-hidden', 'true'); $$('a,button', c).forEach(x => x.tabIndex = -1); track.append(c); });
        if (track.children.length > items.length * 12) break;
      }
      // Even number of copies so translateX(-50%) lands exactly on a copy boundary.
      if ((track.children.length / items.length) % 2) items.forEach(item => { const c = item.cloneNode(true); c.setAttribute('aria-hidden', 'true'); $$('a,button', c).forEach(x => x.tabIndex = -1); track.append(c); });
      track.style.setProperty('--speed', Math.max(20, track.scrollWidth / 2 / speed) + 's');
      container.classList.add('is-moving');
    };
    if (document.readyState === 'complete') fill(); else addEventListener('load', fill);
  };
  $$('.photo-marquee').forEach(m => loop(m, m.querySelector('.marquee-track'), 45));
  $$('.logo-strip').forEach(m => loop(m, m.querySelector('.logo-track'), 40));

  /* Testimonials: slow moving strip on larger screens; swipeable cards with dots and gentle auto-advance on phones. */
  $$('.quote-rail').forEach(rail => {
    const cards = [...rail.children];
    if (!phone.matches && !reduced && cards.length) {
      const view = document.createElement('div'); view.className = 'quote-viewport';
      rail.before(view); view.append(rail);
      [...cards, ...cards, ...cards].forEach((card, i) => { if (i < cards.length) return; const c = card.cloneNode(true); c.setAttribute('aria-hidden', 'true'); rail.append(c); });
      cards.forEach(card => { const c = card.cloneNode(true); c.setAttribute('aria-hidden', 'true'); rail.append(c); });
      rail.classList.add('is-marquee');
      rail.style.setProperty('--speed', cards.length * 16 + 's');
      return;
    }
    if (!phone.matches || cards.length < 2) return;
    const dots = document.createElement('div'); dots.className = 'rail-dots'; dots.setAttribute('aria-hidden', 'true');
    cards.forEach((_, i) => { const b = document.createElement('button'); b.type = 'button'; b.tabIndex = -1; b.addEventListener('click', () => go(i)); dots.append(b); });
    rail.after(dots);
    let index = 0, timer;
    const go = i => { index = (i + cards.length) % cards.length; rail.scrollTo({left: cards[index].offsetLeft - rail.offsetLeft - parseFloat(getComputedStyle(rail).paddingLeft), behavior: reduced ? 'auto' : 'smooth'}); };
    const sync = raf(() => { const mid = rail.scrollLeft + rail.clientWidth / 2; index = cards.reduce((best, c, i) => Math.abs(c.offsetLeft - rail.offsetLeft + c.offsetWidth / 2 - mid) < Math.abs(cards[best].offsetLeft - rail.offsetLeft + cards[best].offsetWidth / 2 - mid) ? i : best, 0); [...dots.children].forEach((d, i) => d.classList.toggle('on', i === index)); });
    rail.addEventListener('scroll', sync, {passive: true}); sync();
    // Gentle auto-advance until the visitor touches the cards; after that only their own swipes move it.
    const stop = () => { clearInterval(timer); rail.removeEventListener('touchstart', stop); rail.removeEventListener('pointerdown', stop); };
    if (!reduced) timer = setInterval(() => go(index + 1), 6000);
    rail.addEventListener('touchstart', stop, {passive: true});
    rail.addEventListener('pointerdown', stop, {passive: true});
  });
  const dotStyle = document.createElement('style');
  dotStyle.textContent = '.rail-dots{display:flex;justify-content:center;gap:8px;margin-top:18px!important}.rail-dots button{width:8px;height:8px;padding:0;border:0;border-radius:99px;background:rgba(0,0,0,.2);transition:width .3s,background-color .3s}.rail-dots button.on{width:26px;background:#0aa6dc}';
  document.head.append(dotStyle);

  /* Productions rail: arrow buttons and mouse dragging. */
  $$('.film-rail').forEach(rail => {
    const head = rail.parentElement.querySelector('.split-head');
    const controls = document.createElement('div'); controls.className = 'rail-controls';
    controls.innerHTML = '<button type="button" aria-label="Previous productions">←</button><button type="button" aria-label="Next productions">→</button>';
    (head || rail).insertAdjacentElement(head ? 'beforeend' : 'beforebegin', controls);
    const [prev, next] = controls.children;
    const step = () => (rail.firstElementChild?.offsetWidth || 300) + 18;
    prev.addEventListener('click', () => rail.scrollBy({left: -step() * 2, behavior: 'smooth'}));
    next.addEventListener('click', () => rail.scrollBy({left: step() * 2, behavior: 'smooth'}));
    const state = raf(() => { prev.disabled = rail.scrollLeft < 8; next.disabled = rail.scrollLeft + rail.clientWidth > rail.scrollWidth - 8; });
    rail.addEventListener('scroll', state, {passive: true}); addEventListener('resize', state); state();
    let down = null;
    rail.addEventListener('pointerdown', e => { if (e.pointerType !== 'mouse') return; down = {x: e.clientX, left: rail.scrollLeft, moved: false}; rail.style.scrollSnapType = 'none'; });
    addEventListener('pointermove', e => { if (!down) return; const dx = e.clientX - down.x; if (Math.abs(dx) > 4) down.moved = true; rail.scrollLeft = down.left - dx; });
    addEventListener('pointerup', () => { if (!down) return; rail.style.scrollSnapType = ''; setTimeout(() => { down = null; }, 0); });
    rail.addEventListener('click', e => { if (down && down.moved) { e.preventDefault(); e.stopPropagation(); } }, true);
  });

  /* Scroll reveal with small staggers. */
  if (!reduced && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver(entries => entries.forEach(entry => {
      if (entry.isIntersecting) { entry.target.classList.add('rv-in'); io.unobserve(entry.target); }
    }), {threshold: .08, rootMargin: '0px 0px -6% 0px'});
    const targets = '.section-head,.about-copy>*,.service-card,.stage-card,.film-card,.press-card,.stat-card,.figure-card,.facility-card,.feature-row,.set-block,.occasion-card,.news-card,.partner-card,.menu-card,.menu-photo,.wp-block-details,.orwost-post-grid>li,.grid-photo,.poster,.contact-details,.form-panel,.orwo-film-copy,.stages-copy,.follow-copy,.cta-inner,.company-inner,.story-inner,.banner-copy,.jake-copy,.reading>*,.footer-cta,.tax-intro-grid>*,.subhead,.orwost-map,.rental-intro,.orwost-chips';
    $$(targets).forEach(el => {
      if (el.closest('.orwost-hero,[aria-hidden="true"],.quote-viewport,.film-rail')) return;
      // Cards in swipe rails stay static: off-screen cards would otherwise fade in mid-swipe and look glitchy.
      if (phone.matches && el.closest('.orwost-carousel-mobile,.occasion-grid,.news-grid,.quote-rail')) return;
      const siblings = [...el.parentElement.children];
      el.style.setProperty('--d', (Math.min(siblings.indexOf(el), 8) % 8) * 70 + 'ms');
      el.classList.add('rv'); io.observe(el);
    });
    $$('.about-collage>*,.orwo-film-art,.follow-video,.jake-photo,.article-cover').forEach((el, i) => { el.style.setProperty('--d', (i % 3) * 120 + 'ms'); el.classList.add('rv-zoom'); io.observe(el); });
    /* Count up the original figures once they appear ($150,000,000, 25%, 88% …). */
    const counter = new IntersectionObserver(entries => entries.forEach(entry => {
      if (!entry.isIntersecting) return; counter.unobserve(entry.target);
      const el = entry.target, m = el.textContent.trim().match(/^(\$?)([\d,]*\d(?:\.\d+)?)(.*)$/s);
      if (!m) return;
      const target = parseFloat(m[2].replace(/,/g, '')), commas = m[2].includes(','), start = performance.now(), dur = 1400, final = el.textContent;
      const fmt = v => commas ? Math.round(v).toLocaleString('en-US') : String(Math.round(v));
      const tick = now => { const t = Math.min(1, (now - start) / dur), e = 1 - Math.pow(1 - t, 3); el.textContent = m[1] + fmt(target * e) + m[3]; if (t < 1) requestAnimationFrame(tick); else el.textContent = final; };
      requestAnimationFrame(tick);
    }), {threshold: .6});
    $$('.stat-number').forEach(el => counter.observe(el));
  }

  /* Reading progress on articles. */
  const bar = document.querySelector('.orwost-progress span'), article = document.querySelector('.orwost-article');
  if (bar && article) {
    const update = raf(() => { const r = article.getBoundingClientRect(); bar.style.setProperty('--p', Math.min(1, Math.max(0, -r.top / Math.max(1, r.height - innerHeight)))); });
    addEventListener('scroll', update, {passive: true}); update();
  }

  /* Lightbox for photo galleries and click-to-play videos (no third-party request before a click). */
  const box = document.createElement('dialog');
  box.className = 'orwost-lightbox';
  box.innerHTML = '<button type="button" class="lb-close" aria-label="Close">×</button><button type="button" class="lb-nav lb-prev" aria-label="Previous photo">←</button><button type="button" class="lb-nav lb-next" aria-label="Next photo">→</button><div class="lb-stage"></div><p class="lb-count" aria-live="polite"></p>';
  document.body.append(box);
  const stage = box.querySelector('.lb-stage'), count = box.querySelector('.lb-count');
  let set = [], at = 0;
  const show = i => {
    at = (i + set.length) % set.length;
    const a = set[at], img = document.createElement('img');
    img.src = a.href; img.alt = a.querySelector('img')?.alt || '';
    stage.replaceChildren(img);
    count.textContent = set.length > 1 ? `${at + 1} / ${set.length}` : '';
  };
  const navs = box.querySelectorAll('.lb-nav');
  box.querySelector('.lb-close').addEventListener('click', () => box.close());
  navs[0].addEventListener('click', () => show(at - 1));
  navs[1].addEventListener('click', () => show(at + 1));
  box.addEventListener('click', e => { if (e.target === box) box.close(); });
  box.addEventListener('close', () => stage.replaceChildren());
  box.addEventListener('keydown', e => { if (set.length > 1 && e.key === 'ArrowLeft') show(at - 1); if (set.length > 1 && e.key === 'ArrowRight') show(at + 1); });
  let touchX = null;
  box.addEventListener('touchstart', e => { touchX = e.touches[0].clientX; }, {passive: true});
  box.addEventListener('touchend', e => { if (touchX === null || set.length < 2) return; const dx = e.changedTouches[0].clientX - touchX; if (Math.abs(dx) > 50) show(at + (dx < 0 ? 1 : -1)); touchX = null; });
  const isImage = a => /\.(webp|jpe?g|png|gif)(\?|$)/i.test(a.getAttribute('href') || '');
  document.addEventListener('click', e => {
    const a = e.target.closest('a');
    if (!a || e.ctrlKey || e.metaKey || e.shiftKey || !box.showModal) return;
    if (a.closest('.orwost-video')) {
      let url; try { url = new URL(a.href); } catch { return; }
      const id = url.searchParams.get('v');
      if (!['www.youtube.com', 'youtube.com'].includes(url.hostname) || !/^[A-Za-z0-9_-]{11}$/.test(id || '')) return;
      e.preventDefault();
      const frame = document.createElement('iframe');
      frame.src = `https://www.youtube-nocookie.com/embed/${id}?autoplay=1&rel=0`;
      frame.title = a.querySelector('img')?.alt || 'ORWO Studios video';
      frame.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen'; frame.allowFullscreen = true;
      const wrap = document.createElement('div'); wrap.className = 'lb-frame'; wrap.append(frame);
      set = []; stage.replaceChildren(wrap); count.textContent = '';
      navs.forEach(n => n.hidden = true);
      box.showModal(); return;
    }
    const group = a.closest('.orwost-gallery,.set-media,.photo-marquee');
    if (!group || !isImage(a)) return;
    e.preventDefault();
    set = $$('a', group).filter(x => isImage(x) && !x.closest('[aria-hidden="true"]'));
    const original = a.closest('[aria-hidden="true"]') ? set.find(x => x.href === a.href) : a;
    navs.forEach(n => n.hidden = set.length < 2);
    show(Math.max(0, set.indexOf(original)));
    box.showModal();
  });

  /* Contact form: submitted in-page (form.getAttribute('action'): a hidden field named "action" shadows form.action). */
  $$('[data-orwost-form]').forEach(form => {
    const opened = performance.now();
    form.addEventListener('submit', async event => {
      event.preventDefault();
      const status = form.querySelector('.form-status'), button = form.querySelector('[type=submit]');
      form.querySelector('[data-elapsed]').value = Math.round(performance.now() - opened);
      $$('[aria-invalid]', form).forEach(x => x.removeAttribute('aria-invalid'));
      $$('.field-error', form).forEach(x => x.textContent = '');
      button.disabled = true; const label = button.textContent; button.textContent = 'Sending…';
      try {
        const response = await fetch(form.getAttribute('action'), {method: 'POST', body: new FormData(form), headers: {Accept: 'application/json'}, credentials: 'same-origin'});
        const data = await response.json();
        status.replaceChildren(); const msg = document.createElement('p');
        msg.className = data.ok ? 'form-success' : 'form-error'; msg.textContent = data.message; status.append(msg);
        for (const [key, message] of Object.entries(data.errors || {})) {
          const input = form.elements.namedItem('orwost_' + key);
          if (input) { input.setAttribute('aria-invalid', 'true'); input.closest('.field').querySelector('.field-error').textContent = message; }
        }
        if (data.ok) form.reset(); else form.querySelector('[aria-invalid]')?.focus();
      } catch {
        status.textContent = 'Your message could not be confirmed. Please try again or email hello@orwo.studio.';
      } finally { button.disabled = false; button.textContent = label; }
    });
  });
})();
