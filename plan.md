# GameTopUp Pro — Full AI Agent Prompt

> Paste this entire prompt into Cursor, Windsurf, Claude Code, or any AI coding agent.

---

## ROLE & CONTEXT

You are a senior full-stack web developer with 10+ years of experience building secure, scalable, production-ready PHP applications. Your code is clean, well-commented, and follows best practices in security, database design, and modern UI.

Build a complete **game top-up recording web application** called **GameTopUp Pro** from scratch. Every file must be fully functional — no placeholders, no TODO comments, no skeleton code.

---

## TECH STACK

| Layer    | Technology                                |
| -------- | ----------------------------------------- |
| Backend  | PHP 8.x Native (no framework)             |
| Database | MySQL 8.x with PDO                        |
| Frontend | Tailwind CSS v3 (CDN), Alpine.js v3 (CDN) |
| Charts   | Chart.js v4 (CDN)                         |
| Export   | jsPDF + AutoTable (PDF), SheetJS (Excel)  |
| Alerts   | SweetAlert2 (CDN)                         |

---

## APPLICATION NAME

**GameTopUp Pro** — A web-based system to record, manage, and report game top-up transactions with role-based access control.

---

## DATABASE SCHEMA (4 Required Tables)

### 1. `users` table

```sql
CREATE TABLE users (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    username      VARCHAR(50) UNIQUE NOT NULL,
    password      VARCHAR(255) NOT NULL,         -- bcrypt hashed
    full_name     VARCHAR(100) NOT NULL,
    avatar        VARCHAR(255) DEFAULT NULL,
    role          ENUM('admin','operator') DEFAULT 'operator',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 2. `categories` table (Detail/Category)

```sql
CREATE TABLE categories (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    name          VARCHAR(100) NOT NULL,
    description   TEXT DEFAULT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 3. `games` table (Master)

```sql
CREATE TABLE games (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    category_id   INT NOT NULL,
    name          VARCHAR(100) NOT NULL,
    thumbnail     VARCHAR(255) DEFAULT NULL,
    publisher     VARCHAR(100) DEFAULT NULL,
    status        ENUM('active','inactive') DEFAULT 'active',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
);
```

### 4. `transactions` table

```sql
CREATE TABLE transactions (
    id             INT PRIMARY KEY AUTO_INCREMENT,
    invoice_code   VARCHAR(50) UNIQUE NOT NULL,
    user_id        INT NOT NULL,
    game_id        INT NOT NULL,
    customer_name  VARCHAR(100) NOT NULL,
    game_uid       VARCHAR(50) NOT NULL,
    item_name      VARCHAR(100) NOT NULL,
    quantity       INT NOT NULL DEFAULT 1,
    price          DECIMAL(15,2) NOT NULL,
    total          DECIMAL(15,2) NOT NULL,
    status         ENUM('pending','success','failed') DEFAULT 'pending',
    notes          TEXT DEFAULT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE RESTRICT
);
```

**Seed Data:** Insert 1 default admin user:

- Username: `admin`, Password: `admin123` (bcrypt hashed), Role: `admin`
- Insert 3 sample categories: `MOBA`, `Battle Royale`, `RPG`
- Insert 5 sample games spread across categories
- Insert 10 sample transactions with varied statuses

---

## FOLDER STRUCTURE

```
gametopup/
├── config/
│   └── database.php
├── auth/
│   ├── login.php
│   └── logout.php
├── middleware/
│   └── auth.php
├── includes/
│   ├── sidebar.php
│   ├── header.php
│   ├── footer.php
│   └── flash.php
├── modules/
│   ├── dashboard/
│   │   └── index.php
│   ├── users/
│   │   ├── index.php
│   │   ├── create.php
│   │   ├── edit.php
│   │   └── delete.php
│   ├── categories/
│   │   ├── index.php
│   │   ├── create.php
│   │   ├── edit.php
│   │   └── delete.php
│   ├── games/
│   │   ├── index.php
│   │   ├── create.php
│   │   ├── edit.php
│   │   └── delete.php
│   └── transactions/
│       ├── index.php
│       ├── create.php
│       ├── edit.php
│       ├── delete.php
│       └── export.php
├── uploads/
│   ├── avatars/
│   └── thumbnails/
├── database.sql
└── index.php
```

---

## FEATURE SPECIFICATIONS

### 1. Authentication System

- **Login page** (`auth/login.php`):
  - Clean, centered card layout with GameTopUp Pro branding
  - Fields: Username, Password (with show/hide toggle)
  - Server-side validation: empty fields → show inline error
  - Use `password_verify()` against bcrypt hash
  - On success → set `$_SESSION['user_id']`, `$_SESSION['role']`, `$_SESSION['full_name']` → redirect to dashboard
  - On failure → show SweetAlert2 error toast
- **Session Guard** (`middleware/auth.php`):
  - Include at top of every protected page
  - If `$_SESSION['user_id']` is not set → `header('Location: /auth/login.php')` and `exit`
- **Logout** (`auth/logout.php`):
  - `session_destroy()` → redirect to login

### 2. Sidebar & Navigation (`includes/sidebar.php`)

- Fixed left sidebar (collapsible on mobile via Alpine.js)
- Logo/brand at top: "GameTopUp Pro" with gamepad icon (SVG inline)
- Menu items with icons:
  - Dashboard
  - Transactions
  - Games
  - Categories
  - Users (visible to admin only, hide for operator)
- Active state highlight on current page
- Bottom section: logged-in user avatar + name + logout button
- Top navbar: hamburger for mobile sidebar toggle, page title, user role badge

### 3. Dashboard (`modules/dashboard/index.php`)

**Summary Cards (grid of 4):**

- Total Transactions (all time)
- Total Revenue (sum of successful transactions)
- Total Games (active)
- Total Users

**Charts (Chart.js):**

- Bar chart: transactions per day (last 7 days)
- Doughnut chart: transaction status breakdown (pending / success / failed)

**Recent Transactions Table:**

- Last 10 transactions with invoice, game, customer, total, status badge, date

### 4. CRUD — Categories (`modules/categories/`)

- `index.php`: paginated table (10/page), search by name, action buttons (Edit, Delete)
- `create.php`: form — Name (required), Description (textarea)
- `edit.php`: pre-filled form
- `delete.php`: POST handler — check if category has linked games before deleting; if yes, block and show error flash

### 5. CRUD — Games (`modules/games/`)

- `index.php`: paginated table with thumbnail preview, category name, status badge, search by game name
- `create.php`: form fields:
  - Category (dropdown from categories table, required)
  - Name (required)
  - Publisher
  - Thumbnail upload (jpg/png/webp, max 2MB, rename with `uniqid()`, store in `uploads/thumbnails/`)
  - Status (Active/Inactive toggle)
- `edit.php`: pre-filled; show current thumbnail; option to replace image
- `delete.php`: POST handler — block if game has transactions

### 6. CRUD — Transactions (`modules/transactions/`)

- `index.php`:
  - Paginated table (10/page)
  - Search by invoice code, customer name, or game name
  - Filter by status (All / Pending / Success / Failed)
  - Filter by date range (from/to)
  - Status badges: yellow=pending, green=success, red=failed
  - Action: Edit, Delete
  - Buttons: "Export PDF", "Export Excel" (trigger `export.php` with current filters)
- `create.php`:
  - Auto-generate `invoice_code`: format `INV-YYYYMMDD-XXXXX` (random 5 chars)
  - Fields: Customer Name, Game (dropdown, required), Game UID, Item Name, Quantity, Price (numeric), Status, Notes
  - Auto-calculate Total = Quantity × Price (live via Alpine.js)
  - `user_id` = `$_SESSION['user_id']`
- `edit.php`: pre-filled; allow editing all fields
- `delete.php`: POST handler with confirmation
- `export.php`:
  - Accept `?type=pdf` or `?type=excel`
  - Respect search/filter params from index
  - PDF: landscape, table with columns — Invoice, Customer, Game, Item, Qty, Price, Total, Status, Date
  - Excel: same columns, formatted

### 7. CRUD — Users (`modules/users/`) — Admin only

- `index.php`: paginated table, search by username or full name
- `create.php`: fields — Full Name, Username, Password, Role, Avatar upload
- `edit.php`: update fields; password field optional (leave blank = keep current); avatar optional
- `delete.php`: prevent deleting yourself (compare with `$_SESSION['user_id']`)

---

## TECHNICAL REQUIREMENTS

### Security

- All DB queries use **PDO prepared statements** — zero raw string interpolation in SQL
- `htmlspecialchars()` on all output to prevent XSS
- `$_SESSION` check on every protected page via middleware
- File uploads: validate MIME type via `finfo_file()`, not just extension; store outside web root is ideal, or restrict direct access via `.htaccess`
- CSRF: add a hidden `csrf_token` field on all forms; validate on POST

### Flash Messages (`includes/flash.php`)

```php
// Set: $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data saved!'];
// Display via SweetAlert2 toast on page load using Alpine.js x-init
```

- Types: `success` (green), `error` (red), `warning` (yellow), `info` (blue)
- Auto-dismiss after 3 seconds

### Server-side Validation Rules

| Field       | Rule                                                      |
| ----------- | --------------------------------------------------------- |
| Name/text   | Not empty, max 100 chars                                  |
| Price       | Numeric, > 0                                              |
| Quantity    | Integer, >= 1                                             |
| Username    | Alphanumeric + underscore, unique                         |
| Password    | Min 6 characters                                          |
| File upload | MIME = image/jpeg or image/png or image/webp, size <= 2MB |

### Pagination

- 10 rows per page
- Show: "Showing X–Y of Z results"
- Page links: Previous, numbered pages, Next
- Preserve search/filter params in page links

### Responsive Design (Tailwind CSS)

- Mobile-first layout
- Sidebar hidden on mobile, toggled via hamburger button (Alpine.js)
- Tables scroll horizontally on small screens (`overflow-x-auto`)
- Cards stack vertically on mobile, grid on desktop
- All forms usable on 375px screen width

---

## UI DESIGN GUIDELINES

- **Color palette:**
  - Primary: Indigo (`indigo-600`) for sidebar, buttons, accents
  - Background: `gray-50` for page, `white` for cards
  - Success: `green-500`, Warning: `yellow-500`, Danger: `red-500`
- **Cards:** white bg, rounded-xl, shadow-sm, subtle border
- **Tables:** striped rows (`even:bg-gray-50`), hover state, sticky header
- **Buttons:**
  - Primary action: `bg-indigo-600 text-white hover:bg-indigo-700 rounded-lg px-4 py-2`
  - Danger: `bg-red-100 text-red-700 hover:bg-red-200`
  - Secondary: outlined style
- **Status badges:** pill-shaped, color-coded
- **Sidebar:** dark (`gray-900`) with indigo accent on active item
- **Typography:** Inter font (Google Fonts CDN), clean hierarchy
- Every page has a clear page title + breadcrumb

---

## DELIVERABLES CHECKLIST

Generate ALL of the following files completely (no skipping):

- [ ] `database.sql` — full schema + seed data
- [ ] `config/database.php` — PDO connection with error handling
- [ ] `index.php` — redirect logic
- [ ] `auth/login.php` — full login page UI + logic
- [ ] `auth/logout.php`
- [ ] `middleware/auth.php`
- [ ] `includes/sidebar.php`
- [ ] `includes/header.php`
- [ ] `includes/footer.php`
- [ ] `includes/flash.php`
- [ ] All CRUD files for: `users`, `categories`, `games`, `transactions`
- [ ] `modules/dashboard/index.php` — with Chart.js graphs
- [ ] `modules/transactions/export.php` — PDF + Excel export

---

## CODING STANDARDS

- Use `camelCase` for PHP variables, `snake_case` for DB columns
- Comment every function with a one-line docblock
- Group related logic into functions where reusable (e.g., `getPaginationLinks($total, $page, $perPage, $params)`)
- No inline styles — use Tailwind classes only
- Consistent indentation: 4 spaces

---

## START INSTRUCTION

Begin by generating `database.sql` first, then `config/database.php`, then `auth/login.php`, then the `includes/` files, then each module in this order: dashboard → categories → games → transactions → users.

Do not ask clarifying questions. Build everything now.
