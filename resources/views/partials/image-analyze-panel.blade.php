{{--
    Image upload + optional AI analysis panel.

    Sits ABOVE the create form. The file input is deliberately outside the
    form and is never submitted: the image is stashed server-side as soon as
    it is chosen and the form carries only the hidden image_temp_token, which
    (unlike a file input) survives a validation bounce via old().

    Required:  $context  one of event|entity|series
    Optional:  $title, $help, $formSelector
--}}
@php
    $panelContext = $context ?? 'event';
    $panelTitle = $title ?? 'Image';
    $panelHelp = $help ?? 'Upload a primary image here and optionally use the "Analyze Image" button to pre-fill the form with details extracted from it.';
    $panelForm = $formSelector ?? 'form';
    $panelHasStashed = (bool) old('image_temp_token');
@endphp

<div class="bg-card rounded-lg border border-border shadow-sm p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-foreground flex items-center gap-2">
            <i class="bi bi-image-fill text-primary"></i>
            {{ $panelTitle }}
        </h2>
    </div>

    <div id="image-analyze-panel"
        data-context="{{ $panelContext }}"
        data-analyze-url="{{ route('images.analyze') }}"
        data-stash-url="{{ route('images.stash') }}"
        data-form-selector="{{ $panelForm }}"
        data-has-stashed="{{ $panelHasStashed ? '1' : '0' }}">

        <div id="image-drop-zone"
            class="border-2 border-dashed border-border rounded-lg p-8 text-center cursor-pointer hover:border-primary/50 transition-colors mb-4">
            <input type="file" id="image-file-input" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">

            <div id="image-drop-content" class="{{ $panelHasStashed ? 'hidden' : '' }}">
                <i class="bi bi-cloud-upload text-4xl text-muted-foreground mb-2 block"></i>
                <p class="text-muted-foreground text-sm">Drag &amp; drop an image here, or</p>
                <button type="button" id="image-browse-btn"
                    class="mt-2 inline-flex items-center px-3 py-1.5 bg-primary text-primary-foreground text-sm rounded-md hover:bg-primary/90 transition-colors">
                    <i class="bi bi-folder2-open mr-1.5"></i>
                    Browse File
                </button>
                <p class="text-xs text-muted-foreground mt-2">Supports JPEG, PNG, GIF, WebP — max 10 MB</p>
            </div>

            <div id="image-preview-content" class="{{ $panelHasStashed ? '' : 'hidden' }}">
                <div id="image-preview-wrap" class="relative inline-block mb-2">
                    <img id="image-preview-img" src="" alt="Image preview"
                        class="max-h-48 mx-auto rounded {{ $panelHasStashed ? 'hidden' : 'block' }}">
                    {{-- Scan overlay: shown only while analysis is running --}}
                    <div id="image-scan-overlay" class="hidden absolute inset-0 rounded overflow-hidden pointer-events-none">
                        <div class="absolute inset-0 bg-black/30"></div>
                        <div class="image-scan-line"></div>
                    </div>
                </div>
                <p id="image-preview-name" class="text-sm text-muted-foreground">
                    {{ $panelHasStashed ? 'Your uploaded image is still attached.' : '' }}
                </p>
                <div class="mt-2 flex items-center justify-center gap-4">
                    <button type="button" id="image-clear-btn"
                        class="text-sm text-muted-foreground hover:text-foreground underline">
                        Choose a different image
                    </button>
                    {{-- The image attaches on save whether or not it was analysed,
                         so there has to be a way to decide against attaching one. --}}
                    <button type="button" id="image-remove-btn"
                        class="text-sm text-muted-foreground hover:text-foreground underline">
                        Remove image
                    </button>
                </div>
            </div>
        </div>

        <p class="text-sm text-muted-foreground mb-4">{{ $panelHelp }}</p>

        <div id="image-status" class="img-analyze-status hidden"></div>

        <div class="flex items-center gap-3">
            <button type="button" id="analyze-image-btn"
                class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                disabled>
                <i class="bi bi-stars mr-2"></i>
                <span id="analyze-btn-text">Analyze Image</span>
            </button>
            <span id="analyze-status" role="status" aria-live="polite" class="sr-only"></span>
        </div>
    </div>
</div>
