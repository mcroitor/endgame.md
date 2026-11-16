<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../site/core/logger/mc/logger.php';
require_once __DIR__ . '/../site/core/template/mc/template.php';
require_once __DIR__ . '/../site/core/router/mc/route.php';
require_once __DIR__ . '/../site/core/router/mc/router.php';
require_once __DIR__ . '/../site/core/database/mc/query.php';
require_once __DIR__ . '/../site/core/database/mc/database.php';
require_once __DIR__ . '/../site/core/database/mc/crud.php';
require_once __DIR__ . '/../site/core/lib.php';
require_once __DIR__ . '/../site/modules/pgn.php';
require_once __DIR__ . '/../site/modules/endgame/lib/pgnparser.php';
global $colors;
require_once __DIR__ . '/../site/modules/diagram/diagram.class.php';
