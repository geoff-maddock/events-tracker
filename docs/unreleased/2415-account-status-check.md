### Account status is checked once the request is signed in (#2415)

The check that refuses a suspended or banned account ran before the request's user was known. It now runs in the web and API route groups, after sign-in, for sessions, API tokens and basic auth. An account that is suspended or banned also loses its API tokens at that moment.
