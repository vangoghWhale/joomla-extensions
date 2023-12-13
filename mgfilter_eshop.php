<?php 

defined('_JEXEC') or die ('Restricted access');


use Joomla\CMS\Factory;


class mgfilterEshop{
    
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
                    // $value1 .= $value . ',';
                    $value1 .= $db->quote($value) . ', ';
                }else{
                    $value1 .= 'NULL, ';
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
     * 
     * 
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

    public static function importEshopData(){
        return 1;
    }
}

?>