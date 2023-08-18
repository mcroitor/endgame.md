-- article capabilities
INSERT INTO capabilities VALUES
(null, "article::create"),
(null, "article::read"),
(null, "article::update"),
(null, "article::dalate");

-- allow article capabilities
INSERT INTO role_capabilities VALUES 
(null, 3, 11, 1),
(null, 1, 12, 1),
(null, 2, 12, 1),
(null, 3, 12, 1),
(null, 3, 13, 1),
(null, 3, 14, 1);

-- default articles
INSERT INTO article VALUES 
(null, "About Chess endgame Study Database", "<p>Written by Croitor Mihail 
( mcroitor(at)gmail.com ), december 2008.
For using this database You need to have JavaScript enebled in Your
browser. All endgames, stocked in this database, were found on
Internet, mostly from sites:</p>
<ul>
    <li><a href='httpw://didok.ru/' target='_blank'>https://didok.ru/</a></li>
    <li><a href='http://akobia.geoweb.ge/' target='_blank'>http://akobia.geoweb.ge/</a></li>
    <li><a href='http://hdelboy.club.fr/' target='_blank'>http://hdelboy.club.fr/</a></li>
    <li><a href='http://crazychess.narod.ru/baza/b.htm' target='_blank'>http://crazychess.narod.ru/baza/b.htm</a></li>
    <li><a href='http://www.vlasak.biz/evcstud.htm#downturn' target='_blank'>http://www.vlasak.biz/evcstud.htm</a></li>
    <li><a href='http://ruszchessstudies.blogspot.com/2008/02/download-pgn-files.html' target='_blank'>http://ruszchessstudies.blogspot.com/</a></li>
</ul>
<p>Many thanks to Lebedev Vasily who sent me some commented endgames,
    to Milan Velimirovic who maked nice web service 'PGN Live'.</p>
<p>Many thanks to composers, who sends me their endgames: Serghey Didukh,
    Siegfried Hornecker, Rainer Staudte, Daniel Keith, Ilham Aliev,
    Guenter Amann.</p>", 1, datetime('now', 'localtime')),
(null, "Some useful links", 
"<ul>
    <li><a href='https://kasparovchess.crestbook.com/' target='_blank'>https://kasparovchess.crestbook.com/</a> - Russian Chess Forum</li>
    <li><a href='https://selivanov.world' target='_blank'>https://selivanov.world</a> - Site of Journal 'Uralski Problemist', Redactor: Andrei Selivanov</li>
    <li><a href='https://matplus.net/' target='_blank'>https://matplus.net/</a> - Site of Journal 'Mat Plus'. Redactor: Milan Velimirovic</li>
    <li><a href='https://yacpdb.org/' target='_blank'>https://yacpdb.org/</a> - YACPDB - chess problem database by Dmidivi Turevski</li>
    <li><a href='https://www.shredderchess.com/online-chess/online-databases/endgame-database.html' target='_blank'>https://www.shredderchess.com/...html</a> - 6-men Online endgame database</li>
    <li><a href='https://web.iol.cz/vaclav.kotesovec/' target='_blank'>https://web.iol.cz/vaclav.kotesovec/</a> - Vaclav Kotesovec's page.</li>
    <li><a href='https://www.arves.org/' target='_blank'>https://www.arves.org/</a> - ARVES site, an international club for chessplayers who are specially interested in Endgamestudies.</li>
</ul>", 1, datetime('now', 'localtime')),
(null, "<em>03.08.2020:</em> Migration!", "<p>I think is a good step to have personal, 
independed page. Welcome to <em>https://endgame.md</em>!</p>", 1, datetime('now', 'localtime'));

-- user menu
INSERT INTO user_menu VALUES(1,'Create Article','/?q=article/new',12);
