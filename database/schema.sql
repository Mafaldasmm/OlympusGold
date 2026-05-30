-- Olympus Gold Gym — SQLite schema
-- Run on an empty database before seed.sql

PRAGMA foreign_keys = ON;

-- ---------------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    username   TEXT    NOT NULL UNIQUE,
    password   TEXT    NOT NULL,
    email      TEXT    UNIQUE,
    name          TEXT,
    profile_photo TEXT,
    role       TEXT    NOT NULL DEFAULT 'member'
                       CHECK (role IN ('member', 'trainer', 'admin')),
    active     INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0, 1)),
    created_at TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ---------------------------------------------------------------------------
-- classes
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS classes (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    title       TEXT    NOT NULL,
    description TEXT,
    type        TEXT,
    trainer_id  INTEGER REFERENCES users (id) ON DELETE SET NULL,
    day_of_week TEXT,
    start_time  TEXT,
    end_time    TEXT,
    capacity    INTEGER NOT NULL DEFAULT 15 CHECK (capacity > 0),
    created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ---------------------------------------------------------------------------
-- enrollments
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS enrollments (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    class_id   INTEGER NOT NULL REFERENCES classes (id) ON DELETE CASCADE,
    created_at TEXT    NOT NULL DEFAULT (datetime('now')),
    UNIQUE (user_id, class_id)
);

-- ---------------------------------------------------------------------------
-- reviews
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reviews (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    class_id   INTEGER NOT NULL REFERENCES classes (id) ON DELETE CASCADE,
    rating     INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment    TEXT,
    created_at TEXT    NOT NULL DEFAULT (datetime('now')),
    UNIQUE (user_id, class_id)
);

-- ---------------------------------------------------------------------------
-- indexes
-- ---------------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_users_role ON users (role);

CREATE INDEX IF NOT EXISTS idx_classes_trainer ON classes (trainer_id);
CREATE INDEX IF NOT EXISTS idx_classes_type ON classes (type);
CREATE INDEX IF NOT EXISTS idx_classes_day ON classes (day_of_week);

CREATE INDEX IF NOT EXISTS idx_enrollments_user ON enrollments (user_id);
CREATE INDEX IF NOT EXISTS idx_enrollments_class ON enrollments (class_id);

CREATE INDEX IF NOT EXISTS idx_reviews_user ON reviews (user_id);
CREATE INDEX IF NOT EXISTS idx_reviews_class ON reviews (class_id);
