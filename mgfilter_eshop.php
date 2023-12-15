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
            
            $table = str_replace(self::dbPrefix(), '', $table);
            
            if (in_array($table, $originTables)) continue;

            self::writeToFile($data, self::$cacheFolder . $table . '.json', false);
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
        $files = scandir($rootPath);
        $files = array_diff($files, ['.', '..']);
        $files1 = array_map(function ($file){
            $filePrefix = 'eshop';
            if (strpos($file, '.sql') !== false && strpos($file, $filePrefix) !== false){
                return $file;
            }
        }, $files);

        $db = Factory::getDbo();
        foreach($files1 as $k => $file){
            if (empty($file)) continue;
            $strQuery = file_get_contents($rootPath . $file);
            $db->setQuery($strQuery);
            if ($db->execute()){
                die('success');
            }
        }

        return 1;
    }

    /**
     * get all column names of a table
     * 
     * @param string $tblName
     * 
     * @return string a string of all tables
     */
    public static function getColumnNames($tblName){
        $db = Factory::getDbo();
        $query = "SHOW COLUMNS FROM `$tblName`";
        $db->setQuery($query);
        $es_product_columns_name = $db->loadColumn();
        
        if (empty($es_product_columns_name)) return '';

        $quoteColumns = array_map(function ($item){
            return '`' . $item .'`';
        }, $es_product_columns_name);

        return implode(',', $quoteColumns);
    }

    /**
     * get structure of a table
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
    public static function dbPrefix(){
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