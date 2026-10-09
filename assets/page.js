(function () {
  'use strict';
  function init(page) {
    if (page.dataset.blcPageReady) return;
    page.dataset.blcPageReady = '1';
    const exam = page.querySelector('[data-blc-exam]');
    const app = page.querySelector('.blc-pt-app');
    if (!exam || !app) return;
    page.querySelectorAll('[data-blc-go]').forEach(function (link) {
      link.addEventListener('click', function (event) {
        event.preventDefault();
        exam.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
        const target = app.querySelector('[data-start], .blc-pt-question, .blc-pt-results h2');
        if (target) {
          if (!target.hasAttribute('tabindex') && target.tagName !== 'BUTTON') target.setAttribute('tabindex', '-1');
          target.focus({ preventScroll: true });
        }
      });
    });
    let previousState = '';
    function update() {
      const result = !!app.querySelector('.blc-pt-results');
      const state = result ? 'result' : app.querySelector('.blc-pt-question') ? 'test' : 'ready';
      page.dataset.blcPhase = state;
      const status = page.querySelector('[data-blc-state]');
      if (status) status.textContent = { ready: 'BAŞLAMAYA HAZIR', test: 'TEST DEVAM EDİYOR', result: 'TEST TAMAMLANDI' }[state];
      page.querySelectorAll('[data-blc-stage]').forEach(function (stage) {
        const current = stage.dataset.blcStage === (result ? 'result' : 'test');
        if (current) stage.setAttribute('aria-current', 'step');
        else stage.removeAttribute('aria-current');
        stage.classList.toggle('is-complete', result && stage.dataset.blcStage === 'test');
      });
      if (state === 'test' && previousState === 'ready') {
        exam.scrollIntoView({ behavior: 'auto', block: 'start' });
        const heading = app.querySelector('.blc-pt-question');
        if (heading) heading.focus({ preventScroll: true });
      }
      previousState = state;
    }
    update();
    new MutationObserver(update).observe(app, { childList: true, subtree: true });
  }
  function boot() { document.querySelectorAll('[data-blc-page]').forEach(init); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
}());
