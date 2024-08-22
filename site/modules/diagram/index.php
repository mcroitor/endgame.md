<?php

$fen = filter_input(INPUT_GET, "fen");
if ($fen === false) {
    exit("<b>using</b>:<br />index.php?fen=&lt;FEN&gt;");
}


$options = array(
    "size" => strtolower(filter_input(INPUT_GET, 'size', FILTER_VALIDATE_INT)),
    "style" => strtolower(filter_input(INPUT_GET, "style", FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? "alpha"),
    "solid" => filter_input(INPUT_GET, "solid", FILTER_VALIDATE_BOOLEAN),
    "color" => strtolower(filter_input(INPUT_GET, "color", FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? "black"),
    "dbl_margin" => filter_input(INPUT_GET, "double", FILTER_VALIDATE_BOOLEAN)
);

require __DIR__ . '/diagram.class.php';

$d = new Diagram($fen, $options);

// header('Content-type: image/png');
imagepng($d->toImage());

