<?php

$fen = filter_input(INPUT_GET, "fen");
if (empty($fen)) {
    exit("<b>using</b>:<br />index.php?fen=&lt;FEN&gt;");
}


$options = array(
    "size" => strtolower(filter_input(INPUT_GET, 'size', FILTER_VALIDATE_INT) ?: ""),
    "style" => strtolower(filter_input(INPUT_GET, "style", FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? "alpha"),
    "solid" => filter_input(INPUT_GET, "solid", FILTER_VALIDATE_BOOLEAN),
    "color" => strtolower(filter_input(INPUT_GET, "color", FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? "black"),
    "dbl_margin" => filter_input(INPUT_GET, "double", FILTER_VALIDATE_BOOLEAN)
);

$etag = md5($fen . "|" . json_encode($options));
$ifNoneMatch = trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''), " \t\n\r\0\x0B\"");

header('Cache-Control: public, max-age=31536000, immutable');
header("ETag: \"{$etag}\"");

if ($ifNoneMatch === $etag) {
    http_response_code(304);
    exit();
}

require __DIR__ . '/diagram.class.php';

$d = new Diagram($fen, $options);

header('Content-type: image/png');
imagepng($d->toImage());

