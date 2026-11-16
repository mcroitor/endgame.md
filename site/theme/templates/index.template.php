<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="app-base" content="<!-- www -->">
    <meta name="keywords" content="chess endgame study database, chess, база шахматных этюдов, шахматы" />
    <script type="text/javascript" src="<!-- www -->/scripts/studydb.js" defer></script>
    <script type="text/javascript" src="<!-- www -->/scripts/lichess/lichess-pgn-viewer.js" defer></script>
    <link rel="stylesheet" href="<!-- www -->/theme/css/normalize.css" />
    <link rel="stylesheet" href="<!-- www -->/theme/css/skeleton.css" />
    <link rel="stylesheet" href="<!-- www -->/theme/css/main.css" />
    <link rel="stylesheet" href="<!-- www -->/scripts/lichess/lichess-pgn-viewer.css" />
    <title>Chess Endgame Study Database</title>
</head>

<body>
    <div class="container">
        <header class="header">
            <h1 class="site-title"><a href="<!-- www -->/">Chess Endgame Study Database</a></h1>
        </header>
        <nav class="navbar" aria-label="Main navigation">
            <div class="navbar-links">
                <a href="<!-- www -->/" class="navbar-link" data-route="/">Search</a>
                <a href="<!-- www -->/?q=about" class="navbar-link" data-route="about">About</a>
                <a href="<!-- www -->/?q=links" class="navbar-link" data-route="links">Links</a>
                <a href="/../builder/" class="navbar-link" data-route="builder">Position Builder (FEN)</a>
            </div>
            <div class="navbar-auth">
                <!-- login-form -->
            </div>
        </nav>
        <main class="main-content">
            <!-- content -->
        </main>
    </div>
    <div id="pgnlive-backdrop" class="pgnlive-backdrop hidden" role="dialog" aria-modal="true" aria-labelledby="pgnlive-title">
        <div id="pgnlive-wrapper">
            <div class="titlebar">
                <span class="titlebar-label" id="pgnlive-title">Solution</span>
                <button type="button" id="pgnlive-close" class="close-icon" aria-label="Close solution">&times;</button>
            </div>
            <div id="pgnlive-meta" class="pgnlive-meta hidden"></div>
            <div id="pgnlive"></div>
            <div id="pgnlive-actions" class="pgnlive-actions hidden"></div>
        </div>
    </div>
    <footer class="container footer">
        <!-- statistics -->
    </footer>
</body>

</html>