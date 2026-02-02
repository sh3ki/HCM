<?php
require_once __DIR__ . '/../config/database.php';

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $this->connect();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

            $this->connection = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
            $this->connection->setAttribute(PDO::ATTR_TIMEOUT, DB_TIMEOUT);

        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }

    public function getConnection() {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database Query Error: " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Params: " . json_encode($params));
            throw new Exception("Database query failed");
        }
    }

    public function select($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function selectOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        $this->query($sql, $data);
        return $this->connection->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $set);

        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";

        $params = array_merge($data, $whereParams);
        return $this->query($sql, $params);
    }

    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params);
    }

    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    public function commit() {
        return $this->connection->commit();
    }

    public function rollback() {
        return $this->connection->rollback();
    }

    public function getLastInsertId() {
        return $this->connection->lastInsertId();
    }

    public function rowCount($stmt) {
        return $stmt->rowCount();
    }

    private function __clone() {}

    public function __wakeup() {
        throw new Exception("Cannot unserialize Database instance");
    }
}

class QueryBuilder {
    private $db;
    private $table;
    private $select = ['*'];
    private $joins = [];
    private $where = [];
    private $orderBy = [];
    private $groupBy = [];
    private $having = [];
    private $limit;
    private $offset;
    private $params = [];

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function table($table) {
        $this->table = $table;
        return $this;
    }

    public function select($columns = ['*']) {
        $this->select = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function join($table, $condition, $type = 'INNER') {
        $this->joins[] = "{$type} JOIN {$table} ON {$condition}";
        return $this;
    }

    public function leftJoin($table, $condition) {
        return $this->join($table, $condition, 'LEFT');
    }

    public function rightJoin($table, $condition) {
        return $this->join($table, $condition, 'RIGHT');
    }

    public function where($column, $operator = null, $value = null) {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $placeholder = ':' . str_replace('.', '_', $column) . '_' . count($this->params);
        $this->where[] = "{$column} {$operator} {$placeholder}";
        $this->params[$placeholder] = $value;

        return $this;
    }

    public function whereIn($column, $values) {
        $placeholders = [];
        foreach ($values as $i => $value) {
            $placeholder = ':' . str_replace('.', '_', $column) . '_in_' . $i;
            $placeholders[] = $placeholder;
            $this->params[$placeholder] = $value;
        }

        $this->where[] = "{$column} IN (" . implode(', ', $placeholders) . ")";
        return $this;
    }

    public function whereNull($column) {
        $this->where[] = "{$column} IS NULL";
        return $this;
    }

    public function whereNotNull($column) {
        $this->where[] = "{$column} IS NOT NULL";
        return $this;
    }

    public function orderBy($column, $direction = 'ASC') {
        $this->orderBy[] = "{$column} {$direction}";
        return $this;
    }

    public function groupBy($column) {
        $this->groupBy[] = $column;
        return $this;
    }

    public function having($condition) {
        $this->having[] = $condition;
        return $this;
    }

    public function limit($limit) {
        $this->limit = $limit;
        return $this;
    }

    public function offset($offset) {
        $this->offset = $offset;
        return $this;
    }

    public function get() {
        $sql = $this->buildSelectQuery();
        return $this->db->select($sql, $this->params);
    }

    public function first() {
        $this->limit(1);
        $sql = $this->buildSelectQuery();
        return $this->db->selectOne($sql, $this->params);
    }

    public function count() {
        $originalSelect = $this->select;
        $this->select = ['COUNT(*) as count'];
        $sql = $this->buildSelectQuery();
        $result = $this->db->selectOne($sql, $this->params);
        $this->select = $originalSelect;
        return $result ? $result['count'] : 0;
    }

    private function buildSelectQuery() {
        $sql = "SELECT " . implode(', ', $this->select) . " FROM {$this->table}";

        if (!empty($this->joins)) {
            $sql .= " " . implode(" ", $this->joins);
        }

        if (!empty($this->where)) {
            $sql .= " WHERE " . implode(" AND ", $this->where);
        }

        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(", ", $this->groupBy);
        }

        if (!empty($this->having)) {
            $sql .= " HAVING " . implode(" AND ", $this->having);
        }

        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(", ", $this->orderBy);
        }

        if ($this->limit) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }
}
?>