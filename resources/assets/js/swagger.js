import SwaggerUI from 'swagger-ui'
import 'swagger-ui/dist/swagger-ui.css';
SwaggerUI({
    dom_id: '#swagger-api',
    url: 'https://dev.arcane.city/postman/schemas/api.yml',
});