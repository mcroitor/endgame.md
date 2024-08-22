CREATE TABLE role (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE NOT NULL
);

CREATE TABLE capabilities(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE NOT NULL
);

CREATE TABLE user (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    login TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    role_id INTEGER NOT NULL DEFAULT 2,
    FOREIGN KEY (role_id) REFERENCES role(id)
);

CREATE TABLE role_capabilities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    role_id INT NOT NULL,
    capability_id INT NOT NULL,
    permission INT NOT NULL DEFAULT 0, -- 0 - deny, 1 - allow
    FOREIGN KEY (role_id) REFERENCES role(id),
    FOREIGN KEY (capability_id) REFERENCES capability(id)
);

CREATE TABLE user_menu (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    reference TEXT NOT NULL,
    capability_id INTEGER NOT NULL
);