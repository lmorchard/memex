DROP TABLE IF EXISTS logins;
CREATE TABLE logins (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    login_name VARCHAR(64) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(32) NOT NULL,
    created DATETIME,
    last_login DATETIME
);

DROP TABLE IF EXISTS profiles;
CREATE TABLE profiles (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    uuid VARCHAR(40) NOT NULL,
    screen_name VARCHAR(64) NOT NULL,
    full_name VARCHAR(128) NOT NULL,
    bio TEXT,
    created DATETIME,
    last_login DATETIME
);

DROP TABLE IF EXISTS profile_attribs;
CREATE TABLE profile_attribs (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    profile_id INTEGER NOT NULL,
    name VARCHAR(255),
    value TEXT
);

DROP TABLE IF EXISTS logins_profiles;
CREATE TABLE logins_profiles (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    login_id INTEGER NOT NULL,
    profile_id INTEGER NOT NULL
);

DROP TABLE IF EXISTS posts;
CREATE TABLE posts (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    uuid VARCHAR(40) NOT NULL,
    profile_id INTEGER NOT NULL,
    url_id INTEGER NOT NULL,
    title VARCHAR(255) default NULL,
    notes TEXT,
    tags TEXT,
    visibility INTEGER,
    user_date DATETIME,
    created DATESTAMP,
    modified DATESTAMP
);

DROP TABLE IF EXISTS tags;
CREATE TABLE tags (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    post_id INTEGER NOT NULL,
    profile_id INTEGER NOT NULL,
    url_id INTEGER NOT NULL,
    tag VARCHAR(128),
    position INTEGER,
    created DATESTAMP,
    modified DATESTAMP
);

DROP TABLE IF EXISTS urls;
CREATE TABLE urls (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    url TEXT,
    hostname VARCHAR(255),
    hash VARCHAR(32) default NULL,
    content TEXT,
    first_profile_id INT default NULL,
    latest_profile_id INT default NULL,
    created DATESTAMP,
    modified DATESTAMP
);

DROP TABLE IF EXISTS updates;
CREATE TABLE updates (
    hash char(32) NOT NULL default '',
    updated DATETIME default NULL
);
