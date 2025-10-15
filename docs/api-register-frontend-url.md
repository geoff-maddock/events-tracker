# API Registration with Frontend URL

## Overview
The `/api/register` endpoint supports an optional `frontend-url` parameter that allows you to specify a custom frontend URL for the email verification link sent to the newly registered user.

## Use Case
This feature is useful when you have a separate frontend application (e.g., a React or Vue.js SPA) that needs to handle email verification, while the backend API handles user registration and verification logic.

## Endpoint
```
POST /api/register
```

## Request Body

### Required Fields
- `name` (string, min: 3, max: 255): User's full name
- `email` (string, email, max: 255, unique): User's email address
- `password` (string, min: 8): User's password
- `g-recaptcha-response` (string): Google reCAPTCHA response token

### Optional Fields
- `frontend-url` (string, url, max: 255): The base URL of your frontend application

## Example Requests

### Without frontend-url (uses default backend URL)
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "securepassword123",
  "g-recaptcha-response": "03AGdBq27..."
}
```

### With frontend-url (uses custom frontend URL)
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "securepassword123",
  "g-recaptcha-response": "03AGdBq27...",
  "frontend-url": "https://myapp.example.com"
}
```

## Behavior

When `frontend-url` is provided:
1. The parameter is validated to ensure it's a valid URL
2. The URL is passed through to the email verification process
3. When the verification email is sent, the verification link will use the provided `frontend-url` as the base URL
4. The verification link will be in the format: `https://myapp.example.com/email/verify/{id}/{hash}`

When `frontend-url` is NOT provided:
1. The verification email will use the default `APP_URL` configured in your backend
2. The verification link will be in the format: `https://api.example.com/email/verify/{id}/{hash}`

## Success Response
```json
{
  "message": "User registered successfully. Please check your email to verify your account.",
  "user": {
    "id": 123,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2025-10-14T04:13:47.000000Z",
    "updated_at": "2025-10-14T04:13:47.000000Z"
  }
}
```

## Error Responses

### Validation Error (422)
```json
{
  "message": "Validation failed",
  "errors": {
    "frontend-url": [
      "The frontend URL must be a valid URL"
    ]
  }
}
```

### Invalid Input (422)
```json
{
  "message": "Validation failed",
  "errors": {
    "email": [
      "This email address is already registered"
    ],
    "password": [
      "A password must be at least 8 characters"
    ]
  }
}
```

## Frontend Implementation Notes

If you're using a custom `frontend-url`, your frontend application should:

1. Have a route that matches `/email/verify/{id}/{hash}` (or configure your route accordingly)
2. Extract the `id` and `hash` parameters from the URL
3. Make a request to the backend verification endpoint to complete the verification
4. Handle the verification response appropriately (redirect to login, show success message, etc.)

Example frontend route handler (React Router):
```javascript
// In your frontend app
<Route path="/email/verify/:id/:hash" element={<EmailVerificationHandler />} />

// EmailVerificationHandler component
function EmailVerificationHandler() {
  const { id, hash } = useParams();
  
  useEffect(() => {
    // Forward the verification request to your backend
    fetch(`https://api.example.com/email/verify/${id}/${hash}?signature=${signature}`)
      .then(response => {
        // Handle success/error
      });
  }, [id, hash]);
  
  return <div>Verifying your email...</div>;
}
```

## Security Notes

- The `frontend-url` is only used for generating the email verification link
- The URL is NOT stored permanently with the user record
- The verification link includes a signed signature to prevent tampering
- The frontend-url is only used during the registration request and is not persisted
