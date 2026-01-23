@props([
    'id' => '',
    'title' => '',
    'size' => 'md', // sm, md, lg, xl, full
])

@php
$sizeClasses = [
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    '2xl' => 'max-w-2xl',
    'full' => 'max-w-full',
];

$sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<!-- Modal Backdrop -->
<div id="{{ $id }}"
     class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
     role="dialog"
     aria-modal="true"
     aria-labelledby="{{ $id }}-title">

    <!-- Modal Container -->
    <div class="bg-card rounded-lg shadow-xl {{ $sizeClass }} w-full max-h-[90vh] flex flex-col border border-border"
         @click.stop>

        <!-- Modal Header -->
        @if($title)
        <div class="flex items-center justify-between p-4 border-b border-border">
            <h2 id="{{ $id }}-title" class="text-lg font-semibold text-foreground">{{ $title }}</h2>
            <button type="button"
                    class="p-1 rounded-md hover:bg-accent text-muted-foreground hover:text-foreground transition-colors"
                    onclick="document.getElementById('{{ $id }}').classList.add('hidden')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        @endif

        <!-- Modal Body -->
        <div class="flex-1 overflow-y-auto p-6">
            {{ $slot }}
        </div>

        <!-- Modal Footer (optional) -->
        @isset($footer)
        <div class="p-4 border-t border-border flex justify-end gap-2">
            {{ $footer }}
        </div>
        @endisset
    </div>
</div>

<!-- JavaScript to handle modal -->
<script>
    // Close modal when clicking backdrop
    document.getElementById('{{ $id }}')?.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.getElementById('{{ $id }}')?.classList.add('hidden');
        }
    });
</script>
