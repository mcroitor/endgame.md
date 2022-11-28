PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;
CREATE TABLE modules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    entry_point TEXT NOT NULL
);
INSERT INTO modules VALUES(1,'articles','articles');
INSERT INTO modules VALUES(2,'statistics','statistics');
COMMIT;
PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;
CREATE TABLE role (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE NOT NULL
);
INSERT INTO role VALUES(1,'guest');
INSERT INTO role VALUES(2,'user');
INSERT INTO role VALUES(3,'admin');
CREATE TABLE capabilities(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE NOT NULL
);
INSERT INTO capabilities VALUES(1,'endgame::create');
INSERT INTO capabilities VALUES(2,'endgame::read');
INSERT INTO capabilities VALUES(3,'endgame::update');
INSERT INTO capabilities VALUES(4,'endgame::delete');
INSERT INTO capabilities VALUES(5,'role::create');
INSERT INTO capabilities VALUES(6,'role::read');
INSERT INTO capabilities VALUES(7,'role::update');
INSERT INTO capabilities VALUES(8,'role::delete');
INSERT INTO capabilities VALUES(9,'role::assign');
INSERT INTO capabilities VALUES(10,'user::authenticate');
INSERT INTO capabilities VALUES(11,'article::create');
INSERT INTO capabilities VALUES(12,'article::read');
INSERT INTO capabilities VALUES(13,'article::update');
INSERT INTO capabilities VALUES(14,'article::dalate');
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
INSERT INTO role_capabilities VALUES(1,2,1,0);
INSERT INTO role_capabilities VALUES(2,2,2,1);
INSERT INTO role_capabilities VALUES(3,2,3,0);
INSERT INTO role_capabilities VALUES(4,2,4,0);
INSERT INTO role_capabilities VALUES(5,3,1,1);
INSERT INTO role_capabilities VALUES(6,3,2,1);
INSERT INTO role_capabilities VALUES(7,3,3,1);
INSERT INTO role_capabilities VALUES(8,3,4,1);
INSERT INTO role_capabilities VALUES(9,3,5,1);
INSERT INTO role_capabilities VALUES(10,3,6,1);
INSERT INTO role_capabilities VALUES(11,3,7,1);
INSERT INTO role_capabilities VALUES(12,3,8,1);
INSERT INTO role_capabilities VALUES(13,3,9,1);
INSERT INTO role_capabilities VALUES(14,1,10,1);
INSERT INTO role_capabilities VALUES(15,3,11,1);
INSERT INTO role_capabilities VALUES(16,1,12,1);
INSERT INTO role_capabilities VALUES(17,2,12,1);
INSERT INTO role_capabilities VALUES(18,3,12,1);
INSERT INTO role_capabilities VALUES(19,3,13,1);
INSERT INTO role_capabilities VALUES(20,3,14,1);
CREATE TABLE article (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    body TEXT NOT NULL,
    author INTEGER NOT NULL,
    published TEXT NOT NULL,
    FOREIGN KEY(author) REFERENCES user(id)
);
INSERT INTO article VALUES(1,'About Chess endgame Study Database',replace('<p>Written by Croitor Mihail \n( mcroitor(at)gmail.com ), december 2008.\nFor using this database You need to have JavaScript enebled in Your\nbrowser. All endgames, stocked in this database, were found on\nInternet, mostly from sites:</p>\n<ul>\n    <li><a href=''httpw://didok.ru/'' target=''_blank''>https://didok.ru/</a></li>\n    <li><a href=''http://akobia.geoweb.ge/'' target=''_blank''>http://akobia.geoweb.ge/</a></li>\n    <li><a href=''http://hdelboy.club.fr/'' target=''_blank''>http://hdelboy.club.fr/</a></li>\n    <li><a href=''http://crazychess.narod.ru/baza/b.htm'' target=''_blank''>http://crazychess.narod.ru/baza/b.htm</a></li>\n    <li><a href=''http://www.vlasak.biz/evcstud.htm#downturn'' target=''_blank''>http://www.vlasak.biz/evcstud.htm</a></li>\n    <li><a href=''http://ruszchessstudies.blogspot.com/2008/02/download-pgn-files.html'' target=''_blank''>http://ruszchessstudies.blogspot.com/</a></li>\n</ul>\n<p>Many thanks to Lebedev Vasily who sent me some commented endgames,\n    to Milan Velimirovic who maked nice web service ''PGN Live''.</p>\n<p>Many thanks to composers, who sends me their endgames: Serghey Didukh,\n    Siegfried Hornecker, Rainer Staudte, Daniel Keith, Ilham Aliev,\n    Guenter Amann.</p>','\n',char(10)),1,'2022-11-25 10:43:14');
INSERT INTO article VALUES(2,'Some useful links',replace('<ul>\n    <li><a href=''https://kasparovchess.crestbook.com/'' target=''_blank''>https://kasparovchess.crestbook.com/</a> - Russian Chess Forum</li>\n    <li><a href=''https://selivanov.world'' target=''_blank''>https://selivanov.world</a> - Site of Journal ''Uralski Problemist'', Redactor: Andrei Selivanov</li>\n    <li><a href=''https://matplus.net/'' target=''_blank''>https://matplus.net/</a> - Site of Journal ''Mat Plus''. Redactor: Milan Velimirovic</li>\n    <li><a href=''https://yacpdb.org/'' target=''_blank''>https://yacpdb.org/</a> - YACPDB - chess problem database by Dmidivi Turevski</li>\n    <li><a href=''https://www.shredderchess.com/online-chess/online-databases/endgame-database.html'' target=''_blank''>https://www.shredderchess.com/...html</a> - 6-men Online endgame database</li>\n    <li><a href=''https://web.iol.cz/vaclav.kotesovec/'' target=''_blank''>https://web.iol.cz/vaclav.kotesovec/</a> - Vaclav Kotesovec''s page.</li>\n    <li><a href=''https://www.arves.org/'' target=''_blank''>https://www.arves.org/</a> - ARVES site, an international club for chessplayers who are specially interested in Endgamestudies.</li>\n</ul>','\n',char(10)),1,'2022-11-25 10:43:14');
INSERT INTO article VALUES(3,'<em>03.08.2020:</em> Migration!',replace('<p>I think is a good step to have personal, \nindepended page. Welcome to <em>https://endgame.md</em>!</p>','\n',char(10)),1,'2022-11-25 10:43:14');
CREATE TABLE modules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    entry_point TEXT NOT NULL
);
INSERT INTO modules VALUES(1,'articles','articles');
INSERT INTO modules VALUES(2,'statistics','statistics');
COMMIT;
