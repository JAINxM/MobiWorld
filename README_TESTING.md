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

---

# Razorpay (Test) Payment Setup

## 1) Add your Razorpay Test Keys
- Edit `includes/config/razorpay.php`
  - `RAZORPAY_KEY_ID` = `rzp_test_...`
  - `RAZORPAY_KEY_SECRET` = your test secret

## 2) Test the flow
1. Login
2. Add items to cart
3. Go to `checkout.php`
4. Select **Razorpay (Test)**
5. Click **Confirm & Place Order**

Backend endpoints used:
- `api/razorpay_create_order.php` (creates Razorpay order from cart total)
- `api/razorpay_verify.php` (verifies signature, inserts DB order, clears cart)

---

# Currency: USD -> INR

UI currency is set to INR (₹). If your DB prices are still in USD, run the one-time conversion:

```bash
C:\xampp\php\php.exe MobiWorld\tools\convert_usd_to_inr.php 83.00
```

This updates `product_master`, `cart_items`, `order_items`, and `orders` by multiplying values with the rate and writes a marker file `.currency_migrated_to_inr` to prevent running twice.
