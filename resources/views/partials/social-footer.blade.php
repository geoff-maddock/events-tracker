<footer>
  <!-- Grid container -->
  <div class="float-start">
    <!-- Section: Social media -->
    <section class="mb-4">
        @if (config('app.social_facebook') !== "")
        <!-- Facebook -->
        <a
            class="btn btn-link btn-floating btn-lg m-1"
            href="{{ config('app.social_facebook') }}"
            role="button"
            target="_"
            title="Facebook"
            ><i class="bi bi-facebook"></i></a>
        @endif
        
        @if (config('app.social_twitter') !== "")
      <!-- Twitter -->
      <a
        class="btn btn-link btn-floating btn-lg m-1"
        href="{{ config('app.social_twitter') }}"
        role="button"
        target="_"
        title="Twitter"
        ><i class="bi bi-twitter"></i
      ></a>
      @endif
      @if (config('app.social_instagram') !== "")
      <!-- Instagram -->
      <a
        class="btn btn-link btn-floating btn-lg m-1"
        href="{{ config('app.social_instagram') }}"
        role="button"
        target="_"
        title="Instagram"
        ><i class="bi bi-instagram"></i
      ></a>
      @endif
      @if (config('app.social_github') !== "")
      <!-- Github -->
      <a
        class="btn btn-link btn-floating btn-lg m-1"
        href="{{ config('app.social_github') }}"
        role="button"
        target="_"
        title="Github"
        ><i class="bi bi-github"></i
      ></a>
      @endif
    </section>
    <!-- Section: Social media -->
  </div>
  <!-- Grid container -->

</footer>