# API Notes

## Filtering Lists
You can apply filters to routes using the following:


- To filter by a specific field, use the field name as the key and the value as the value.
  - Example: `GET /api/events?filters[name]=Event Name`
- To order by a specific field, use the field name as the key and the value as the value.
  - Example: `GET /api/events?sort=model.property&direction=asc`