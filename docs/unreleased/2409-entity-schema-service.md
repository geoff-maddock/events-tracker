### Entity structured data in its own service (#2409)

The JSON-LD of an entity page (the entity node, its `sameAs` links and the breadcrumbs) was built by four methods on the `Entity` model. It now lives in `App\Services\EntitySchema`, next to `EventSchema` and `SeriesSchema`. The output is unchanged.
