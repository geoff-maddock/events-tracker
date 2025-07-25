# API Notes

## API Endpoints
- Visit the API docs endpoint https://your-domain.com/api/docs for a full list of available endpoints.

### Documentation Generation
- Generated using Swagger js referencing `public/postman` folder.

## Usage
- Currently requires a basic auth username and password to access the API.
- You can also request a user token to access the API.

### Authenticaion
- You can query the API using basic auth or a user token.
- To authenticate using basic auth, include the `Authorization` header with the value `Basic base64(username:password)`.
- To authenticate using a user token, include the `Authorization` header with the value `Bearer your_user_token`.
- To aquire a user token, you can use the `/api/auth/token` endpoint with basic auth credentials.
  When requesting a token, include in the body a `token_name` key with the desired name for the token.

### Filtering Lists
You can apply filters to routes using the following:

- To filter by a specific field, use the field name as the key and the value as the value.
  - Example: `GET /api/events?filters[name]=Event Name`
- To order by a specific field, use the field name as the key and the value as the value.
  - Example: `GET /api/events?sort=model.property&direction=asc`
- To filter events by multiple tags you can provide a comma separated list or repeat the `filters[tag]` parameter.
  - `GET /api/events?filters[tag]=music,art`
  - When you need events that contain *all* of the supplied tags, use `filters[tag_all]` instead.
  - `GET /api/events?filters[tag_all]=music,art`

