-- article tables
CREATE TABLE article (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    body TEXT NOT NULL,
    author INTEGER NOT NULL,
    published TEXT NOT NULL,
    FOREIGN KEY(author) REFERENCES user(id)
);
