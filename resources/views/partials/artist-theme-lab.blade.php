{{-- Included only by the local/testing layout; never part of production UI. --}}
<details id="artist-theme-lab" class="artist-theme-lab">
    <summary><span class="artist-lab-dot" aria-hidden="true"></span><span data-artist-label>Artist themes</span><small>DEV</small></summary>
    <div class="artist-lab-body">
        <label for="artist-theme-select">Artist theme study</label>
        <select id="artist-theme-select" aria-describedby="artist-theme-note">
            <option value="">Original Arcane City</option>
        </select>
        <p id="artist-theme-note" data-artist-note aria-live="polite"></p>
        <div class="artist-lab-actions">
            <button type="button" data-artist-step="-1" aria-label="Previous artist theme">← Previous</button>
            <button type="button" data-artist-step="1" aria-label="Next artist theme">Next →</button>
        </div>
        <label class="artist-lab-check"><input type="checkbox" data-artist-flourishes checked> Decorative flourishes</label>
        <button type="button" data-artist-reset>Restore original theme</button>
        <small>Browser-only experiment · no account changes</small>
    </div>
</details>
