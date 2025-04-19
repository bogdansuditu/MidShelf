<?php
$dbPath = getenv('SQLITE_DB_PATH') ?: '/var/www/data/midshelf.db';

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Enable foreign keys
    $db->exec('PRAGMA foreign_keys = ON;');

    // Create users table
    $db->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME
    )');

    // Create categories table
    $db->exec('CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        description TEXT,
        icon TEXT NOT NULL,
        color TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )');

    // Create locations table
    $db->exec('CREATE TABLE IF NOT EXISTS locations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        description TEXT,
        is_predefined BOOLEAN DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )');

    // Create tags table
    $db->exec('CREATE TABLE IF NOT EXISTS tags (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )');

    // Create items table
    $db->exec('CREATE TABLE IF NOT EXISTS items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        description TEXT,
        category_id INTEGER,
        location_id INTEGER,
        rating INTEGER CHECK(rating BETWEEN 1 AND 5),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (category_id) REFERENCES categories(id),
        FOREIGN KEY (location_id) REFERENCES locations(id)
    )');

    // Create items_tags table (many-to-many relationship)
    $db->exec('CREATE TABLE IF NOT EXISTS items_tags (
        item_id INTEGER,
        tag_id INTEGER,
        PRIMARY KEY (item_id, tag_id),
        FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
    )');

    // Create user_settings table
    $db->exec('CREATE TABLE IF NOT EXISTS user_settings (
        user_id INTEGER NOT NULL,
        setting_key TEXT NOT NULL,
        setting_value TEXT,
        PRIMARY KEY (user_id, setting_key),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )');

    echo "Database initialized successfully!\n";
} catch (PDOException $e) {
    die("Database initialization failed: " . $e->getMessage() . "\n");
}
