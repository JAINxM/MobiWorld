# MobiWorld Backend Testing Guide (Apache 8082 + MySQL 3307)

## 🚀 **Your XAMPP URLs**
```
App: http://localhost:8082/MobiWorld/
phpMyAdmin: http://localhost:8082/phpmyadmin
debug_db.php: http://localhost:8082/MobiWorld/debug_db.php
```

## ✅ **Backend Status**
- **MySQL**: localhost:3307 ✓ 
- **Apache**: port 8082 ✓
- **Database**: mobiworld ✓
- **Registration**: WORKS ✓
- **Login**: WORKS ✓

## 🧪 **Test 1: API Registration**
```bash
curl -X POST http://localhost:8082/MobiWorld/api/register.php \
-H "Content-Type: application/json" \
-d '{"full_name":"API Test","email":"api8082@test.com","password":"123456"}'
```

## 🧪 **Test 2: Check Data**
```
Browser: http://localhost:8082/MobiWorld/debug_db.php
phpMyAdmin: http://localhost:8082/phpmyadmin → mobiworld → user_master
```

## 🧪 **Test 3: Frontend**
```
1. http://localhost:8082/MobiWorld/register.php → new account
2. http://localhost:8082/MobiWorld/login.php → login  
3. http://localhost:8082/MobiWorld/index.php ✓
```

## 🎯 **SUCCESS =**
```
debug_db.php shows new user
phpMyAdmin shows data  
Frontend login redirects
```

**Backend READY!** 🎉
