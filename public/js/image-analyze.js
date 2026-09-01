/**
 * Image upload + optional AI analysis panel.
 *
 * Drives resources/views/partials/image-analyze-panel.blade.php on the event,
 * entity and series create pages. Configuration arrives through data-*
 * attributes on #image-analyze-panel, so this file has no server-rendered
 * values in it and can live in public/js.
 *
 * Two server round-trips:
 *   1. images.stash   - on file select. Holds the image so it attaches on save
 *                       whether or not the user ever runs an analysis.
 *   2. images.analyze - on button press. Returns extracted data AND re-stashes
 *                       into the same token, so there is only ever one temp file.
 *
 * NOTE: this file is not scanned by Tailwind (its `content` globs cover
 * resources/views and resources/js only). Never assemble Tailwind utility
 * strings here - use the .img-analyze-status-* classes defined in
 * resources/css/tailwind.css instead, or they will be purged in production.
 */
(function () {
    'use strict';

    var panel = document.getElementById('image-analyze-panel');
    if (!panel) return;

    // ── Config ─────────────────────────────────────────────────────────────
    var CONTEXT = panel.dataset.context || 'event';
    var ANALYZE_URL = panel.dataset.analyzeUrl;
    var STASH_URL = panel.dataset.stashUrl;
    var FORM_SELECTOR = panel.dataset.formSelector || 'form';

    var DEFAULT_EVENT_TYPE = 'Concert';
    var DJ_EVENT_TYPE = 'Club Night';
    var DJ_KEYWORDS = [
        'dj', 'club', 'electronic', 'techno', 'house', 'drum and bass',
        'jungle', 'dnb', 'edm', 'rave', 'dance music', 'club night'
    ];

    /**
     * Per-context mapping from response keys to form fields.
     *
     *   text         - element ids set verbatim (id === response key)
     *   selectByName - { selectElementId: responseKey } matched against
     *                  existing options only, never created
     *   multiMatch   - { selectElementId: responseKey } Select2 multi-selects,
     *                  match-only
     *   multiCreate  - { selectElementId: responseKey } Select2 multi-selects
     *                  that may create missing options (only where the field
     *                  has data-tags="true" AND the controller free-creates)
     *   rules        - extra passes run after the mapping
     */
    var CONTEXT_MAPS = {
        event: {
            text: ['name', 'slug', 'short', 'description', 'start_at', 'end_at',
                   'door_at', 'presale_price', 'door_price', 'primary_link', 'ticket_link'],
            selectByName: { venue_id: 'venue_name', promoter_id: 'promoter_name' },
            multiMatch: { tag_list: 'tag_list', entity_list: 'related_entities' },
            multiCreate: {},
            rules: [minAgeRule, eventTypeRule]
        },
        entity: {
            text: ['name', 'slug', 'short', 'description', 'started_at',
                   'facebook_username', 'instagram_username'],
            selectByName: { entity_type_id: 'entity_type_name' },
            multiMatch: { role_list: 'role_list', tag_list: 'tag_list' },
            // alias_list is the one legitimate free-create target: the field
            // sets data-tags="true" and EntitiesController::store already
            // creates aliases for non-numeric values.
            multiCreate: { alias_list: 'alias_list' },
            rules: [confidenceRule]
        },
        series: {
            text: ['name', 'slug', 'short', 'description', 'founded_at', 'soundcheck_at',
                   'door_at', 'start_at', 'end_at', 'length', 'presale_price', 'door_price',
                   'primary_link', 'ticket_link', 'facebook_username', 'instagram_username',
                   'twitter_username'],
            selectByName: {
                venue_id: 'venue_name',
                promoter_id: 'promoter_name',
                occurrence_type_id: 'occurrence_type_name',
                occurrence_week_id: 'occurrence_week_name',
                occurrence_day_id: 'occurrence_day_name'
            },
            multiMatch: { tag_list: 'tag_list', entity_list: 'related_entities' },
            multiCreate: {},
            rules: [minAgeRule, eventTypeRule]
        }
    };

    // ── Element handles ────────────────────────────────────────────────────
    var fileInput = document.getElementById('image-file-input');
    var dropZone = document.getElementById('image-drop-zone');
    var dropContent = document.getElementById('image-drop-content');
    var previewContent = document.getElementById('image-preview-content');
    var previewImg = document.getElementById('image-preview-img');
    var previewName = document.getElementById('image-preview-name');
    var overlay = document.getElementById('image-scan-overlay');
    var statusEl = document.getElementById('image-status');
    var analyzeBtn = document.getElementById('analyze-image-btn');
    var analyzeBtnText = document.getElementById('analyze-btn-text');
    var ariaStatus = document.getElementById('analyze-status');
    var browseBtn = document.getElementById('image-browse-btn');
    var clearBtn = document.getElementById('image-clear-btn');
    var removeBtn = document.getElementById('image-remove-btn');

    var selectedFile = null;

    function tokenInput() {
        return document.querySelector(FORM_SELECTOR + ' #image_temp_token')
            || document.getElementById('image_temp_token');
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) return meta.content;
        var input = document.querySelector('input[name="_token"]');
        return input ? input.value : '';
    }

    // ── Status ─────────────────────────────────────────────────────────────
    function setStatus(message, type) {
        if (!statusEl) return;
        if (!message) {
            statusEl.classList.add('hidden');
            statusEl.textContent = '';
            return;
        }
        statusEl.textContent = message;
        statusEl.className = 'img-analyze-status img-analyze-status-' + (type || 'info');
        statusEl.classList.remove('hidden');
    }

    // ── File selection ─────────────────────────────────────────────────────
    function setFile(file) {
        selectedFile = file;

        var reader = new FileReader();
        reader.onload = function (e) {
            if (previewImg) {
                previewImg.src = e.target.result;
                previewImg.classList.remove('hidden');
                previewImg.classList.add('block');
            }
        };
        reader.readAsDataURL(file);

        if (previewName) previewName.textContent = file.name;
        if (dropContent) dropContent.classList.add('hidden');
        if (previewContent) previewContent.classList.remove('hidden');
        if (analyzeBtn) analyzeBtn.disabled = false;

        setStatus('', '');
        stashFile(file);
    }

    function clearSelection() {
        selectedFile = null;
        if (fileInput) fileInput.value = '';
        if (dropContent) dropContent.classList.remove('hidden');
        if (previewContent) previewContent.classList.add('hidden');
        if (overlay) overlay.classList.add('hidden');
        if (ariaStatus) ariaStatus.textContent = '';
        if (analyzeBtn) analyzeBtn.disabled = true;

        var token = tokenInput();
        if (token) token.value = '';

        setStatus('', '');
    }

    /**
     * Hold the image server-side as soon as it is chosen. This is what makes
     * "pick an image but never analyze" still attach the image on save.
     */
    function stashFile(file) {
        if (!STASH_URL) return;

        var body = new FormData();
        body.append('image', file);
        body.append('_token', csrfToken());

        var existing = tokenInput();
        if (existing && existing.value) body.append('image_temp_token', existing.value);

        fetch(STASH_URL, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (res) { return res.json().catch(function () { return {}; }); })
            .then(function (json) {
                if (!json || !json.success || !json.image_temp_token) {
                    setStatus('The image could not be uploaded, so it will not be attached. You can still fill in the form.', 'error');
                    return;
                }
                var input = tokenInput();
                if (input) input.value = json.image_temp_token;
            })
            .catch(function () {
                setStatus('The image could not be uploaded, so it will not be attached. You can still fill in the form.', 'error');
            });
    }

    // ── Analyse ────────────────────────────────────────────────────────────
    function analyze() {
        if (!selectedFile || !ANALYZE_URL) return;

        if (analyzeBtn) analyzeBtn.disabled = true;
        if (analyzeBtnText) analyzeBtnText.textContent = 'Analyzing…';
        if (overlay) overlay.classList.remove('hidden');
        if (ariaStatus) ariaStatus.textContent = 'Analyzing image…';
        setStatus('', '');

        var body = new FormData();
        body.append('image', selectedFile);
        body.append('context', CONTEXT);
        body.append('_token', csrfToken());

        var existing = tokenInput();
        if (existing && existing.value) body.append('image_temp_token', existing.value);

        fetch(ANALYZE_URL, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (response) {
                return response.json()
                    .catch(function () { return {}; })
                    .then(function (json) { return { ok: response.ok, json: json }; });
            })
            .then(function (result) {
                if (!result.ok || !result.json.success) {
                    setStatus(result.json.message || 'Failed to analyse the image. Please try again.', 'error');
                    return;
                }

                var input = tokenInput();
                if (input && result.json.image_temp_token) {
                    input.value = result.json.image_temp_token;
                }

                populateForm(result.json.data || {});
            })
            .catch(function (err) {
                if (window.console) console.error('Image analysis error:', err);
                setStatus('An unexpected error occurred. Please try again.', 'error');
            })
            .then(function () {
                if (analyzeBtn) analyzeBtn.disabled = false;
                if (analyzeBtnText) analyzeBtnText.textContent = 'Analyze Image';
                if (overlay) overlay.classList.add('hidden');
                if (ariaStatus) ariaStatus.textContent = '';
            });
    }

    // ── Populate ───────────────────────────────────────────────────────────
    function populateForm(data) {
        var map = CONTEXT_MAPS[CONTEXT] || CONTEXT_MAPS.event;
        var i;

        for (i = 0; i < map.text.length; i++) {
            setField(map.text[i], data[map.text[i]]);
        }

        Object.keys(map.selectByName).forEach(function (selectId) {
            matchSelectByName(selectId, data[map.selectByName[selectId]]);
        });

        Object.keys(map.multiMatch).forEach(function (selectId) {
            var values = data[map.multiMatch[selectId]];
            if (Array.isArray(values) && values.length) {
                selectExistingOptions(selectId, values);
            }
        });

        Object.keys(map.multiCreate).forEach(function (selectId) {
            var values = data[map.multiCreate[selectId]];
            if (Array.isArray(values) && values.length) {
                createAndSelectOptions(selectId, values);
            }
        });

        var handled = false;
        for (i = 0; i < map.rules.length; i++) {
            if (map.rules[i](data) === true) handled = true;
        }

        if (!handled) {
            setStatus('✓ Form pre-filled from the image. Please review and adjust before saving.', 'success');
        }

        var form = document.querySelector(FORM_SELECTOR);
        if (form && form.scrollIntoView) {
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    /**
     * A datetime-local input rejects anything but YYYY-MM-DDTHH:MM, and does
     * so silently. Models routinely return "2026-08-15 20:00" or a bare date,
     * so coerce rather than drop the value.
     */
    function coerceForInput(el, value) {
        var str = String(value);

        if (el.type === 'datetime-local') {
            str = str.replace(' ', 'T');
            if (/^\d{4}-\d{2}-\d{2}$/.test(str)) str += 'T00:00';
            var m = str.match(/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2})/);
            return m ? m[1] : '';
        }

        if (el.type === 'date') {
            var d = str.match(/^(\d{4}-\d{2}-\d{2})/);
            return d ? d[1] : '';
        }

        return str;
    }

    function setField(id, value) {
        if (value === null || value === undefined || value === '') return;
        var el = document.getElementById(id);
        if (!el) return;

        var coerced = coerceForInput(el, value);
        if (coerced === '') return;

        el.value = coerced;
    }

    function matchSelectByName(selectId, name) {
        if (!name) return false;
        var el = document.getElementById(selectId);
        if (!el || !el.options) return false;

        var lower = String(name).trim().toLowerCase();
        var i;

        for (i = 0; i < el.options.length; i++) {
            if (el.options[i].text.trim().toLowerCase() === lower) {
                el.value = el.options[i].value;
                triggerSelect2(selectId);
                return true;
            }
        }
        for (i = 0; i < el.options.length; i++) {
            if (el.options[i].text.trim().toLowerCase().indexOf(lower) === 0) {
                el.value = el.options[i].value;
                triggerSelect2(selectId);
                return true;
            }
        }
        return false;
    }

    function triggerSelect2(selectId) {
        if (window.jQuery && jQuery('#' + selectId).data('select2')) {
            jQuery('#' + selectId).trigger('change');
        }
    }

    /**
     * Select existing options in a Select2 multi-select whose text matches any
     * of the supplied names (exact, then starts-with). Never creates options.
     */
    function selectExistingOptions(selectId, names) {
        if (!window.jQuery) return;
        var $el = jQuery('#' + selectId);
        if (!$el.length) return;

        var values = [];

        names.forEach(function (name) {
            if (!name) return;
            var lower = String(name).trim().toLowerCase();
            var matched = false;

            $el.find('option').each(function () {
                if (jQuery(this).text().trim().toLowerCase() === lower) {
                    var val = jQuery(this).val();
                    if (values.indexOf(val) === -1) values.push(val);
                    matched = true;
                }
            });

            if (!matched) {
                $el.find('option').each(function () {
                    if (jQuery(this).text().trim().toLowerCase().indexOf(lower) === 0) {
                        var val = jQuery(this).val();
                        if (values.indexOf(val) === -1) values.push(val);
                    }
                });
            }
        });

        if (values.length) {
            $el.val(values).trigger('change');
        }
    }

    /**
     * As above, but appends an option for any name with no match. Only valid
     * where the field allows free text and the controller creates the record.
     */
    function createAndSelectOptions(selectId, names) {
        if (!window.jQuery) return;
        var $el = jQuery('#' + selectId);
        if (!$el.length) return;

        var values = ($el.val() || []).slice();

        names.forEach(function (name) {
            if (!name) return;
            var trimmed = String(name).trim();
            if (!trimmed) return;
            var lower = trimmed.toLowerCase();
            var existing = null;

            $el.find('option').each(function () {
                if (existing === null && jQuery(this).text().trim().toLowerCase() === lower) {
                    existing = jQuery(this).val();
                }
            });

            if (existing === null) {
                $el.append(new Option(trimmed, trimmed, true, true));
                existing = trimmed;
            }

            if (values.indexOf(existing) === -1) values.push(existing);
        });

        if (values.length) {
            $el.val(values).trigger('change');
        }
    }

    // ── Rules ──────────────────────────────────────────────────────────────
    function minAgeRule(data) {
        if (data.min_age === undefined || data.min_age === null) return false;
        var el = document.getElementById('min_age');
        if (!el) return false;
        var age = parseInt(data.min_age, 10);
        el.value = (age === 18 || age === 21) ? String(age) : '0';
        return false;
    }

    /**
     * Match event_type_id against existing options, falling back to
     * "Club Night" for DJ/electronic events or "Concert" otherwise.
     */
    function eventTypeRule(data) {
        var el = document.getElementById('event_type_id');
        if (!el) return false;

        if (data.event_type_name && matchSelectByName('event_type_id', data.event_type_name)) {
            return false;
        }

        var haystack = [data.event_type_name || '']
            .concat(Array.isArray(data.tag_list) ? data.tag_list : [])
            .concat(Array.isArray(data.related_entities) ? data.related_entities : [])
            .join(' ')
            .toLowerCase();

        var isDj = DJ_KEYWORDS.some(function (kw) { return haystack.indexOf(kw) !== -1; });

        matchSelectByName('event_type_id', isDj ? DJ_EVENT_TYPE : DEFAULT_EVENT_TYPE);
        return false;
    }

    /**
     * Entity identification is a genuine guess, so surface how confident the
     * model was instead of letting a weak match look authoritative.
     */
    function confidenceRule(data) {
        if (!data.name) {
            setStatus('The image could not be confidently identified, so the form was left blank. The image will still be attached when you save.', 'error');
            return true;
        }

        var confidence = (data.confidence || '').toLowerCase();
        var identified = data.identified_as ? String(data.identified_as) : data.name;

        if (confidence === 'low' || confidence === 'medium') {
            setStatus('Identified as ' + identified + ' (' + confidence + ' confidence). Please verify these values before saving.', 'info');
            return true;
        }

        setStatus('✓ Identified as ' + identified + '. Please review and adjust before saving.', 'success');
        return true;
    }

    // ── Wiring ─────────────────────────────────────────────────────────────
    if (fileInput) {
        fileInput.addEventListener('change', function () {
            if (this.files && this.files[0]) setFile(this.files[0]);
        });
    }

    if (browseBtn && fileInput) {
        browseBtn.addEventListener('click', function () { fileInput.click(); });
    }

    if (clearBtn) {
        // Open the picker without clearing first: cancelling the dialog must
        // not throw away the image the user already has attached. Choosing a
        // new file replaces it (and reuses the token, so no orphan temp file).
        clearBtn.addEventListener('click', function () {
            if (fileInput) fileInput.click();
        });
    }

    if (removeBtn) {
        removeBtn.addEventListener('click', function () {
            clearSelection();
            setStatus('Image removed. Nothing will be attached when you save.', 'info');
        });
    }

    if (dropZone) {
        dropZone.addEventListener('dragover', function (e) { e.preventDefault(); });
        dropZone.addEventListener('drop', function (e) {
            e.preventDefault();
            var file = e.dataTransfer && e.dataTransfer.files ? e.dataTransfer.files[0] : null;
            if (file && file.type.indexOf('image/') === 0) setFile(file);
        });
    }

    if (analyzeBtn) {
        analyzeBtn.addEventListener('click', analyze);
    }

    // A validation bounce returns with the token still in old input: tell the
    // user their image survived, even though the file input is necessarily empty.
    if (panel.dataset.hasStashed === '1') {
        setStatus('Your uploaded image is still attached. Choose a different image to replace it.', 'info');
    }
})();
