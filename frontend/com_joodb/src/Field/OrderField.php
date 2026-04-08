<?php
/**
 * @version     5.0.0
 * @package     com_joodb
 * @copyright   Copyright (C) 2011-2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Dirk Hoeschen - Feenders <hoeschen@feenders.de> - http://www.feenders.de
 */

namespace Feenders\Component\Joodb\Site\Field;

defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

/**
 * Generates a select List with all fields of a joodb table
 */
class OrderField extends FormField
{
    /**
     * The name of the element.
     */
    protected $type = 'Order';

    protected function getInput()
    {
		$app = Factory::getApplication();
	    $doc = $app->getDocument();
        $options = array("ftitle", "fdate","ftitle","fid","random");
        $labels = array("JGLOBAL_USE_GLOBAL","JGLOBAL_MOST_RECENT_FIRST","JGLOBAL_TITLE_ALPHABETICAL","JFIELD_ORDERING_LABEL","JDB_RANDOM");
        if (empty($this->value)) $this->value = array();
        $html = '<select id="jform_params_orderby" class="form-select" name="jform[params][orderby]"';
        $match = false;
        foreach ($options AS $n => $val) {
            $html .= '<option value="'.$val.'"';
            if ($this->value==$val) {
                $html .= ' selected="selected" ';
                $match = true;
            }
            $html .= '>'.Text::_($labels[$n]).'</option>';
        }
        if (!$match) {
            $html .= '<option selected="selected">'.$this->value.'</option>';
        }
        $html .= '<option value="">'.Text::_("JDB_ORDER_BY_CUSTOM_FIELD").'</option>';
        $html .= '</select>';
        $html .= '<input id="jform_params_orderby_custom" type="text" name="jform[params][orderby]"  class="form-control" value="" placeholder="'.Text::_("JDB_FIELD_NAME").'" disabled="disabled" style="display: none" />';
	    HTMLHelper::_('jquery.framework');
        $doc->addScriptDeclaration("
        (function ($) {
        $(document).ready(function() {
            $('#jform_params_orderby_chzn').remove();
            $('#jform_params_orderby').show();
            $('#jform_params_orderby').change(function(){
                if ($(this).val()=='') {
                    $(this).hide().prop('disabled', true );
                    $('#jform_params_orderby_custom').show().prop('disabled', false ).focus();
                }
             });
            $('#jform_params_orderby_custom').keyup(function(e) {
                if (e.keyCode == 27) {
                    $(this).hide().prop('disabled', true );
                    $('#jform_params_orderby').show().prop('disabled', false ).prop('selectedIndex',0).focus();
                }
             });
        })
        })(jQuery);
        ");
        return $html;

    }
}


