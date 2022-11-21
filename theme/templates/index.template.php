<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="keywords" content="chess endgame study database, chess, база шахматных этюдов, шахматы" />
    <script type="text/javascript" src="scripts/studydb.js"></script>
    <script type="text/javascript" src="scripts/lichess/lichess-pgn-viewer.js"></script>
    <link rel="stylesheet" href="theme/css/normalize.css" />
    <link rel="stylesheet" href="theme/css/skeleton.css" />
    <link rel="stylesheet" href="theme/css/main.css" />
    <link rel="stylesheet" href="scripts/lichess/lichess-pgn-viewer.css" />
    <title>Chess Endgame Study Database</title>
</head>

<body>
    <section class="container">
        <h3 class='header'>Chess Endgame Study Database</h3>
        <div class="navbar">
            <a href="#" onclick="showItem('diag');" class="navbar-link">Query Results</a>
            <a href="#" onclick="showItem('about');" class="navbar-link">About</a>
            <a href="#" onclick="showItem('linx');" class="navbar-link">Links</a>
            <a href="../builder/" class="navbar-link">FENBuilder</a>
            <!-- login-form -->
        </div>
        <p>There are <strong><!-- total_endgames --></strong> endgames in the database.</p>
        <div class="searchform">
            <h5>Search by:</h5>
            <!-- <div>
                <div>endgame ID:</div>
                <div><input type="text" name="pid" id="pid" /></div>
            </div> -->
            <div class="row">
                <div class="two columns">author: </div>
                <div class="ten columns">
                    <input type="text" name="author" id="author" onfocus="showAdvanced();" />
                </div>
            </div>
            <div class="hided" id="advanced">
                <div class="row">
                    <div class="two columns">White pieces: </div>
                    <div class="four columns">
                        <select id="wsign">
                            <option value='&lt;'>&lt;</option>
                            <option value='='>=</option>
                            <option value='&gt;'>&gt;</option>
                        </select>
                        <input type="number" name="wpiece" min="0" id="wpiece" />
                    </div>
                    <div class="two columns">Black pieces: </div>
                    <div class="four columns">
                        <select id="bsign">
                            <option value='&lt;'>&lt;</option>
                            <option value='='>=</option>
                            <option value='&gt;'>&gt;</option>
                        </select>
                        <input type="number" name="bpiece" min="0" id="bpiece" />
                    </div>
                </div>
                <div class="row">
                    <div class="two columns">Stipulation: </div>
                    <div class="four columns">
                        <select id="stipulation">
                            <option value='-'>Any stipulation</option>
                            <option value='White wins'>White wins</option>
                            <option value='Draw'>Draw</option>
                            <option value='*'>Not specified</option>
                        </select>
                    </div>
                    <div class="two columns">Theme: </div>
                    <div class="four columns">
                        <select id="theme">
                            <!-- themes -->
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="two columns">Date: </div>
                    <div class="ten columns">
                        <input type="number" min="0" max="3000" id="fromDate" /> -
                        <input type="number" min="0" max="3000" id="toDate" />
                    </div>
                </div>
                <div class="row">
                    <div class="twelve columns">
                        Is cooked: <input type="checkbox" id="cook" />
                    </div>
                </div>
            </div>
        </div>
        <input type="button" value="Make Query" onclick="getPosition(0)" />
        <input type="button" value="Clear Form" onclick="clearAll()" />
        <hr class="line" />
        <div id="stat"></div>
        <div id="diag"></div>
        <div class="showed" id="about">
            <fieldset class="line">
                <legend>Last Changes:</legend>
                <ul>
                    <!-- last_changes -->
                </ul>
            </fieldset>
            <fieldset class="line">
                <legend><em>03.08.2020:</em> Migration!</legend>
                I think is a good step to have personal, independed page. Welcome
                to <em>https://endgame.md</em>!
            </fieldset>
            <fieldset class="line">
                <legend>About Chess endgame Study Database</legend>
                Written by Croitor Mihail ( mcroitor(at)gmail.com ), december 2008.
                For using this database You need to have JavaScript enebled in Your
                browser. All endgames, stocked in this database, were found on
                Internet, mostly from sites:
                <ul>
                    <li><a href="httpw://didok.ru/" target="_blank">https://didok.ru/</a></li>
                    <li><a href="http://akobia.geoweb.ge/" target="_blank">http://akobia.geoweb.ge/</a></li>
                    <li><a href="http://hdelboy.club.fr/" target="_blank">http://hdelboy.club.fr/</a></li>
                    <li><a href="http://crazychess.narod.ru/baza/b.htm" target="_blank">http://crazychess.narod.ru/baza/b.htm</a></li>
                    <li><a href="http://www.vlasak.biz/evcstud.htm#downturn" target="_blank">http://www.vlasak.biz/evcstud.htm</a></li>
                    <li><a href="http://ruszchessstudies.blogspot.com/2008/02/download-pgn-files.html" target="_blank">http://ruszchessstudies.blogspot.com/</a></li>
                </ul>
                <p>Many thanks to Lebedev Vasily who sent me some commented endgames,
                    to Milan Velimirovic who maked nice web service 'PGN Live'.</p>
                <p>Many thanks to composers, who sends me their endgames: Serghey Didukh,
                    Siegfried Hornecker, Rainer Staudte, Daniel Keidiv, Ilham Aliev,
                    Guenter Amann.</p>
            </fieldset>
        </div>
        <div class="hidden" id="linx">
            <p>Some useful links:</p>
            <ul>
                <li><a href="https://kasparovchess.crestbook.com/" target="_blank">https://kasparovchess.crestbook.com/</a> - Russian Chess Forum</li>
                <li><a href="https://selivanov.world" target="_blank">https://selivanov.world</a> - Site of Journal "Uralski Problemist", Redactor: Andrei Selivanov</li>
                <li><a href="https://matplus.net/" target="_blank">https://matplus.net/</a> - Site of Journal "Mat Plus". Redactor: Milan Velimirovic</li>
                <li><a href="https://yacpdb.org/" target="_blank">https://yacpdb.org/</a> - YACPDB - chess problem database by Dmidivi Turevski</li>
                <li><a href="https://www.shredderchess.com/online-chess/online-databases/endgame-database.html" target="_blank">https://www.shredderchess.com/...html</a> - 6-men Online endgame database</li>
                <li><a href="https://web.iol.cz/vaclav.kotesovec/" target="_blank">https://web.iol.cz/vaclav.kotesovec/</a> - Vaclav Kotesovec's page.</li>
                <li><a href="https://www.arves.org/" target="_blank">https://www.arves.org/</a> - ARVES site, an international club for chessplayers who are specially interested in Endgamestudies.</li>
            </ul>
        </div>
        <div id="debug"></div>
        <div id="pgnlive-wrapper" class="hidden">
            <div class="titlebar"><a href="javascript:close()" class="close-icon">X</a></div>
            <div id="pgnlive"></div>
        </div>
    </section>
</body>

</html>