<?php
namespace Lib\Solr;
abstract class Searcher {
    const HOST_MASTER = 'master';
    const HOST_SLAVE = 'slave';
    const URI_QUERY = 'select';
    const URI_UPDATE = 'update';
    const URI_LUKE = 'admin/luke';
    const MAX_ROWS = 1000;
    const DEFAULT_WT = 'json';
    const DEFAULT_QUERY = '*:*';
    const DEFAULT_ROWS = 10;
    const FACET_SORT_COUNT = 'count';
    const FACET_SORT_INDEX = 'index';
    private static $host = array(), $cores = array(), $counter = 0, $rows = array(), $fieldList = array(), $dbs = array(), $uniqueKey = array();
    private $q = self::DEFAULT_QUERY, $fq = array(), $sort = array(), $page = 1;
    private $facetField = array(), $facetQuery = array(), $facetLimit = 100, $facetSort = self::FACET_SORT_COUNT, $facetOffset = 0, $facetMincount = 0;
    private $facetDateQuery = array(), $facetRangeQuery = array();
    /**
     * 是否需要根据solr找到的id去mysql或memcache中读取出对象
     * @var boolean
     */
    private $loadObject = true;
    /**
     * 是否highlight
     * @var boolean
     */
    private $hl = false;
    /**
     * 需要highlight的字段,留空的话就只会highlight schema.xml中的defaultSearchField, 默认为空
     * @var array
     */
    private $hlFieldList = array();
    /**
     * 字段中highlight片段的个数,默认为1个,即只会高亮匹配到的第一个
     * @var int
     */
    private $hlSnippets = 1;
    /**
     * highlight的字段返回的字符个数,默认为100个字符
     * @var int
     */
    private $hlFragsize = 100;
    /**
     * 是否强制访问master 默认为false 可使用$this->forceMaster(true|false)来切换
     * @var boolean
     */
    private $forceMaster = false;
    /**
     * 指定是否长时间等待solr响应,通过$this->setWait(true)来指定长时间等待
     * @var boolean
     */
    private $isWait = false;
    /**
     * 最后一次请求的错误信息,通过$this->lastError()可以在外部查看
     * @var array
     */
    private $lastError = array();

    abstract protected function __construct();

    /**
     * 初始化一core的信息
     * @param array $options 可以自定义的一些选项 其中core是必须指定的
     * <pre>
     * $options = array(
     *     'core' => 'your core name',
     *     'rows' => 10,//指定每次请求返回的行数 不指定默认为10条
     *     'fieldList' => array('id', 'title')//指定返回的数据字段(field list), 不指定默认只返回id
     *     'dbObject' => 'MysqlData object',//指定继承于MysqlData的orm类名字
     *     'uniqueKey' => 'id' //core中的uniqueKey字段名
     * )
     * </pre>
     * @return \Lib\Solr\Searcher
     * @throws \Exception
     */
    final protected function init(array $options = array()) {
        $required = array_flip(array(
            'core',
        ));
        if (count($diff = array_diff_key($required, $options)) > 0) {
            throw new \Exception('key [' . implode('],[', array_keys($diff)) . '] must be specified in options');
        }
        self::$cores[$this->className()] = $options['core'];//core name
        self::$rows[$this->className()] = isset($options['rows']) && ctype_digit((string)$options['rows']) && $options['rows'] <= self::MAX_ROWS ? $options['rows'] : self::DEFAULT_ROWS;
        self::$fieldList[$this->className()] = isset($options['fieldList']) && is_array($options['fieldList']) && !empty($options['fieldList']) ? $options['fieldList'] : array('id');
        self::$dbs[$this->className()] = isset($options['dbObject']) && class_exists($options['dbObject']) ? $options['dbObject'] : null;
        self::$uniqueKey[$this->className()] = isset($options['uniqueKey']) ? $options['uniqueKey'] : 'id';
        return $this;
    }

    /**
     * 获取core中的uniqueKey字段名字
     * @return string solr core的uniqueKey的字段名
     */
    public function uniqueKey() {
        return self::$uniqueKey[$this->className()];
    }


    /**
     * 获取实例化的类名称
     * @return string 实例化的类名称
     */
    private function className() {
        return get_class($this);
    }

    /**
     * 获取设置的solr的core名称
     * @return string solr core的名称
     */
    private function coreName() {
        return self::$cores[$this->className()];
    }

    /**
     * 强制切换到master进行操作, 在$this->buildUrl()的时候会用$this->masterCore()的url,并在切换回来之前都一直从master访问
     * @param boolean $bool true或false, 默认为true
     * @return \Lib\Solr\Searcher
     */
    public function forceMaster($bool = true) {
        $this->forceMaster = (bool)$bool;
        return $this;
    }

    /**
     * 返回最后一次请求的错误信息
     * @return array solr返回的错误信息
     */
    public function lastError() {
        return $this->lastError;
    }

    /**
     * 获取init()时指定的数据库类实例 如果没有在init()的时候指定dbObject或指定的dbObject不是MysqlData的子类则返回null
     * @return MysqlData
     */
    private function dbObject() {
        if ($this->getLoadObject() == false) {
            return null;
        }
        if (self::$dbs[$this->className()] instanceof \Lib\Mysql\Data) {
            return self::$dbs[$this->className()];
        } elseif (!is_null(self::$dbs[$this->className()])) {
            $dbObject = new self::$dbs[$this->className()]();
            self::$dbs[$this->className()] = $dbObject instanceof \Lib\Mysql\Data ? $dbObject : null;
            return self::$dbs[$this->className()];
        } else {
            return null;
        }
    }

    /**
     * 通过数据库类实例获取一组ids的数据 若没有指定数据库类或不是MysqlData的子类 直接返回空数组
     * @param array $keys 需要获取的数据的ids数组
     * @return array 通过数据库类查找到的数据
     */
    private function dbLoadByIds(array $keys) {
        $result = array();
        $dbObject = $this->dbObject();
        if (!is_null($dbObject)) {
            $keys = array_filter($keys, function($v) {
                return ctype_digit((string)$v) && $v > 0 ? true : false;
            });
            $result = $dbObject->loadByIds($keys);
        }
        return $result;
    }

    /**
     * 用curl请求solr地址
     * @param string $url solr的请求地址
     * @param boolean $wait 是否等待到成功 最多等待25秒
     * @param string|array $postData 需要提交的数据
     * @return boolean|json 失败返回false,成功返回请求的结果,默认是根据self::DEFAULT_WT来返回json数据
     */
    private function request($url, $wait = false, $postData = null) {
        $timeout = $wait ? 25 : 3;//if $wait timeout after 25s, otherwise timeout after 3s
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);//if get binary data need to set header false
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        if (!empty($postData)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/json"));
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
        $body = curl_exec($ch);
        $this->lastError = array();
        $this->increaseCounter();
        $info = curl_getinfo($ch);
        $error = curl_error($ch);
        if ($body === false || $info['http_code'] != 200 || $error != '') {
            if ($body) $this->lastError = json_decode($body, true);
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        return $body;
    }

    /**
     * 增加请求solr的次数
     * @return \Lib\Solr\Searcher
     */
    private function increaseCounter() {
        self::$counter++;
        return $this;
    }

    /**
     * 设定返回搜索结果的行数
     * @param int $rows 必须是正整数而且小于等于self::MAX_ROWS
     * @return \Lib\Solr\Searcher
     */
    public function setRows($rows) {
        if (ctype_digit((string)$rows) && $rows <= self::MAX_ROWS) {
            self::$rows[$this->className()] = $rows;
        }
        return $this;
    }

    /**
     * 获取当前solr实例每次搜索返回的行数
     * @return int 当前设置的返回行数
     */
    public function getRows() {
        return self::$rows[$this->className()];
    }

    /**
     * 指定是否长时间等待solr响应
     * @param boolean $flag true为长等待(25s) false为不等待(1s)
     * @return \Lib\Solr\Searcher
     */
    public function setWait($flag) {
        $this->isWait = is_bool($flag) ? $flag : false;
        return $this;
    }

    /**
     * 获取当前设置的fl (field list)
     * @return array field list数组
     */
    public function getFieldList() {
        return self::$fieldList[$this->className()];
    }

    /**
     * 指定field list的字段
     * @param array $fieldList 指定的filed list字段
     * @return \Lib\Solr\Searcher
     */
    public function setFieldList(array $fieldList) {
        self::$fieldList[$this->className()] = array('id');
        foreach (array_diff($fieldList, $this->getFieldList()) as $field) {
            self::$fieldList[$this->className()][] = $field;
        }
        return $this;
    }

    /**
     * 解析field list的参数
     * @return string 用于solr查询的field list参数
     */
    private function parseFieldList() {
        return implode(',', $this->getFieldList());
    }

    /**
     * 指定当前页码
     * @param int $page 当前的页码不是正整数的情况默认为1
     * @return \Lib\Solr\Searcher
     */
    public function setPage($page) {
        $this->page = ctype_digit((string)$page) && $page > 0 ? (int)$page : 1;
        return $this;
    }

    /**
     * 搜索df(default search field)字段的关键词 为空的话则使用*:*
     * @param string $string 搜索的关键字
     * @return \Lib\Solr\Searcher
     */
    public function defaultQuery($string = self::DEFAULT_QUERY) {
        $string = strlen(trim($string)) == 0 || $string == self::DEFAULT_QUERY ? self::DEFAULT_QUERY : $this->solrEscape($string);
        $this->q = $string;
        return $this;
    }

    /**
     * 添加fq(filter query)的查询
     * @param string $field
     * @param string $value
     * @return \Lib\Solr\Searcher
     */
    public function query($field, $value) {
        if (strlen(trim($field)) !=0 && strlen(trim($value)) != 0) {
            $this->fq[] = "{$field}:{$value}";
        }
        return $this;
    }

    /**
     * 对某个字段进行范围搜索
     * @param string $field 搜索的字段名
     * @param string $lower 搜索范围开始值
     * @param string $upper 搜索范围结束值
     * @return \Lib\Solr\Searcher
     */
    public function rangeQuery($field, $lower = '*', $upper = '*') {
        $lower = strlen(trim($lower)) == 0 ? '*' : $lower;
        $upper = strlen(trim($upper)) == 0 ? '*' : $upper;
        return $this->query($field, "[{$lower} TO {$upper}]");
    }

    /**
     * 搜索某一整天时间段的结果 查询字段需要是索引的date或tdate类型
     * @param string $field 查询字段 其在solr中的fieldType需要是date或tdate类型 如2000-01-01T01:01:01Z
     * @param string $value 日期如yyyy-mm-dd的格式,会自动补上当天开始和结束时分秒的值
     * @return \Lib\Solr\Searcher
     */
    public function dateQuery($field, $value) {
        if (preg_match('/^\d{4}-([0][1-9]|1[012])-([012][0-9]|3[01])$/', $value)) {
            $this->query($field, "[{$value}T00:00:00Z TO {$value}T23:59:59Z]");
        }
        return $this;
    }

    /**
     * 搜索某一段时间范围的结果 查询字段需要是索引的date或tdate类型
     * @param string $field 查询字段 其在solr中的fieldType需要是date或tdate类型 如2000-01-01T01:01:01Z
     * @param type $startDateTime 时间范围开始值,支持yyyy-mm-dd | yyyy-mm-dd hh:mm | yyyy-mm-dd hh:mm:ss 的格式 用*或为空则不限
     * @param type $endDateTime 时间范围结束值,支持yyyy-mm-dd | yyyy-mm-dd hh:mm | yyyy-mm-dd hh:mm:ss 的格式 用*或为空则不限
     * @return \Lib\Solr\Searcher
     */
    public function dateRangeQuery($field, $startDateTime = '*', $endDateTime = '*') {
        //$pattern = '/^\d{4}-([0][1-9]|1[012])-([012][0-9]|3[01])$|^\d{4}-([0][1-9]|1[012])-([012][0-9]|3[01])\s([01][0-9]|2[0123]):[0-5][0-9](:[0-5][0-9])?$|^\*$/';
        if (strlen(trim($startDateTime)) == 0) $endDateTime = '*';
        if (strlen(trim($endDateTime)) == 0) $endDateTime = '*';
        if ((strtotime($startDateTime) || $endDateTime == '*') && (strtotime($endDateTime) || $endDateTime == '*')) {
            $startDateTime = $startDateTime != '*' ? $this->formatDateTime($startDateTime) : $startDateTime;
            $endDateTime = $endDateTime != '*' ? $this->formatDateTime($endDateTime) : $endDateTime;
            $this->rangeQuery($field, $startDateTime, $endDateTime);
        }
        return $this;
    }

    /**
     * 搜索某一段时间范围的结果 查询字段需要是索引的timestamp
     * @param string $field 查询字段 其在solr中的fieldType需要是int或tint等数字类型 如1325350861
     * @param string $startDateTime 时间范围开始值,支持yyyy-mm-dd | yyyy-mm-dd hh:mm | yyyy-mm-dd hh:mm:ss 的格式 用*或为空为不限
     * @param string $endDateTime 时间范围结束值,支持yyyy-mm-dd | yyyy-mm-dd hh:mm | yyyy-mm-dd hh:mm:ss 的格式 用*或为空为不限
     * @return \Lib\Solr\Searcher
     */
    public function timestampRangeQuery($field, $startDateTime = '*', $endDateTime = '*') {
        //$pattern = '/^\d{4}-([0][1-9]|1[012])-([012][0-9]|3[01])$|^\d{4}-([0][1-9]|1[012])-([012][0-9]|3[01])\s([01][0-9]|2[0123]):[0-5][0-9](:[0-5][0-9])?$|^\*$/';
        if (strlen(trim($startDateTime)) == 0) $startDateTime = '*';
        if (strlen(trim($endDateTime)) == 0) $endDateTime = '*';
        if ((($startDateTimeStamp = strtotime($startDateTime)) || $startDateTime = '*') && (($endDateTimeStamp = strtotime($endDateTime)) || $endDateTime == '*')) {
            $startDateTime = $startDateTime != '*' ? $startDateTimeStamp : $startDateTime;
            $endDateTime = $endDateTime != '*' ? $endDateTimeStamp : $endDateTime;
            $this->rangeQuery($field, $startDateTime, $endDateTime);
        }
        return $this;
    }

    /**
     * 搜索某一整天时间段的结果 查询字段需要是索引的int或tint类型
     * @param string $field 查询字段 其在solr中的fieldType需要是int或tint类型 如1325350861
     * @param string $value 日期如yyyy-mm-dd的格式,会自动补上当天开始和结束时分秒的值
     * @return \Lib\Solr\Lib\Solr\Searcher
     */
    public function timestampQuery($field, $value) {
        if (preg_match('/^\d{4}-([0][1-9]|1[012])-([012][0-9]|3[01])$/', $value)) {
            $this->query($field, '[' . strtotime("{$value} 00:00:00") . ' TO ' . strtotime("{$value} 23:59:59") . ']');
        }
        return $this;
    }

    /**
     * 搜索查询字段包括一系列值
     * @param string 查询的字段
     * @param array $values 查询字段包括的一系列值
     * @return \Lib\Solr\Searcher
     */
    public function inQuery($field, array $values = array()) {
        if (!empty($values)) $this->query($field, '(' . implode(' OR ', $values) . ')');
        return $this;
    }

    /**
     * 生成fq[filter query]的查询字符
     * @return string
     */
    private function parseFq() {
        return implode(' ', $this->fq);
    }

    /**
     * 格式化时间为solr的date类型时间
     * @param string|int $date 可以是时间戳或YYYY-MM-DD | YYYY-MM-DD HH:MM | YYYY-MM-DD HH:MM:SS
     * @return string solr的date类型的查询格式 如 2012-12-13T12:13:14Z
     */
    public function formatDateTime($date) {
        if (ctype_digit((string)$date)) {
            return date('Y-m-d\TH:i:s\Z', $date);
        }
        return date('Y-m-d\TH:i:s\Z', strtotime($date));
    }

    /**
     * 过滤q(default query)的查询字符
     * @param string $value 查询的字符
     * @return string 过滤后的查询字符
     */
    private function solrEscape($value) {
        $pattern = '/(\+|-|&&|\|\||!|\(|\)|\{|}|\[|]|\^|"|~|\*|\?|:|\\\)/';
        return preg_replace($pattern, '\\\$1', $value);
    }


    /**
     * 更新solr索引
     * @param array $data 需要更新的数据
     * <pre>
     * single data update
     * $data = array(
     *     'id' => 1,
     *     'title' => 'my title',
     *     ...
     * );
     * multi data update
     * $data = array(
     *     array(
     *         'id' => 1,
     *         'title' => 'my title1',
     *         ...
     *     ),
     *     array(
     *         'id' => 2,
     *         'title' => 'my title2',
     *     ),
     *     ...
     * );
     * </pre>
     * @param boolean $commit 是否进行commit操作,默认为执行提交
     * @return boolean 成功返回true,失败false,若失败可以用$this->lastError()查看出错信息
     */
    public function update(array $data, $commit = true) {
        $params = array(
            'master' => true,
            'requestHandler' => self::URI_UPDATE,
        );
        if ((bool)$commit) $params['commit'] = 'true';
        $url = $this->buildUrl('', $params);
        if (!is_array(current($data))) $data = array($data);//single data
        if (empty($data)) {
            $json = null;
        } elseif (isset($data['delete'])) {
            $json = json_encode($data);//for delete
        } else {
            $json = json_encode(array_values($data));//for update
        }
        return $this->request($url, $this->isWait, $json);
    }

    /**
     * 执行solr的commit操作
     * @return boolean 成功返回true,失败返回false,若失败可以用$this->lastError()查看出错信息
     */
    public function commit() {
        return $this->update(array(), true);
    }

    /**
     * 通过id在solr中删除一条数据
     * @param int $id 要删除的id,只能是正整数
     * @return boolean 成功返回true,失败返回false,失败原因可以用$this->lastError()查看
     * @throws \Exception id不为正整数的时候抛错
     */
    public function delete($id) {
        if (ctype_digit((string)$id) && $id > 0) {
            $uniqueKey = $this->uniqueKey();
            $data = array(
                'delete' => array(
                    $uniqueKey => $id,
                )
            );
            return $this->update($data);
        } else {
            throw new \Exception("{$uniqueKey}:{$id} is not a numeric");
        }
    }

    /**
     * 根据id进行批量删除
     * @param array $ids 需要删除的id, 各个id必须是正整数,否则会过滤掉
     * @return boolean 成功返回true,失败返回false,若出错可以通过$this->lastError()查看原因
     * @throws \Exception 过滤后的ids为空数组的情况
     */
    public function deleteByIds(array $ids) {
       $ids = array_filter($ids, function($v) {
            return ctype_digit((string) $v) && $v > 0 ? true : false;
        });
        if (!empty($ids)) {
            $query = 'id:(' . implode(' OR ', $ids) . ')';
            try {
                return $this->deleteByQuery($query);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        } else {
            throw new \Exception('ids is empty');
        }
    }

    /**
     * 根据条件删除solr中的数据
     * @param string $query 符合 solr 查询语法的查询条件
     * @return boolean
     * @throws \Exception query为空的情况
     */
    public function deleteByQuery($query) {
        if (strlen(trim($query)) == 0) throw new \Exception('a query syntax is required');
        $data = array(
            'delete' => array(
                'query' => $query,
            )
        );
        return $this->update($data);
    }

    /**
     * 获取当前solr core的schema
     * @return boolean|array
     */
    public function schema() {
        $parmas = array(
            'show' => 'schema',
            'requestHandler' => self::URI_LUKE,
        );
        $url = $this->buildUrl('', $parmas);
        if (($body = $this->request($url))) {
            return json_decode($body, true);
        }
        return false;
    }

    /**
     * 根据q[default query], fq[filter query], sort 来搜索得出结果
     * @return array 查询结果
     * <pre>
     * <code>
     * $result = array(
     * &nbsp;&nbsp;'currentPage' => 1,//当前页
     * &nbsp;&nbsp;'totalPage' => 224,//总页数
     * &nbsp;&nbsp;'rows' => 10,//每页记录数
     * &nbsp;&nbsp;'numFound' => 2233,//总记录数
     * &nbsp;&nbsp;'start' => 0,//跳过条数
     * &nbsp;&nbsp;'docs' => array(
     * &nbsp;&nbsp;&nbsp;&nbsp;0 => array(
     * &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'id' => 1,
     * &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;...
     * &nbsp;&nbsp;&nbsp;&nbsp;),
     * &nbsp;&nbsp;&nbsp;&nbsp;1 => array(
     * &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'id' => 2,
     * &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;...
     * &nbsp;&nbsp;&nbsp;&nbsp;),
     * &nbsp;&nbsp;&nbsp;&nbsp;...
     * &nbsp;&nbsp;),
     * );
     * </code>
     * </pre>
     */
    public function search() {
        $result = false;
        $q = $this->q;
        $options['fq'] = $this->parseFq();
        if (!empty($this->sort)) $options['sort'] = $this->parseSort();
        $options['rows'] = $this->getRows();
        $options['page'] = $this->page;
        $options['start'] = ($options['page'] - 1) * $options['rows'];
        $options['fl'] = $this->parseFieldList();
        if ($this->hl) {
            $options['hl'] = 'true';
            $options['hl.fl'] = implode(',', $this->hlFieldList);
            foreach (array('hl.fragsize' => 'hlFragsize', 'hl.snippets' => 'hlSnippets') as $hlParam => $hlProperty) {
                $options[$hlParam] = $this->{$hlProperty};
            }
        }
        if (($data = $this->request($this->buildUrl($q, $options), $this->isWait))) {
            $data = json_decode($data, true);
            $result['currentPage'] = $options['page'];
            $result['totalPage'] = ceil($data['response']['numFound'] / $options['rows']);
            $result['rows'] = $options['rows'];
            $result += $data['response'];
            //如果init()的时候指定了db的对象,则根据查找到的ids来通过db读取数据
            if (!is_null($this->dbObject()) && !empty($result['docs'])) {
                $uniqueKey = $this->uniqueKey();
                $result['docs'] = $this->dbLoadByIds(array_map(function($v) use($uniqueKey){
                    return isset($v[$uniqueKey]) ? $v[$uniqueKey] : null;
                }, $result['docs']));
            }
            if ($this->hl) $result['highlighting'] = $data['highlighting'];
        }
        return $result;
    }

    /**
     * 自定义查询条件的层面搜索(facet query)
     * @param sting $field 查询的字段 不能为空
     * @param string $value 查询的值 不能为空
     * @return \Lib\Solr\Searcher
     */
    public function facetQuery($field, $value) {
        if (strlen(trim($field)) > 0 && strlen(trim($value)) > 0) {
            $this->facetQuery[] = "{$field}:{$value}";
        }
        return $this;
    }

    /**
     * 自定义范围的层面搜索(facet query)
     * @param string $field 查询的字段 不能为空 其在solr中的fieldType需要是date或tdate类型 如2000-01-01T01:01:01Z
     * @param string $start 查询字段的开始值 为空则会自动用*替换
     * @param string $end 查询字段的结束值 为空则会自动用*替换
     * @return \Lib\Solr\Searcher
     */
    public function facetQueryRange($field, $start = '*', $end = '*') {
        $start = strlen(trim($start)) == 0 ? '*' : $start;
        $end = strlen(trim($end)) == 0 ? '*' : $end;
        return $this->facetQuery($field, "[{$start} TO {$end}]");
    }

    /**
     * 自定义时间范围的层面搜索(facet query) 查询字段需要是索引的date或tdate类型
     * @param string $field 查询的字段 不能为空
     * @param string $startDateTime 时间范围开始值,支持yyyy-mm-dd | yyyy-mm-dd hh:mm | yyyy-mm-dd hh:mm:ss 的格式 用*或为空则不限
     * @param string $endDateTime 时间范围结束值,支持yyyy-mm-dd | yyyy-mm-dd hh:mm | yyyy-mm-dd hh:mm:ss 的格式 用*或为空则不限
     * @return \Lib\Solr\Searcher
     */
    public function facetQueryDateRange($field, $startDateTime = '*', $endDateTime = '*') {
        $pattern = '/^\d{4}-([0][1-9]|1[012])-([012][0-9]|3[01])$|^\d{4}-([0][1-9]|1[012])-([012][0-9]|3[01])\s([01][0-9]|2[0123]):[0-5][0-9](:[0-5][0-9])?$|^\*$/';
        if (strlen(trim($startDateTime)) == 0) $startDateTime = '*';
        if (strlen(trim($endDateTime)) == 0) $endDateTime = '*';
        if (preg_match($pattern, $startDateTime) && preg_match($pattern, $endDateTime)) {
            $startDateTime = $startDateTime != '*' ? $this->formatDateTime($startDateTime) : $startDateTime;
            $endDateTime = $endDateTime != '*' ? $this->formatDateTime($endDateTime) : $endDateTime;
            $this->facetQueryRange($field, $startDateTime, $endDateTime);
        }
        return $this;
    }

    /**
     * 自定义时间范围的层面搜索(facet query) 查询字段需要是索引的timestamp
     * @param string $field 查询字段 其在solr中的fieldType需要是int或tint等数字类型 如1325350861
     * @param string $startDateTime 时间范围开始值,支持yyyy-mm-dd | yyyy-mm-dd hh:mm | yyyy-mm-dd hh:mm:ss 的格式 用*或为空为不限
     * @param string $endDateTime 时间范围结束值,支持yyyy-mm-dd | yyyy-mm-dd hh:mm | yyyy-mm-dd hh:mm:ss 的格式 用*或为空为不限
     * @return \Lib\Solr\Searcher
     */
    public function facetQueryTimestampRange($field, $startDateTime = '*', $endDateTime = '*') {
        $pattern = '/^\d{4}-([0][1-9]|1[012])-([012][0-9]|3[01])$|^\d{4}-([0][1-9]|1[012])-([012][0-9]|3[01])\s([01][0-9]|2[0123]):[0-5][0-9](:[0-5][0-9])?$|^\*$/';
        if (strlen(trim($startDateTime)) == 0) $startDateTime = '*';
        if (strlen(trim($endDateTime)) == 0) $endDateTime = '*';
        if (preg_match($pattern, $startDateTime) && preg_match($pattern, $endDateTime)) {
            $startDateTime = $startDateTime != '*' ? strtotime($startDateTime) : $startDateTime;
            $endDateTime = $endDateTime != '*' ? strtotime($endDateTime) : $endDateTime;
            $this->facetQueryRange($field, $startDateTime, $endDateTime);
        }
        return $this;
    }

    /**
     * 时间层面的搜索, 只能搜索fieldType是DateField的字段
     * @param string $field 搜索的字段,在solr中的字段类型必须是DateField,如[date], [tdate]
     * @param string $start 时间范围开始值,支持yyyy-mm-dd | yyyy-mm-dd hh:mm | yyyy-mm-dd hh:mm:ss 的格式 为空是NOW
     * @param string $end 时间范围结束值,支持yyyy-mm-dd | yyyy-mm-dd hh:mm | yyyy-mm-dd hh:mm:ss 的格式 为空是NOW
     * @param string $gap 从开始时间到结束时间的里每个层面的时间间隔 如+1DAY,+2MONTH,+3YEAR,+1HOUR,+60MINUTE,+3600SECOND,
     * @return \Lib\Solr\Searcher
     */
    public function facetDateQuery($field, $start, $end, $gap) {
        $pattern = '/^\d{4}-([0][1-9]|1[012])-([012][0-9]|3[01])$|^\d{4}-([0][1-9]|1[012])-([012][0-9]|3[01])\s([01][0-9]|2[0123]):[0-5][0-9](:[0-5][0-9])?$|^NOW$/';
        if (strlen(trim($start)) == 0) $start = 'NOW';
        if (strlen(trim($end)) == 0) $end = 'NOW';
        if (strpos($gap, '+') === false) $gap = "+{$gap}";
        if (preg_match($pattern, $start) && preg_match($pattern, $end) && $this->dateGap($gap)) {
            $start = $start != 'NOW' ? $this->formatDateTime($start) : $start;
            $end = $end != 'NOW' ? $this->formatDateTime($end) : $end;
            $this->facetDateQuery[$field] = array(
                'start' => $start,
                'end' => $end,
                'gap' => $gap,
            );
        }
        return $this;
    }

    /**
     * 各种字段类型的层面范围搜索(包含时间),只要参数正确就会返回对应的层面结果
     * @param string $field 搜索的字段
     * @param string $start 范围开始值
     * @param string $end 范围结束值
     * @param string $gap 开始值到结束值之间的间隔增量
     */
    public function facetRangeQuery($field, $start, $end, $gap) {
        $this->facetRangeQuery[$field] = array(
            'start' => $start,
            'end' => $end,
            'gap' => $gap,
        );
        return $this;
    }

    /**
     * 验证gap是否符合规范
     * @param string $gap 间隔的时间返回
     * @return boolean 符合规范返回true, 其他返回false
     */
    private function dateGap($gap) {
        $pattern = '/^\+[1-9][0-9]*(DAY[S]?|HOUR[S]?|MONTH[S]?|YEAR[S]?|MINUTE[S]?|SECOND[S]?)$/';
        return preg_match($pattern, $gap);
    }

    /**
     * 解析facet.date.query和facet.range.query的参数
     * @param string $type facet的请求类型, 可以是date或range
     * @return array
     */
    private function parseFacetQuery($type) {
        $result = array();
        if (!in_array($type, array('date', 'range'))) return $result;
        $property = 'facet' . ucfirst($type) . 'Query';
        $result["facet.{$type}"] = array_keys($this->{$property});
        foreach ($this->{$property} as $field => $attr) {
            foreach (array('start', 'end', 'gap') as $val) {
                $result["f.{$field}.facet.{$type}.{$val}"] = $attr[$val];
            }
        }
        return $result;
    }

    /**
     * 根据字段来进行层面搜索(facet query)
     * @param sting|array $field 需要进行facet query的字段 可以是字符或数组
     * @return \Lib\Solr\Searcher
     */
    public function facetField($field) {
        if (is_string($field) && !in_array($field, $this->facetField)) {
            $this->facetField[] = $field;
        } elseif (is_array($field)) {
            foreach (array_diff($field, $this->facetField) as $eachField) {
                $this->facetField[] = $eachField;
            }
        }
        return $this;
    }

    /**
     * 执行facet_field返回结果的排序规则, 其中[count]是按照每个层面包含文档数量来排序, [index]是按照各个层面的名字进行排序
     * @param string $sort 排序规则可以是[count]|[index]
     * @return \Lib\Solr\Searcher
     */
    public function facetSort($sort) {
        $allowedFacetSort = array(
            self::FACET_SORT_COUNT,
            self::FACET_SORT_INDEX,
        );
        if (in_array($sort, $allowedFacetSort)) $this->facetSort = $sort;
        return $this;
    }

    /**
     * 指定facet_field的offset值, 用于分页
     * @param int $offset 默认为0, 必须是非负数
     * @return \Lib\Solr\Searcher
     */
    public function facetOffset($offset) {
        $this->facetOffset = ctype_digit((string)$offset) ? $offset : 0;
        return $this;
    }

    /**
     * 指定facet_field中返回层面中最小的count,没有指定时默认为0 也就是count数大于0的都返回
     * @param int $mincount 最小的count数, 必须为非负数
     * @return \Lib\Solr\Searcher
     */
    public function facetMincount($mincount) {
        $this->facetMincount = ctype_digit((string)$mincount) ? $mincount : 0;
        return $this;
    }

    /**
     * 设置每个facet_field的返回结果条数
     * @param int $limit 指定的返回条数,只能是正整数,否则默认是最多100条
     * @return \Lib\Solr\Searcher
     */
    public function facetLimit($limit) {
        $this->facetLimit = ctype_digit((string)$limit) && $limit > 0 ? $limit : 100;
        return $this;
    }

    /**
     * do nothing
     * @return \Lib\Solr\Searcher
     */
    public function facetPrefix() {
        return $this;
    }

    /**
     * 层面搜索(facet query)的结果
     * @return boolean|array 层面搜索的结果请求失败为false 请求成功为几个层面搜索结果的数组
     * <pre>
     * <code>
     * $result = array(
     *     'facet_queries' => array(
     *         'createdtime:[2013-01-01T00:00:00Z TO 2013-01-02T00:00:00Z]' => 31,
     *         'addtime:[1356969600 TO 1357056000]' => 34,
     *     ),
     *     'facet_fields' => array(
     *         'cid' => array(
     *             12 => 3255,
     *             15 => 2250,
     *             8 => 2076,
     *         ),
     *         'language' => array(
     *             '' => 16486,
     *             '英语' => 1548,
     *             '国语' => 1272,
     *             '日语' => 425,
     *         ),
     *     ),
     *     'facet_dates' => array(
     *         'createdtime' => array(
     *             '2012-12-30T00:00:00Z' => 38,
     *             '2012-12-31T00:00:00Z' => 38,
     *             '2013-01-01T00:00:00Z' => 38,
     *             ...
     *             'gap' => +1DAY,
     *             'start' => '2012-12-30T00:00:00Z',
     *             'end' => '2013-01-28T00:00:00Z',
     *         )
     *
     *     ),
     *     'facet_ranges' => array(
     *         'hits' => array(
     *             'counts' => array(
     *                 '100-150' => 646,
     *                 '150-200' => 426,
     *                 '200-250' => 256,
     *                 ...
     *                 '450-500' => 17,
     *             )
     *             'gap' => 50,
     *             'start' => 100,
     *             'end' => 500,
     *         )
     *     ),
     * );
     * </code>
     * </pre>
     */
    public function facetSearch() {
        $result = false;
        $q = $this->q;
        if (!empty($this->facetField)) $options['facet.field'] = $this->facetField;
        if (!empty($this->facetQuery)) $options['facet.query'] = $this->facetQuery;
        $options['rows'] = 0;//need not get any rows
        $options['page'] = 1;
        $options['start'] = 0;
        //$options['fl'] = 'id';//only need id cuz it won't be use anywhere
        $options['facet'] = 'true';
        if ($this->facetLimit != 100) $options['facet.limit'] = $this->facetLimit;
        if ($this->facetSort != self::FACET_SORT_COUNT) $options['facet.sort'] = $this->facetSort;
        if ($this->facetOffset != 0) $options['facet.offset'] = $this->facetOffset;
        if ($this->facetMincount != 0) $options['facet.mincount'] = $this->facetMincount;
        if (!empty($this->facetDateQuery)) $options += $this->parseFacetQuery('date');
        if (!empty($this->facetRangeQuery)) $options += $this->parseFacetQuery('range');
        if (($data = $this->request($this->buildUrl($q, $options), $this->isWait))) {
            $data = json_decode($data, true);
            $result = $data['facet_counts'];
            if (!empty($result['facet_fields'])) {
                $result['facet_fields'] = array_map(function($item) {
                    $res = array();
                    foreach (array_chunk($item, 2) as $eachChunk) {
                        $res[$eachChunk[0]] = $eachChunk[1];
                    }
                    return $res;
                }, $result['facet_fields']);
            }
            if (!empty($result['facet_ranges'])) {
                $result['facet_ranges'] = array_map(function($item) {
                    foreach (array_chunk($item['counts'], 2) as $key => $eachChunk) {
                        $edge = $eachChunk[0] + $item['gap'];
                        $res['counts']["{$eachChunk[0]}-{$edge}"] = $eachChunk[1];
                    }
                    $res += array(
                        'gap' => $item['gap'],
                        'start' => $item['start'],
                        'end' => $item['end'],
                    );
                    return $res;
                }, $result['facet_ranges']);
            }
        }
        return $result;
    }

    /**
     * highlight开关
     * @param boolean $bool 设置成true就返回highlight的数据,false不返回
     * @return \Lib\Solr\Searcher
     */
    public function hl($bool = true) {
        $this->hl = (bool)$bool;
        return $this;
    }

    /**
     * highlight指定需要匹配到需要highlight的字段
     * @param array $fields 需要高亮的字段array
     * @return \Lib\Solr\Searcher
     */
    public function hlFieldList(array $fields) {
        $this->hlFieldList += array_unique(array_diff($fields, $this->hlFieldList));
        return $this;
    }

    /**
     * highlight匹配到的字段中需要highlight的次数,默认为1次
     * @param int $num 需要highlight的次数,只能是正整数
     * @return \Lib\Solr\Searcher
     */
    public function hlSnippets($num) {
        $this->hlSnippets = ctype_digit((string)$num) && $num > 0 ? $num : 1;
        return $this;
    }

    /**
     * highlight匹配到的字段返回片段的字符长度,默认为100
     * @param int $size highlight字段的字符长度 默认100,只能是正整数
     * @return \Lib\Solr\Searcher
     */
    public function hlFragsize($size) {
        $this->hlFragsize = ctype_digit((string)$size) && $size > 0 ? $size : 100;
        return $this;
    }

    /**
     * 构建请求的url地址
     * @param string $q 请求的default query的内容
     * @param array $options 其他参数
     * <pre>
     * $options = array(
     * &nbsp;&nbsp;'requestHandler' => 'select',//访问solr core的requestHandler 不指定默认为select
     * &nbsp;&nbsp;'master' => true,//需要在访问master时使用 比如update或commit操作 另外$this->forceMaster = true也会访问master 否则默认是访问slave
     * &nbsp;&nbsp;'rows' => 10,//返回的行数
     * &nbsp;&nbsp;'start' => 0,//跳过条数
     * &nbsp;&nbsp;'sort' => 'id desc',//排序规则
     * &nbsp;&nbsp;'fq' => 'createdtime:[* TO 2013-01-16T00:12:13Z]',//filter query字符
     * &nbsp;&nbsp;...//fl, facet.field, facet.query, facet, facet.limit, facet.sort...
     * );
     * </pre>
     * @return string 请求的url
     */
    private function buildUrl($q = '', array $options = array()) {
        $params = array(
            'omitHeader' => 'true',//忽略请求状态和时间
            'wt' => self::DEFAULT_WT,
        );
        if (strlen(trim($q)) > 0) $params['q'] = $q;
        $optional = array(
            'rows', 'start', 'fl', 'sort',
            'facet.field', 'facet.query', 'facet', 'facet.limit', 'facet.sort', 'facet.offset', 'facet.mincount',
            'hl', 'hl.fl', 'hl.fragsize', 'hl.snippets',
            'commit',
            'show',
        );
        foreach ($optional as $v) {
            if (isset($options[$v])) $params[$v] = $options[$v];
        }
        foreach (array('date', 'range') as $type) {
            if (isset($options["facet.{$type}"])) {
                $params["facet.{$type}"] = $options["facet.{$type}"];
                foreach ($options["facet.{$type}"] as $field) {
                    $prefix = "f.{$field}.facet.{$type}.";
                    foreach (array('start', 'end', 'gap') as $val) {
                        $params["{$prefix}{$val}"] = $options["{$prefix}{$val}"];
                    }
                }
            }
        }
        if (!empty($options['fq'])) $params['fq'] = $options['fq'];
        $requestHandler = isset($options['requestHandler']) ? $options['requestHandler'] : self::URI_QUERY;//默认请求'/select'
        $host = (isset($options['master']) && $options['master']) || $this->forceMaster ? $this->masterCore() : $this->slaveCore();
        $pattern = '/%5B\d?%5D=/';//replace name[0]=1&name[1]=2 to name=1&name=2
        return "{$host}{$requestHandler}?" . preg_replace($pattern, '=', http_build_query($params));
    }

    /**
     * 设定排序规则
     * @param array $sorts 排序规则
     * <pre>
     * $sorts = array(
     * &nbsp;&nbsp;'createdtime' => 'desc',
     * &nbsp;&nbsp;'title' => 'asc'
     * )
     * </pre>
     * @return \Lib\Solr\Searcher
     */
    public function sort(array $sorts = array()) {
        $allowed = array('asc', 'desc');
        foreach ($sorts as $field => $sort) {
            $sort = strtolower($sort);
            if (strlen($field) != 0 && in_array($sort, $allowed)) {
                $this->sort[$field] = $sort;
            }
        }
        return $this;
    }

    /**
     * 生成排序的查询字符
     * @return string 排序的查询字符
     */
    private function parseSort() {
        return implode(',', array_map(function($field, $sort) {
            return "{$field} {$sort}";
        }, array_keys($this->sort), $this->sort));
    }

    /**
     * 通过config获取配置的solr的master和slave
     * @return array 配置的solr地址信息
     * @throws \Exception 配置文件找不到
     */
    private function host() {
        $className = $this->className();
        if (!isset(self::$host[$className])) {
            try {
                self::$host[$className] = \Lib\Config::load('solr.' . $this->coreName());
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
        return self::$host[$className];
    }

    /**
     * 获取配置文件中solr的master地址
     * @return string solr的master访问地址
     * @throws \Exception 找不到配置master的地址
     */
    private function master() {
        $host = $this->host();
        if (isset($host[self::HOST_MASTER]['host'])) {
            return $host[self::HOST_MASTER]['host'];
        } else {
            throw new \Exception("searcher master is undefined");
        }
    }

    /**
     * 获取配置文件中solr的slave的地址
     * @return string solr的slave访问地址
     * @throws \Exception 找不到配置的slave地址
     */
    private function slave() {
        $host = $this->host();
        if (isset($host[self::HOST_SLAVE]['host'])) {
            return $host[self::HOST_SLAVE]['host'];
        } else {
            throw new \Exception("searcher slave is undefined");
        }
    }

    /**
     * 获取solr core的master访问地址
     * @return string solr core的master地址
     */
    private function masterCore() {
        return "{$this->master()}{$this->coreName()}/";
    }

    /**
     * 获取solr core的slave访问地址
     * @return string
     */
    private function slaveCore() {
        return "{$this->slave()}{$this->coreName()}/";
    }

    /**
     * 设置是否在search()的时候从mysql或memcache中读取数据对象
     * @param boolean $flag true为需要读取, false为不需要
     * @return \Lib\Solr\Searcher
     */
    public function setLoadObject($flag) {
        $this->loadObject = $flag;
        return $this;
    }

    /**
     * search()的时候是否要从mysql或memcache中读取数据对象
     * @return boolean true为需要读取, false为不读取
     */
    public function getLoadObject() {
        return $this->loadObject;
    }
}

?>
