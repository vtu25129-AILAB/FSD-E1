## Skill Academy (PHP + MySQL on XAMPP)

### Tech stack
- **Frontend**: HTML5, CSS3, ES6
- **Backend**: PHP (XAMPP)
- **Database**: MySQL

### Features implemented
- **Secure authentication**: registration + login for **Student** and **Teacher** only
- **Student module**
  - `register.php`, `login.php`
  - `student/home.php`, `student/courses.php`, `student/course.php`
  - Progress tracking (10 modules) + **certificate generation**
  - `student/doubts.php`, `student/profile.php`, `student/certificates.php`
- **Teacher module**
  - `teacher/dashboard.php`
  - Add/manage courses: `teacher/courses.php`
  - Enrollment/completion table + certificate links: `teacher/enrollments.php`
  - Doubts reply: `teacher/doubts.php`
  - `teacher/profile.php`
- **Seed data**: **30 courses** (15 free + 15 paid)

### Setup (XAMPP)
1. Start **Apache** and **MySQL** from XAMPP Control Panel.
2. Open phpMyAdmin at `http://localhost/phpmyadmin`
3. Import the database:
   - Import file: `database/schema.sql`
4. Open the app:
   - `http://localhost/skill-academy/`

### Demo teacher login (after DB import)
- **Email**: `teacher@skillacademy.local`
- **Password**: `Teacher@123`

### Notes
- Certificate download is implemented as a **printable certificate page** (use “Download / Print” → save as PDF).
- You can replace any seeded YouTube playlist URLs with real playlists later.

