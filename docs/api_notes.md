# API Notes

## API Endpoints
- Visit the API docs endpoint https://dev.arcane.city/api/docs for a full list of available endpoints.

### Documentaion Generation
- Generated using Swagger js referencing `public/postman` folder.

## Usage
- Currently requires a basic auth username and password to access the API.

### Filtering Lists
You can apply filters to routes using the following:

- To filter by a specific field, use the field name as the key and the value as the value.
  - Example: `GET /api/events?filters[name]=Event Name`
- To order by a specific field, use the field name as the key and the value as the value.
  - Example: `GET /api/events?sort=model.property&direction=asc`

