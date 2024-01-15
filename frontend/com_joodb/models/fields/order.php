<?php
/**
 * @file order.php created 19.04.2012, 17:34:19
 * @package		Joodb
 * @author	feenders - dirk hoeschen (hoeschen@feenders.de)
 * @abstract	custom component for client
 * @link	http://www.feenders.de
 * @copyright	Copyright (C) 2015 computer daten netze :: feenders
 * @license		CC-GNU-LGPL
 * @version  1.0
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

/**
 * Generates a select List with all fields of a joodb table
 */

class JFormFieldOrder extends JFormField
{
    /**
     * The name of the element.
     */
    protected $type = 'Order';

    protected function getInput()
    {
        $options = array("ftitle", "fdate","ftitle","fid","random");
        $labels = array("JGLOBAL_USE_GLOBAL","JGLOBAL_MOST_RECENT_FIRST","JGLOBAL_TITLE_ALPHABETICAL","JFIELD_ORDERING_LABEL","RANDOM");
        if (empty($this->value)) $this->value = array();
        $html = '<select id="jform_params_orderby" class="form-select" name="jform[params][orderby]"';
        $match = false;
        foreach ($options AS $n => $val) {
            $html .= '<option value="'.$val.'"';
            if ($this->value==$val) {
                $html .= ' selected="selected" ';
                $match = true;
            }
            $html .= '>'.JText::_($labels[$n]).'</option>';
        }
        if (!$match) {
            $html .= '<option selected="selected">'.$this->value.'</option>';
        }
        $html .= '<option value="">'.JText::_('ORDER_BY_CUSTOM_FIELD').'</option>';
        $html .= '</select>';
        $html .= '<input id="jform_params_orderby_custom" type="text" name="jform[params][orderby]"  class="form-control" value="" placeholder="'.JText::_('FIELD_NAME').'" disabled="disabled" style="display: none" />';
        $doc = JFactory::getDocument();
        if (!is_file(JPATH_LIBRARIES . '/cms/html/jquery.php')) {
            $doc->addScript(JUri::root(true) . '/media/com_joodb/js/jquery.min.js');
            $doc->addScriptDeclaration('jQuery.noConflict();');
        } else {
            JHtml::_('jquery.framework');
        }
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


