<?php

$simbols["std"] = array();

$simbols["std"]["8"] = chr(52);
$simbols["std"]["7"] = chr(52);
$simbols["std"]["6"] = chr(52);
$simbols["std"]["5"] = chr(52);
$simbols["std"]["4"] = chr(52);
$simbols["std"]["3"] = chr(52);
$simbols["std"]["2"] = chr(52);
$simbols["std"]["1"] = chr(52);

$simbols["std"]["|"] = chr(54);
$_top = chr(56);
$_bottom = chr(50);
$simbols["std"]["top"] = " "; // "1222222223"; //"!\"\"\"\"\"\"\"\"#";
for ($i = 0; $i != 8; ++$i) {
    $simbols["std"]["top"] .= $_top;
}
$simbols["std"]["bottom"] = " "; //"6777777778";
for ($i = 0; $i != 8; ++$i) {
    $simbols["std"]["bottom"] .= $_bottom;
}
$simbols["std"]["/"] = chr(52);

$simbols["std"]["tl"] = chr(55);
$simbols["std"]["tt"] = chr(56);
$simbols["std"]["tr"] = chr(57);
$simbols["std"]["bl"] = chr(49);
$simbols["std"]["bb"] = chr(50);
$simbols["std"]["br"] = chr(51);

$simbols["std"]["-"] = chr(32);
$simbols["std"]["+"] = chr(48);

$simbols["std"]["a"] = chr(50);
$simbols["std"]["b"] = chr(50);
$simbols["std"]["c"] = chr(50);
$simbols["std"]["d"] = chr(50);
$simbols["std"]["e"] = chr(50);
$simbols["std"]["f"] = chr(50);
$simbols["std"]["g"] = chr(50);
$simbols["std"]["h"] = chr(50);

$simbols["std"]["K0"] = chr(75);
$simbols["std"]["Q0"] = chr(81);
$simbols["std"]["R0"] = chr(82);
$simbols["std"]["B0"] = chr(66);
$simbols["std"]["N0"] = chr(78);
$simbols["std"]["P0"] = chr(80);

$simbols["std"]["K1"] = chr(40);
$simbols["std"]["Q1"] = chr(43);
$simbols["std"]["R1"] = chr(44);
$simbols["std"]["B1"] = chr(39);
$simbols["std"]["N1"] = chr(41);
$simbols["std"]["P1"] = chr(42);

$simbols["std"]["k0"] = chr(107);
$simbols["std"]["q0"] = chr(113);
$simbols["std"]["r0"] = chr(114);
$simbols["std"]["b0"] = chr(98);
$simbols["std"]["n0"] = chr(110);
$simbols["std"]["p0"] = chr(112);

$simbols["std"]["k1"] = chr(34);
$simbols["std"]["q1"] = chr(37);
$simbols["std"]["r1"] = chr(38); //oops!
$simbols["std"]["b1"] = chr(33);
$simbols["std"]["n1"] = chr(35);
$simbols["std"]["p1"] = chr(36);

$simbols["std"]["X0"] = chr(32);
$simbols["std"]["X1"] = chr(48);

$simbols["std"]["delta"] = 0;
