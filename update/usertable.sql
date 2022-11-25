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

INSERT INTO role (id, name) VALUES (1, "guest"), (2, "user"), (3, "admin");

INSERT INTO capabilities (id, name) VALUES 
    (1, "endgame::create"),
    (2, "endgame::read"),
    (3, "endgame::update"),
    (4, "endgame::delete"),
    (5, "role::create"),
    (6, "role::read"),
    (7, "role::update"),
    (8, "role::delete"),
    (9, "role::assign"),
    (10, "user::authenticate");

INSERT INTO role_capabilities (role_id, capability_id, permission) VALUES
    (2, 1, 0),
    (2, 2, 1),
    (2, 3, 0),
    (2, 4, 0),
    (3, 1, 1),
    (3, 2, 1),
    (3, 3, 1),
    (3, 4, 1),
    (3, 5, 1),
    (3, 6, 1),
    (3, 7, 1),
    (3, 8, 1),
    (3, 9, 1),
    (1, 10, 1);

INSERT INTO user (name, login, password, role_id) VALUES
    ("Admin", "admin", "password", 3),
    ("User", "user", "password", 2);
