# Postman (MobiWorld API)

## Import
- Import `postman/MobiWorld.postman_collection.json`
- Import `postman/MobiWorld.postman_environment.json`, then select environment **MobiWorld (Local)**

## Base URL
Default is `http://localhost:8082/MobiWorld` (see `README_TESTING.md`). If your Apache port/path is different, update `baseUrl` in the environment.

## Typical run order
1. `Auth/Login` (or `Auth/Register` once)
2. `Products/List Products` (auto-sets `product_id`)
3. `Cart/Add To Cart` → `Cart/Get Cart`
4. `Orders/Place Order` → `Orders/List Orders`

Notes:
- Auth is PHP session-cookie based; Postman should persist cookies automatically per domain.
- Razorpay verify needs real `razorpay_*` fields from the frontend checkout.

