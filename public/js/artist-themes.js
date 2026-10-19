/** Dev-only theme lab. Included by the layout ONLY in local/testing environments.
 * Run synchronously in <head> so a saved palette applies before the first paint.
 * Original artwork is not embedded; the flourishes are small original SVG studies.
 */
(function () {
    'use strict';
    const themes = [
        { id: 'joe-taylor', name: 'Joe Taylor', title: 'Living garden', mode: 'light', note: 'Acid pastels, elastic borders and little botanical familiars. JT-02, JT-04, JT-10.' },
        { id: 'flesh-from-the-acid', name: 'Flesh from the Acid', title: 'Electric relic', mode: 'dark', note: 'Bone ink, rust-red accents, chain-like rules and scratched print. FFA-01, FFA-04, FFA-05.' },
        { id: 'tuukzs', name: 'Tuukzs', title: 'Spirit room', mode: 'dark', note: 'Violet atmosphere, luminous filaments and soft spectral rings. TKZ-01, TKZ-03, TKZ-05.' },
        { id: 'fuuurio', name: 'Fuuurio', title: 'Chrome garden', mode: 'dark', note: 'Iridescent trim, jewel highlights and a fine wireframe horizon. FUR-01, FUR-03, FUR-04.' },
        { id: 'noirmatic', name: 'Noirmatic', title: 'Ritual index', mode: 'dark', note: 'Olive-black codex, artifact frames, geometric seals and measured spacing. NMR-01–11.' },
        { id: 'redaderr', name: 'Redaderr', title: 'Feedback friend', mode: 'light', note: 'Dusty pink, sage, offset ink and an equipment-creature cameo. RED-01, RED-05.' },
        { id: 'indecent-ink', name: 'Indecent Ink', title: 'Bone architecture', mode: 'dark', note: 'Monochrome engraving, radial structures and fine double borders. INK-01–08.' },
        { id: 'hollowed-graphix', name: 'Hollowed Graphix', title: 'After-hours portal', mode: 'dark', note: 'Green fog, amethyst shadows, arched doorways and distant guardians. HGX-01–05.' },
        { id: 'doomkitsch', name: 'Doomkitsch', title: 'Candy dossier', mode: 'light', note: 'Candy lilac, peach, stippled panels and a lumpy little grotesque. DMK-01–04.' },
        { id: 'infiniteyay', name: 'Infiniteyay', title: 'Recursive desktop', mode: 'light', note: 'Lavender desktop windows, nested frames and jewel-colored fragments. INF-01, INF-04, INF-08.' },
        { id: 'high-road-design', name: 'The High Road Design', title: 'Midnight ephemera', mode: 'light', note: 'Warm paper, ochre, comic ink and a friendly sound-system mascot. HRD-01, HRD-02, HRD-12.' },
        { id: 'horriblparty', name: 'Horriblparty', title: 'Friendly haunt', mode: 'light', note: 'Flat lemon, pink and blue, bold outlines and a sleepy living building. HBP-02, HBP-11, HBP-13.' },
        { id: 'dasha-plesen', name: 'Dasha Plesen', title: 'Culture dish', mode: 'light', note: 'Ivory specimen fields, coral colonies, turquoise channels and scalloped edges. DPL-02, DPL-07, DPL-15.' },
    ];
    const root = document.documentElement;
    const key = 'arcane-city:artist-theme:v1';
    const detailsKey = key + ':flourishes';
    const read = (name) => { try { return localStorage.getItem(name); } catch { return null; } };
    const write = (name, value) => { try { localStorage.setItem(name, value); } catch { /* Session-only preview when storage is blocked. */ } };
    const originalMode = root.classList.contains('dark') ? 'dark' : 'light';
    const query = new URLSearchParams(window.location.search);
    let selected = query.has('artist-theme') ? query.get('artist-theme') : read(key);
    let flourishes = read(detailsKey) !== 'off';
    const find = (id) => themes.find((theme) => theme.id === id);
    if (!find(selected)) selected = '';

    function apply(id) {
        const theme = find(id);
        selected = theme ? theme.id : '';
        if (theme) {
            root.dataset.artistTheme = theme.id;
            root.classList.remove('dark', 'light');
            root.classList.add(theme.mode);
            // Existing layouts may also put the base theme on body.
            document.body?.classList.remove('dark', 'light');
        } else {
            delete root.dataset.artistTheme;
            root.classList.remove('dark', 'light');
            const savedMode = read('theme');
            root.classList.add(['dark', 'light'].includes(savedMode) ? savedMode : originalMode);
            document.body?.classList.remove('dark', 'light');
        }
        root.dataset.artistFlourishes = flourishes ? 'on' : 'off';
        write(key, selected);
        // A URL override is one-shot: subsequent in-page selections must survive reloads.
        if (query.has('artist-theme')) {
            const url = new URL(window.location.href);
            url.searchParams.delete('artist-theme');
            try { window.history.replaceState(null, '', url); } catch { /* Nonessential. */ }
        }
        window.dispatchEvent(new CustomEvent('artist-theme-change', { detail: { id: selected } }));
    }
    apply(selected);

    document.addEventListener('DOMContentLoaded', () => {
        if (selected) document.body.classList.remove('dark', 'light');
        const panel = document.getElementById('artist-theme-lab');
        if (!panel) return;
        const select = panel.querySelector('select');
        const note = panel.querySelector('[data-artist-note]');
        const label = panel.querySelector('[data-artist-label]');
        const toggle = panel.querySelector('[data-artist-flourishes]');
        themes.forEach((theme) => select.add(new Option(theme.name + ' · ' + theme.title, theme.id)));
        function sync() {
            const theme = find(selected);
            select.value = selected;
            label.textContent = theme ? theme.title : 'Artist themes';
            note.textContent = theme ? theme.note : 'Choose a study to preview across the site. Original illustrations and CSS details inspired by your 13-artist catalog.';
            toggle.checked = flourishes;
            toggle.disabled = !theme;
        }
        function change(id) { apply(id); sync(); }
        select.addEventListener('change', () => change(select.value));
        panel.querySelector('[data-artist-reset]').addEventListener('click', () => change(''));
        panel.querySelectorAll('[data-artist-step]').forEach((button) => button.addEventListener('click', () => {
            const index = themes.findIndex((theme) => theme.id === selected);
            const step = Number(button.dataset.artistStep);
            change(themes[(index + step + themes.length) % themes.length].id);
        }));
        toggle.addEventListener('change', () => {
            flourishes = toggle.checked;
            write(detailsKey, flourishes ? 'on' : 'off');
            root.dataset.artistFlourishes = flourishes ? 'on' : 'off';
        });
        panel.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') { panel.open = false; panel.querySelector('summary').focus(); }
        });
        // The ordinary light/dark control returns to the original theme first.
        window.addEventListener('artist-theme-reset', () => change(''));
        sync();
    });
})();
