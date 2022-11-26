CREATE TABLE modules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    entry_point TEXT NOT NULL
);

INSERT INTO modules VALUES (null, "articles", "articles");
