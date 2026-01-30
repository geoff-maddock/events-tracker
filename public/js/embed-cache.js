/**
 * Embed Cache Utility
 * 
 * Caching utility for event, series, and entity embeds with TTL support.
 * Stores embed data in localStorage with timestamps to manage expiration.
 * Port of the React frontend embedCache.ts for use in the Laravel project.
 */
var EmbedCache = (function () {
    var CACHE_KEY_PREFIX = 'embed_cache_';
    var DEFAULT_TTL_MS = 7 * 24 * 60 * 60 * 1000; // 7 days in milliseconds

    /**
     * Generate a cache key for a specific resource
     * @param {string} resourceType - 'events', 'entities', or 'series'
     * @param {string} slug - The slug of the resource
     * @param {string} endpoint - 'embeds' or 'minimal-embeds'
     * @returns {string} The cache key
     */
    function getCacheKey(resourceType, slug, endpoint) {
        return CACHE_KEY_PREFIX + resourceType + '_' + slug + '_' + endpoint;
    }

    /**
     * Check if cached data is still valid based on TTL
     * @param {Object} cachedItem - The cached item with timestamp and ttl
     * @returns {boolean} Whether the cache is still valid
     */
    function isValid(cachedItem) {
        var now = Date.now();
        var age = now - cachedItem.timestamp;
        return age < cachedItem.ttl;
    }

    /**
     * Get embeds from cache if available and not expired
     * @param {string} resourceType - 'events', 'entities', or 'series'
     * @param {string} slug - The slug of the resource
     * @param {string} endpoint - 'embeds' or 'minimal-embeds' (default: 'minimal-embeds')
     * @returns {Array|null} Array of embed HTML strings or null if not cached/expired
     */
    function get(resourceType, slug, endpoint) {
        endpoint = endpoint || 'minimal-embeds';

        try {
            var key = getCacheKey(resourceType, slug, endpoint);
            var cached = localStorage.getItem(key);

            if (!cached) {
                return null;
            }

            var cachedItem = JSON.parse(cached);

            if (isValid(cachedItem)) {
                console.log('EmbedCache: Loaded from cache:', resourceType + '/' + slug, '(' + endpoint + ')');
                return cachedItem.data;
            }

            // Cache expired, remove it
            console.log('EmbedCache: Cache expired, removing:', resourceType + '/' + slug, '(' + endpoint + ')');
            localStorage.removeItem(key);
            return null;
        } catch (error) {
            console.warn('EmbedCache: Error reading cache:', error);
            return null;
        }
    }

    /**
     * Store embeds in cache with TTL
     * @param {string} resourceType - 'events', 'entities', or 'series'
     * @param {string} slug - The slug of the resource
     * @param {Array} data - Array of embed HTML strings
     * @param {string} endpoint - 'embeds' or 'minimal-embeds' (default: 'minimal-embeds')
     * @param {number} ttlMs - Time to live in milliseconds (default: 7 days)
     */
    function set(resourceType, slug, data, endpoint, ttlMs) {
        endpoint = endpoint || 'minimal-embeds';
        ttlMs = ttlMs || DEFAULT_TTL_MS;

        try {
            var key = getCacheKey(resourceType, slug, endpoint);
            var cachedItem = {
                data: data,
                timestamp: Date.now(),
                ttl: ttlMs
            };

            localStorage.setItem(key, JSON.stringify(cachedItem));
            console.log('EmbedCache: Saved to cache:', resourceType + '/' + slug, '(' + endpoint + ')', 'TTL:', Math.round(ttlMs / 1000 / 60 / 60) + 'h');
        } catch (error) {
            console.warn('EmbedCache: Error setting cache:', error);
        }
    }

    /**
     * Clear a specific embed cache entry
     * @param {string} resourceType - 'events', 'entities', or 'series'
     * @param {string} slug - The slug of the resource
     * @param {string} endpoint - 'embeds' or 'minimal-embeds' (default: 'minimal-embeds')
     */
    function clear(resourceType, slug, endpoint) {
        endpoint = endpoint || 'minimal-embeds';

        try {
            var key = getCacheKey(resourceType, slug, endpoint);
            localStorage.removeItem(key);
        } catch (error) {
            console.warn('EmbedCache: Error clearing cache:', error);
        }
    }

    /**
     * Clear all embed caches for a specific resource
     * @param {string} resourceType - 'events', 'entities', or 'series'
     * @param {string} slug - The slug of the resource
     */
    function clearAll(resourceType, slug) {
        try {
            clear(resourceType, slug, 'embeds');
            clear(resourceType, slug, 'minimal-embeds');
        } catch (error) {
            console.warn('EmbedCache: Error clearing all caches:', error);
        }
    }

    /**
     * Clear all embed caches in localStorage
     */
    function clearAllCaches() {
        try {
            var keys = Object.keys(localStorage);
            keys.forEach(function (key) {
                if (key.indexOf(CACHE_KEY_PREFIX) === 0) {
                    localStorage.removeItem(key);
                }
            });
        } catch (error) {
            console.warn('EmbedCache: Error clearing all caches:', error);
        }
    }

    /**
     * Get the default TTL in milliseconds
     * @returns {number} Default TTL in milliseconds
     */
    function getDefaultTTL() {
        return DEFAULT_TTL_MS;
    }

    // Public API
    return {
        get: get,
        set: set,
        clear: clear,
        clearAll: clearAll,
        clearAllCaches: clearAllCaches,
        getDefaultTTL: getDefaultTTL
    };
})();


/**
 * Embed Loader Utility
 * 
 * Loads embeds for events, series, and entities with caching support.
 * Checks cache before making API calls, and stores results in cache.
 */
var EmbedLoader = (function () {

    /**
     * Load embeds for a resource, using cache if available
     * @param {string} resourceType - 'events', 'entities', or 'series'
     * @param {string} slug - The slug of the resource
     * @param {Object} options - Options for loading embeds
     * @param {string} options.endpoint - 'embeds' or 'minimal-embeds' (default: 'minimal-embeds')
     * @param {boolean} options.forceRefresh - Skip cache and fetch fresh data (default: false)
     * @param {Function} options.onSuccess - Callback when embeds are loaded successfully
     * @param {Function} options.onError - Callback when there's an error loading embeds
     * @returns {Promise} Promise that resolves with the embed data
     */
    function load(resourceType, slug, options) {
        options = options || {};
        var endpoint = options.endpoint || 'minimal-embeds';
        var forceRefresh = options.forceRefresh || false;
        var onSuccess = options.onSuccess || function () { };
        var onError = options.onError || function () { };

        return new Promise(function (resolve, reject) {
            // Try cache first (unless force refresh is requested)
            if (!forceRefresh) {
                var cachedEmbeds = EmbedCache.get(resourceType, slug, endpoint);
                if (cachedEmbeds !== null) {
                    console.log('EmbedLoader: Using cached embeds for ' + resourceType + '/' + slug);
                    onSuccess(cachedEmbeds);
                    resolve(cachedEmbeds);
                    return;
                }
            }

            // Build URL (use web route instead of API to avoid auth)
            var apiUrl = '/' + resourceType + '/' + slug + '/' + endpoint;

            console.log('EmbedLoader: Fetching embeds from ' + apiUrl);

            // Fetch from API
            $.ajax({
                url: apiUrl,
                method: 'GET',
                dataType: 'json'
            }).done(function (response) {
                var embedsData = response.data || [];

                // Only cache if there is data to cache
                if (embedsData && embedsData.length > 0) {
                    EmbedCache.set(resourceType, slug, embedsData, endpoint);
                    console.log('EmbedLoader: Loaded and cached ' + embedsData.length + ' embeds for ' + resourceType + '/' + slug);
                } else {
                    console.log('EmbedLoader: Loaded 0 embeds for ' + resourceType + '/' + slug + ' (not cached)');
                }

                onSuccess(embedsData);
                resolve(embedsData);
            }).fail(function (xhr, status, error) {
                console.error('EmbedLoader: Error fetching embeds for ' + resourceType + '/' + slug + ':', error);
                onError(error);
                reject(error);
            });
        });
    }

    /**
     * Load embeds for an event
     * @param {string} slug - The event slug
     * @param {Object} options - Options for loading embeds
     * @returns {Promise} Promise that resolves with the embed data
     */
    function loadEventEmbeds(slug, options) {
        return load('events', slug, options);
    }

    /**
     * Load embeds for a series
     * @param {string} slug - The series slug
     * @param {Object} options - Options for loading embeds
     * @returns {Promise} Promise that resolves with the embed data
     */
    function loadSeriesEmbeds(slug, options) {
        return load('series', slug, options);
    }

    /**
     * Load embeds for an entity
     * @param {string} slug - The entity slug
     * @param {Object} options - Options for loading embeds
     * @returns {Promise} Promise that resolves with the embed data
     */
    function loadEntityEmbeds(slug, options) {
        return load('entities', slug, options);
    }

    /**
     * Preload embeds for multiple resources (useful for card grids)
     * @param {Array} resources - Array of {type, slug} objects
     * @param {Object} options - Options for loading embeds
     * @returns {Promise} Promise that resolves when all embeds are loaded
     */
    function preloadEmbeds(resources, options) {
        var promises = resources.map(function (resource) {
            return load(resource.type, resource.slug, options).catch(function (error) {
                // Don't fail the entire preload if one fails
                console.warn('EmbedLoader: Failed to preload embeds for ' + resource.type + '/' + resource.slug);
                return [];
            });
        });

        return Promise.all(promises);
    }

    /**
     * Invalidate cache when a resource is saved
     * Call this after saving an event, series, or entity
     * @param {string} resourceType - 'events', 'entities', or 'series'
     * @param {string} slug - The slug of the resource
     */
    function invalidateCache(resourceType, slug) {
        console.log('EmbedLoader: Invalidating cache for ' + resourceType + '/' + slug);
        EmbedCache.clearAll(resourceType, slug);
    }

    /**
     * Refresh embeds for a resource (invalidate cache and reload)
     * @param {string} resourceType - 'events', 'entities', or 'series'
     * @param {string} slug - The slug of the resource
     * @param {Object} options - Options for loading embeds
     * @returns {Promise} Promise that resolves with the embed data
     */
    function refresh(resourceType, slug, options) {
        options = options || {};
        options.forceRefresh = true;
        return load(resourceType, slug, options);
    }

    /**
     * Render embeds into a target element
     * @param {string} targetSelector - jQuery selector for the target element
     * @param {Array} embeds - Array of embed HTML strings
     */
    function renderEmbeds(targetSelector, embeds) {
        if (embeds && embeds.length > 0) {
            $(targetSelector).html(embeds.join(''));
        }
    }

    // Public API
    return {
        load: load,
        loadEventEmbeds: loadEventEmbeds,
        loadSeriesEmbeds: loadSeriesEmbeds,
        loadEntityEmbeds: loadEntityEmbeds,
        preloadEmbeds: preloadEmbeds,
        invalidateCache: invalidateCache,
        refresh: refresh,
        renderEmbeds: renderEmbeds
    };
})();
