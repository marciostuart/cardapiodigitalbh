(() => {
  const header = document.querySelector('.marketing-header');
  const toggle = document.querySelector('.menu-toggle');
  const nav = document.querySelector('.header-nav');
  const setHeaderState = () => header?.classList.toggle('is-scrolled', window.scrollY > 18);
  setHeaderState(); window.addEventListener('scroll', setHeaderState, { passive: true });
  toggle?.addEventListener('click', () => { const open = header.classList.toggle('menu-open'); toggle.setAttribute('aria-expanded', String(open)); });
  nav?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => { header?.classList.remove('menu-open'); toggle?.setAttribute('aria-expanded', 'false'); }));
  const observer = new IntersectionObserver((entries) => entries.forEach((entry) => { if (entry.isIntersecting) { entry.target.classList.add('is-visible'); observer.unobserve(entry.target); } }), { threshold: .12 });
  document.querySelectorAll('.reveal').forEach((element) => observer.observe(element));
  document.querySelectorAll('details').forEach((item) => item.addEventListener('toggle', () => item.closest('.faq-list')?.querySelectorAll('details[open]').forEach((other) => { if (other !== item) other.removeAttribute('open'); })));
})();
