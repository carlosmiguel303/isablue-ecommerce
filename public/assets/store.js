(() => {
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];
  const money = n => 'S/ ' + Number(n).toFixed(2);
  const storage = {
    get(k) { try { return sessionStorage.getItem(k); } catch { return null; } },
    set(k, v) { try { sessionStorage.setItem(k, v); } catch {} },
  };

  /* Toast */
  let toastTimer;
  function toast(message) {
    const el = $('#toast');
    if (!el) return;
    el.textContent = message;
    el.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => (el.hidden = true), 3500);
  }

  /* Cuentas regresivas */
  const timers = $$('[data-countdown]');
  if (timers.length) {
    const pad = n => String(n).padStart(2, '0');
    const tick = () => timers.forEach(el => {
      const left = Math.max(0, new Date(el.dataset.countdown) - Date.now());
      const s = Math.floor(left / 1000);
      $('[data-d]', el).textContent = pad(Math.floor(s / 86400));
      $('[data-h]', el).textContent = pad(Math.floor(s % 86400 / 3600));
      $('[data-m]', el).textContent = pad(Math.floor(s % 3600 / 60));
      $('[data-s]', el).textContent = pad(s % 60);
    });
    tick();
    setInterval(tick, 1000);
  }

  /* Slider del inicio */
  const slider = $('[data-slider]');
  if (slider) {
    const slides = $$('.hero-slide', slider), dots = $$('[data-slide]', slider);
    const pause = $('[data-slide-pause]', slider);
    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let current = 0, autoplay, paused = motion.matches, hovering = false;
    const show = i => {
      if (!slides.length) return;
      current = (i + slides.length) % slides.length;
      slides.forEach((s, n) => (s.hidden = n !== current));
      dots.forEach((d, n) => d.setAttribute('aria-pressed', String(n === current)));
    };
    const play = () => {
      clearInterval(autoplay);
      if (pause) {
        pause.textContent = paused ? 'Reanudar' : 'Pausar';
        pause.setAttribute('aria-label', paused ? 'Reanudar carrusel' : 'Pausar carrusel');
        pause.setAttribute('aria-pressed', String(paused));
      }
      if (slides.length > 1 && !paused && !hovering && !document.hidden && !slider.contains(document.activeElement)) {
        autoplay = setInterval(() => show(current + 1), 4000);
      }
    };
    dots.forEach(d => d.addEventListener('click', () => { show(Number(d.dataset.slide)); play(); }));
    $('[data-slide-prev]', slider)?.addEventListener('click', () => { show(current - 1); play(); });
    $('[data-slide-next]', slider)?.addEventListener('click', () => { show(current + 1); play(); });
    pause?.addEventListener('click', () => { paused = !paused; play(); });
    slider.addEventListener('focusin', play);
    slider.addEventListener('focusout', () => setTimeout(play, 0));
    slider.addEventListener('keydown', e => {
      if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
      e.preventDefault();
      show(current + (e.key === 'ArrowLeft' ? -1 : 1));
      play();
    });
    let touch;
    slider.addEventListener('touchstart', e => {
      touch = e.touches.length === 1 ? { x: e.touches[0].clientX, y: e.touches[0].clientY } : null;
    }, { passive: true });
    slider.addEventListener('touchend', e => {
      if (!touch || !e.changedTouches.length) return;
      const dx = e.changedTouches[0].clientX - touch.x, dy = e.changedTouches[0].clientY - touch.y;
      if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) { show(current + (dx < 0 ? 1 : -1)); play(); }
      touch = null;
    }, { passive: true });
    motion.addEventListener('change', () => { if (motion.matches) paused = true; play(); });
    document.addEventListener('visibilitychange', play);
    play();
  }

  /* Menú móvil y desplegables */
  const menuBtn = $('[data-menu-toggle]');
  const menu = $('[data-menu]');
  menuBtn?.addEventListener('click', () => {
    const open = menu.classList.toggle('open');
    menuBtn.setAttribute('aria-expanded', String(open));
  });
  $$('.nav-drop > button').forEach(btn => btn.addEventListener('click', () => {
    const drop = btn.parentElement;
    const open = !drop.classList.contains('open');
    $$('.nav-drop.open').forEach(d => d.classList.remove('open'));
    drop.classList.toggle('open', open);
    btn.setAttribute('aria-expanded', String(open));
  }));
  document.addEventListener('click', e => { if (!e.target.closest('.nav-drop')) $$('.nav-drop.open').forEach(d => d.classList.remove('open')); });

  /* Filtros plegados en móvil */
  const filter = $('.filters');
  if (filter && window.matchMedia('(max-width:900px)').matches) filter.open = false;

  /* Mini carrito lateral */
  const drawer = $('#cart-drawer');
  const backdrop = $('.drawer-backdrop');
  function renderDrawer(data) {
    const body = $('[data-drawer-lines]');
    body.replaceChildren();
    if (!data.lines.length) {
      const p = document.createElement('p');
      p.className = 'muted';
      p.textContent = 'Tu carrito está vacío. ¡Hay muchos juguetes esperándote!';
      body.append(p);
    }
    data.lines.forEach(l => {
      const row = document.createElement('div');
      row.className = 'drawer-line';
      const img = document.createElement('img');
      img.src = l.image; img.alt = ''; img.width = 64; img.height = 64;
      const info = document.createElement('div');
      const a = document.createElement('a');
      a.href = l.url; a.textContent = l.name;
      const qty = document.createElement('small');
      qty.textContent = 'Cantidad: ' + l.quantity;
      info.append(a, qty);
      const price = document.createElement('strong');
      price.textContent = money(l.total);
      row.append(img, info, price);
      body.append(row);
    });
    $('[data-drawer-subtotal]').textContent = money(data.subtotal);
    const free = $('[data-free]');
    if (data.missing_for_free !== null && data.lines.length) {
      free.hidden = false;
      $('[data-free-text]').textContent = data.missing_for_free > 0
        ? '¡Te faltan ' + money(data.missing_for_free) + ' para el envío gratis!'
        : '🎉 ¡Tienes envío gratis!';
      $('[data-free-bar]').style.width = Math.min(100, data.subtotal / data.free_from * 100) + '%';
    } else free.hidden = true;
    setCount(data.count);
  }
  function setCount(n) {
    $$('[data-cart-count]').forEach(e => { e.textContent = n; e.hidden = !n; e.classList.remove('bump'); void e.offsetWidth; e.classList.add('bump'); });
  }
  function openDrawer(data) {
    if (!drawer) return;
    if (data) renderDrawer(data);
    else fetch('/carrito/mini', { headers: { Accept: 'application/json' } }).then(r => r.json()).then(renderDrawer).catch(() => {});
    drawer.classList.add('open');
    drawer.setAttribute('aria-hidden', 'false');
    backdrop.hidden = false;
    document.body.style.overflow = 'hidden';
  }
  function closeDrawer() {
    drawer?.classList.remove('open');
    drawer?.setAttribute('aria-hidden', 'true');
    if (backdrop) backdrop.hidden = true;
    document.body.style.overflow = '';
  }
  $$('[data-cart-open]').forEach(a => a.addEventListener('click', e => {
    if (location.pathname === '/carrito' || location.pathname === '/checkout') return;
    e.preventDefault();
    openDrawer();
  }));
  $$('[data-drawer-close]').forEach(b => b.addEventListener('click', closeDrawer));

  /* Agregar al carrito sin recargar */
  $$('[data-add-cart]').forEach(form => form.addEventListener('submit', async event => {
    if (event.submitter?.name === 'buy_now') return; // "Comprar ahora" va directo al checkout
    event.preventDefault();
    const button = event.submitter || $('button', form);
    button.disabled = true;
    try {
      const res = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
      const data = await res.json();
      if (!res.ok) { toast(Object.values(data.errors || {}).flat()[0] || 'No se pudo agregar el juguete.'); return; }
      openDrawer(data);
    } catch {
      toast('No pudimos conectar. Inténtalo de nuevo.');
    } finally {
      button.disabled = false;
    }
  }));

  /* Selector de cantidad */
  $$('[data-qty]').forEach(box => {
    const input = $('input', box);
    $$('button', box).forEach(b => b.addEventListener('click', () => {
      const v = Number(input.value || 1) + Number(b.dataset.step);
      input.value = Math.max(Number(input.min || 1), Math.min(Number(input.max || 99), v));
    }));
  });

  /* Galería de producto */
  $$('[data-photo]').forEach(button => button.addEventListener('click', () => {
    const img = $('#detail-photo');
    img.src = button.dataset.photo;
    $$('[data-photo]').forEach(b => b.setAttribute('aria-current', String(b === button)));
  }));

  /* Checkout: total según la entrega elegida */
  $$('[data-shipping]').forEach(radio => radio.addEventListener('change', () => {
    const total = $('#checkout-total');
    const shipping = Number(radio.dataset.shipping);
    $('#shipping-total').textContent = radio.dataset.shippingLabel || (shipping ? money(shipping) : 'Gratis');
    total.textContent = money(Number(total.dataset.subtotal) + shipping);
    $$('[data-pay-amount]').forEach(e => (e.textContent = money(Number(total.dataset.subtotal) + shipping)));
  }));
  $('[data-shipping]:checked')?.dispatchEvent(new Event('change'));

  /* Copiar número de Yape */
  $$('[data-copy]').forEach(b => b.addEventListener('click', async () => {
    try { await navigator.clipboard.writeText(b.dataset.copy); toast('Número copiado'); } catch { toast(b.dataset.copy); }
  }));

  /* Nombre del archivo elegido */
  $$('.file-drop input[type=file]').forEach(input => input.addEventListener('change', () => {
    const label = $('[data-file-name]', input.closest('.file-drop'));
    if (label && input.files[0]) label.textContent = '✓ ' + input.files[0].name;
  }));

  /* Evitar doble envío */
  $$('form[data-once]').forEach(f => f.addEventListener('submit', () => {
    const b = $('button[type=submit], button:not([type])', f) || (f.id && $('button[form="' + f.id + '"]'));
    if (b) setTimeout(() => { b.disabled = true; b.textContent = 'Procesando…'; }, 0);
  }));

  /* Popup de oferta (una vez por sesión) */
  const popup = $('[data-popup]');
  if (popup && !storage.get('isablue_popup')) {
    setTimeout(() => { popup.hidden = false; storage.set('isablue_popup', '1'); }, Number(popup.dataset.delay || 4) * 1000);
    popup.addEventListener('click', e => { if (e.target === popup || e.target.closest('[data-popup-close]')) popup.hidden = true; });
  }
  document.addEventListener('keydown', e => {
    if (e.key !== 'Escape') return;
    if (popup) popup.hidden = true;
    closeDrawer();
  });

  /* Barra fija de compra en móvil */
  if ($('.sticky-buy')) document.body.classList.add('has-sticky');

  /* Sugerencias de búsqueda */
  let searchTimer, searchAbort;
  const search = $('#search');
  search?.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchAbort?.abort();
    const list = $('#search-suggestions');
    list.replaceChildren();
    if (search.value.trim().length < 2) return;
    searchTimer = setTimeout(async () => {
      try {
        searchAbort = new AbortController();
        const res = await fetch('/buscar/sugerencias?q=' + encodeURIComponent(search.value), { signal: searchAbort.signal });
        if (!res.ok) return;
        const data = await res.json();
        list.replaceChildren(...data.map(p => { const o = document.createElement('option'); o.value = p.name; return o; }));
      } catch {}
    }, 250);
  });
})();
