// Optional one-shot motion. The page is fully visible before this enhancement runs.
(() => {
  const gsap = window.gsap;
  const main = document.querySelector('main');
  if (!gsap || !main || !window.matchMedia || !window.IntersectionObserver) return;

  const selectors = [
    '.hero-copy > .eyebrow', '.hero-copy > h1', '.hero-copy > p:not(.eyebrow)',
    '.hero-copy > .hero-index', '.section-heading > div',
    '.quality-copy > .eyebrow', '.quality-copy > h2', '.quality-copy > p:not(.eyebrow)',
    '.manufacturing-heading > div',
    '.company-numbers h2', '.numbers-grid > div', '.offer-intro > div',
    '.offer-intro > p', '.offer-options article > div > h3', '.offer-options article > div > p',
    '.cooperation-intro',
    '.project-next-step h2',
    '.project-next-step p'
  ];
  // The cooperation cards own their entrances to avoid competing motion.
  const targets = [...main.querySelectorAll(selectors.join(','))]
    .filter((target) => !target.closest('.cooperation-steps'));
  const cooperation = main.querySelector('.cooperation-section');
  if (!targets.length && !cooperation) return;

  const seen = new WeakSet();
  const counters = [...main.querySelectorAll('[data-count-to]')];
  const finalNumbers = new Map(counters.map((element) => [element, element.textContent]));
  const formatter = new Intl.NumberFormat(document.documentElement.lang === 'en' ? 'en-GB' : 'bs-BA');
  const addCount = (timeline, element, position = 0) => {
    const finalText = finalNumbers.get(element);
    const value = { number: 0 };
    const suffix = finalText.replace(/[\d.,]/g, '');
    const format = (number) => element.dataset.countPad
      ? String(number).padStart(Number(element.dataset.countPad), '0')
      : formatter.format(number);
    timeline.to(value, {
      number: Number(element.dataset.countTo),
      duration: element.dataset.countPad ? 0.9 : 1.6,
      ease: 'power1.out',
      onStart: () => { element.textContent = format(0) + suffix; },
      onUpdate: () => { element.textContent = format(Math.floor(value.number)) + suffix; },
      onComplete: () => { element.textContent = finalText; }
    }, position);
  };
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
      for (const element of elements) {
        const numbers = element.querySelectorAll('[data-count-to]');
        if (!numbers.length) continue;
        const count = gsap.timeline();
        for (const number of numbers) addCount(count, number);
      }
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
      for (const number of counters.filter((element) => !element.closest('.cooperation-section'))) {
        number.textContent = finalNumbers.get(number);
      }
    };
  });

  if (cooperation) {
    media.add('(prefers-reduced-motion: no-preference)', (context) => {
      let active = true;
      const steps = [...cooperation.querySelectorAll('.cooperation-steps li')];
      const pending = new Set(steps.filter((step) => !seen.has(step)));
      const sequences = new Map();
      gsap.set([...pending], { y: 28, autoAlpha: 0 });

      context.add('revealCards', (entering) => {
        if (!active || !entering.length) return;
        const sequence = gsap.timeline({
          defaults: { duration: 0.5, ease: 'power2.out', overwrite: 'auto' }
        });
        entering.forEach((step, index) => {
          sequences.set(step, sequence);
          const start = index * 0.14;
          sequence.to(step,
            { y: 0, autoAlpha: 1, duration: 0.7, clearProps: 'transform,opacity,visibility' }, start);
          sequence.fromTo(step.querySelector('.step-number'),
            { scale: 0.88 },
            { scale: 1, clearProps: 'transform' }, start);
          addCount(sequence, step.querySelector('[data-count-to]'), start);
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
          entering.push(entry.target);
        }
        context.revealCards(entering);
        if (!pending.size) observer.disconnect();
      }, { rootMargin: '0px 0px -48px 0px', threshold: 0.08 });
      for (const step of pending) observer.observe(step);

      const finishFocused = () => {
        observer.disconnect();
        for (const sequence of new Set(sequences.values())) sequence.progress(1);
        pending.clear();
        for (const step of steps) seen.add(step);
        gsap.set(steps, { clearProps: 'transform,opacity,visibility' });
      };
      cooperation.addEventListener('focusin', finishFocused);
      return () => {
        active = false;
        observer.disconnect();
        cooperation.removeEventListener('focusin', finishFocused);
        for (const number of cooperation.querySelectorAll('[data-count-to]')) {
          number.textContent = finalNumbers.get(number);
        }
      };
    });
  }

  // Revert on navigation (including bfcache); resumed pages stay fully visible.
  window.addEventListener('pagehide', () => media.revert(), { once: true });
})();
