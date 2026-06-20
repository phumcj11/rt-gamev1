# AR Lucky Elephant Hunt 🐘

Mobile-first WebAR retail campaign game. Customers scan a QR code, hunt lucky elephant mascots via AR, collect 3 items, and win random rewards.

## Tech Stack

- **Frontend:** HTML, Tailwind CSS, JavaScript
- **WebAR:** MindAR + A-Frame
- **Backend:** PHP
- **Database:** MySQL

## Quick Start (XAMPP)

1. Place project in `c:\xampp\htdocs\rt-gamear1`
2. Start **Apache** and **MySQL** in XAMPP
3. Import database:
   ```bash
   mysql -u root < database/schema.sql
   ```
4. Open: [http://localhost/rt-gamear1/](http://localhost/rt-gamear1/)

## Pages

| URL | Description |
|-----|-------------|
| `/index.php` | Landing page with QR entry point |
| `/game.php` | WebAR camera game |
| `/reward.php` | Reward reveal + registration form |
| `/admin/login.php` | Staff admin login |
| `/admin/dashboard.php` | Stats dashboard |
| `/admin/redeem.php` | Search & redeem coupon codes |

## Game Flow

1. Customer scans QR → landing page
2. Tap **Start AR Game**
3. Allow camera permission
4. Point camera at image target (store logo / poster / shopping bag)
5. 3D elephant mascot appears on target
6. Tap mascot to collect lucky items (3 total)
7. Random reward is assigned
8. Customer fills name, phone, branch, nationality
9. Coupon code saved to MySQL
10. Staff verifies & redeems via admin panel

## Admin Login

- **URL:** `/admin/login.php`
- **Username:** `admin`
- **Password:** `admin123`

Change the password after first login in production.

## AR Image Targets

### Demo (included)

For testing, the game uses the MindAR **card example** target:
- Scan target image: `assets/targets/demo-scan-target.png`
- Compiled file: `assets/targets/store-target.mind`

Print `demo-scan-target.png` or display it on a screen to test AR.

### Production (your store branding)

1. Design a high-contrast target image (see `assets/targets/store-target.svg`)
2. Compile at [MindAR Image Target Compiler](https://hiukim.github.io/mind-ar-js-doc/tools/compile)
3. Replace `assets/targets/store-target.mind` with your compiled file
4. Print targets on store logo, shopping bags, and campaign posters

## Language Support

Thai and English — toggle via header button on all public pages. Add `?lang=th` or `?lang=en` to any URL.

## Database Config

Edit `config.php` if needed:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ar_elephant_hunt');
define('DB_USER', 'root');
define('DB_PASS', '');
```

## Reward Pool

Rewards are weighted randomly from the `reward_pool` table. Edit via MySQL or add an admin UI later.

## QR Code

Generate a QR code pointing to:
```
http://your-domain/rt-gamear1/index.php
```

Place QR codes at store entrances, counters, and campaign materials.

## File Structure

```
rt-gamear1/
├── index.php
├── game.php
├── reward.php
├── config.php
├── api/
│   ├── collect-item.php
│   └── save-player.php
├── admin/
│   ├── login.php
│   ├── dashboard.php
│   ├── redeem.php
│   └── logout.php
├── assets/
│   ├── css/app.css
│   ├── js/game.js
│   ├── targets/
│   └── models/
├── database/schema.sql
├── includes/
├── lang/
└── README.md
```

## Requirements

- PHP 8.0+
- MySQL 5.7+
- HTTPS recommended for camera access on mobile devices
- Modern mobile browser (Chrome/Safari)

## License

Campaign project — customize freely for your retail store.
