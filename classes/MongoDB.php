<?php
/**
 * MongoDB 数据库驱动（官方 MongoDB PHP 扩展标准实现）
 * 
 * 本驱动基于官方 MongoDB\Client 实现，同时提供 SQL 风格的兼容接口，
 * 确保现有业务代码无需大幅修改即可运行。
 */
class MongoDBDriver extends Database {
    protected $type = 'mongodb';
    private $client;
    private $database;

    protected function connect() {
        if (!class_exists('MongoDB\Client')) {
            throw new Exception(
                "MongoDB PHP 扩展未安装\n\n" .
                "安装方法:\n" .
                "1. 安装 PHP MongoDB 扩展: pecl install mongodb\n" .
                "2. 在 php.ini 中添加: extension=mongodb.so\n" .
                "3. 安装 Composer 库: composer require mongodb/mongodb"
            );
        }

        $host = $this->config['host'] ?? 'localhost';
        $port = $this->config['port'] ?? 27017;
        $database = $this->config['database'] ?? 'blog';
        $username = $this->config['username'] ?? '';
        $password = $this->config['password'] ?? '';

        if (!empty($username) && !empty($password)) {
            $uri = sprintf(
                'mongodb://%s:%s@%s:%d/%s',
                urlencode($username),
                urlencode($password),
                $host,
                $port,
                $database
            );
        } else {
            $uri = sprintf('mongodb://%s:%d', $host, $port);
        }

        try {
            $this->client = new MongoDB\Client($uri, [
                'connectTimeoutMS' => 10000,
                'socketTimeoutMS' => 30000,
            ]);
            $this->database = $this->client->selectDatabase($database);
            $this->database->command(['ping' => 1]);
        } catch (Exception $e) {
            throw new Exception(
                "MongoDB 连接失败: " . $e->getMessage() . "\n\n" .
                "排查方法:\n" .
                "1. 确认 MongoDB 服务已启动\n" .
                "2. 检查主机地址、端口、用户名、密码是否正确\n" .
                "3. 确认数据库 '{$database}' 已存在"
            );
        }
    }

    public function disconnect() {
        $this->client = null;
        $this->database = null;
    }

    public function getClient() {
        return $this->client;
    }

    public function getDatabase() {
        return $this->database;
    }

    public function selectCollection($collection) {
        return $this->database->selectCollection($collection);
    }

    public function query($sql, $params = []) {
        $sql = $this->replacePlaceholders($sql, $params);
        $sql = trim($sql, "; \t\n\r\0\x0B");
        $lower = strtolower($sql);

        if (strpos($lower, 'select ') === 0) {
            return $this->execSelect($sql);
        }
        if (strpos($lower, 'insert ') === 0) {
            return $this->execInsert($sql);
        }
        if (strpos($lower, 'update ') === 0) {
            return $this->execUpdate($sql);
        }
        if (strpos($lower, 'delete ') === 0) {
            return $this->execDelete($sql);
        }
        if (strpos($lower, 'create table') === 0) {
            return $this->execCreateTable($sql);
        }
        if (strpos($lower, 'alter table') === 0) {
            return new MongoDumbStatement([]);
        }
        if (strpos($lower, 'drop table') === 0) {
            return $this->execDropTable($sql);
        }
        if (strpos($lower, 'show tables') === 0 || strpos($lower, 'describe ') === 0) {
            return new MongoDumbStatement([]);
        }

        return new MongoDumbStatement([]);
    }

    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt instanceof MongoDumbStatement) {
            $rows = $stmt->getRows();
            return $rows[0] ?? null;
        }
        return null;
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt instanceof MongoDumbStatement) {
            return $stmt->getRows();
        }
        return [];
    }

    private function replacePlaceholders($sql, $params) {
        if (empty($params)) return $sql;
        $parts = explode('?', $sql);
        $result = '';
        foreach ($parts as $i => $part) {
            $result .= $part;
            if ($i < count($params)) {
                $val = $params[$i];
                if (is_string($val)) {
                    $result .= "'" . addslashes($val) . "'";
                } elseif (is_null($val)) {
                    $result .= 'NULL';
                } elseif (is_bool($val)) {
                    $result .= $val ? '1' : '0';
                } else {
                    $result .= $val;
                }
            }
        }
        return $result;
    }

    private function execSelect($sql) {
        $sql = preg_replace('/\s+/', ' ', trim($sql));

        $hasJoin = stripos($sql, ' join ') !== false;
        if ($hasJoin) {
            return $this->execSelectWithJoin($sql);
        }

        $pattern = '/^SELECT\s+(.+?)\s+FROM\s+(\w+)(?:\s+AS\s+\w+|\s+\w+)?(?:\s+WHERE\s+(.+?))?(?:\s+ORDER\s+BY\s+(.+?))?(?:\s+LIMIT\s+(\d+)(?:\s+OFFSET\s+(\d+))?)?$/is';
        if (!preg_match($pattern, $sql, $m)) {
            return new MongoDumbStatement([]);
        }

        $columnsStr = trim($m[1]);
        $collectionName = trim($m[2]);
        $whereClause = isset($m[3]) ? trim($m[3]) : null;
        $orderByClause = isset($m[4]) ? trim($m[4]) : null;
        $limit = isset($m[5]) ? intval($m[5]) : 0;
        $offset = isset($m[6]) ? intval($m[6]) : 0;

        $collection = $this->database->selectCollection($collectionName);
        $filter = $whereClause ? $this->parseWhere($whereClause) : [];
        $options = [];

        if ($orderByClause) {
            $sort = [];
            $parts = explode(',', $orderByClause);
            foreach ($parts as $part) {
                $part = trim($part);
                $dir = 1;
                if (preg_match('/^(.+?)\s+(DESC|ASC)$/i', $part, $om)) {
                    $part = trim($om[1]);
                    $dir = strtoupper($om[2]) === 'DESC' ? -1 : 1;
                }
                $part = $this->stripTablePrefix($part);
                $sort[$part] = $dir;
            }
            if (!empty($sort)) {
                $options['sort'] = $sort;
            }
        }

        if ($limit > 0) {
            $options['limit'] = $limit;
        }
        if ($offset > 0) {
            $options['skip'] = $offset;
        }

        $isCount = false;
        $countAlias = 'count';
        if (stripos($columnsStr, 'COUNT(') !== false) {
            $isCount = true;
            if (preg_match('/COUNT\s*\(\s*\*\s*\)\s*(?:AS\s+(\w+))?/i', $columnsStr, $cm)) {
                if (isset($cm[1])) $countAlias = $cm[1];
            }
        }

        if ($isCount) {
            $count = $collection->countDocuments($filter);
            return new MongoDumbStatement([[$countAlias => $count]]);
        }

        $cursor = $collection->find($filter, $options);
        $rows = [];
        foreach ($cursor as $doc) {
            $row = $this->docToArray($doc);
            $rows[] = $row;
        }

        if ($columnsStr !== '*') {
            $projection = [];
            $cols = explode(',', $columnsStr);
            $hasAlias = false;
            $colAliases = [];
            foreach ($cols as $col) {
                $col = trim($col);
                $alias = null;
                if (preg_match('/^(.+?)\s+AS\s+(\w+)$/i', $col, $am)) {
                    $col = trim($am[1]);
                    $alias = $am[2];
                    $hasAlias = true;
                }
                $col = $this->stripTablePrefix($col);
                $projection[] = $col;
                if ($alias) {
                    $colAliases[$col] = $alias;
                }
            }

            if ($hasAlias || count($projection) > 0) {
                $projected = [];
                foreach ($rows as $row) {
                    $newRow = [];
                    foreach ($projection as $col) {
                        $displayName = $colAliases[$col] ?? $col;
                        $newRow[$displayName] = $row[$col] ?? null;
                    }
                    $projected[] = $newRow;
                }
                $rows = $projected;
            }
        }

        return new MongoDumbStatement($rows);
    }

    private function execSelectWithJoin($sql) {
        $sql = preg_replace('/\s+/', ' ', trim($sql));

        $selectEnd = stripos($sql, ' FROM ');
        if ($selectEnd === false) return new MongoDumbStatement([]);
        $columnsStr = trim(substr($sql, 7, $selectEnd - 7));

        $fromPart = trim(substr($sql, $selectEnd + 6));
        $whereIdx = stripos($fromPart, ' WHERE ');
        $orderIdx = stripos($fromPart, ' ORDER BY ');
        $limitIdx = stripos($fromPart, ' LIMIT ');

        $endIdx = strlen($fromPart);
        if ($whereIdx !== false && $whereIdx < $endIdx) $endIdx = $whereIdx;
        if ($orderIdx !== false && $orderIdx < $endIdx) $endIdx = $orderIdx;
        if ($limitIdx !== false && $limitIdx < $endIdx) $endIdx = $limitIdx;

        $fromClause = trim(substr($fromPart, 0, $endIdx));
        $whereClause = $whereIdx !== false ? trim(substr($fromPart, $whereIdx + 7,
            min($orderIdx !== false ? $orderIdx : strlen($fromPart),
                $limitIdx !== false ? $limitIdx : strlen($fromPart)) - $whereIdx - 7)) : null;
        $orderByClause = null;
        if ($orderIdx !== false) {
            $orderEnd = $limitIdx !== false ? $limitIdx : strlen($fromPart);
            $orderByClause = trim(substr($fromPart, $orderIdx + 10, $orderEnd - $orderIdx - 10));
        }
        $limit = 0;
        $offset = 0;
        if ($limitIdx !== false) {
            $limitPart = trim(substr($fromPart, $limitIdx + 7));
            if (preg_match('/^(\d+)(?:\s+OFFSET\s+(\d+))?/i', $limitPart, $lm)) {
                $limit = intval($lm[1]);
                $offset = isset($lm[2]) ? intval($lm[2]) : 0;
            }
        }

        $tables = $this->parseFromClause($fromClause);
        if (empty($tables)) return new MongoDumbStatement([]);

        $mainTable = $tables[0];
        $mainCollection = $this->database->selectCollection($mainTable['name']);
        $filter = $whereClause ? $this->parseWhere($whereClause, $mainTable['alias']) : [];

        $mainCursor = $mainCollection->find($filter);
        $mainDocs = [];
        foreach ($mainCursor as $doc) {
            $mainDocs[] = $this->docToArray($doc);
        }

        $resultDocs = [];
        foreach ($mainDocs as $mainDoc) {
            $row = $mainDoc;
            $matched = true;

            for ($ti = 1; $ti < count($tables); $ti++) {
                $joinTable = $tables[$ti];
                $joinCollection = $this->database->selectCollection($joinTable['name']);

                $leftCol = $joinTable['leftCol'];
                $rightCol = $joinTable['rightCol'];

                $leftVal = $row[$leftCol] ?? null;
                if ($leftVal === null) {
                    if (strtolower($joinTable['type']) === 'left') {
                        continue;
                    } else {
                        $matched = false;
                        break;
                    }
                }

                $joinFilter = [$rightCol => $leftVal];
                $joinCursor = $joinCollection->find($joinFilter, ['limit' => 100]);
                $joinDocs = [];
                foreach ($joinCursor as $jdoc) {
                    $joinDocs[] = $this->docToArray($jdoc);
                }

                if (empty($joinDocs)) {
                    if (strtolower($joinTable['type']) === 'left') {
                        foreach ($joinCollection->findOne([], ['limit' => 1]) ?: [] as $k => $v) {
                            $row[$k] = null;
                        }
                    } else {
                        $matched = false;
                        break;
                    }
                } else {
                    foreach ($joinDocs as $jdoc) {
                        foreach ($jdoc as $k => $v) {
                            if (!isset($row[$k])) {
                                $row[$k] = $v;
                            }
                        }
                    }
                }
            }

            if ($matched) {
                $resultDocs[] = $row;
            }
        }

        if ($orderByClause) {
            $sortCol = $orderByClause;
            $sortDir = 1;
            if (preg_match('/^(.+?)\s+(DESC|ASC)$/i', $orderByClause, $om)) {
                $sortCol = trim($om[1]);
                $sortDir = strtoupper($om[2]) === 'DESC' ? -1 : 1;
            }
            $sortCol = $this->stripTablePrefix($sortCol);

            usort($resultDocs, function($a, $b) use ($sortCol, $sortDir) {
                $va = $a[$sortCol] ?? '';
                $vb = $b[$sortCol] ?? '';
                if (is_numeric($va) && is_numeric($vb)) {
                    return $sortDir * ($va - $vb);
                }
                return $sortDir * strcmp($va, $vb);
            });
        }

        if ($offset > 0) {
            $resultDocs = array_slice($resultDocs, $offset);
        }
        if ($limit > 0) {
            $resultDocs = array_slice($resultDocs, 0, $limit);
        }

        $isCount = false;
        $countAlias = 'count';
        if (stripos($columnsStr, 'COUNT(') !== false) {
            $isCount = true;
            if (preg_match('/COUNT\s*\(\s*\*\s*\)\s*(?:AS\s+(\w+))?/i', $columnsStr, $cm)) {
                if (isset($cm[1])) $countAlias = $cm[1];
            }
        }

        if ($isCount) {
            return new MongoDumbStatement([[$countAlias => count($resultDocs)]]);
        }

        if ($columnsStr === '*') {
            return new MongoDumbStatement($resultDocs);
        }

        $cols = explode(',', $columnsStr);
        $colDefs = [];
        $starTables = [];
        foreach ($cols as $col) {
            $col = trim($col);
            if ($col === '*') {
                $starTables[] = null;
                continue;
            }
            if (substr($col, -2) === '.*') {
                $starTables[] = substr($col, 0, -2);
                continue;
            }
            $alias = null;
            if (preg_match('/^(.+?)\s+AS\s+(\w+)$/i', $col, $am)) {
                $col = trim($am[1]);
                $alias = $am[2];
            }
            $colName = $this->stripTablePrefix($col);
            $colDefs[] = ['name' => $colName, 'alias' => $alias ?? $colName];
        }

        $projected = [];
        foreach ($resultDocs as $row) {
            $newRow = [];
            if (!empty($starTables)) {
                foreach ($row as $k => $v) {
                    $newRow[$k] = $v;
                }
            }
            foreach ($colDefs as $def) {
                $newRow[$def['alias']] = $row[$def['name']] ?? null;
            }
            $projected[] = $newRow;
        }

        return new MongoDumbStatement($projected);
    }

    private function parseFromClause($fromClause) {
        $tables = [];
        $lower = strtolower($fromClause);

        $joinIdx = $this->findFirstJoinIndex($lower);
        $firstTableStr = $joinIdx > 0 ? trim(substr($fromClause, 0, $joinIdx)) : $fromClause;
        $rest = $joinIdx > 0 ? trim(substr($fromClause, $joinIdx)) : '';

        $mainTable = $this->parseTableRef($firstTableStr);
        if ($mainTable) {
            $mainTable['type'] = 'inner';
            $tables[] = $mainTable;
        }

        while (!empty($rest)) {
            $lowerRest = strtolower($rest);
            $joinType = 'inner';
            $joinLen = 0;

            if (strpos($lowerRest, 'left outer join ') === 0) {
                $joinType = 'left';
                $joinLen = 16;
            } elseif (strpos($lowerRest, 'left join ') === 0) {
                $joinType = 'left';
                $joinLen = 10;
            } elseif (strpos($lowerRest, 'inner join ') === 0) {
                $joinType = 'inner';
                $joinLen = 11;
            } elseif (strpos($lowerRest, 'join ') === 0) {
                $joinType = 'inner';
                $joinLen = 5;
            } else {
                break;
            }

            $rest = trim(substr($rest, $joinLen));

            $onIdx = stripos($rest, ' ON ');
            if ($onIdx === false) break;

            $tableStr = trim(substr($rest, 0, $onIdx));
            $onClause = trim(substr($rest, $onIdx + 4));

            $nextJoinIdx = $this->findFirstJoinIndex(strtolower($onClause));
            if ($nextJoinIdx > 0) {
                $rest = trim(substr($onClause, $nextJoinIdx));
                $onClause = trim(substr($onClause, 0, $nextJoinIdx));
            } else {
                $rest = '';
            }

            $table = $this->parseTableRef($tableStr);
            if ($table) {
                $table['type'] = $joinType;
                $this->parseJoinCondition($onClause, $table);
                $tables[] = $table;
            }
        }

        return $tables;
    }

    private function findFirstJoinIndex($lower) {
        $indices = [
            strpos($lower, ' left join '),
            strpos($lower, ' left outer join '),
            strpos($lower, ' inner join '),
            strpos($lower, ' join '),
        ];
        $min = false;
        foreach ($indices as $idx) {
            if ($idx !== false && ($min === false || $idx < $min)) {
                $min = $idx;
            }
        }
        return $min === false ? -1 : $min;
    }

    private function parseTableRef($str) {
        $str = trim($str);
        if (empty($str)) return null;

        $parts = preg_split('/\s+/', $str);
        $table = ['name' => $parts[0], 'alias' => null];
        if (count($parts) >= 2) {
            if (strtolower($parts[1]) === 'as' && count($parts) >= 3) {
                $table['alias'] = $parts[2];
            } else {
                $table['alias'] = $parts[1];
            }
        }
        return $table;
    }

    private function parseJoinCondition($onClause, &$table) {
        $onClause = trim($onClause);
        $parts = preg_split('/\s*=\s*/', $onClause);
        if (count($parts) === 2) {
            $left = trim($parts[0]);
            $right = trim($parts[1]);
            $table['leftCol'] = $this->stripTablePrefix($left);
            $table['rightCol'] = $this->stripTablePrefix($right);
        }
    }

    private function parseWhere($whereClause, $tableAlias = null) {
        $filter = [];
        $parts = preg_split('/\s+AND\s+/i', $whereClause);

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            if (preg_match('/^\d+\s*=\s*\d+$/', $part)) {
                continue;
            }

            $ops = ['>=', '<=', '!=', '<>', '>', '<', ' LIKE ', '='];
            $found = false;
            foreach ($ops as $op) {
                $opLower = strtolower($op);
                $partLower = strtolower($part);
                $idx = strpos($partLower, $opLower);
                if ($idx !== false && $idx > 0) {
                    $col = trim(substr($part, 0, $idx));
                    $val = trim(substr($part, $idx + strlen($op)));
                    $col = $this->stripTablePrefix($col);
                    $val = $this->stripQuotes($val);

                    $op = trim(strtoupper($op));

                    switch ($op) {
                        case '=':
                            $filter[$col] = $val;
                            break;
                        case '!=':
                        case '<>':
                            $filter[$col] = ['$ne' => $val];
                            break;
                        case '>':
                            $filter[$col] = ['$gt' => $val];
                            break;
                        case '<':
                            $filter[$col] = ['$lt' => $val];
                            break;
                        case '>=':
                            $filter[$col] = ['$gte' => $val];
                            break;
                        case '<=':
                            $filter[$col] = ['$lte' => $val];
                            break;
                        case 'LIKE':
                            $pattern = str_replace('%', '.*', $val);
                            $pattern = str_replace('_', '.', $pattern);
                            $filter[$col] = ['$regex' => $pattern, '$options' => 'i'];
                            break;
                    }
                    $found = true;
                    break;
                }
            }
        }

        return $filter;
    }

    private function stripTablePrefix($column) {
        $dotIdx = strpos($column, '.');
        if ($dotIdx !== false) {
            return substr($column, $dotIdx + 1);
        }
        return $column;
    }

    private function stripQuotes($val) {
        $val = trim($val);
        if ((strpos($val, "'") === 0 && substr($val, -1) === "'") ||
            (strpos($val, '"') === 0 && substr($val, -1) === '"')) {
            return substr($val, 1, -1);
        }
        return $val;
    }

    private function execInsert($sql) {
        $pattern = '/^INSERT\s+INTO\s+(\w+)\s*\((.+?)\)\s*VALUES\s*(.+)$/is';
        if (!preg_match($pattern, $sql, $m)) {
            return new MongoDumbStatement([]);
        }

        $collectionName = trim($m[1]);
        $columnsStr = trim($m[2]);
        $valuesStr = trim($m[3]);

        $columns = array_map('trim', explode(',', $columnsStr));

        $valueSets = $this->parseValueSets($valuesStr);

        $collection = $this->database->selectCollection($collectionName);

        $insertedIds = [];
        foreach ($valueSets as $values) {
            $doc = [];
            foreach ($columns as $i => $col) {
                $val = $values[$i] ?? null;
                if ($val === 'NULL' || $val === 'null') {
                    $val = null;
                }
                $doc[$col] = $val;
            }
            $result = $collection->insertOne($doc);
            $insertedIds[] = (string)$result->getInsertedId();
        }

        return new MongoDumbStatement([], count($insertedIds));
    }

    private function parseValueSets($valuesStr) {
        $sets = [];
        $valuesStr = trim($valuesStr, "; \t\n\r\0\x0B");

        $currentSet = [];
        $currentVal = '';
        $inString = false;
        $stringChar = '';
        $depth = 0;

        for ($i = 0; $i < strlen($valuesStr); $i++) {
            $ch = $valuesStr[$i];

            if ($inString) {
                $currentVal .= $ch;
                if ($ch === $stringChar) {
                    if ($i + 1 < strlen($valuesStr) && $valuesStr[$i + 1] === $stringChar) {
                        $currentVal .= $valuesStr[$i + 1];
                        $i++;
                    } else {
                        $inString = false;
                    }
                }
                continue;
            }

            if ($ch === "'" || $ch === '"') {
                $inString = true;
                $stringChar = $ch;
                $currentVal .= $ch;
                continue;
            }

            if ($ch === '(') {
                if ($depth === 0) {
                    $currentSet = [];
                    $currentVal = '';
                }
                $depth++;
                continue;
            }

            if ($ch === ')') {
                $depth--;
                if ($depth === 0) {
                    $currentSet[] = $this->stripQuotes(trim($currentVal));
                    $sets[] = $currentSet;
                    $currentSet = [];
                    $currentVal = '';
                }
                continue;
            }

            if ($ch === ',' && $depth === 1) {
                $currentSet[] = $this->stripQuotes(trim($currentVal));
                $currentVal = '';
                continue;
            }

            if ($depth >= 1) {
                $currentVal .= $ch;
            }
        }

        return $sets;
    }

    private function execUpdate($sql) {
        $pattern = '/^UPDATE\s+(\w+)\s+SET\s+(.+?)(?:\s+WHERE\s+(.+))?$/is';
        if (!preg_match($pattern, $sql, $m)) {
            return new MongoDumbStatement([]);
        }

        $collectionName = trim($m[1]);
        $setStr = trim($m[2]);
        $whereClause = isset($m[3]) ? trim($m[3]) : null;

        $updateData = [];
        $parts = explode(',', $setStr);
        foreach ($parts as $part) {
            $part = trim($part);
            $eqIdx = strpos($part, '=');
            if ($eqIdx !== false) {
                $col = trim(substr($part, 0, $eqIdx));
                $val = $this->stripQuotes(trim(substr($part, $eqIdx + 1)));
                if ($val === 'NULL' || $val === 'null') {
                    $val = null;
                }
                $updateData[$col] = $val;
            }
        }

        $filter = $whereClause ? $this->parseWhere($whereClause) : [];
        $collection = $this->database->selectCollection($collectionName);
        $result = $collection->updateMany($filter, ['$set' => $updateData]);

        return new MongoDumbStatement([], $result->getModifiedCount());
    }

    private function execDelete($sql) {
        $pattern = '/^DELETE\s+FROM\s+(\w+)(?:\s+WHERE\s+(.+))?$/is';
        if (!preg_match($pattern, $sql, $m)) {
            return new MongoDumbStatement([]);
        }

        $collectionName = trim($m[1]);
        $whereClause = isset($m[2]) ? trim($m[2]) : null;

        $filter = $whereClause ? $this->parseWhere($whereClause) : [];
        $collection = $this->database->selectCollection($collectionName);
        $result = $collection->deleteMany($filter);

        return new MongoDumbStatement([], $result->getDeletedCount());
    }

    private function execCreateTable($sql) {
        if (preg_match('/CREATE TABLE (?:IF NOT EXISTS )?(\w+)/i', $sql, $m)) {
            $collectionName = $m[1];
            try {
                $this->database->createCollection($collectionName);
            } catch (Exception $e) {
            }
        }
        return new MongoDumbStatement([]);
    }

    private function execDropTable($sql) {
        if (preg_match('/DROP TABLE (?:IF EXISTS )?(\w+)/i', $sql, $m)) {
            $collectionName = $m[1];
            try {
                $this->database->dropCollection($collectionName);
            } catch (Exception $e) {
            }
        }
        return new MongoDumbStatement([]);
    }

    private function docToArray($doc) {
        $array = [];
        foreach ($doc as $key => $value) {
            if ($value instanceof MongoDB\BSON\ObjectId) {
                $array[$key] = (string)$value;
            } elseif ($value instanceof MongoDB\BSON\UTCDateTime) {
                $array[$key] = $value->toDateTime()->format('Y-m-d H:i:s');
            } else {
                $array[$key] = $value;
            }
        }
        return $array;
    }

    public function insertOne($collection, $document) {
        try {
            $coll = $this->selectCollection($collection);
            $result = $coll->insertOne($document);
            return (string)$result->getInsertedId();
        } catch (Exception $e) {
            throw new Exception("MongoDB 插入失败: " . $e->getMessage());
        }
    }

    public function insertMany($collection, $documents) {
        try {
            $coll = $this->selectCollection($collection);
            $result = $coll->insertMany($documents);
            return $result->getInsertedCount();
        } catch (Exception $e) {
            throw new Exception("MongoDB 批量插入失败: " . $e->getMessage());
        }
    }

    public function updateOne($collection, $filter, $update, $options = []) {
        try {
            $coll = $this->selectCollection($collection);
            $result = $coll->updateOne($filter, $update, $options);
            return $result->getModifiedCount();
        } catch (Exception $e) {
            throw new Exception("MongoDB 更新失败: " . $e->getMessage());
        }
    }

    public function updateMany($collection, $filter, $update, $options = []) {
        try {
            $coll = $this->selectCollection($collection);
            $result = $coll->updateMany($filter, $update, $options);
            return $result->getModifiedCount();
        } catch (Exception $e) {
            throw new Exception("MongoDB 批量更新失败: " . $e->getMessage());
        }
    }

    public function deleteOne($collection, $filter) {
        try {
            $coll = $this->selectCollection($collection);
            $result = $coll->deleteOne($filter);
            return $result->getDeletedCount();
        } catch (Exception $e) {
            throw new Exception("MongoDB 删除失败: " . $e->getMessage());
        }
    }

    public function deleteMany($collection, $filter) {
        try {
            $coll = $this->selectCollection($collection);
            $result = $coll->deleteMany($filter);
            return $result->getDeletedCount();
        } catch (Exception $e) {
            throw new Exception("MongoDB 批量删除失败: " . $e->getMessage());
        }
    }

    public function findOne($collection, $filter = [], $options = []) {
        try {
            $coll = $this->selectCollection($collection);
            $doc = $coll->findOne($filter, $options);
            if ($doc === null) return null;
            return $this->docToArray($doc);
        } catch (Exception $e) {
            throw new Exception("MongoDB 查询失败: " . $e->getMessage());
        }
    }

    public function find($collection, $filter = [], $options = []) {
        try {
            $coll = $this->selectCollection($collection);
            $cursor = $coll->find($filter, $options);
            $results = [];
            foreach ($cursor as $doc) {
                $results[] = $this->docToArray($doc);
            }
            return $results;
        } catch (Exception $e) {
            throw new Exception("MongoDB 查询失败: " . $e->getMessage());
        }
    }

    public function count($collection, $filter = []) {
        try {
            $coll = $this->selectCollection($collection);
            return $coll->countDocuments($filter);
        } catch (Exception $e) {
            throw new Exception("MongoDB 计数失败: " . $e->getMessage());
        }
    }

    public function lastInsertId() {
        return null;
    }

    public function beginTransaction() {
        return true;
    }

    public function commit() {
        return true;
    }

    public function rollback() {
        return true;
    }

    public function escape($string) {
        return $string;
    }

    public function testConnection() {
        try {
            $this->database->command(['ping' => 1]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

class MongoDumbStatement {
    private $rows;
    private $rowCount;
    private $cursor = 0;

    public function __construct($rows = [], $rowCount = 0) {
        $this->rows = $rows;
        $this->rowCount = $rowCount > 0 ? $rowCount : count($rows);
    }

    public function getRows() {
        return $this->rows;
    }

    public function fetch() {
        if ($this->cursor >= count($this->rows)) {
            return false;
        }
        return $this->rows[$this->cursor++];
    }

    public function fetchAll() {
        return $this->rows;
    }

    public function rowCount() {
        return $this->rowCount;
    }
}
