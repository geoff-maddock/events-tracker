### Dropzone 6.3.5 (#2412)

The photo upload box on event, entity, series, blog and profile pages ran on a 2021 pre-release of Dropzone (`6.0.0-beta.2`). It is now the current stable release, 6.3.5, which also drops two packages the pre-release pulled in (`@swc/helpers`, `just-extend`). The upload box looks and works as before.

Deploy: `npm ci && npm run build`.
