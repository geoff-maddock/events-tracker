import SwaggerUI from 'swagger-ui'
import 'swagger-ui/dist/swagger-ui.css';

const {
    host, hostname, href, origin, pathname, port, protocol, search
} = window.location

SwaggerUI({
    dom_id: '#swagger-api',
    url: origin + '/postman/schemas/api.yml',
});