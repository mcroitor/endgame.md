<?php

/**
 * common library.
 */

/**
 * translit cyrilic to latin
 * @param string $str
 * @return string
 */
function translitIt(string $str): string
{
    $tr = array(
        "А" => "A",
        "Б" => "B",
        "В" => "V",
        "Г" => "G",
        "Д" => "D",
        "Е" => "E",
        "Ж" => "J",
        "З" => "Z",
        "И" => "I",
        "Й" => "Y",
        "К" => "K",
        "Л" => "L",
        "М" => "M",
        "Н" => "N",
        "О" => "O",
        "П" => "P",
        "Р" => "R",
        "С" => "S",
        "Т" => "T",
        "У" => "U",
        "Ф" => "F",
        "Х" => "H",
        "Ц" => "TS",
        "Ч" => "CH",
        "Ш" => "SH",
        "Щ" => "SCH",
        "Ъ" => "",
        "Ы" => "Y",
        "Ь" => "",
        "Э" => "E",
        "Ю" => "YU",
        "Я" => "YA",
        "а" => "a",
        "б" => "b",
        "в" => "v",
        "г" => "g",
        "д" => "d",
        "е" => "e",
        "ж" => "j",
        "з" => "z",
        "и" => "i",
        "й" => "y",
        "к" => "k",
        "л" => "l",
        "м" => "m",
        "н" => "n",
        "о" => "o",
        "п" => "p",
        "р" => "r",
        "с" => "s",
        "т" => "t",
        "у" => "u",
        "ф" => "f",
        "х" => "h",
        "ц" => "ts",
        "ч" => "ch",
        "ш" => "sh",
        "щ" => "sch",
        "ъ" => "y",
        "ы" => "y",
        "ь" => "",
        "э" => "e",
        "ю" => "yu",
        "я" => "ya"
    );
    return strtr($str, $tr);
}

class facade
{
    public static function translitIt(string $str): string
    {
        return translitIt($str);
    }

    public static function file(string $filename): string
    {
        return file_get_contents($filename);
    }

    public static function template(
        string $filename, 
        string $path = \config::template_dir,
        array $modifiers = ["prefix" => "<!-- ", "suffix" => " -->"]
        ): \mc\template
    {
        return new \mc\template(
            facade::file("{$path}/{$filename}"),
            $modifiers
        );
    }
}
