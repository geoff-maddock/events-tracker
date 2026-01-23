<footer class="mt-8">
    <div class="flex flex-wrap gap-2">
        @if (config('app.social_facebook') !== "")
            <a href="{{ config('app.social_facebook') }}"
               class="inline-flex items-center justify-center w-10 h-10 rounded-full text-muted-foreground hover:text-primary hover:bg-muted transition-colors"
               target="_blank"
               title="Facebook">
                <i class="bi bi-facebook text-xl"></i>
            </a>
        @endif

        @if (config('app.social_twitter') !== "")
            <a href="{{ config('app.social_twitter') }}"
               class="inline-flex items-center justify-center w-10 h-10 rounded-full text-muted-foreground hover:text-primary hover:bg-muted transition-colors"
               target="_blank"
               title="Twitter">
                <i class="bi bi-twitter text-xl"></i>
            </a>
        @endif

        @if (config('app.social_instagram') !== "")
            <a href="{{ config('app.social_instagram') }}"
               class="inline-flex items-center justify-center w-10 h-10 rounded-full text-muted-foreground hover:text-primary hover:bg-muted transition-colors"
               target="_blank"
               title="Instagram">
                <i class="bi bi-instagram text-xl"></i>
            </a>
        @endif

        @if (config('app.social_github') !== "")
            <a href="{{ config('app.social_github') }}"
               class="inline-flex items-center justify-center w-10 h-10 rounded-full text-muted-foreground hover:text-primary hover:bg-muted transition-colors"
               target="_blank"
               title="Github">
                <i class="bi bi-github text-xl"></i>
            </a>
        @endif
    </div>
</footer>
