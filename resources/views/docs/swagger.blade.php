<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.app_name', config('app.name')) }} API</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.30.0/swagger-ui.css" integrity="sha384-++DMKo1369T5pxDNqojF1F91bYxYiT1N7b1M15a7oCzEodfljztKlApQoH6eQSKI" crossorigin="anonymous">
</head>

<body>
    <div id="swagger-api"></div>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.30.0/swagger-ui-bundle.js" integrity="sha384-9CJCDqW5gKboEGedI4f6FbRtGJUQDktyO4ALxunoU7Zh2AaMp93UH1iVtC0SzT9/" crossorigin="anonymous"></script>
    <script>
        SwaggerUIBundle({
            dom_id: '#swagger-api',
            url: @json(asset('postman/schemas/api.yml')),
        });
    </script>
</body>

</html>
