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
        foreach($products as $k => $row){
            $insertQuery .= "INSERT INTO `#__eshop_products` ($quoteColumns)";
            $values = " VALUES (";
            foreach($row as $column => $value){
                $values .= $db->quote($value) . ',';
            }
            $values .= ')';
            $insertQuery .= $values;
            $insertQuery .= ';';
            fwrite($file, $insertQuery);
        }
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