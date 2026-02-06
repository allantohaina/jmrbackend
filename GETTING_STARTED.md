# Getting Started - JMR Textile User Management System

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+ (with mbstring extension enabled)
- PostgreSQL database
- Composer
- PHP file uploads enabled (fileinfo required)

### Installation Steps

1. **Database Setup**
   
   The database and table should already be created. Verify by running:
   ```bash
   php spark migrate:status
   ```

   If migrations haven't been run yet:
   ```bash
   php spark migrate
   ```

2. **Environment Configuration**
   
   Verify your `.env` file has the correct settings:
   ```env
   CI_ENVIRONMENT = development
   
   database.default.hostname = localhost
   database.default.database = jmrtextile
   database.default.username = allan
   database.default.password = etherion
   database.default.DBDriver = postgres
   
   JWT_SECRET_KEY = jmr-textile-secret-key-change-in-production-2026
   ```

3. **Start the Server**

   **Option A: Using PHP Built-in Server (Development)**
   ```bash
   php spark serve
   ```
   Server will start at `http://localhost:8080`

   **Option B: Using Apache/Nginx**
   Point your web server's document root to the `public/` directory.

---

## 🧪 Testing the API

### Using cURL

#### 1. Test User Registration
```bash
curl -X POST http://localhost:8080/api/users/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "Test123456",
    "first_name": "Test",
    "last_name": "User",
    "phone": "+1234567890"
  }'
```

#### 2. Test User Login
```bash
curl -X POST http://localhost:8080/api/users/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@jmrtextile.com",
    "password": "admin123"
  }'
```

**Save the token from the response!**

#### 3. Test Get Profile (Protected Route)
```bash
curl -X GET http://localhost:8080/api/users/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

#### 4. Test Update Profile
```bash
curl -X PUT http://localhost:8080/api/users/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Updated",
    "last_name": "Name"
  }'
```

#### 5. Test Admin Endpoint (List All Users)
```bash
curl -X GET http://localhost:8080/api/users \
  -H "Authorization: Bearer ADMIN_TOKEN_HERE"
```

---

## 📱 Testing with Postman

1. **Import Collection**
   - Create a new collection called "JMR Textile API"
   - Set base URL variable: `{{base_url}}` = `http://localhost:8080/api`

2. **Create Requests**
   - POST `{{base_url}}/users/register`
   - POST `{{base_url}}/users/login`
   - GET `{{base_url}}/users/profile`
   - PUT `{{base_url}}/users/profile`
   - DELETE `{{base_url}}/users/profile`
   - GET `{{base_url}}/users` (Admin)
   - GET `{{base_url}}/users/:id` (Admin)
   - PUT `{{base_url}}/users/:id` (Admin)
   - DELETE `{{base_url}}/users/:id` (Admin)

3. **Set Authorization**
   - After login, copy the token
   - In protected requests, add header: `Authorization: Bearer <token>`

---

## 🔗 Next.js Integration

### 1. Install Dependencies
```bash
npm install
# or
yarn install
```

### 2. Configure Environment Variables

Create `.env.local` in your Next.js project:
```env
NEXT_PUBLIC_API_URL=http://localhost:8080/api
```

### 3. Create API Client

Copy the API client code from `API_DOCUMENTATION.md` to `lib/api.ts` in your Next.js project.

### 4. Create Auth Context (Optional)

```typescript
// contexts/AuthContext.tsx
'use client';

import { createContext, useContext, useState, useEffect } from 'react';
import { authAPI } from '@/lib/api';

interface User {
  id: string;
  email: string;
  first_name: string;
  last_name: string;
  role: string;
}

interface AuthContextType {
  user: User | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => void;
  register: (userData: any) => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Check if user is logged in
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
      setUser(JSON.parse(storedUser));
    }
    setLoading(false);
  }, []);

  const login = async (email: string, password: string) => {
    const response = await authAPI.login(email, password);
    setUser(response.data.user);
  };

  const logout = () => {
    authAPI.logout();
    setUser(null);
  };

  const register = async (userData: any) => {
    const response = await authAPI.register(userData);
    setUser(response.data.user);
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, logout, register }}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
};
```

### 5. Protect Routes

```typescript
// middleware.ts
import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

export function middleware(request: NextRequest) {
  const token = request.cookies.get('token')?.value;

  if (!token && request.nextUrl.pathname.startsWith('/dashboard')) {
    return NextResponse.redirect(new URL('/login', request.url));
  }

  return NextResponse.next();
}

export const config = {
  matcher: ['/dashboard/:path*', '/profile/:path*'],
};
```

---

## ✅ Verification Checklist

### Backend
- [ ] Database `jmrtextile` exists
- [ ] Table `users` is created
- [ ] Admin account exists (email: `admin@jmrtextile.com`)
- [ ] JWT_SECRET_KEY is set in `.env`
- [ ] Server starts without errors
- [ ] Registration endpoint works
- [ ] Login endpoint returns valid token
- [ ] Protected routes require authentication
- [ ] Admin routes require admin role
- [ ] Upload endpoints accept allowed types and size limits

### Frontend (Next.js)
- [ ] API client is configured
- [ ] Environment variables are set
- [ ] Login page works
- [ ] Registration page works
- [ ] Token is stored in localStorage
- [ ] Protected routes redirect to login
- [ ] User can access their profile
- [ ] Admin can access admin panel

---

## 🐛 Troubleshooting

### "Call to undefined function mb_strpos()"
**Solution:** Enable the mbstring extension in your `php.ini`:
```ini
extension=mbstring
```

### "Connection refused" when testing API
**Solution:** Make sure the server is running:
```bash
php spark serve
```

### "CORS error" in browser
**Solution:** Verify CORS is configured in `app/Config/Cors.php` with your frontend URL.

### "Invalid token" error
**Solution:** 
1. Check that JWT_SECRET_KEY is set in `.env`
2. Verify the token is being sent in the `Authorization` header
3. Make sure the token hasn't expired (24h validity)

### Uploads fail or are truncated
**Solution:** Align `php.ini` upload limits with app config:
```ini
file_uploads = On
upload_max_filesize = 10M
post_max_size = 12M
max_file_uploads = 20
```
Then restart the web server.

### Database connection error
**Solution:** Verify database credentials in `.env` and ensure PostgreSQL is running.

---

## 📚 Additional Resources

- [CodeIgniter 4 Documentation](https://codeigniter.com/user_guide/)
- [JWT.io - JWT Debugger](https://jwt.io/)
- [Postman Documentation](https://learning.postman.com/)
- [Next.js Documentation](https://nextjs.org/docs)

---

## 🔐 Security Notes

### For Production:
1. **Change JWT Secret Key**
   ```env
   JWT_SECRET_KEY = your-very-secure-random-key-here
   ```

2. **Update CORS Origins**
   Edit `app/Config/Cors.php` to include only your production domain.

3. **Enable HTTPS**
   Set `app.forceGlobalSecureRequests = true` in `.env`

4. **Change Admin Password**
   Login as admin and update the password immediately.

5. **Add Rate Limiting**
   Consider implementing rate limiting for login/registration endpoints.

6. **Enable CSRF Protection**
   For web forms (if applicable), enable CSRF in `app/Config/Filters.php`

---

## 📞 Support

For issues or questions, refer to:
- `API_DOCUMENTATION.md` for API details
- CodeIgniter 4 documentation
- Project repository issues

---

**Happy Coding! 🚀**
