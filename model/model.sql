-- This is Flourish SQL, not MySQL!
-- http://flourishlib.com/docs/FlourishSql

-- Each board thread is also the first post of the thread
-- Each thread or reply may have one image associated and one file associated

-- articles table: News for the front page

-- fallback_cache table:
-- This is for showing statistics on the front page and other places
-- If APC is available, it used in favour of this table

-- BUG Why does date_created in categories table get extra attributes?

DROP TABLE IF EXISTS fallback_cache;
DROP TABLE IF EXISTS f_a_qs;
DROP TABLE IF EXISTS articles;
DROP TABLE IF EXISTS board_rules;
DROP TABLE IF EXISTS replies;
DROP TABLE IF EXISTS threads;
DROP TABLE IF EXISTS boards;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS site_settings;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS image_files;
DROP TABLE IF EXISTS files;

CREATE TABLE files (
  file_id INTEGER AUTOINCREMENT PRIMARY KEY,
  filename VARCHAR(255) NOT NULL,
  mime_type VARCHAR(128) DEFAULT 'application/octet-stream' NOT NULL,
  date_created TIMESTAMP NOT NULL,
  date_updated TIMESTAMP NOT NULL,
  timezone VARCHAR(64) NOT NULL
);
INSERT INTO files (file_id, filename, date_created, date_updated) VALUES (1, '###none', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

CREATE TABLE image_files (
  file_id INTEGER AUTOINCREMENT PRIMARY KEY,
  filename VARCHAR(255) NOT NULL,
  filename_thumb VARCHAR(255) NOT NULL,
  date_created TIMESTAMP NOT NULL,
  date_updated TIMESTAMP NOT NULL,
  timezone VARCHAR(64) NOT NULL
);
INSERT INTO image_files (file_id, filename, filename_thumb, date_created, date_updated) VALUES (1, '###none', '###none', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

CREATE TABLE users (
  user_id INTEGER AUTOINCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email_address VARCHAR(128) NOT NULL,
  user_password VARCHAR(255) NOT NULL,
  auth_level VARCHAR(128) DEFAULT 'user' NOT NULL,
  date_created TIMESTAMP NOT NULL,
  date_updated TIMESTAMP NOT NULL,
  timezone VARCHAR(64) NOT NULL
);
CREATE UNIQUE INDEX idx_users_name ON users (name);
CREATE UNIQUE INDEX idx_users_email ON users (email_address);

INSERT INTO users (name, email_address, user_password, auth_level, date_created, date_updated, timezone) VALUES ('admin', 'admin@whatever.com', 'fCryptography::password_hash#IMJYInU76D#0d6ae9b9cda7a17cfece50e0664e6fa11762be65', 'admin', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 'America/Los_Angeles');
INSERT INTO users (name, email_address, user_password, auth_level, date_created, date_updated, timezone) VALUES ('first_mod', 'mod@whatever.com', 'fCryptography::password_hash#IMJYInU76D#0d6ae9b9cda7a17cfece50e0664e6fa11762be65', 'moderator', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 'America/Los_Angeles');

CREATE TABLE site_settings (
  name VARCHAR(255) PRIMARY KEY,
  setting_value TEXT NOT NULL, -- can be any type
  last_edited_user_id INTEGER NOT NULL REFERENCES users(user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  date_created TIMESTAMP NOT NULL,
  date_updated TIMESTAMP NOT NULL,
  timezone VARCHAR(64) NOT NULL
);

-- Top level categories (these do not have a page)
CREATE TABLE categories (
  name VARCHAR(255) PRIMARY KEY,
  date_created TIMESTAMP NOT NULL,
  date_updated TIMESTAMP NOT NULL,
  timezone VARCHAR(64) NOT NULL
);

CREATE TABLE boards (
  name VARCHAR(255) PRIMARY KEY,
  category_name VARCHAR(255) NOT NULL REFERENCES categories(name) ON DELETE RESTRICT ON UPDATE CASCADE,
  board_type VARCHAR(255) DEFAULT 'image' NOT NULL CHECK (board_type IN ('image', 'audio', 'video', 'file')),
  short_u_r_l VARCHAR(16) NOT NULL,
  date_created TIMESTAMP NOT NULL,
  date_updated TIMESTAMP NOT NULL,
  timezone VARCHAR(64) NOT NULL
);
CREATE UNIQUE INDEX idx_boards_short_u_r_l ON boards (short_u_r_l);

CREATE TABLE threads (
  thread_id INTEGER AUTOINCREMENT PRIMARY KEY,
  name VARCHAR(128) DEFAULT 'Anonymous' NOT NULL,
  email_address VARCHAR(128) DEFAULT '' NOT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT DEFAULT '' NOT NULL,
  board_name VARCHAR(255) NOT NULL REFERENCES boards(name) ON DELETE RESTRICT ON UPDATE CASCADE,
  is_anonymous BOOLEAN DEFAULT 1 NOT NULL,
  deletion_password VARCHAR(255) NOT NULL,
  file_id INTEGER DEFAULT 1 NOT NULL REFERENCES files(file_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  image_file_id INTEGER DEFAULT 1 NOT NULL REFERENCES image_files(file_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  expiration_time TIMESTAMP NOT NULL,
  date_created TIMESTAMP NOT NULL,
  date_updated TIMESTAMP NOT NULL,
  timezone VARCHAR(64) NOT NULL
);

CREATE TABLE replies (
  reply_id INTEGER AUTOINCREMENT PRIMARY KEY,
  name VARCHAR(128) DEFAULT 'Anonymous' NOT NULL,
  email_address VARCHAR(128) DEFAULT '' NOT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT DEFAULT '' NOT NULL,
  thread_id INTEGER NOT NULL REFERENCES threads(thread_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  is_anonymous BOOLEAN DEFAULT 1 NOT NULL,
  deletion_password VARCHAR(255) NOT NULL,
  file_id INTEGER DEFAULT 1 NOT NULL REFERENCES files(file_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  image_file_id INTEGER DEFAULT 1 NOT NULL REFERENCES image_files(file_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  date_created TIMESTAMP NOT NULL,
  date_updated TIMESTAMP NOT NULL,
  timezone VARCHAR(64) NOT NULL
);

CREATE TABLE board_rules (
  board_name VARCHAR(255) NOT NULL REFERENCES boards(name) ON DELETE CASCADE ON UPDATE CASCADE,
  rules_text TEXT NOT NULL,
  last_edited_user_id INTEGER NOT NULL REFERENCES users(user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  date_created TIMESTAMP NOT NULL,
  date_updated TIMESTAMP NOT NULL,
  timezone VARCHAR(64) NOT NULL
);

CREATE TABLE articles (
  article_id INTEGER AUTOINCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  user_id INTEGER NOT NULL REFERENCES users(user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  date_created TIMESTAMP NOT NULL,
  date_updated TIMESTAMP NOT NULL,
  timezone VARCHAR(64) NOT NULL
);

CREATE TABLE f_a_qs (
  question VARCHAR(255) PRIMARY KEY,
  answer TEXT NOT NULL,
  user_id INTEGER NOT NULL REFERENCES users(user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  date_created TIMESTAMP NOT NULL,
  date_updated TIMESTAMP NOT NULL,
  timezone VARCHAR(64) NOT NULL
);

CREATE TABLE fallback_cache (
  name VARCHAR(255) PRIMARY KEY,
  cached_value VARCHAR(255) NOT NULL,
  expiration INTEGER DEFAULT 0 NOT NULL,
  date_created TIMESTAMP NOT NULL,
  date_updated TIMESTAMP NOT NULL,
  timezone VARCHAR(64) NOT NULL
);
