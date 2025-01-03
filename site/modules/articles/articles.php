<?php

namespace modules\articles;

include_once __DIR__ . "/capabilities/article_capabilities.php";

use mc\template;
use modules\articles\capabilities\ARTICLE_CAPABILITY;

class articles
{
    /**
     * @property \mc\sql\crud $crud
     */
    protected static $crud;
    public const MODULE_DIR = __DIR__;
    public const TEMPLATES_DIR = articles::MODULE_DIR . "/templates/";

    public static function init()
    {
        articles::$crud = new \mc\sql\crud(
            \config::$db,
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
        $data = articles::get($offset, $limit);
        $result = "";
        $template = \facade::template("/article.template.php", articles::TEMPLATES_DIR);
        foreach ($data as $article) {
            $result = $template->fill($article)->value() . $result;
        }
        return $result;
    }
    
    #[\mc\route("article/new")]
    public static function createHtml() {
        if (\mc\user::has_capability(ARTICLE_CAPABILITY::CREATE) === false){
            header("location:" . \config::www);
            exit();
        }
        $template = \facade::template("/article-form.template.php", articles::TEMPLATES_DIR);
        return $template->fill(["path" => \config::www . "/modules/articles"])->value();
    }

    #[\mc\route("article/create")]
    public static function create() {
        if (\mc\user::has_capability(ARTICLE_CAPABILITY::CREATE) === false){
            header("location:" . \config::www);
            exit();
        }

        $data = [
            \meta\article::AUTHOR => \mc\user::id(),
            \meta\article::TITLE => filter_input(INPUT_POST, "article-title"),
            \meta\article::BODY => filter_input(INPUT_POST, "article-body"),
            \meta\article::PUBLISHED => date("Y-m-d H:i:s"),
        ];
        self::$crud->insert($data);
        header("location:" . \config::www . "/?q=about");
        return "";
    }

}
