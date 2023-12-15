<?php 


defined('_JEXEC') or die('Restricted access');


use Joomla\CMS\Router\Route;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Plugin\PluginHelper;


class JFormFieldJamgfilter extends FormField{
    public $type = 'jamgfilter';

    public function getInput(){
        $plugin = PluginHelper::getPlugin('system', 'megafilter_export_3rd');
        $pluginId = $plugin->id;
        $html = '';
        $html .= '<ul class="export-dropdown-menu">';
        $html .= '<li><a href="'.Route::_('index.php?option=com_plugins&view=plugin&layout=edit&extension_id='.$pluginId.'&action=jamgfilter_load_data').'">Load all data</a></li>';
        $html .= '<li><a href="'.Route::_('index.php?option=com_plugins&view=plugin&layout=edit&extension_id='.$pluginId.'&action=jamgfilter_export_data').'">Start Exporting Eshop data</a></li>';
        $html .= '<li><a href="'.Route::_('index.php?option=com_plugins&view=plugin&layout=edit&extension_id='.$pluginId.'&action=jamgfilter_import_eshop').'">Importing Eshop data</a></li>';
        /* $html .= '<li><a href="'.Route::_('index.php?option=com_plugins&view=plugin&layout=edit&extension_id='.$pluginId.'&action=jlexcleartrash').'">Clear all jlex trash data</a></li>';
        $html .= '<li><a href="'.Route::_('index.php?option=com_plugins&view=plugin&layout=edit&extension_id='.$pluginId.'&action=jlexcleartrash_no_assoc').'">1st install, Clear all jlex trash data no assoc with #__associations</a></li>'; */
        $html .= '</ul>';
        return $html;
    }
}

?>