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
        articles::$crud = new \mc\sql\crud(
            new \mc\sql\database(config::dsn),
            \meta\article::__name__);
    }

    public static function get($offset, $limit)
    {
        return articles::$crud->all($offset, $limit);
    }

    #[\mc\route("about")]
    public static function getHtml(array $params) {
        $offset = isset($params[0])? (int)$params[0] : 0;
        $limit = isset($params[1]) ? (int)$params[1] : 5;
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
    
    #[\mc\route("article/new")]
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

    #[\mc\route("article/register")]
    public static function register() {
        if (\mc\user::has_capability("article::create") === false){
            header("location:" . config::www);
            exit();
        }

        $data = [
            \meta\article::AUTHOR => \mc\user::id(),
            \meta\article::TITLE => filter_input(INPUT_POST, "article-title"),
            \meta\article::BODY => filter_input(INPUT_POST, "article-body"),
            \meta\article::PUBLISHED => date("Y-m-d H:i:s"),
        ];
        self::$crud->insert($data);
        header("location:" . config::www . "/?q=about");
        return "";
    }
}
