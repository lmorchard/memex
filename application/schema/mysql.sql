SET NAMES 'utf8';

DROP TABLE IF EXISTS logins;
CREATE TABLE logins (
    id INTEGER NOT NULL AUTO_INCREMENT,
    login_name VARCHAR(64) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(32) NOT NULL,
    created DATETIME,
    last_login DATETIME,
    PRIMARY KEY (id),
    UNIQUE INDEX login_name (login_name),
    KEY email (email)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS profiles;
CREATE TABLE profiles (
    id INTEGER NOT NULL AUTO_INCREMENT,
    uuid VARCHAR(40) NOT NULL,
    screen_name VARCHAR(64) NOT NULL,
    full_name VARCHAR(128) NOT NULL,
    bio TEXT,
    created DATETIME,
    last_login DATETIME,
    PRIMARY KEY (id),
    UNIQUE KEY uuid (uuid),
    UNIQUE KEY screen_name (screen_name)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS profile_attribs;
CREATE TABLE profile_attribs (
    id INTEGER NOT NULL AUTO_INCREMENT,
    profile_id INTEGER NOT NULL,
    name VARCHAR(255),
    value TEXT,
    PRIMARY KEY (id),
    UNIQUE KEY profile_id_name (profile_id, name)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS logins_profiles;
CREATE TABLE logins_profiles (
    id INTEGER NOT NULL AUTO_INCREMENT,
    login_id INTEGER NOT NULL,
    profile_id INTEGER NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY login_id_profile_id (login_id, profile_id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS posts;
CREATE TABLE posts (
    id INTEGER NOT NULL AUTO_INCREMENT,
    uuid VARCHAR(40) NOT NULL,
    signature VARCHAR(40) NOT NULL,
    profile_id INTEGER NOT NULL,
    url_id INTEGER NOT NULL,
    title VARCHAR(255) default NULL,
    notes TEXT default NULL,
    tags TEXT DEFAULT NULL,
    visibility INTEGER DEFAULT NULL,
    user_date DATETIME DEFAULT NULL,
    created DATETIME DEFAULT NULL,
    modified DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY profile_id_url_id (profile_id, url_id),
    UNIQUE KEY uuid (uuid),
    KEY url_id (url_id),
    KEY user_date (user_date)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS tags;
CREATE TABLE tags (
    id INTEGER NOT NULL AUTO_INCREMENT,
    post_id INTEGER NOT NULL,
    profile_id INTEGER NOT NULL,
    url_id INTEGER NOT NULL,
    tag VARCHAR(128),
    position INTEGER DEFAULT 0,
    created DATETIME,
    modified DATETIME,
    PRIMARY KEY (id),
    KEY profile_id_url_id_tag (profile_id, url_id, tag),
    KEY post_id (post_id),
    KEY profile_id_tag (profile_id, tag),
    KEY url_id (url_id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS urls;
CREATE TABLE urls (
    id INTEGER NOT NULL AUTO_INCREMENT,
    url TEXT NOT NULL,
    hostname VARCHAR(255) default NULL,
    hash VARCHAR(32) default NULL,
    content TEXT,
    first_profile_id INT default NULL,
    latest_profile_id INT default NULL,
    created DATETIME,
    modified DATETIME,
    PRIMARY KEY (id),
    UNIQUE KEY url (url(255)),
    KEY hash (hash)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS updates;
CREATE TABLE updates (
    hash char(32) NOT NULL default '',
    updated DATETIME default NULL
) CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS message_queue;
CREATE TABLE message_queue (
    uuid VARCHAR(40) NOT NULL,
    batch_uuid VARCHAR(40) DEFAULT NULL,
    batch_seq INTEGER DEFAULT 0,
    created DATETIME,
    modified DATETIME,
    scheduled_for DATETIME,
    reserved_at DATETIME,
    reserved_until DATETIME,
    finished_at DATETIME,
    priority INTEGER DEFAULT 0,
    topic VARCHAR(255),
    object VARCHAR(255),
    method VARCHAR(255),
    context TEXT DEFAULT NULL,
    body TEXT DEFAULT NULL,
    signature CHAR(32) DEFAULT NULL,
    PRIMARY KEY (uuid),
    KEY created (created),
    KEY priority (priority),
    KEY batch_seq (batch_seq),
    KEY signature (signature),
    KEY reserved_at (reserved_at),
    KEY finished_at (finished_at),
    KEY scheduled_for (scheduled_for),
    KEY batch_uuid (batch_uuid)
) CHARACTER SET utf8 COLLATE utf8_general_ci;
