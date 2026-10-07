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
    '.project-next-step h2',
    '.project-next-step p'
  ];
  // The cooperation sequence owns its heading and steps as one choreography.
  const targets = [...main.querySelectorAll(selectors.join(','))]
    .filter((target) => !target.closest('.cooperation-section'));
  const cooperation = main.querySelector('.cooperation-section');
  if (!targets.length && !cooperation) return;

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

  if (cooperation) {
    media.add({
      desktop: '(min-width: 768px)',
      mobile: '(max-width: 767px)',
      reduceMotion: '(prefers-reduced-motion: reduce)'
    }, (context) => {
      if (context.conditions.reduceMotion || seen.has(cooperation)) return;
      let active = true;
      let sequence;
      const steps = [...cooperation.querySelectorAll('.cooperation-steps li')];
      const axis = context.conditions.desktop ? 'scaleX' : 'scaleY';

      context.add('revealCooperation', () => {
        if (!active || seen.has(cooperation)) return;
        seen.add(cooperation);
        observer.disconnect();
        if (cooperation.contains(document.activeElement)) return;

        // Create only on entry: the section stays readable while off screen.
        sequence = gsap.timeline({
          defaults: { duration: 0.5, ease: 'power2.out', overwrite: 'auto' }
        });
        sequence.fromTo(cooperation.querySelectorAll('.section-heading > div, .cooperation-intro'),
          { y: 12, autoAlpha: 0.45 },
          { y: 0, autoAlpha: 1, stagger: 0.08, clearProps: 'transform,opacity,visibility' }, 0);

        steps.forEach((step, index) => {
          const start = 0.12 + index * 0.36;
          sequence.fromTo(step.querySelector('.step-number'),
            { scale: 0.78, autoAlpha: 0.4 },
            { scale: 1, autoAlpha: 1, clearProps: 'transform,opacity,visibility' }, start);
          sequence.fromTo(step.querySelectorAll('h3, p'),
            { y: 18, autoAlpha: 0.35 },
            { y: 0, autoAlpha: 1, stagger: 0.07, clearProps: 'transform,opacity,visibility' }, start + 0.08);
          const progress = step.querySelector('.step-progress');
          if (progress) {
            sequence.fromTo(progress, { [axis]: 0 },
              { [axis]: 1, duration: 0.55, ease: 'power1.inOut', clearProps: 'transform' }, start + 0.18);
          }
        });
      });

      const observer = new IntersectionObserver((entries) => {
        if (entries.some((entry) => entry.isIntersecting)) context.revealCooperation();
      }, { rootMargin: '0px 0px 32px 0px', threshold: 0.08 });
      observer.observe(cooperation.querySelector('.cooperation-steps'));

      const finishFocused = () => {
        seen.add(cooperation);
        observer.disconnect();
        // A focus jump presents all steps immediately, including the CTA.
        if (sequence) sequence.progress(1);
      };
      cooperation.addEventListener('focusin', finishFocused);
      return () => {
        active = false;
        observer.disconnect();
        cooperation.removeEventListener('focusin', finishFocused);
      };
    });
  }

  // Revert on navigation (including bfcache); resumed pages stay fully visible.
  window.addEventListener('pagehide', () => media.revert(), { once: true });
})();
