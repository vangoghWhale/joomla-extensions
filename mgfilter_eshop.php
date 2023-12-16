<?php 

defined('_JEXEC') or die ('Restricted access');


use Joomla\CMS\Factory;


class mgfilterEshop{

    public static $cacheFolder = JPATH_ROOT . '/plugins/system/megafilter_export_3rd/cache/';
    
    public function __construct()
    {
        // todo
    }

    public static function exportToSql($tblName){
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*')->from($tblName);
        $db->setQuery($query);
        $products = $db->loadAssocList();

        $es = ['product' => JPATH_ROOT . '/cache/' . str_replace('#__', '', $tblName) . '.sql'];
        $file = fopen($es['product'], 'w');

        $quoteColumns = self::getColumnNames($tblName);

        $nullDate = $db->getNullDate();

        $insertQuery = '';
        $insertQuery .= "INSERT INTO `#__eshop_products` ($quoteColumns)";
        $v1 = '';
        foreach($products as $k => $row){
            $value1 = '( ';
            foreach($row as $column => $value){
                // $value1 .= $db->quote($value) . ',';
                if (!empty($value)){
                    $value1 .= $db->quote($value) . ',';
                }else{
                    switch ($column) {
                        case 'product_threshold_notify':
                            $value1 .= 0;
                            break;
                        default:
                            $value1 .= 'NULL,';
                            break;
                    }
                }
            }
            $value1 = substr($value1, 0, -1);
            $value1 .= ' )';
            $v1 .= $value1 . ', ';
        }
        $v1 = substr($v1, 0, -2);
        $insertQuery .= " VALUES " . $v1;
        $insertQuery .= ';';
        fwrite($file, $insertQuery);
        fclose($file);
    }

    /**
     * get all tables had data, compare these tables with origin table of source code
     * then skip all tables existed in origin source had data
     * then save it into each table.json file
     * 
     * @param string $name
     *
     * @return void
     */
    public static function loadData($name){
        $tables = self::getAllTables($name);
        $originTables = self::loadEshopOriginTables();

        $db = Factory::getDbo();
        foreach($tables as $table){
            $query = $db->getQuery(true);
            $query->select('*')
                ->from($table);
            $db->setQuery($query);
            $data = $db->loadAssocList();
            
            if (empty($data)) continue;
            
            $table = str_replace(self::getDbPrefix(), '', $table);
            
            if (in_array($table, $originTables)) continue;

            self::writeToFile($data, self::$cacheFolder . $table . '.json');
        }
    }

    /**
     * get all eshop tables already had table when first installing
     * 
     * @return array
     */
    public static function loadEshopOriginTables(){
        $path = self::$cacheFolder . 'eshop_origin_tbls.json';
        if (is_file($path)){
            return json_decode(file_get_contents($path));
        }
        return [];
    }

    /**
     * get all table with a specific table name prefx
     * 
     * @param string $name
     * 
     * @return array $tblsNeed was re-index
     */
    public static function getAllTables($name){
        $db = Factory::getDbo();
        $query = "SHOW TABLES";
        $db->setQuery($query);
        $allTables = $db->loadColumn();
        $tblsNeed = array_map(function ($tbl) use ($name){
            if (strpos($tbl, $name) !== false){
                return $tbl;
            }
        }, $allTables);
        $tblsNeed = array_values(array_filter($tblsNeed));
        return $tblsNeed;
    }

    /**
     * execute mysql query in each sql file
     * 
     * @return void
     */
    public static function importEshopData($filePrefix=null){
        if (empty($filePrefix)) return ;

        // currently load 1 file to test
        $rootPath = JPATH_ROOT . '/cache/';
        $files = scandir(self::$cacheFolder);
        $files = array_diff($files, ['.', '..']);
        $files1 = array_map(function ($file){
            $filePrefix = 'eshop';
            if (strpos($file, '.json') !== false && strpos($file, $filePrefix) !== false){
                return $file;
            }
        }, $files);

        // remove a file: eshop origin tables
        $searchString = 'eshop_origin_tbls.json';
        $idx = array_search($searchString, $files1);
        if ($idx !== false) {
            unset($files1[$idx]);
        }
        // end


        $db = Factory::getDbo();
        foreach($files1 as $k => $file){
            if (empty($file)) continue;
            $tbl = self::getDbPrefix() . str_replace('.json', '', $file);
            // $columns = self::getColumnNames($tbl);

            $data_ = json_decode(file_get_contents(self::$cacheFolder . $file));
            $data_ = self::prepareData($tbl, $data_);
            $data = $data_['data'];
            $columns = $data_['column_names'];
            $query = "INSERT INTO `$tbl` ($columns) VALUES $data";
            // echo '<pre>';print_r($query);echo '</pre>';
            try{
                $db->setQuery($query);
                $db->execute();
            }catch (RuntimeException $e){
                echo '<pre>';print_r([$file, $query]);echo '</pre>';
                echo '<pre>'. print_r($e, true) .'</pre>';die();
            }
        }

        return 1;
    }

    public static function prepareData($tbl, $data){
        $table = $tbl;
        $tblStructure = self::getTableStructure($table);
        $allNotNullCols = self::getNotNullColumns($tblStructure);
        $tblColumns = self::getColumnNames($table, true);
        $oldColumns = array_keys((array) $data[0]);
        $diffColumns = array_diff($tblColumns, $oldColumns);
        // $diffColumns = array_merge($diffColumns, ['product_call_for_price', 'product_threshold_notify']);
        $dataMiss = [];
        $db = Factory::getDbo();
        foreach($tblStructure as $k => $val){
            if (in_array($val[0], $diffColumns)){
                $dataMiss[$val[0]] = $db->quote($val[4]);
            }
        }
        /**
         * 0: field name
         * 1: type
         * 2: Null value YES|NO
         * 3: key PRI|MUL
         * 4: default value
         * 5: extra auto_increment
         */
        $db = Factory::getDbo();
        $newData = [];
        $strData = '';
        foreach($data as $k => $val){
            foreach($val as $col => $v){
                if (in_array($col, $tblColumns)){
                    if (empty($v)){
                        if (isset($allNotNullCols[$col])){
                            $newData[$k][$col] = $allNotNullCols[$col];
                        }else{
                            $newData[$k][$col] = 'NULL';
                        }
                    }else{
                        if ($col === 'id'){
                            $newData[$k][$col] = 'NULL';
                        }else{
                            $newData[$k][$col] = $db->quote($v);
                        }
                    }
                }
            }
            $newData[$k] = array_merge($newData[$k], $dataMiss);
            $strData .= '('. implode(',', array_merge($newData[$k], $dataMiss)) . '),';
        }
        
        $strData = substr($strData, 0, -1);
        $quoteColumns = array_map(function ($col) use ($db){
            return $db->quoteName($col);
        }, array_keys($newData[0]));

        return ['data' => $strData, 'column_names' => implode(',', $quoteColumns)];
    }

    public static function getNotNullColumns($data){
        if (empty($data)) return ;
        $db = Factory::getDbo();
        $cols = [];
        foreach($data as $val){
            if (!isset($val[4])) continue;
            $cols[$val[0]] = is_string($val[4]) ? $db->quote($val[4]) : $val[4];
        }

        return $cols;
    }

    /**
     * get all column names of a table
     * 
     * @param string $tblName prefix_tblName
     * 
     * @return string a string of all tables
     */
    public static function getColumnNames($tblName, $raw=false){
        $db = Factory::getDbo();
        $query = "SHOW COLUMNS FROM `$tblName`";
        $db->setQuery($query);
        $es_product_columns_name = $db->loadColumn();
        
        if (empty($es_product_columns_name)) return '';

        if ($raw) return $es_product_columns_name;

        $quoteColumns = array_map(function ($item){
            return '`' . $item .'`';
        }, $es_product_columns_name);

        return implode(',', $quoteColumns);
    }

    /**
     * get structure of a table
     * return:
     * 0: field name
     * 1: type
     * 2: Null value YES|NO
     * 3: key PRI|MUL
     * 4: default value
     * 5: extra auto_increment
     * 
     * @param string $tblName a table name
     * 
     * @return array
     */
    public static function getTableStructure($tblName){
        $db = Factory::getDbo();
        $query = "SHOW COLUMNS FROM `$tblName`";
        $db->setQuery($query);
        return $db->loadRowList();
    }

    /**
     * get db prefix
     * 
     * @return string
     */
    public static function getDbPrefix(){
        $db = Factory::getDbo();
        return $db->getPrefix();
    }

    /**
	 * write data into json file | write only if file not exsit
     * 
     * @param array $data
     * @param string $path_to_file
	 * 
	 * @return void
	 */
    public static function writeToFile($data, $path_to_file, $append=true){
        if (!$append){
            file_put_contents($path_to_file, json_encode($data).PHP_EOL);
            return ;
        }
        if(!is_file($path_to_file)){
            file_put_contents($path_to_file, json_encode($data).PHP_EOL, FILE_APPEND | LOCK_EX);
            return ;
        }
    }
}

?>