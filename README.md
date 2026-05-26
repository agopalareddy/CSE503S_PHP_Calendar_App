# Chronos — Personal Calendar & Events Manager 📅✨

[![Course](https://img.shields.io/badge/WUSTL-CSE%20503S-blue.svg)](https://cse.wustl.edu/)
[![Backend](https://img.shields.io/badge/PHP-8.4-indigo.svg)](https://www.php.net/)
[![Database](https://img.shields.io/badge/MySQL-8.0-blue.svg)](https://www.mysql.com/)
[![Frontend](https://img.shields.io/badge/Vanilla%20JS-ES6-yellow.svg)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![Styling](https://img.shields.io/badge/CSS3-Vanilla-violet.svg)](https://developer.mozilla.org/en-US/docs/Web/CSS)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

> A secure, high-fidelity personal scheduling and event management application. This application features a premium, responsive glassmorphic dark-theme dashboard, low-latency client-side filters, and robust security defenses against common web vulnerabilities. Built with PHP 8.4 and MySQL.

---

## 📌 Table of Contents

- [🌟 Key Features](#-key-features)
- [🛠️ Tech Stack & Architecture](#️-tech-stack--architecture)
- [💾 Database Schema](#-database-schema)
- [⚙️ Setup & Local Installation](#-setup--local-installation)
- [🚀 Run & Quick Start](#-run--quick-start)
- [🛡️ Security & Architecture Best Practices](#️-security--architecture-best-practices)
- [🤝 Contributing & Support](#-contributing--support)
- [📄 License](#-license)

---

## 🌟 Key Features

- **📅 Dynamic Calendar Dashboard:** A fully responsive calendar month-view grid rendering dynamic daily appointments with interactive hover highlights and pop-up details.
- **⚡ Low-Latency Category Filtering:** Effortlessly filter events by categories (`Work`, `Personal`, `Social`, `Other`) using local JavaScript transitions without full-page reloads.
- **🎨 Glassmorphic Visuals:** Aesthetic dark-slate design system powered by the **Outfit** Google Font, fluid CSS transitions, custom HSL-tailored variables, and premium glassmorphic cards.
- **🛡️ Comprehensive Security:** Cryptographically secure CSRF protection, SQL Injection prevention via MySQL Prepared Statements, robust password hashing, and complete output sanitization.
- **✏️ Event Details Overlay:** Elegant modal controls and dedicated view pages supporting real-time category updates and cascading deletions.
- **📊 Metrics & Profile Analytics:** Visual dashboard widgets tracking personal productivity metrics (Total Events, Upcoming Events, Account metadata).

---

## 🛠️ Tech Stack & Architecture

### Technology Stack
- **Backend Core**: PHP 8.4 (Session tracking, anti-CSRF token verification, prepared statements)
- **Database Engine**: MySQL 8.0+ (Relational structure with foreign key cascades)
- **Frontend Scripting**: Vanilla ES6 ECMAScript (Fetch API integration, asynchronous rendering)
- **Styling System**: CSS Custom Properties (Responsive dark theme, glassmorphism card overlays)

### Directory Structure
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

Deploy the following relational MySQL schemas to configure the database:

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

## ⚙️ Setup & Local Installation

### Prerequisites
* **Web Server**: Apache or Nginx configured with PHP 8.4 support
* **Database**: MySQL 8.0+

### Installation
1. **Clone the Repository & Move to Directory**:
   ```bash
   git clone https://github.com/agopalareddy/tales-we-weave.git # (Sub-repository path: calendar-app)
   cd calendar-app
   ```

2. **Configure Environment Variables**:
   Create a `.env` file in the root of the `calendar-app/` directory:
   ```ini
   DB_SERVER=localhost
   DB_USERNAME=your_mysql_username
   DB_PASSWORD=your_mysql_password
   DB_NAME=calendar
   ```

3. **Deploy the Database**:
   Import the schemas listed in the [Database Schema](#-database-schema) section into your MySQL instance.

---

## 🚀 Run & Quick Start

Configure your Nginx or Apache server to target the `/public/` subdirectory for clean routing and public safety.

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

Verify your server connection and navigate to `http://localhost/calendar/` (or your customized server domain).

---

## 🛡️ Security & Architecture Best Practices

1. **Session Hardening:** All views check for valid user IDs via secure PHP session management (`session_start()`) and reject unauthorized traffic.
2. **CSRF Seals:** Custom, session-mapped cryptographic tokens prevent cross-site request forgery attacks.
3. **Bcrypt Hashing:** User passwords are encrypted using PHP `password_hash()` with secure cost factor parameters.
4. **SQL Injection Prevention:** Native MySQL prepared statements (`mysqli_stmt_bind_param`) explicitly bind inputs to isolate queries from parameters.
5. **XSS Protection:** Strict output sanitization using `htmlspecialchars()` on all dynamic user inputs before rendering inside the DOM.

---

## 🤝 Contributing & Support

### Contributions
This application is part of the **CSE 503S: Rapid Prototyping and Creative Programming** workspace at Washington University in St. Louis. Issues and PRs are welcome!

---

## 📄 License

Distributed under the **MIT License**. See `LICENSE` for details.
