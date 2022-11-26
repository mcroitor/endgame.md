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
        <div class="header">
            <h1>Chess Endgame Study Database</h1>
        </div>
        <div class="navbar">
            <a href="/" class="navbar-link">Query Results</a>
            <a href="/?q=about" class="navbar-link">About</a>
            <a href="/?q=links" class="navbar-link">Links</a>
            <a href="../builder/" class="navbar-link">FENBuilder</a>
            <!-- login-form -->
        </div>
        <p>There are <strong><!-- total_endgames --></strong> endgames in the database.</p>
        <!-- content -->
        <div class="footer"><!-- statisitcs --></div>
        <div id="pgnlive-wrapper" class="hidden">
            <div class="titlebar"><a href="javascript:close()" class="close-icon">X</a></div>
            <div id="pgnlive"></div>
        </div>
    </section>
</body>

</html>