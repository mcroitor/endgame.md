<?php
namespace core\database;

class sql {
    public const SELECT = "SELECT";
    public const DELETE = "DELETE";
    public const UPDATE = "UPDATE";
    public const INSERT = "INSERT";

    protected const SQL_TEMPLATE = [
        self::SELECT => "SELECT %fields% FROM %table% %where% %order% %limit%",
        self::DELETE => "DELETE FROM %table% %where%",
        self::UPDATE => "UPDATE %table% %set% %where%",
        self::INSERT => "INSERT INTO %table% %fields% %values%"
    ];

    private $query;
    private $type;
    private $fields;
    private $table;
    private $where;
    private $limit;

    public function __construct(array $config) {
        $this->type = $config['type'];
        $this->fields = $config['fields'];
        $this->table = $config['table'];
        $this->where = $config['where'];
        $this->limit = $config['limit'];
        $this->query = $this->build_query();
    }

    private function prepare_fields() {
        $fields = "";
        foreach ($this->fields as $field) {
            $fields .= $field . ", ";
        }
        return substr($fields, 0, -2);
    }

    private function prepare_where() {
        $where = "";
        foreach ($this->where as $key => $value) {
            $where .= $key . " = '" . $value . "' AND ";
        }
        return substr($where, 0, -5);
    }

    public function build_query(){
        $query = "";
        $replace_rule = [
            '%fields%' => $this->prepare_fields(),
            '%table%' => $this->table,
            '%where%' => $this->prepare_where(),
            '%order%' => $this->order,
            '%limit%' => $this->limit
        ];

        foreach (self::SQL_TEMPLATE as $key => $value) {
            if ($key == $this->type) {
                $query = strtr($value, $replace_rule);
            }
        }
        return $query;
    }
    public function get_query() {
        return $this->query;
    }

    public function get_type() {
        return $this->type;
    }
}