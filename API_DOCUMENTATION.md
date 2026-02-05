# API Documentation - JMR Textile User Management

## Base URL
```
http://localhost:8080/api
```

## Authentication
Most endpoints require JWT authentication. Include the token in the Authorization header:
```
Authorization: Bearer <your-jwt-token>
```

---

## Endpoints

### 1. User Registration
**POST** `/users/register`

Register a new user account.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "SecurePass123",
  "first_name": "John",
  "last_name": "Doe",
  "phone": "+1234567890"
}
```

**Response (201 Created):**
```json
{
  "status": "success",
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": "uuid-here",
      "email": "user@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "phone": "+1234567890",
      "role": "user",
      "is_active": true,
      "created_at": "2026-02-05 10:00:00"
    },
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

**Validation Rules:**
- `email`: required, valid email, unique
- `password`: required, min 8 characters
- `first_name`: required, max 100 characters
- `last_name`: required, max 100 characters
- `phone`: optional, max 20 characters

---

### 2. User Login
**POST** `/users/login`

Authenticate and receive a JWT token.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "SecurePass123"
}
```

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "user": {
      "id": "uuid-here",
      "email": "user@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "role": "user"
    },
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

**Error Response (401 Unauthorized):**
```json
{
  "status": "error",
  "message": "Invalid email or password"
}
```

---

### 3. Get User Profile
**GET** `/users/profile`

🔒 **Requires Authentication**

Get the authenticated user's profile.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "id": "uuid-here",
    "email": "user@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "phone": "+1234567890",
    "role": "user",
    "is_active": true,
    "created_at": "2026-02-05 10:00:00",
    "updated_at": "2026-02-05 10:00:00"
  }
}
```

---

### 4. Update User Profile
**PUT** `/users/profile`

🔒 **Requires Authentication**

Update the authenticated user's profile.

**Headers:**
```
Authorization: Bearer <token>
```

**Request Body:**
```json
{
  "first_name": "Jane",
  "last_name": "Smith",
  "phone": "+9876543210",
  "password": "NewSecurePass123"
}
```

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Profile updated successfully",
  "data": {
    "id": "uuid-here",
    "email": "user@example.com",
    "first_name": "Jane",
    "last_name": "Smith",
    "phone": "+9876543210",
    "role": "user"
  }
}
```

---

### 5. Delete User Profile
**DELETE** `/users/profile`

🔒 **Requires Authentication**

Soft delete the authenticated user's account.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Account deleted successfully"
}
```

---

### 6. List All Users (Admin)
**GET** `/users`

🔒 **Requires Authentication + Admin Role**

Get a list of all users (admin only).

**Headers:**
```
Authorization: Bearer <admin-token>
```

**Response (200 OK):**
```json
{
  "status": "success",
  "data": [
    {
      "id": "uuid-1",
      "email": "admin@jmrtextile.com",
      "first_name": "Admin",
      "last_name": "User",
      "role": "admin",
      "is_active": true,
      "created_at": "2026-02-05 09:00:00"
    },
    {
      "id": "uuid-2",
      "email": "user@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "role": "user",
      "is_active": true,
      "created_at": "2026-02-05 10:00:00"
    }
  ]
}
```

---

### 7. Get User by ID (Admin)
**GET** `/users/{id}`

🔒 **Requires Authentication + Admin Role**

Get details of a specific user by ID.

**Headers:**
```
Authorization: Bearer <admin-token>
```

**Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "id": "uuid-here",
    "email": "user@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "phone": "+1234567890",
    "role": "user",
    "is_active": true,
    "created_at": "2026-02-05 10:00:00",
    "updated_at": "2026-02-05 10:00:00"
  }
}
```

---

### 8. Update User by ID (Admin)
**PUT** `/users/{id}`

🔒 **Requires Authentication + Admin Role**

Update any user's information (admin only).

**Headers:**
```
Authorization: Bearer <admin-token>
```

**Request Body:**
```json
{
  "first_name": "Updated",
  "last_name": "Name",
  "role": "admin",
  "is_active": false
}
```

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "User updated successfully",
  "data": {
    "id": "uuid-here",
    "email": "user@example.com",
    "first_name": "Updated",
    "last_name": "Name",
    "role": "admin",
    "is_active": false
  }
}
```

---

### 9. Delete User by ID (Admin)
**DELETE** `/users/{id}`

🔒 **Requires Authentication + Admin Role**

Soft delete a user account (admin only).

**Headers:**
```
Authorization: Bearer <admin-token>
```

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "User deleted successfully"
}
```

---

## Error Responses

### 400 Bad Request
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "email": "The email field must contain a valid email address.",
    "password": "The password field must be at least 8 characters in length."
  }
}
```

### 401 Unauthorized
```json
{
  "status": "error",
  "message": "Unauthorized. Token missing or invalid."
}
```

### 403 Forbidden
```json
{
  "status": "error",
  "message": "Access denied. Admin privileges required."
}
```

### 404 Not Found
```json
{
  "status": "error",
  "message": "User not found"
}
```

### 500 Internal Server Error
```json
{
  "status": "error",
  "message": "An error occurred while processing your request"
}
```

---

## Next.js Integration Example

### API Client (`lib/api.ts`)

```typescript
const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8080/api';

// Helper function to get token
const getToken = () => {
  if (typeof window !== 'undefined') {
    return localStorage.getItem('token');
  }
  return null;
};

// Helper function to make authenticated requests
const fetchWithAuth = async (endpoint: string, options: RequestInit = {}) => {
  const token = getToken();
  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    ...options.headers,
  };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const response = await fetch(`${API_URL}${endpoint}`, {
    ...options,
    headers,
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.message || 'An error occurred');
  }

  return data;
};

// Auth API
export const authAPI = {
  register: async (userData: {
    email: string;
    password: string;
    first_name: string;
    last_name: string;
    phone?: string;
  }) => {
    const data = await fetchWithAuth('/users/register', {
      method: 'POST',
      body: JSON.stringify(userData),
    });
    
    if (data.data.token) {
      localStorage.setItem('token', data.data.token);
      localStorage.setItem('user', JSON.stringify(data.data.user));
    }
    
    return data;
  },

  login: async (email: string, password: string) => {
    const data = await fetchWithAuth('/users/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });
    
    if (data.data.token) {
      localStorage.setItem('token', data.data.token);
      localStorage.setItem('user', JSON.stringify(data.data.user));
    }
    
    return data;
  },

  logout: () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
  },

  getProfile: async () => {
    return fetchWithAuth('/users/profile');
  },

  updateProfile: async (userData: {
    first_name?: string;
    last_name?: string;
    phone?: string;
    password?: string;
  }) => {
    return fetchWithAuth('/users/profile', {
      method: 'PUT',
      body: JSON.stringify(userData),
    });
  },

  deleteProfile: async () => {
    return fetchWithAuth('/users/profile', {
      method: 'DELETE',
    });
  },
};

// Admin API
export const adminAPI = {
  getAllUsers: async () => {
    return fetchWithAuth('/users');
  },

  getUserById: async (id: string) => {
    return fetchWithAuth(`/users/${id}`);
  },

  updateUser: async (id: string, userData: any) => {
    return fetchWithAuth(`/users/${id}`, {
      method: 'PUT',
      body: JSON.stringify(userData),
    });
  },

  deleteUser: async (id: string) => {
    return fetchWithAuth(`/users/${id}`, {
      method: 'DELETE',
    });
  },
};
```

### Usage in Components

```typescript
'use client';

import { useState } from 'react';
import { authAPI } from '@/lib/api';

export default function LoginPage() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    try {
      const response = await authAPI.login(email, password);
      console.log('Login successful:', response.data.user);
      // Redirect to dashboard
      window.location.href = '/dashboard';
    } catch (err: any) {
      setError(err.message);
    }
  };

  return (
    <form onSubmit={handleLogin}>
      {error && <div className="error">{error}</div>}
      <input
        type="email"
        value={email}
        onChange={(e) => setEmail(e.target.value)}
        placeholder="Email"
        required
      />
      <input
        type="password"
        value={password}
        onChange={(e) => setPassword(e.target.value)}
        placeholder="Password"
        required
      />
      <button type="submit">Login</button>
    </form>
  );
}
```

---

## Default Admin Account

For testing purposes, a default admin account is created:

- **Email:** `admin@jmrtextile.com`
- **Password:** `admin123`
- **Role:** `admin`

⚠️ **Important:** Change this password in production!
