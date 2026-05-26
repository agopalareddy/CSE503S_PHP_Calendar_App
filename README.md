# Personal Calendar & Events Manager

A secure, high-fidelity personal scheduling and event management application built with **PHP 8.4** and **MySQL**. This application features a premium, responsive glassmorphic dark-theme dashboard, low-latency client-side filters, and robust security defenses against common web vulnerabilities.

---

## 🌟 Key Features

*   **Dynamic Calendar Dashboard**: A fully responsive calendar month-view grid rendering dynamic daily appointments with interactive hover highlights.
*   **Low-Latency Category Filtering**: Effortlessly filter events by categories (`Work`, `Personal`, `Social`, `Other`) using local JavaScript transitions without full-page reloads.
*   **Sleek Modern Visuals**: Aesthetic dark-slate design system powered by the **Outfit** Google Font, fluid CSS transitions, custom HSL-tailored variables, and glassmorphism cards.
*   **Strict Security Architecture**:
    *   **Robust CSRF Protection**: Cryptographically secure anti-CSRF tokens validating all state-changing endpoints (updates, insertions, deletions).
    *   **SQL Injection Prevention**: All data mutations and selects are bound through MySQL Prepared Statements (`mysqli_stmt_bind_param`).
    *   **Secure Authentication**: User registration and logins powered by industry-standard cryptographically hashed password databases (`password_hash` with bcrypt).
    *   **XSS Protection**: Comprehensive output sanitization using specialized `htmlspecialchars()` filters.
*   **Event Details Overlay**: Beautiful modal controls and dedicated view pages supporting category modifications and deletions.
*   **Metrics & Profile Logs**: Visual dashboard cards tracking personal productivity metrics (Total Events, Upcoming Events, Account details).

---

## 🛠️ Technology Stack

*   **Backend Core**: PHP 8.4
*   **Database Engine**: MySQL
*   **Styling (CSS)**: Vanilla CSS3 (CSS Variables, Flexbox, CSS Grid, Glassmorphic overlays)
*   **Interactions (JS)**: Vanilla ECMAScript 6 (Fetch API, dynamic rendering, coordinator-based listeners)

---

## 📁 Repository Structure

```
calendar-app/
├── includes/
│   ├── auth.php         # Authentication helpers & database validation
│   ├── config.php       # Environment configuration loader & DB connection pool
│   └── functions.php    # Common sanitization & CSRF token generator utilities
│
├── public/
│   ├── api/
│   │   └── events.php   # REST API endpoint for calendar events (Prepared statements)
│   │
│   ├── index.php        # Core Calendar dashboard page
│   ├── login.php        # Secure authentication login portal
│   ├── logout.php       # Session termination script
│   ├── past_events.php  # Completed appointments history & log
│   ├── profile.php      # User metrics summary & settings
│   ├── register.php     # Account creation page
│   ├── style.css        # Responsive slate design system
│   └── view_event.php   # Dynamic event detail card & editor modal
│
├── .gitignore           # Ignored system files and sensitive environment values
└── README.md            # ★ This comprehensive GitHub documentation
```

---

## 💾 Database Schema

Deploy the following relational MySQL schemas to set up the database:

### 1. Users Table
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Events Table
```sql
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME DEFAULT NULL,
    category ENUM('work', 'personal', 'social', 'other') DEFAULT 'other',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🚀 Setup & Local Installation

### 1. Prerequisites
- **Web Server**: Apache or Nginx configured with PHP 8.4 support
- **Database Server**: MySQL 8.0+

### 2. Set Up Environment Configuration
Create a `.env` file in the root of the `calendar-app/` directory (properly excluded from git in `.gitignore`) and define database credentials:

```ini
DB_SERVER=localhost
DB_USERNAME=your_mysql_username
DB_PASSWORD=your_mysql_password
DB_NAME=calendar
```

### 3. Configure Local Nginx/Apache Path
Point your local web server configuration to target the `/public/` subdirectory for clean routing and public safety.

For Nginx:
```nginx
location /calendar/ {
    alias /opt/calendar/public/;
    index index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $request_filename;
        include fastcgi_params;
    }
}
```

---

## 🛡️ Security Best Practices Enforced
1.  **Session Hardening**: All views check for valid user IDs via `session_start()` and automatically reject unauthorized HTTP requests.
2.  **CSRF Seals**: Custom cryptographic hashes mapped to individual sessions prevent remote scripting attacks.
3.  **Bcrypt Hashing**: Passwords are securely hashed with bcrypt using standard cost configurations to counter brute-force table attacks.
4.  **Prepared Statements**: Dynamic query arguments are explicitly bound via native parameter interfaces to prevent SQL injection.
