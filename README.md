**this is dog shit explanation**
# ConfigMaster

**ConfigMaster** is a web-based platform for the **centralized management of server configurations**. Designed for system administrators and advanced users, it simplifies the configuration and monitoring of services like **NGINX**, **MariaDB**, **rsnapshot**, and more — securely accessible from anywhere.

---

## 🌐 Project Architecture

The project is composed of three main layers:

### 1. Frontend (Vue.js)

* Framework: [Vue 3](https://vuejs.org/)
* Styling: Tailwind CSS with custom animations
* Features:

  * Dynamic navigation using `<router-view>` and `<RouterLink>`
  * Animated, collapsible sidebar
  * File editing and validation interface
  * Real-time updates via WebSocket

### 2. Backend (PHP + WebSocket)

* Language: PHP 8.x
* Web server: **NGINX with PHP-FPM**
* WebSocket: [Ratchet](http://socketo.me/) + [ReactPHP](https://reactphp.org/)
* Features:

  * REST API for configuration file operations
  * File parsing (e.g., NGINX config to JSON)
  * Real-time broadcasting via WebSocket
  * Security with JWT, CSRF protection, and RSA-encrypted credentials

### 3. Server Daemon (Go)

* Language: Go
* Purpose:

  * Applies configuration changes
  * Safely reloads or restarts services
  * Limits access to pre-authorized commands only
* Communication: Protected UNIX socket or named pipe

---

## 🚀 Project Goals

* Provide a **single web dashboard** to manage multiple server-side services.
* Allow **secure, real-time configuration editing** with live feedback.
* Enable **remote access** to server configurations from any location.
* Ensure **modularity and extensibility** to support new services over time.
* Lay the groundwork for **multi-node and multi-user** setups.

---

## 📁 Repository Structure

```
/frontend/         → Vue Single Page Application
/backend/          → PHP API and WebSocket server
/daemon/           → Go daemon for server-side operations
/nginx/            → Example NGINX configurations
/docs/             → Architecture diagrams and documentation
```

---

## ⚙️ How It Works with NGINX

The system is hosted using **NGINX**, configured as follows:

* Vue frontend is built (`vite build`) and served from the `/dist` directory.
* PHP backend is executed via **PHP-FPM**.
* WebSocket connections on `/ws` are proxied to an internal PHP Ratchet server.
* The Go daemon runs as a background service and is triggered securely by the backend.

**Basic NGINX configuration example:**

```nginx
server {
    listen 443 ssl;
    server_name configmaster.local;

    ssl_certificate     /etc/ssl/certs/cert.pem;
    ssl_certificate_key /etc/ssl/private/key.pem;

    location / {
        root /var/www/configmaster/frontend/dist;
        index index.html;
        try_files $uri $uri/ /index.html;
    }

    location /api/ {
        proxy_pass http://unix:/run/php/php8.2-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /var/www/configmaster/backend/api/index.php;
    }

    location /ws/ {
        proxy_pass http://localhost:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
}
```

---

## 🔐 Security Features

* **RSA encryption** of credentials on the frontend
* **JWT authentication** with refresh support
* **CSRF token validation** on each state-changing request
* **CORS** headers for safe development and deployment
* **mTLS (mutual TLS)** support for high-security production environments

---

## 🛠 Requirements

* Node.js (>= 18.x)
* PHP (>= 8.1) + Composer
* Go (>= 1.21)
* NGINX (>= 1.18) with PHP-FPM
* MariaDB/MySQL (optional: for user/auth management)

---

## 🧪 Running Locally

```bash
# Clone the repository
git clone https://github.com/your-username/configmaster.git
cd masterclass

# Start frontend (Vue)
cd frontend
npm install
npm run dev

# Backend (PHP) should be served via nginx + php-fpm
# Adjust nginx.conf or in sites-avalabile create a server block(previede in the repo) and genertate certificates (if for personal use)

# Start the WebSocket server
cd backend/websocket
php websocket-server.php

# Start the Go daemon
cd daemon
go build -o config-daemon main.go
sudo ./config-daemon
```

---

## 📈 Project Status

* ✅ NGINX config parsing and editing (fully working)
* ✅ WebSocket integration for real-time communication
* ✅ Secure login with RSA + JWT
* 🔄 In progress: MariaDB and rsnapshot modules
* 🔜 Coming soon:

  * Multi-user support with roles
  * System backup scheduling and cron management
  * Visual dashboard with service state monitoring

---

## 📄 License

This project is released under the **MIT License**.
See the [LICENSE](./LICENSE) file for details.

---

## 👤 Author

**Singh Gurpreet**
Graduation project – 4^BIN, NULLO BALDINI ITIS RAVENNA
Contact: \[[CONTACT ME](mailto:gurpreetchouhan2007@gmail.com)]
