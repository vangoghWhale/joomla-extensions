<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;

defined('_JEXEC') or die ('Restricted access');

class plgSystemMegafilter_export_3rd extends CMSPlugin{

    var $pluginName = 'jamegafilterExport3rd';

    public function __construct($subject, $params)
    {
        parent::__construct($subject, $params);
    }

    public function onAfterDispatch(){
        $app = Factory::getApplication();
        $input = $app->input;
        $action = $input->get('action');

        require_once __DIR__ . '/mgfilter_eshop.php';

        switch ($action){
            case 'jamgfilter_export_data':
                mgfilterEshop::exportToSql('#__eshop_products');
                break;
            case 'jamgfilter_import_eshop':
                mgfilterEshop::importEshopData();
                break;
            default:
                break;
        }
    }

    // trigger when saving
    public function onExtensionBeforeSave($context, $tbl, $is_new){
        // todo
    }

    public function onAfterInitialise(){
        // todo
    }

    public function onAfterRoute(){
        // todo
    }

    public function onContentPrepareForm($form, $data){
        // todo
    }

}

?>