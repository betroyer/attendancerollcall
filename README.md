# SFIT Student's Attendance System

PHP + MySQL attendance system for teachers, built for XAMPP.

## Features

1. **User Login** — Teachers sign in to keep attendance data secure
2. **Take Attendance** — Select date, class, and subject; mark students Present, Absent, or Late
3. **Roll Call** — Call students one by one and mark them as you go (keyboard shortcuts supported)
4. **View Attendance Report** — Search records by Student ID or by Class
5. **Print & Export** — Print the report or download it as an Excel-ready CSV file

## Setup

1. Start **Apache** and **MySQL** in XAMPP
2. Import the database (if not already imported):

```bash
/c/xampp/mysql/bin/mysql -u root < database/schema.sql
```

Or open phpMyAdmin → Import → `database/schema.sql`

3. Open in the browser:

`http://localhost/attendance%20system/login.php`

## Demo login

| Username | Password   |
|----------|------------|
| teacher  | teacher123 |
| admin    | teacher123 |

## Sample students

Use IDs like `STU-001`, `STU-002`, … when searching reports.
