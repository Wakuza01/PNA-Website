(function () {
  'use strict';

  var TRANSITION = 650;  // ms for width collapse / expand
  var HOLD       = 3200; // ms each word is displayed

  function initLoop(wrap) {
    var words;
    try { words = JSON.parse(wrap.dataset.words); } catch (e) { return; }
    if (!words || words.length < 2) return;

    var loop   = wrap.querySelector('.tw-loop');
    var textEl = wrap.querySelector('.tw-text');
    if (!loop || !textEl) return;

    var index = 0;

    /* Measure the natural rendered width of a given string */
    function measure(text) {
      loop.style.transition = 'none';
      loop.style.opacity    = '0';
      loop.style.maxWidth   = '2000px';
      textEl.textContent    = text;
      var w = loop.scrollWidth;
      loop.style.maxWidth   = '0px';
      return w;
    }

    /* Set initial visible state */
    loop.style.overflow  = 'hidden';
    loop.style.maxWidth  = measure(words[0]) + 'px';
    loop.style.opacity   = '1';
    loop.style.transition = 'none';

    function cycleTo(nextIndex) {
      /* --- EXIT: collapse width + fade --- */
      loop.style.transition =
        'max-width ' + TRANSITION + 'ms cubic-bezier(0.4,0,0.2,1),' +
        'opacity '   + Math.round(TRANSITION * 0.55) + 'ms ease';
      loop.style.maxWidth = '0px';
      loop.style.opacity  = '0';

      setTimeout(function () {
        /* Measure new word while invisible */
        var newW = measure(words[nextIndex]);
        index = nextIndex;

        /* --- ENTER: expand + fade in --- */
        requestAnimationFrame(function () {
          requestAnimationFrame(function () {
            var fadeDelay = Math.round(TRANSITION * 0.25);
            loop.style.transition =
              'max-width ' + TRANSITION + 'ms cubic-bezier(0.4,0,0.2,1),' +
              'opacity '   + Math.round(TRANSITION * 0.55) + 'ms ease ' + fadeDelay + 'ms';
            loop.style.maxWidth = newW + 'px';
            loop.style.opacity  = '1';
          });
        });
      }, TRANSITION + 50);
    }

    /* Wait for page-load hero animation to finish before starting loop */
    setTimeout(function () {
      setInterval(function () {
        cycleTo((index + 1) % words.length);
      }, HOLD);
    }, 2200);
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.tw-wrap').forEach(initLoop);
  });
})();