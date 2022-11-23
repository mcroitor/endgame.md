<?php

use core\template;

class articles
{
    /**
     * @property \mc\sql\crud
     */
    protected static $crud;

    public static function init()
    {
        articles::$crud = new \mc\sql\crud(new \mc\sql\database(config::dsn), "articles");
    }

    public static function get($offset, $limit)
    {
        return articles::$crud->all($offset, $limit);
    }

    public static function getHtml($offset, $limit) {
        $template = file_get_contents(config::template_dir . "article.template.php");
        $data = articles::get($offset, $limit);
        $result = "";
        foreach ($data as $article) {
            $result .= (new template($template))->fill($article)->value();
        }
        return $result;
    }
}
