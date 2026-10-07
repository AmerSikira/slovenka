// Optional one-shot motion. The page is fully visible before this enhancement runs.
(() => {
  const gsap = window.gsap;
  const main = document.querySelector('main');
  if (!gsap || !main || !window.matchMedia || !window.IntersectionObserver) return;

  const selectors = [
    '.hero-copy > .eyebrow', '.hero-copy > h1', '.hero-copy > p:not(.eyebrow)',
    '.hero-copy > .hero-index', '.section-heading > div', '.intro-grid > div',
    '.quality-copy > .eyebrow', '.quality-copy > h2', '.quality-copy > p:not(.eyebrow)',
    '.manufacturing-heading > div',
    '.company-numbers h2', '.numbers-grid > div', '.offer-intro > div',
    '.offer-intro > p', '.offer-options article > div > h3', '.offer-options article > div > p',
    '.cooperation-steps li', '.project-next-step h2',
    '.project-next-step p'
  ];
  const targets = [...main.querySelectorAll(selectors.join(','))];
  if (!targets.length) return;

  const seen = new WeakSet();
  const media = gsap.matchMedia();
  media.add('(prefers-reduced-motion: no-preference)', (context) => {
    let active = true;
    const pending = new Set(targets.filter((target) => !seen.has(target)));

    // Observer callbacks run later; registering them keeps their tweens in the
    // matchMedia context so a preference change also reverts in-progress motion.
    context.add('reveal', (elements) => {
      if (!active || !elements.length) return;
      gsap.fromTo(elements, { y: 16, autoAlpha: 0.45 }, {
        y: 0,
        autoAlpha: 1,
        duration: 0.65,
        stagger: { amount: Math.min(0.14, elements.length * 0.035) },
        ease: 'power2.out',
        overwrite: 'auto',
        clearProps: 'transform,opacity,visibility'
      });
    });

    const observer = new IntersectionObserver((entries) => {
      if (!active) return;
      const entering = [];
      for (const entry of entries) {
        if (!entry.isIntersecting || !pending.has(entry.target)) continue;
        pending.delete(entry.target);
        seen.add(entry.target);
        observer.unobserve(entry.target);
        if (!entry.target.contains(document.activeElement)) entering.push(entry.target);
      }
      // Nothing is hidden while waiting: only these near-viewport elements move.
      context.reveal(entering);
      if (!pending.size) observer.disconnect();
    }, { rootMargin: '0px 0px 48px 0px', threshold: 0.04 });

    for (const target of pending) observer.observe(target);

    const finishFocused = (event) => {
      for (const target of targets) {
        if (!target.contains(event.target)) continue;
        seen.add(target);
        pending.delete(target);
        observer.unobserve(target);
        // Keyboard focus immediately gets the final, unobscured presentation.
        for (const tween of gsap.getTweensOf(target)) tween.progress(1);
      }
      if (!pending.size) observer.disconnect();
    };
    main.addEventListener('focusin', finishFocused);

    return () => {
      active = false;
      observer.disconnect();
      main.removeEventListener('focusin', finishFocused);
    };
  });

  // Revert on navigation (including bfcache); resumed pages stay fully visible.
  window.addEventListener('pagehide', () => media.revert(), { once: true });
})();
