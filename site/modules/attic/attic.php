<?php

namespace modules\attic;

use mc\template;
use modules\articles\articles;

/**
 * just another type of articles (very specific) for showing
 * projects for downloading.
 */
class attic
{
    /**
     * @property \mc\sql\crud $crud
     */
    protected static $crud;

    public static function init()
    {
        attic::$crud = new \mc\sql\crud(\config::$db, "attic");
    }

    public static function get($offset, $limit)
    {
        return attic::$crud->all($offset, $limit);
    }

    public static function getHtml(array $params) {
        $offset = isset($params[0])? (int)$params[0] : 0;
        $limit = isset($params[1]) ? (int)$params[1] : 5;
        $template = file_get_contents(__DIR__ . "/attic.template.php");
        $data = articles::get($offset, $limit);
        $result = "";
        $template = new template($template);
        $template->set_prefix("<!-- ");
        $template->set_suffix(" -->");
        foreach ($data as $article) {
            $result .= $template->fill($article)->value();
        }
        return $result;
    }
}
