(() => {
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];

  // Previsualiza las imágenes locales sin enviarlas hasta guardar.
  $$('[data-banner-files]').forEach(input => {
    let urls = [];
    input.addEventListener('change', () => {
      urls.forEach(URL.revokeObjectURL);
      urls = [];
      const box = input.closest('form').querySelector('[data-banner-previews]');
      box.replaceChildren();
      const files = [...input.files];
      input.setCustomValidity(files.length > 5 ? 'Elige hasta 5 imágenes por carga.' : '');
      files.slice(0, 5).forEach(file => {
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
          input.setCustomValidity('Usa imágenes JPG, PNG o WebP de hasta 5 MB.');
          return;
        }
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.alt = file.name;
        urls.push(img.src);
        box.append(img);
      });
    });
  });

  // Menú lateral en móvil
  const sidebar = document.querySelector('[data-sidebar]');
  document.querySelector('[data-sidebar-toggle]')?.addEventListener('click', e => { e.stopPropagation(); sidebar.classList.toggle('open'); });
  document.addEventListener('click', e => { if (sidebar?.classList.contains('open') && !e.target.closest('[data-sidebar]')) sidebar.classList.remove('open'); });

  // Confirmaciones antes de acciones importantes
  $$('form[data-confirm]').forEach(f => f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));

  // Selección múltiple de pedidos para confirmar Yape
  const boxes = $$('input[name="ids[]"]');
  const counter = document.querySelector('[data-selected-count]');
  const refresh = () => { if (counter) { const n = boxes.filter(b => b.checked).length; counter.textContent = n + (n === 1 ? ' seleccionado' : ' seleccionados'); } };
  boxes.forEach(b => b.addEventListener('change', refresh));
  document.querySelector('[data-check-all]')?.addEventListener('change', e => { boxes.forEach(b => (b.checked = e.target.checked)); refresh(); });

  // Evitar doble clic en formularios
  $$('form').forEach(f => f.addEventListener('submit', e => {
    if (e.defaultPrevented) return;
    const b = f.querySelector('button:not([type=button])');
    if (b) setTimeout(() => (b.disabled = true), 0);
  }));
})();

// Aviso de pedidos nuevos: consulta cada minuto mientras el panel está abierto.
(() => {
  let lastId = null;
  const baseTitle = document.title;
  const check = async () => {
    try {
      const res = await fetch('/admin/pedidos/ultimo', { headers: { Accept: 'application/json' } });
      if (!res.ok) return;
      const d = await res.json();
      if (lastId !== null && d.id && d.id > lastId) {
        document.title = '🔔 (' + d.awaiting + ') Nuevo pedido · ' + baseTitle;
        const bar = document.createElement('a');
        bar.className = 'new-order-alert';
        bar.href = '/admin/pedidos/' + d.id;
        bar.textContent = '🔔 ¡Nuevo pedido ' + d.reference + '! ' + d.text + ' — toca para verlo';
        document.body.append(bar);
        try { const ctx = new AudioContext(); const o = ctx.createOscillator(); o.frequency.value = 880; o.connect(ctx.destination); o.start(); o.stop(ctx.currentTime + 0.25); } catch {}
      }
      if (d.id) lastId = Math.max(lastId ?? 0, d.id);
    } catch {}
  };
  check();
  setInterval(check, 60000);
})();
