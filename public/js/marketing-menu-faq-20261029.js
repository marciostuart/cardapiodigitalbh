(() => {
  const header = document.querySelector('.marketing-header');
  const toggle = document.querySelector('.menu-toggle');
  const nav = document.querySelector('.header-nav');
  const setHeaderState = () => header?.classList.toggle('is-scrolled', window.scrollY > 18);
  setHeaderState(); window.addEventListener('scroll', setHeaderState, { passive: true });
  toggle?.addEventListener('click', () => { const open = header.classList.toggle('menu-open'); toggle.setAttribute('aria-expanded', String(open)); toggle.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu'); });
  nav?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => { header?.classList.remove('menu-open'); toggle?.setAttribute('aria-expanded', 'false'); }));
  const observer = new IntersectionObserver((entries) => entries.forEach((entry) => { if (entry.isIntersecting) { entry.target.classList.add('is-visible'); observer.unobserve(entry.target); } }), { threshold: .12 });
  document.querySelectorAll('.reveal').forEach((element) => observer.observe(element));
  document.querySelectorAll('.faq-list summary').forEach((summary) => summary.addEventListener('click', (event) => { const current = summary.closest('details'); const shouldOpen = !current.open; summary.closest('.faq-list')?.querySelectorAll('details[open]').forEach((other) => { other.open = false; }); current.open = shouldOpen; event.preventDefault(); }));
})();
