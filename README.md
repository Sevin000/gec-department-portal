# CST463 Assignment 3 — GEC Wayanad Department Portal

This version extends the Assignment 2 Department Portal. It uses PHP + PDO + MySQL, JSON-only API responses, server-side live search, and session-protected create/update operations.

## Requirements
- PHP 8+ with PDO MySQL extension (`php-mysql` on Ubuntu)
- MySQL or MariaDB
- Browser with Fetch API support

## 1. Install packages on Ubuntu
```bash
sudo apt update
sudo apt install php-cli php-mysql mysql-server
```

## 2. Create and seed the database
```bash
sudo mysql < database.sql
```
If your MySQL root account uses a password or a separate database user, update `config.php` accordingly. For better security, create a dedicated MySQL user rather than using root.

## 3. Configure database and demo login
Edit `config.php`:
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- Demo username: `admin`
- Demo password: `gecadmin123`

The assignment explicitly excludes password hashing and registration, so these are fixed demonstration credentials. Do not use them on a public production server.

## 4. Start the PHP server
From this directory:
```bash
php -S localhost:8002 router.php
```
Open `http://localhost:8002`. Keep the terminal running.

## API routes
- `GET /api/department` — department overview, programmes, facilities and research areas
- `GET /api/items` — list faculty
- `GET /api/items?q=keyword` — search faculty by name, designation or specialization
- `POST /api/login` — start an authenticated PHP session; JSON `{ "username": "admin", "password": "gecadmin123" }`
- `GET /api/session` — check current session
- `POST /api/items` — create faculty; requires login
- `PUT /api/items/1` — update faculty; requires login
- `POST /api/logout` — destroy session

All API responses are JSON. The PHP API does not generate HTML. `router.php` maps clean API paths to PHP endpoint files for the built-in development server.

## Test with curl
Public read:
```bash
curl -i http://localhost:8002/api/items
curl -i 'http://localhost:8002/api/items?q=Alice'
```
Unauthenticated mutation should be rejected with HTTP 401:
```bash
curl -i -X POST http://localhost:8002/api/items -H 'Content-Type: application/json' -d '{"name":"Test Person","designation":"Assistant Professor","specialization":["Networks"]}'
```
Login and save the session cookie:
```bash
curl -i -c cookies.txt -X POST http://localhost:8002/api/login -H 'Content-Type: application/json' -d '{"username":"admin","password":"gecadmin123"}'
```
Create using the authenticated session:
```bash
curl -i -b cookies.txt -X POST http://localhost:8002/api/items -H 'Content-Type: application/json' -d '{"name":"Prof. Test Person","designation":"Assistant Professor","specialization":["Networks"]}'
```
Update a record (replace `1` with a real ID):
```bash
curl -i -b cookies.txt -X PUT 'http://localhost:8002/api/items/1' -H 'Content-Type: application/json' -d '{"name":"Prof. Alice A","designation":"Associate Professor","specialization":["Artificial Intelligence","Machine Learning"]}'
```

## Database design
The schema is normalized across departments, programmes, department_programmes, faculty, specializations, faculty_specializations, facilities and research_areas. Many-to-many relationships are represented using junction tables. Prepared statements are used for user-supplied SQL values.

## Notes
- This is a teaching/demo project, not an official GEC Wayanad service. Sample faculty names are fictional.
- The assignment requests Create, Read and Update; Delete is intentionally not implemented.
- Do not deploy the fixed demo password to a public production server.
