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
    var events = ['pointerdown', 'keydown', 'scroll', 'touchstart'];
    function load() {
      if (loaded) { return; }
      loaded = true;
      events.forEach(function (e) { window.removeEventListener(e, load); });
      var s = document.createElement('script');
      s.async = true;
      s.src = 'https://www.googletagmanager.com/gtag/js?id={{ config('app.analytics') }}';
      document.head.appendChild(s);
    }
    events.forEach(function (e) { window.addEventListener(e, load, { once: true, passive: true }); });
    window.addEventListener('load', function () { setTimeout(load, 5000); });
  })();
</script>
