-- modules table
CREATE TABLE modules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE NOT NULL,
    entry_point TEXT UNIQUE NOT NULL
);
-- user tables 
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
-- endgame tables
CREATE TABLE raw (id INTEGER PRIMARY KEY, data TEXT);
CREATE TABLE endgame (
  pid INTEGER PRIMARY KEY,
  fen TEXT NOT NULL DEFAULT "8/8/8/8/8/8/8/8 w - - 0 1",
  author TEXT DEFAULT NULL,
  date TEXT NOT NULL DEFAULT "0000.00.00",
  source TEXT NOT NULL DEFAULT "Unknown",
  award TEXT NOT NULL,
  stipulation TEXT NOT NULL DEFAULT "Win",
  commentary text NOT NULL,
  whitep INTEGER NOT NULL,
  blackp INTEGER NOT NULL,
  piece_pattern TEXT NOT NULL,
  theme TEXT NOT NULL DEFAULT "unknown",
  cook INTEGER NOT NULL DEFAULT 0
);
-- endgame import statistics table 
CREATE TABLE changes (
  id INTEGER PRIMARY KEY,
  nr_games INTEGER NOT NULL,
  filename TEXT DEFAULT NULL,
  date TEXT DEFAULT NULL
);
-- access statistics table
CREATE TABLE statistic (
  id INTEGER PRIMARY KEY,
  query TEXT NOT NULL,
  ip TEXT NOT NULL,
  time TEXT NOT NULL
);
-- composers information table
CREATE TABLE IF NOT EXISTS composer(
  family_name TEXT,
  first_name TEXT,
  second_name TEXT,
  id_alphabet TEXT,
  country TEXT,
  birth TEXT,
  id_method TEXT,
  death TEXT,
  id_own_alphabet TEXT
);
