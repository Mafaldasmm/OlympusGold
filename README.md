# ltw10g02

## Features

**All users:**
- [x] Register a new account.
- [x] Log in and out.
- [x] Edit their profile, including name, username, password, and profile photo.

**Members:**
- [x] Browse the schedule of available fitness classes, filtering by type, trainer, day, or time.
- [x] Enroll in and cancel enrollment from upcoming classes, subject to capacity limits.
- [x] View trainer profiles, including their specializations and the classes they teach.
- [x] Check the current availability of equipment in the main training area.
- [x] Leave ratings and reviews for classes they have attended.

**Trainers:**
- [x] Manage their public profile, including bio, specializations, and certifications.
- [x] View the roster of members enrolled in their classes.
- [x] Track and manage their assigned class schedule.

**Admins:**
- [x] Manage members and trainers (create, update, and deactivate accounts).
- [x] Manage the class catalog (create, edit, and remove classes) and assign trainers to them.
- [x] Manage equipment in the main training area (add, update availability status, and remove items).
- [x] Elevate a user to admin status.
- [x] Oversee and ensure the smooth operation of the entire system.

**Extra:**
- [x] Class waitlist when a class is full (join, leave, automatic promotion on cancellation).
- [x] Trainer analytics for assigned classes (enrollments, capacity utilization, review averages, waitlist and enrolled members).
- [x] Admin gym-wide analytics (popular classes, capacity utilization, review summaries, equipment status, member activity; searchable class overview).

## Running

From the project root (matches course delivery test):

```bash
php -S localhost:9000
```

Open http://localhost:9000/ — the database is created automatically on first request if missing.

To reset or recreate the database manually:

```bash
php reset_db.php
# or: php database/init.php --force
```

## Credentials

- admin / demo123
- joao.membro / demo123
- ana.silva / demo123
