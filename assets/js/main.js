/* ═══════════════════════════════════════════
   AdmissionSeason – Main v5
   ═══════════════════════════════════════════ */

function initApp() {
  const header = document.querySelector('.header');
  const scrollTop = document.getElementById('scrollTop');
  const mobileToggle = document.getElementById('mobileToggle');
  const navWrapper = document.getElementById('navWrapper');

  /* ─── Sticky Header (legacy + pro) ─── */
  window.addEventListener('scroll', () => {
    const y = window.scrollY;
    const hdr = document.querySelector('.header') || document.querySelector('.pro-header');
    hdr?.classList.toggle('scrolled', y > 10);
    scrollTop?.classList.toggle('visible', y > 400);
  }, { passive: true });
  scrollTop?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

  /* ─── Mobile Nav ─── */
  if (mobileToggle && navWrapper) {
    const toggle = (force) => {
      const open = force !== undefined ? force : navWrapper.classList.toggle('open');
      if (force !== undefined) navWrapper.classList.toggle('open', force);
      mobileToggle.querySelector('i').className = open ? 'ph ph-x' : 'ph ph-list';
    };
    mobileToggle.addEventListener('click', () => toggle());
    navWrapper.querySelectorAll('.nav-dd > .nav-link').forEach(l => {
      l.addEventListener('click', function (e) {
        if (window.innerWidth <= 900) { e.preventDefault(); this.closest('.nav-dd').classList.toggle('open'); }
      });
    });
    navWrapper.querySelectorAll('.nav-links > li:not(.nav-dd) .nav-link, .dd-menu a').forEach(l => {
      l.addEventListener('click', () => { if (window.innerWidth <= 900) toggle(false); });
    });
    document.addEventListener('click', e => { if (navWrapper.classList.contains('open') && !e.target.closest('.nav-container')) toggle(false); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') { navWrapper.classList.contains('open') && toggle(false); document.querySelector('.search-overlay')?.classList.remove('open'); } });
  }

  /* ─── Search Tabs (legacy + pro) ─── */
  document.querySelectorAll('.search-tab, .pro-search-tabs button').forEach(t => {
    t.addEventListener('click', () => {
      t.closest('.search-tabs, .pro-search-tabs')?.querySelectorAll('button, .search-tab').forEach(s => s.classList.remove('active'));
      t.classList.add('active');
    });
  });

  /* ─── Search ─── */
  window.handleSearch = function (e) {
    if (e) e.preventDefault();
    const q = document.getElementById('heroSearch')?.value?.trim();
    const state = document.getElementById('heroState')?.value;
    if (!q && !state) return false;
    const p = new URLSearchParams();
    if (q) p.set('q', q);
    if (state) p.set('state', state);
    window.location.href = '/search?' + p.toString();
    return false;
  };
  document.getElementById('heroSearch')?.addEventListener('keydown', e => { if (e.key === 'Enter') handleSearch(e); });

  /* ─── Animated Counters ─── */
  const counters = document.querySelectorAll('.counter');
  if (counters.length) {
    const go = (el) => {
      const t = parseInt(el.dataset.target, 10);
      if (!t || isNaN(t)) { el.textContent = '0'; return; }
      const dur = 1400, start = performance.now();
      const step = (n) => { const p = Math.min((n - start) / dur, 1); el.textContent = Math.floor((1 - Math.pow(1 - p, 3)) * t); if (p < 1) requestAnimationFrame(step); };
      requestAnimationFrame(step);
    };
    const obs = new IntersectionObserver((entries) => {
      entries.forEach(e => { if (e.isIntersecting) { go(e.target); obs.unobserve(e.target); } });
    }, { threshold: 0.5 });
    counters.forEach(c => obs.observe(c));
  }

  /* ─── Scroll Reveal ─── */
  const reveals = document.querySelectorAll('.reveal');
  if (reveals.length) {
    const obs = new IntersectionObserver((entries) => {
      entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); } });
    }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });
    reveals.forEach(el => obs.observe(el));
  }

  /* ─── Smooth Scroll ─── */
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', function (e) {
      const h = this.getAttribute('href');
      if (!h || h === '#') return;
      e.preventDefault();
      const t = document.querySelector(h);
      if (t) t.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });

  /* ─── Newsletter ─── */
  window.handleNewsletter = function (e) {
    e.preventDefault();
    const input = e.target.querySelector('input');
    if (input?.value?.trim()) { toast('Thank you! You are now subscribed.'); input.value = ''; }
    return false;
  };

  /* ─── Toast ─── */
  function toast(msg) {
    const old = document.querySelector('.toast-msg');
    if (old) old.remove();
    const el = document.createElement('div');
    el.className = 'toast-msg';
    el.textContent = msg;
    el.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(80px);background:#0B2447;color:#fff;padding:14px 28px;border-radius:12px;font-size:.88rem;font-weight:500;z-index:9999;opacity:0;transition:all .4s cubic-bezier(.34,1.56,.64,1);box-shadow:0 8px 32px rgba(0,0,0,.4);max-width:90vw;text-align:center;font-family:"Space Grotesk",sans-serif;border:1px solid rgba(79,140,255,.2)';
    document.body.appendChild(el);
    requestAnimationFrame(() => { el.style.opacity = '1'; el.style.transform = 'translateX(-50%) translateY(0)'; });
    setTimeout(() => { el.style.opacity = '0'; el.style.transform = 'translateX(-50%) translateY(80px)'; setTimeout(() => el.remove(), 400); }, 3000);
  }

  /* ─── College Card Actions ─── */
  document.querySelectorAll('.uni-card .btn-outline').forEach(b => {
    b.addEventListener('click', function (e) {
      e.stopPropagation();
      const name = this.closest('.uni-card-body')?.querySelector('h3')?.textContent || 'College';
      toast('Brochure download coming soon for: ' + name);
    });
  });
  document.querySelectorAll('.uni-card .btn-primary').forEach(b => {
    b.addEventListener('click', function (e) {
      e.stopPropagation();
      const name = this.closest('.uni-card-body')?.querySelector('h3')?.textContent || 'College';
      toast('Application form coming soon for: ' + name);
    });
  });

  /* ─── Table row click ─── */
  document.querySelectorAll('.rank-table tbody tr').forEach(row => {
    row.addEventListener('click', function () { this.querySelector('.action-cell a')?.click(); });
  });

}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initApp);
} else {
  initApp();
}
