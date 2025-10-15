# Frontend Integration Example

This document provides a complete example of how to integrate the `frontend-url` parameter in a frontend application.

## Example: React Application

### 1. Registration Component

```jsx
// components/Register.jsx
import React, { useState } from 'react';
import axios from 'axios';

function Register() {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
  });
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError(null);

    try {
      // Get reCAPTCHA token (assuming you have reCAPTCHA set up)
      const recaptchaToken = await window.grecaptcha.execute('YOUR_SITE_KEY', {
        action: 'register',
      });

      // Include the frontend URL in the registration request
      const response = await axios.post('https://api.example.com/api/register', {
        name: formData.name,
        email: formData.email,
        password: formData.password,
        'g-recaptcha-response': recaptchaToken,
        'frontend-url': window.location.origin, // or 'https://myapp.example.com'
      });

      if (response.status === 201) {
        setSuccess(true);
        // Show success message to user
        alert('Registration successful! Please check your email to verify your account.');
      }
    } catch (err) {
      if (err.response?.status === 422) {
        setError(err.response.data.errors);
      } else {
        setError({ general: 'An error occurred during registration.' });
      }
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <input
        type="text"
        placeholder="Name"
        value={formData.name}
        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
      />
      <input
        type="email"
        placeholder="Email"
        value={formData.email}
        onChange={(e) => setFormData({ ...formData, email: e.target.value })}
      />
      <input
        type="password"
        placeholder="Password"
        value={formData.password}
        onChange={(e) => setFormData({ ...formData, password: e.target.value })}
      />
      
      {error && (
        <div className="error">
          {Object.values(error).map((err, i) => (
            <div key={i}>{Array.isArray(err) ? err[0] : err}</div>
          ))}
        </div>
      )}
      
      {success && (
        <div className="success">
          Registration successful! Check your email for verification link.
        </div>
      )}
      
      <button type="submit">Register</button>
    </form>
  );
}

export default Register;
```

### 2. Email Verification Handler

```jsx
// components/EmailVerificationHandler.jsx
import React, { useEffect, useState } from 'react';
import { useParams, useSearchParams, useNavigate } from 'react-router-dom';
import axios from 'axios';

function EmailVerificationHandler() {
  const { id, hash } = useParams();
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const [status, setStatus] = useState('verifying');
  const [message, setMessage] = useState('');

  useEffect(() => {
    const verifyEmail = async () => {
      try {
        // Extract all query parameters from the URL
        const signature = searchParams.get('signature');
        const expires = searchParams.get('expires');
        
        // Forward the verification request to the backend
        const response = await axios.get(
          `https://api.example.com/email/verify/${id}/${hash}`,
          {
            params: {
              signature,
              expires,
            },
          }
        );

        if (response.status === 200) {
          setStatus('success');
          setMessage('Your email has been verified successfully!');
          
          // Redirect to login page after 2 seconds
          setTimeout(() => {
            navigate('/login');
          }, 2000);
        }
      } catch (err) {
        setStatus('error');
        if (err.response?.status === 403) {
          setMessage('Invalid or expired verification link.');
        } else {
          setMessage('An error occurred during verification.');
        }
      }
    };

    verifyEmail();
  }, [id, hash, searchParams, navigate]);

  return (
    <div className="verification-container">
      {status === 'verifying' && (
        <div>
          <div className="spinner">Loading...</div>
          <p>Verifying your email address...</p>
        </div>
      )}
      
      {status === 'success' && (
        <div className="success">
          <h2>Email Verified!</h2>
          <p>{message}</p>
          <p>Redirecting to login...</p>
        </div>
      )}
      
      {status === 'error' && (
        <div className="error">
          <h2>Verification Failed</h2>
          <p>{message}</p>
          <button onClick={() => navigate('/login')}>Go to Login</button>
        </div>
      )}
    </div>
  );
}

export default EmailVerificationHandler;
```

### 3. Router Configuration

```jsx
// App.jsx
import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import Register from './components/Register';
import EmailVerificationHandler from './components/EmailVerificationHandler';
import Login from './components/Login';

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/register" element={<Register />} />
        <Route path="/email/verify/:id/:hash" element={<EmailVerificationHandler />} />
        <Route path="/login" element={<Login />} />
        {/* Other routes */}
      </Routes>
    </BrowserRouter>
  );
}

export default App;
```

## Example: Vue.js Application

### 1. Registration Component

```vue
<!-- components/Register.vue -->
<template>
  <form @submit.prevent="handleSubmit">
    <input v-model="form.name" type="text" placeholder="Name" required />
    <input v-model="form.email" type="email" placeholder="Email" required />
    <input v-model="form.password" type="password" placeholder="Password" required />
    
    <div v-if="error" class="error">
      <div v-for="(err, key) in error" :key="key">
        {{ Array.isArray(err) ? err[0] : err }}
      </div>
    </div>
    
    <div v-if="success" class="success">
      Registration successful! Check your email for verification link.
    </div>
    
    <button type="submit">Register</button>
  </form>
</template>

<script>
import axios from 'axios';

export default {
  name: 'Register',
  data() {
    return {
      form: {
        name: '',
        email: '',
        password: '',
      },
      error: null,
      success: false,
    };
  },
  methods: {
    async handleSubmit() {
      this.error = null;
      this.success = false;
      
      try {
        // Get reCAPTCHA token
        const recaptchaToken = await window.grecaptcha.execute('YOUR_SITE_KEY', {
          action: 'register',
        });

        const response = await axios.post('https://api.example.com/api/register', {
          name: this.form.name,
          email: this.form.email,
          password: this.form.password,
          'g-recaptcha-response': recaptchaToken,
          'frontend-url': window.location.origin,
        });

        if (response.status === 201) {
          this.success = true;
        }
      } catch (err) {
        if (err.response?.status === 422) {
          this.error = err.response.data.errors;
        } else {
          this.error = { general: 'An error occurred during registration.' };
        }
      }
    },
  },
};
</script>
```

### 2. Email Verification Handler

```vue
<!-- components/EmailVerificationHandler.vue -->
<template>
  <div class="verification-container">
    <div v-if="status === 'verifying'">
      <div class="spinner">Loading...</div>
      <p>Verifying your email address...</p>
    </div>
    
    <div v-if="status === 'success'" class="success">
      <h2>Email Verified!</h2>
      <p>{{ message }}</p>
      <p>Redirecting to login...</p>
    </div>
    
    <div v-if="status === 'error'" class="error">
      <h2>Verification Failed</h2>
      <p>{{ message }}</p>
      <button @click="$router.push('/login')">Go to Login</button>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'EmailVerificationHandler',
  data() {
    return {
      status: 'verifying',
      message: '',
    };
  },
  async mounted() {
    const { id, hash } = this.$route.params;
    const { signature, expires } = this.$route.query;
    
    try {
      const response = await axios.get(
        `https://api.example.com/email/verify/${id}/${hash}`,
        {
          params: { signature, expires },
        }
      );

      if (response.status === 200) {
        this.status = 'success';
        this.message = 'Your email has been verified successfully!';
        
        setTimeout(() => {
          this.$router.push('/login');
        }, 2000);
      }
    } catch (err) {
      this.status = 'error';
      if (err.response?.status === 403) {
        this.message = 'Invalid or expired verification link.';
      } else {
        this.message = 'An error occurred during verification.';
      }
    }
  },
};
</script>
```

## Configuration Notes

### 1. Environment Variables

Create a `.env` file in your frontend project:

```env
VITE_API_URL=https://api.example.com
VITE_RECAPTCHA_SITE_KEY=your_recaptcha_site_key
```

### 2. Axios Configuration

```javascript
// utils/axios.js
import axios from 'axios';

const instance = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

export default instance;
```

### 3. CORS Configuration

Ensure your backend's CORS configuration allows requests from your frontend domain:

```php
// config/cors.php
'paths' => ['api/*', 'sanctum/csrf-cookie', 'email/verify/*'],
'allowed_origins' => [
    'https://myapp.example.com',
    'http://localhost:3000', // for development
],
```

## Testing

### 1. Manual Testing

1. Register a new user with the frontend-url parameter
2. Check your email for the verification link
3. Click the link and verify it points to your frontend
4. Confirm that email is verified in the database

### 2. Example cURL Request

```bash
curl -X POST https://api.example.com/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "g-recaptcha-response": "test-token",
    "frontend-url": "https://myapp.example.com"
  }'
```

## Troubleshooting

### Issue: Verification link goes to backend instead of frontend
- Check that you're passing the `frontend-url` parameter in the registration request
- Verify the URL is valid (must start with http:// or https://)

### Issue: Verification fails with 403 error
- Check that the signature and expires parameters are being forwarded correctly
- Ensure the verification link hasn't expired (default: 60 minutes)

### Issue: CORS errors
- Update your backend CORS configuration to allow your frontend domain
- Ensure the verification route is included in the CORS paths
