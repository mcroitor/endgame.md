INSERT INTO modules VALUES (null, "articles", "articles");
INSERT INTO modules VALUES (null, "endgame", "endgame");

-- user data
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
    (10, "user::authenticate"),
    (11, "article::create"),
    (12, "article::read"),
    (13, "article::update"),
    (14, "article::delete");

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

-- users
INSERT INTO user (name, login, password, role_id) VALUES
    ("Admin", "admin", "password", 3),
    ("User", "user", "password", 2);

-- user menu
INSERT INTO user_menu VALUES(2,'Create Endgame','/?q=endgame/new',1);
INSERT INTO user_menu VALUES(3,'Import Endgames','/?q=endgame/import',1);