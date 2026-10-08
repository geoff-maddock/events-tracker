<!-- Global site tag (gtag.js) - Google Analytics -->
{{-- gtag.js costs over a second of main-thread time on a mid-range phone, so it
     loads after the page has settled: on the visitor's first interaction, or a few
     seconds after load, whichever comes first (#2302). gtag() calls made before then
     queue in dataLayer and are sent when it loads. Visits that leave within those
     first seconds without interacting aren't counted; that trade-off was accepted. --}}
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '{{ config('app.analytics') }}');

  (function () {
    var loaded = false;
    // Input events only, never 'scroll': page scripts scroll elements themselves
    // during load (FullCalendar does), which isn't interaction. Every way a visitor
    // scrolls starts with one of these: wheel, touch, keys, or a scrollbar drag
    // (pointerdown). On wide screens the page scrolls inside <main>, so they're
    // caught on document in the capture phase.
    var events = ['pointerdown', 'keydown', 'wheel', 'touchstart'];
    var options = { capture: true, passive: true };
    function load() {
      if (loaded) { return; }
      loaded = true;
      events.forEach(function (e) { document.removeEventListener(e, load, options); });
      var s = document.createElement('script');
      s.async = true;
      s.src = 'https://www.googletagmanager.com/gtag/js?id={{ config('app.analytics') }}';
      document.head.appendChild(s);
    }
    events.forEach(function (e) { document.addEventListener(e, load, options); });
    window.addEventListener('load', function () { setTimeout(load, 5000); });
  })();
</script>
