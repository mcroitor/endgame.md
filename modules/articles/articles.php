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
        articles::$crud = new \mc\sql\crud(new \mc\sql\database(config::dsn), "article");
    }

    public static function get($offset, $limit)
    {
        return articles::$crud->all($offset, $limit);
    }

    public static function getHtml($offset, $limit) {
        $template = file_get_contents(__DIR__ . "/article.template.php");
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
    public static function createHtml() {
        if (\mc\user::has_capability("article::create") === false){
            header("location:" . config::www);
            exit();
        }
        $template = file_get_contents(__DIR__ . "/article-form.template.php");
        $template = new template($template);
        $template->set_prefix("<!-- ");
        $template->set_suffix(" -->");
        return $template->fill(["path" => config::www . "/modules/articles"])->value();
    }
}
