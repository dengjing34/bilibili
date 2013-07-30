<?php
namespace Lib\Mysql;

abstract class Data {
    const MODE_MASTER = 'master', MODE_SLAVE = 'slave';
    const CACHE_PREFIX = 'mysql_';
    const SHOW_SQL_IN_HTML = '_show_sql';
    private $attris = array();
    public static $counter = 0;
    private static $connections = array(), $dbs = array(), $searchers = array(), $appendWhere = array(), $sql = array(), $result = array();
    private static $columns = array(), $tables = array(), $keys = array(), $required = array();
    private static $switchMaster = false, $showSql = false;
    private static $memcache, $apc;

    /**
     * 初始化配置
     * @param array $options
     * @throws \Exception 在config里面找不到默认的databse配置会抛异常
     */
    public function init(array $options) {
        $className = $this->className();
        self::$keys[$className] = isset($options['key']) ? $options['key'] : null;
        self::$tables[$className] = isset($options['table']) ? $options['table'] : null;
        self::$columns[$className] = isset($options['columns']) ? $options['columns'] : array();
        self::$required[$className] = isset($options['required']) ? $options['required'] : array();
        self::$dbs[$className] = isset($options['db']) ? $options['db'] : null;
        foreach (array('keys', 'tables', 'columns', 'dbs') as $requiredOption) {
            if (!self::${$requiredOption}[$className]) {
                throw new \Exception("{$className}'s {$requiredOption} must be specified when init");
            }
        }
        self::$searchers[$className] = isset($options['searcher']) && $options['searcher'] ? $options['searcher'] : null;
    }

    /**
     * 获取mysql表主键对应的对象属性名
     * @return string
     */
    final public function key() {
        return self::$keys[$this->className()];
    }

    /**
     * 打印出执行的sql
     * @param boolean $show true为打印, false为不打印
     * @return \Lib\Mysql\Data
     */
    public function showSql($show) {
        self::$showSql = $show;
        return $this;
    }

    /**
     * 获取类名,包含命名空间
     * @return string
     */
    final protected function className() {
        return get_class($this);
    }

    /**
     * 获取appendWhere的内容
     * @return string
     */
    final protected function getAppendWhere() {
        $className = $this->className();
        if (!isset(self::$appendWhere[$className])) {
            self::$appendWhere[$className] = null;
        }
        return self::$appendWhere[$className];
    }

    /**
     * 切换到从master读取数据
     * @return \Lib\Mysql\Data
     */
    final public function switchMaster() {
        self::$switchMaster = true;
        return $this;
    }

    /**
     * 切换到从slave读取数据
     * @return \Lib\Mysql\Data
     */
    final public function switchSlave() {
        self::$switchMaster = false;
        return $this;
    }

    /**
     * 设定appendWhere的内容
     * @param string $string 需要append的字符
     * @return \Lib\Mysql\Data
     */
    final protected function setAppendWhere($string) {
        $className = $this->className();
        if (isset(self::$appendWhere[$className])) {
            self::$appendWhere[$className] .= $string;
        } else {
            self::$appendWhere[$className] = $string;
        }
        return $this;
    }

    /**
     * 返回对象和属性的字段映射关系, key是对象属性, value是数据库字段
     * @return array
     */
    final public function columns() {
        return self::$columns[$this->className()];
    }

    /**
     * 返回数据库表名
     * @return string
     */
    final public function table() {
        return self::$tables[$this->className()];
    }

    /**
     * 返回save时必填的属性
     * @return array
     */
    final private function required() {
        return self::$required[$this->className()];
    }

    /**
     * 获取mysql的connection资源
     * @param string $node master或slave节点
     * @return resource a MySQL link identifier
     * @throws \Exception 找不到配置的mysql节点的时候抛出异常
     */
    private function getConnection($node = self::MODE_SLAVE) {
        $dbName = self::$dbs[$this->className()];
        if (self::$switchMaster) {
            $node = self::MODE_MASTER;
        }
        if (!isset(self::$connections[$dbName][$node])) {
            try {
                $config = \Lib\Config::load("mysql.{$dbName}.{$node}");
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
            self::$connections[$dbName][$node] = mysql_connect($config['host'], $config['user'], $config['password']) or die(mysql_error());
            mysql_select_db($dbName) or die(mysql_error());
            mysql_query('set names utf8') or die(mysql_error());            
        }
        return self::$connections[$dbName][$node];
    }

    /**
     * 关闭mysql连接
     * @param string $node 关闭的节点,master 或 slave
     * @return \Lib\Mysql\Data
     */
    private function closeConnection($node = self::MODE_SLAVE) {
        unset(self::$connections[self::$dbs[$this->className()]][$node]);
        return $this;
    }

    /**
     * 重置对象的属性
     * @return \Lib\Mysql\Data
     */
    public function reset() {
        foreach (array_keys($this->columns()) as $objCol) {
            if (isset($this->{$objCol})) {
                $this->{$objCol} = null;
            }
        }
        return $this;
    }

    /**
     * 生成mysql类的memcache或apc key
     * @param string|array $key 要存储的key,可以是数组或字符,不能为空
     * @return string|null
     */
    protected function cacheKey($key) {
        $className = $this->className();
        $prefix = self::CACHE_PREFIX . self::$dbs[$className] . "_{$this->table()}_";
        if (is_scalar($key) && $key) {
            return $prefix . $key;
        } elseif (is_array($key) && !empty($key)) {
            return array_map(function($v) use($prefix) {
                        return $prefix . $v;
                    }, $key);
        } else {
            return null;
        }        
    }

    protected static function genKey($key) {
        $className = get_called_class();
        $prefix = self::CACHE_PREFIX . "{$className}_";
        if (is_scalar($key)) {
            return $prefix . $key;
        } elseif (is_array($key)) {
            return array_map(function($v) use ($prefix) {
                return $prefix . $v;
            }, $key);
        }
        return null;
    }

    /**
     * 获取apc实例
     * @return \Lib\Apc
     */
    protected function apcInstance() {
        if (is_null(self::$apc)) {
            self::$apc = \Lib\Apc::instance();
        }
        return self::$apc;
    }


    /**
     * 获取Cache的实例
     * @return \Lib\Cache
     */
    protected function cacheInstance() {
        return \Lib\Cache::instance();
    }

    protected function cacheGet($key) {
        return $this->cacheInstance()->get($this->cacheKey($key));
    }

    protected function cacheSet($key, $value) {
        return $this->cacheInstance()->set($this->cacheKey($key), $value);
    }

    protected function cacheDel($key) {
        return $this->cacheInstance()->delete($this->cacheKey($key));
    }

    protected function cacheGetMulti($keys) {
        return $this->cacheInstance()->getMulti($this->cacheKey($keys));
    }

    /**
     * 获取init()指定的searcher实例 若init()没有指定searcher或指定的searcher不是继承于Searcher类则返回null
     * @return \Lib\Searcher 未指定或制定的searcher不是继承于MysqlData的类会返回null
     */
    private function getSearcher() {
        $className = $this->className();
        if (self::$searchers[$className] instanceof \Lib\Solr\Searcher) {
            return self::$searchers[$className];
        } elseif (!is_null(self::$searchers[$className]) && class_exists(self::$searchers[$className])) {
            $searcher = new self::$searchers[$className]();
            self::$searchers[$className] = $searcher instanceof \Lib\Solr\Searcher ? $searcher : null;
            return self::$searchers[$className];
        } else {
            return null;
        }
    }

    /**
     * 通过init()指定的searcher来更新solr
     * @return boolean 没有指定searcher或更新失败返回false
     * @throws \Exception 更新失败会抛出异常并说明原因
     */
    private function searcherUpdate() {
        $searcher = $this->getSearcher();
        if (!is_null($searcher)) {
            $data = $this->parseSearcherData();
            if (!empty($data)) {
                $result = $searcher->update($data);
                if (!$result) {
                    $error = $searcher->lastError();
                    throw new \Exception($error['error']['msg'], $error['error']['code']);
                }
                return $result;
            }
        }
        return false;
    }

    /**
     * 通过init()指定的searcher来删除solr的数据
     * @return boolean 成功返回true,失败抛出异常,没有key直接返回false
     * @throws \Exception 删除solr中的数据失败的时候会抛出异常并说明原因
     */
    private function searcherDelete() {
        $key = $this->key();
        $id = $this->{$key};
        if (ctype_digit((string) $id) && !is_null($searcher = $this->getSearcher())) {
            $result = $searcher->delete($id);
            if (!$result) {
                $error = $searcher->lastError();
                throw new \Exception($error['error']['msg'], $error['error']['code']);
            }
            return $result;
        }
        return false;
    }

    /**
     * 解析需要提交到solr里面的数据 包括columns()和attris的属性
     * @return array 解析后的数组
     */
    private function parseSearcherData() {
        $result = array();
        foreach (array_keys($this->columns()) as $property) {
            if ($property == 'attributeData') {
                continue;
            }
            $result[$property] = $this->{$property};
        }
        foreach ($this->attris as $key => $val) {
            $result[$key] = $val;
        }
        return $result;
    }

    /**
     * 根据主键读取一条数据
     * @param int $value 主键的value,若为空则会根据对象的属性$this->key()的值来读取
     * @return \Lib\Mysql\Data
     * @throws \Exception
     */
    public function load($value = null) {
        $key = $this->key();
        if (!is_null($value)) {
            $this->{$key} = $value;
        }
        $columns = $this->columns();
        if (is_null($this->{$key}))
            throw new \Exception('No key has been set when load ' . $this->className(), 111);
        $row = false;
        if (($row = $this->cacheGet($this->{$key}))) {
            foreach ($columns as $objCol => $dbCol) {
                if ($objCol == 'attributeData') {
                    $this->attris = $row->attris;
                } else {
                    $this->{$objCol} = $row->{$objCol};
                }
            }
        } else {
            $this->{$key} = mysql_real_escape_string($this->{$key});
            $colstr = '`' . implode('`, `', $columns) . '`';
            $where = "WHERE `{$columns[$key]}` = '{$this->{$key}}'";
            $className = $this->className();
            self::$sql[$className] = "SELECT {$colstr} FROM {$this->table()} {$where} LIMIT 1";
            if ($this->query()) {
                $row = mysql_fetch_assoc(self::$result[$className]);
            } else {
                $this->clean();
                throw new \Exception("{$className}::{$this->$key} not found\n", 11);
            }
            $this->parseRow($this, $row, $columns);
            $this->clean();
            $this->cacheSet($this->{$key}, $this);
        }
        unset($row);
        return $this;
    }

    /**
     * 执行mysql语句
     * @param string $node 执行sql的节点, master 或 slave
     * @return int 影响行数
     * @throws \Exception
     */
    protected function query($node = self::MODE_SLAVE) {
        self::$counter++;
        $className = $this->className();
        $connection = $this->getConnection($node);
        if (self::$showSql || (isset($_GET[self::SHOW_SQL_IN_HTML]) && $_GET[self::SHOW_SQL_IN_HTML])) {
            $br = PHP_SAPI == 'cli' ? PHP_EOL : '<br />' . PHP_EOL;
            echo self::$sql[$className] . $br;
        }
        if ((self::$result[$className] = mysql_query(self::$sql[$className], $connection))) {
            return mysql_affected_rows($connection);
        } else {
            $errno = mysql_errno($connection);
            if ($errno == '2006' || $errno == '2013') { // skip 'MySQL server has gone away' error
                return $this->closeConnection()->query($node);
            } else {
                throw new \Exception("There's something wrong with the sql! " . self::$sql[$className], 22);
            }
        }
    }

    /**
     * 把db的array解析到对象上
     * @param \Lib\MySql\Data $_obj
     * @param array $row
     * @param array $columns
     * @return type
     */
    public function parseRow($_obj, $row, $columns) {
        foreach ($columns as $objcol => $dbcol) {
            if ($objcol != 'attributeData') {
                $_obj->$objcol = $row[$dbcol];
                continue;
            }

            preg_match_all("/([^:]+):(.*)\n/", rtrim($row[$dbcol]) . "\n", $matches);
            $attrs = array();
            foreach ($matches[1] as $attKey => $attrName) {
                $attrs[$attrName] = $matches[2][$attKey];
            }
            $_obj->attris = str_replace(array('%%', '%n'), array("%", "\n"), $attrs);
            unset($attrs, $matches);
        }
        return $_obj;
    }

    protected function clean() {
        $className = $this->className();
        self::$sql[$className] = null;
        self::$result[$className] = null;
        self::$appendWhere[$className] = null;
        return $this;
    }

    public function find($options = array()) {
        $rows = $this->getQuery($options);
        $className = $this->className();
        $objs = array();
        $columns = $this->columns();
        if ($rows) {
            $objClean = new $className();
            while ($row = mysql_fetch_assoc(self::$result[$className])) {
                $obj = clone $objClean;
                $this->parseRow($obj, $row, $columns);
                if (is_null($obj->{$this->key()})) {
                    continue;
                }
                $objs[$obj->{$this->key()}] = $obj;
                unset($row);
            }
        }
        $this->clean();
        return $objs;
    }

    /**
     * 获取分页数据
     * @param array $options 键可以是page(默认1),limit(默认10),whereAnd
     * @return array 分页结果
     * <pre>
     * array(
     * &nbsp;&nbsp;'currentPage' => 1,
     * &nbsp;&nbsp;'totalPage' => 10,
     * &nbsp;&nbsp;'rows' => 10,
     * &nbsp;&nbsp;'numFound' => 100,
     * &nbsp;&nbsp;'start' => 0,
     * &nbsp;&nbsp;'docs' => array(
     * &nbsp;&nbsp;&nbsp;&nbsp;主键值 => \Lib\Mysql\Data对象
     * )
     * </pre>
     */
    public function pageResult(array $options = array()) {
        $limit = 10;        
        if (isset($options['limit']) && ctype_digit((string)$options['limit']) && $options['limit'] <= 1000) {
            $limit = $options['limit'];
            unset($options['limit']);
        }
        $page = isset($options['page']) && ctype_digit((string)$options['page']) && $options['page'] > 0 ? $options['page'] : 1;
        $offset = ($page - 1) * $limit;
        $count = $this->count($options);
        $options['limit'] = "{$offset}, {$limit}";
        $data = $this->find($options);
        $result = array(
            'currentPage' => $page,
            'totalPage' => ceil($count / $limit),
            'rows' => $limit,
            'numFound' => $count,
            'start' => $offset,
            'docs' => $data,
        );
        return $result;
    }

    /**
     * 获取对象属性或attris中的属性,优先获取对象属性的
     * @param string $name 属性名字
     * @return string||boolean 能够找到返回属性值, 找不到返回false
     */
    public function get($name) {
        $columns = $this->columns();
        if (isset($columns[$name]) && isset($this->$name))
            return $this->$name;
        if (isset($this->attris[$name]) && isset($this->attris[$name]))
            return $this->attris[$name];
        return false;
    }

    /**
     * 设置对象属性或attris中属性的值,优先设置到对象属性中
     * @param string $name 属性名
     * @param mixed $value 属性值
     * @return \Lib\Mysql\Data
     */
    public function set($name, $value) {
        $name = iconv('GBK', 'UTF-8', @iconv('UTF-8', 'GBK//IGNORE', $name));
        $value = iconv('GBK', 'UTF-8', @iconv('UTF-8', "GBK//IGNORE", $value));
        $columns = $this->columns();
        if (isset($columns[$name])) {
            $this->$name = $value;
            return $this;
        }
        if (strlen($name) == 0)
            return;
        $this->attris[$name] = $value;
        return $this;
    }

    /**
     * 删除attris属性中的一个键
     * @param string $name
     * @return \Lib\Mysql\Data
     */
    public function del($name) {
        if (isset($this->attris[$name])) {
            unset($this->attris[$name]);
        }
        return $this;
    }

    private function getQuery($options = array()) {
        $key = $this->key();
        if (!is_array($options)) {
            $options = array();
        }
        $columns = $this->columns();
        $clauses = $this->clause($options, array(
            'limit' => 'LIMIT 1000',
            'order' => "ORDER BY `{$columns[$key]}` DESC",
        ));
        $colstr = implode('`, `', $columns);
        self::$sql[$this->className()] = "SELECT `{$colstr}` FROM `" . $this->table() . "` {$clauses['index']} {$clauses['where']} {$clauses['order']} {$clauses['limit']}";
        return $this->query();
    }

    private function clause($options = array(), $clauses = array()) {
        $columns = $this->columns();

        if (!isset($clauses['where']))
            $clauses['where'] = 'WHERE 1 = 1';
        if (!isset($clauses['index']))
            $clauses['index'] = '';
        if (!isset($clauses['limit']))
            $clauses['limit'] = 'LIMIT 1';

        foreach ($columns as $objcol => $dbcol) {
            if (isset($this->$objcol) && !is_null($this->$objcol)) {
                $value = mysql_real_escape_string($this->$objcol);
                $clauses['where'] .= " AND `{$columns[$objcol]}` = '{$value}'";
            }
        }

        if (isset($options['whereAnd']) && is_array($options['whereAnd'])) {
            foreach ($options['whereAnd'] as $expr)
                $this->whereAnd($expr[0], $expr[1]);
        }

        if (($appendWhere = $this->getAppendWhere())) {
            $clauses['where'] .= $appendWhere;
        }

        if (isset($options['limit'])) {
            $clauses['limit'] = "LIMIT {$options['limit']}";
        }

        if (isset($options['useIndex'])) {
            $clauses['index'] = "FORCE INDEX ({$options['useIndex']})";
        }

        if (isset($options['order']) && is_array($options['order'])) {
            $ords = array();
            foreach ($options['order'] as $objcol => $sort)
                $ords[] = "`{$objcol}` {$sort}";
            $clauses['order'] = strtr("ORDER BY " . implode(', ', $ords), $columns);
        }

        if (isset($options['order']) && $options['order'] == 'no') {
            $clauses['order'] = "";
        }

        $clauses['where'] = str_replace('1 = 1 AND ', '', $clauses['where']);

        return $clauses;
    }

    /**
     * 在sql的where后面增加条件
     * @param string $property 属性名字
     * @param string $expression sql表达式
     * @return \Lib\Mysql\Data
     */
    public function whereAnd($property, $expression) {
        $columns = $this->columns();
        return $this->setAppendWhere(" AND `{$columns[$property]}` {$expression}");
    }

    /**
     * 获取count的数量
     * @param array $options whereAnd等参数
     * @return int
     */
    public function count($options = array()) {
        if (!is_array($options)) {
            $options = array();
        }
        $clauses = $this->clause($options);
        $className = $this->className();
        self::$sql[$className] = "SELECT COUNT(1) FROM `" . $this->table() . "` {$clauses['index']} {$clauses['where']}";
        $number = $this->query() ? current(mysql_fetch_assoc(self::$result[$className])) : null;
        $this->clean();
        return $number;
    }

    /**
     * 有key的时候是update,没有key的时候是insert
     * @return \Lib\Mysql\Data
     * @throws \Exception init()指定的saveNeeds字段有空值的时候会抛出异常
     */
    public function save() {
        $required = $this->required();
        if (!empty($required)) {
            foreach ($required as $o) {
                if (strlen($this->get($o)) == 0) {
                    throw new \Exception($this->className() . "的属性:$o 不能为空");
                }
            }
        }
        $key = $this->key();
        if (isset($this->{$key}) && strlen($this->{$key}) !== 0) {
            $this->update()->cacheSet($this->{$key}, $this);
        } else {
            $this->insert();
        }
        $this->searcherUpdate(); //更新solr
        $this->clean();
        return $this;
    }

    /**
     * 写入一条记录
     * @return \Lib\Mysql\Data
     */
    private function insert() {
        $cols = array();
        $vals = array();
        $columns = $this->columns();
        if (isset($columns['attributeData']))
            $this->attributeData = $this->getAttributeData();
        foreach ($columns as $objcol => $dbcol) {
            if (isset($this->$objcol) && !is_null($this->$objcol)) {
                $cols[] = "`{$dbcol}`";
                $value = mysql_real_escape_string($this->$objcol);
                $vals[] = "'{$value}'";
            }
        }
        if (property_exists($this, 'attributeData')) {
            unset($this->attributeData);
        }
        $colstr = implode(', ', $cols);
        $valstr = implode(', ', $vals);

        self::$sql[$this->className()] = "INSERT INTO `" . $this->table() . "` ({$colstr}) VALUES ({$valstr})";
        $this->query(self::MODE_MASTER);
        $key = $this->key();
        if (!isset($this->{$key})) {
            $connection = $this->getConnection(self::MODE_MASTER);
            $this->{$key} = mysql_insert_id($connection) or die(mysql_error($connection));
        }
        return $this;
    }

    /**
     * 更新一条记录
     * @return \Lib\Mysql\Data
     */
    private function update() {
        $key = $this->key();
        $sets = array();
        $columns = $this->columns();
        if (isset($columns['attributeData']))
            $this->attributeData = $this->getAttributeData();
        foreach ($columns as $objcol => $dbcol) {
            if ($objcol == $key)
                continue;
            if (is_null($this->$objcol)) {
                $sets[] = "`{$dbcol}` = null";
            } else {
                $value = mysql_real_escape_string($this->$objcol);
                $sets[] = "`{$dbcol}` = '{$value}'";
            }
        }
        if (property_exists($this, 'attributeData')) {
            unset($this->attributeData);
        }
        $setstr = implode(', ', $sets);
        $where = "`{$columns[$key]}` = '{$this->$key}'";
        self::$sql[$this->className()] = "UPDATE `" . $this->table() . "` SET {$setstr} WHERE {$where} LIMIT 1";
        $this->query(self::MODE_MASTER);
        return $this;
    }

    /**
     * 删除一条记录
     * @param int $value 主键的value
     * @return \Lib\Mysql\Data
     * @throws \Exception
     */
    public function delete($value = null) {
        $key = $this->key();
        if (!is_null($value)) {
            $this->$key = $value;
        }
        var_dump($this);
        $columns = $this->columns();
        if (!isset($this->$key) || (isset($this->$key) && is_null($this->$key))) {
            throw new \Exception("{$columns[$key]} is null when delete");
        }

        $where = "WHERE {$columns[$key]} = '{$this->{$key}}'";
        self::$sql[$this->className()] = "DELETE FROM `" . $this->table() . "` {$where} LIMIT 1";
        $this->query(self::MODE_MASTER);
        $this->clean();
        $this->reset()->cacheDel($this->{$key}); //delete memcache
        $this->searcherDelete(); //delete solr
        return $this;
    }

    /**
     * 获取attributedata的文本值
     * @return string
     */
    private function getAttributeData() {
        $attris = str_replace(array("%", "\n"), array('%%', '%n'), $this->attris);
        $ret = '';
        foreach ($attris as $eachKey => $eachValue) {
            $ret .= trim("$eachKey:$eachValue") . "\n";
        }
        return $ret;
    }

    public function htmlspecialchars() {
        foreach ($this->columns() as $objcol => $dbcol) {
            $this->$objcol = isset($this->$objcol) && !is_null($this->$objcol) ? htmlspecialchars($this->$objcol, ENT_NOQUOTES, 'ISO-8859-1', false) : NULL;
        }
        foreach ($this->attris as $key => $value) {
            $this->attris[$key] = is_null($value) ? null : htmlspecialchars($value, ENT_NOQUOTES, 'ISO-8859-1', false);
        }
    }

    /**
     * 执行mysql的group by 查询
     * @param array $groupByFields 需要group的字段构成的数组
     * @param array $countFields 一些需要聚合函数的字段构成的数组
     * @param array $options limit, order等条件
     * @return array 查询结果
     * <pre>
     * $jubao = new Jubao();
     * $jubao->whereAnd('insertedTime', '> 1234567890');
     * $jubao->groupBy(
     *     array('cityEnglishName','firstCategoryEnglishName'),
     *     array('Count' => 'distinct id', 'Max' => 'id'), //key可以是Sum, Max, Min, Count, Avg 各种MySQL支持的聚合函数
     *     array('limit' => '10', 'order' => array('Count' => 'desc')) //如果要按Count结果排序，字段名就写Count
     * );
     *
     * Return Sample:
     * array(
     *     array('cityEnglishName' => 'shanghai', 'firstCategoryEnglishName'　=> 'fuwu', 'Count' => 20, 'Max' => 99999),
     *     array('cityEnglishName' => 'shanghai', 'firstCategoryEnglishName'　=> 'jiaoyou', 'Count' => 10, 'Max' => 88888),
     * );
     * </pre>
     */
    public function groupBy(array $groupByFields, array $countFields, array $options = array()) {
        $clauses = $this->clause($options, array('limit' => 'LIMIT 10', 'order' => ''));
        $groupBySql = count($groupByFields) ? implode(',', $groupByFields) : '';
        $countSql = '';
        foreach ($countFields as $func => $column) {
            $countSql .= "{$func}($column) AS {$func},";
        }
        $columns = $this->columns();
        $groupBySql = strtr($groupBySql, $columns);
        $countSql = strtr($countSql, $columns);
        $clauses['groupBy'] = count($groupByFields) ? 'GROUP BY ' . $groupBySql : '';
        self::$sql[$this->className()] = "SELECT " . trim($groupBySql . ',' . $countSql, ',') . " FROM `" . $this->table() . "` {$clauses['index']} {$clauses['where']} {$clauses['groupBy']} {$clauses['order']} {$clauses['limit']}";
        $flipColumns = array_flip($columns);
        $objs = array();
        if ($this->query()) {
            $className = $this->className();
            $objClean = new $className();
            while ($row = mysql_fetch_assoc(self::$result[$className])) {
                $obj = array();
                foreach ($row as $key => $value) {
                    if (isset($flipColumns[$key]))
                        $key = $flipColumns[$key];
                    $obj[$key] = $value;
                }
                $objs[] = $obj;
                unset($row);
            }
        }
        $this->clean();
        return $objs;
    }

    /**
     * 根据ids来获取数据
     * @param array $keys 需要获取的id数组,如array(1, 2, 3)
     * @return array 返回能够查询到的数据构成的数组,没有查询到的id不会包含在其中
     */
    public function loadByIds($keys) {
        $keys = array_filter(array_combine($keys, $keys), function($v) {
                    return ctype_digit((string) $v) && $v > 0 ? true : false;
                });
        if (count($keys) == 0)
            return array();
        $key = $this->key();
        $columns = $this->columns();
        $className = $this->className();
        $objClean = new $className;
        //get data from memcache
        $data = array_combine(
            array_keys($keys), $this->cacheGetMulti(array_keys($keys))
        );
        //Get data from db
        $keyArrays = array_chunk(array_keys(array_filter($data, function($v) {
            return !is_object($v);
        })), 100);
        $opts = array();
        foreach ($keyArrays as $ks) {
            $this->whereAnd($key, 'IN (' . implode(',', $ks) . ')');
            $opts['limit'] = count($ks);
            $opts['order'] = 'no';
            if (($rows = $this->getQuery($opts))) {
                while ($row = mysql_fetch_assoc(self::$result[$className])) {
                    $_obj = clone $objClean;
                    $this->parseRow($_obj, $row, $columns);
                    $data[$_obj->{$key}] = $_obj;
                    //store to memcache
                    $this->cacheSet($_obj->{$key}, $_obj);
                }
            }
            $this->clean();
        }
        return array_filter($data, function($v) {
            return is_object($v);
        });
    }

    public function __wakeup() {
        if (!isset(self::$dbs[$this->className()]))
            $this->__construct();
    }

    /**
     * 返回已经执行过的mysql数据库查询次数
     * @return int 数据库查询的次数
     */
    public static function counter() {
        return self::$counter;
    }

}

?>
