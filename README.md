# ⚔️ Kabuto Esports — Tournament Registration Platform

<div align="center">

![Kabuto Esports](https://img.shields.io/badge/Kabuto-Esports-f5a623?style=for-the-badge&logo=gamepad&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![PayU](https://img.shields.io/badge/PayU-Payment-00A3E0?style=for-the-badge&logo=payu&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

**India's Premier BGMI Tournament Registration & Payment Platform**

[🌐 Live Site](https://kabutoesports.com) · [🐛 Report Bug](https://github.com/shahnawazofficial/kabuto_esports_website/issues) · [💡 Request Feature](https://github.com/shahnawazofficial/kabuto_esports_website/issues)

</div>

---

## 📸 Screenshots

> Coming soon — Live at [kabutoesports.com](https://kabutoesports.com)

---

## ✨ Features

### 🎮 Player Features
- **Tournament Listings** — Browse all upcoming BGMI tournaments with filters
- **Squad/Duo/Solo Registration** — Full team registration with IGN, UID & WhatsApp for all players
- **Secure Payments** — Integrated PayU Now payment gateway for paid tournaments
- **Coupon Codes** — Discount coupons with percentage or fixed amount support
- **Referral System** — Track registrations by referral username
- **User Dashboard** — View your registered tournaments and payment status
- **Auth-Aware UI** — Navbar dynamically shows Login/Signup or Profile/Logout

### 🔧 Admin Features
- **Tournament Management** — Create, edit, delete tournaments with banner uploads
- **Registration Management** — View all registrations with payment status
- **Payment Tracking** — Full payment history and status updates
- **Coupon Generator** — Create discount coupons with expiry, usage limits, and min-fee rules
- **Admin Portal** — Separate secure admin panel at `/admin`

### 🔒 Security
- CSRF protection on all forms
- Bcrypt password hashing
- SQL injection prevention via prepared statements
- Session security with regeneration
- Rate limiting on registration attempts
- Sensitive config excluded from version control

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.0+ (vanilla, no framework) |
| Database | MySQL 5.7+ / MariaDB |
| Frontend | HTML5, CSS3 (Vanilla), JavaScript (ES6+) |
| Payment | PayU Now (India) |
| Hosting | Hostinger Shared Hosting |
| Auth | Custom session-based authentication |

---

## 📁 Project Structure

```
kabuto-esports/
├── admin/                  # Admin panel
│   ├── includes/           # Admin auth, layout
│   ├── tournaments.php     # Manage tournaments
│   ├── registrations.php   # View registrations
│   ├── payments.php        # Payment tracking
│   └── coupons.php         # Coupon management
├── config/
│   ├── config.example.php  # ← Copy this to config.php
│   └── database.php        # DB connection class
├── includes/               # Shared PHP classes
│   ├── functions.php       # Global helpers
│   ├── Security.php        # CSRF, sanitization
│   ├── UserAuth.php        # Authentication
│   ├── PayUGateway.php     # Payment integration
│   ├── RegistrationHelper.php
│   ├── EmailService.php
│   └── navbar.php          # Shared auth-aware navbar
├── public/                 # Web root files
│   ├── index.php           # Homepage
│   ├── tournaments.php     # Tournament listing
│   ├── tournament.php      # Tournament detail
│   ├── register.php        # Registration form
│   ├── dashboard.php       # User dashboard
│   ├── login.php / signup.php
│   ├── validate_coupon.php # Coupon AJAX API
│   └── assets/             # CSS, JS, images
├── database/
│   └── kabuto_esports.sql  # Database schema
└── .htaccess               # URL routing
```

---

## 🚀 Installation & Setup

### Prerequisites
- PHP 8.0+
- MySQL 5.7+
- Web server (Apache with mod_rewrite)
- PayU merchant account (for paid tournaments)

### Steps

**1. Clone the repository**
```bash
git clone https://github.com/shahnawazofficial/kabuto_esports_website.git
cd kabuto_esports_website
```

**2. Configure the application**
```bash
cp config/config.example.php config/config.php
```
Edit `config/config.php` and fill in:
- Database credentials
- PayU merchant key & salt
- SMTP email settings
- App URL

**3. Import the database**
```bash
mysql -u your_user -p your_database < database/kabuto_esports.sql
```

**4. Set up web server**

Point your web root to the project root. The `.htaccess` handles URL routing automatically.

**5. Create uploads directory**
```bash
mkdir -p uploads/banners
chmod 755 uploads/
```

**6. Create first admin account**

Visit `/setup.php` (if available) or insert directly:
```sql
INSERT INTO admins (username, password, role)
VALUES ('admin', '$2y$12$YOUR_BCRYPT_HASH', 'super_admin');
```

**7. Access admin panel**
```
https://yourdomain.com/admin/login.php
```

---

## 🔑 Environment Variables

Copy `config/config.example.php` → `config/config.php` and configure:

```php
// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

// PayU Payment Gateway
define('PAYU_KEY',  'YOUR_PAYU_MERCHANT_KEY');
define('PAYU_SALT', 'YOUR_PAYU_SALT');
define('PAYU_ENV',  'production'); // or 'test'

// App
define('APP_URL', 'https://yourdomain.com');
define('SECRET_KEY', 'random-32-char-string');
```

> ⚠️ **Never commit `config/config.php`** — it's in `.gitignore` for security.

---

## 🎟️ Coupon System

Admins can create coupon codes with:
- **Percentage discount** (e.g. 20% off)
- **Fixed amount discount** (e.g. ₹50 off)
- **Expiry date/time**
- **Usage limits**
- **Minimum entry fee** requirement

Users apply coupons during registration with live AJAX validation — the discounted price updates in real-time before payment.

---

## 💳 Payment Flow

```
User fills form → Apply coupon (optional) → Proceed to Payment
    → PayU payment page → Payment success/failure
    → Webhook updates status → Confirmation email sent
```

Supports both **free tournaments** (instant confirmation) and **paid tournaments** (PayU gateway).

---

## 🤝 Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📄 License

Distributed under the MIT License. See `LICENSE` for more information.

---

## 👤 Author

**Shahnawaz** — [@shahnawazofficial](https://github.com/shahnawazofficial)

🌐 Live Platform: [kabutoesports.com](https://kabutoesports.com)

---

<div align="center">
Made with ❤️ for the Indian BGMI Esports Community
</div>
