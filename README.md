# Store Preschool

A mobile-friendly preschool shop for **Store.PreschoolApp.in**, built with PHP, MySQL, HTML, CSS, Bootstrap and JavaScript.

Parents and schools can browse toys, books, stationery, art supplies, furniture, learning materials and class kits, then check out with a school name, mobile number and delivery address. Razorpay is wired in for online payment. Cash on delivery and a demo payment are available when Razorpay keys are not set.

## Requirements

- XAMPP (Apache + MySQL + PHP 8.1 or newer)
- The site folder at `htdocs/storepreschoolapp`

## Install

1. Start **Apache** and **MySQL** in XAMPP.
2. Open [http://localhost/storepreschoolapp/install.php](http://localhost/storepreschoolapp/install.php) and click **Install database**.

Or import the SQL file yourself:

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root --default-character-set=utf8mb4 < database/storepreschool.sql
```

3. Open [http://localhost/storepreschoolapp/](http://localhost/storepreschoolapp/).

If MySQL uses a password, set `DB_USER` and `DB_PASS` in `config/config.php` before installing. The default XAMPP account is `root` with an empty password. The app connects to `127.0.0.1` on port `3306`.

The installer creates the `storepreschool` database. Reinstalling asks you to type `RESET` because it deletes the store data.

## Demo logins

Turn these off before a public launch by setting `DEMO_MODE` to `false` in `config/config.php`, then change the passwords.

| Role | Email | Password |
| --- | --- | --- |
| Admin | admin@storepreschoolapp.in | Admin@123 |
| Parent | parent@example.com | Parent@123 |
| Parent | rahul@example.com | Parent@123 |
| Parent | priya@example.com | Parent@123 |

Admin panel: [http://localhost/storepreschoolapp/admin/](http://localhost/storepreschoolapp/admin/)

Try coupon codes `WELCOME10`, `SCHOOL15` and `FLAT100`.

## Razorpay

1. Create test keys in the [Razorpay dashboard](https://dashboard.razorpay.com/app/keys).
2. Put them in `config/config.php`:

```php
define('RAZORPAY_KEY_ID', 'rzp_test_...');
define('RAZORPAY_KEY_SECRET', '...');
```

3. At checkout choose **Pay online with Razorpay**. The server creates a Razorpay order and checks the payment signature before marking the order paid.

If the keys are empty, the pay page shows **Complete demo payment** so checkout can still be tested. Cash on delivery does not need Razorpay. Orders stay unpaid until an admin marks payment as paid, or until the online payment is verified.

## What you can do

**Store**

- Search, categories, product pages, best sellers, trending and new arrivals
- Starter kit plus Nursery, Jr. KG and Sr. KG kits
- Cart, wishlist, coupons, checkout and order tracking
- Bulk ordering for schools
- Customer registration, login, profile and order history

**Admin**

- Sales, orders and customer counts
- Products, categories, stock, photos and visibility
- Customers, orders, payment status and delivery status
- Coupons, contact messages and a sales report with CSV export

Uploaded product photos go in `uploads/products`. PHP files cannot run from that folder.

## Folder map

- `config/` database and Razorpay settings
- `database/storepreschool.sql` schema and sample products
- `includes/` shared layout, cart, checkout and orders
- `api/` cart, wishlist, search and Razorpay verification
- `admin/` store administration
- `assets/` CSS, JavaScript and the icon

## Before going live

- Set `APP_DEBUG` and `DEMO_MODE` to `false`
- Change `BASE_URL` to `''` if the site is served from the domain root
- Replace the demo passwords
- Add live Razorpay keys
- Serve the site over HTTPS
# storepreschoolapp
