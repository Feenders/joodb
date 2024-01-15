<?php

// no direct access
defined('_JEXEC') or die('Restricted access');


use Joomla\CMS\Button\TransitionButton;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;

$params = &$this->params;
$item = &$this->item;
$fields = &$this->fields;
$app = JFactory::getApplication();

// 	Load the JEditor object
if ($this->config->get('internal_editor', 1) == 0) {
	$editor = new JEditor($app->get('editor'));
} else {
	require_once(JPATH_ROOT . '/media/com_joodb/editor.php');
	$editor = new JDBEditor();
}

JHtml::_('jquery.framework');
JHTML::_('behavior.formvalidator');
JHtml::_('behavior.keepalive');
JHtml::_('bootstrap.tooltip', '.hasTooltip');
JHtml::_('behavior.multiselect');

JFactory::getApplication()->getDocument()->getWebAssetManager()
	->usePreset('choicesjs')
	->useScript('webcomponent.field-fancy-select');

?>
<form action="index.php" method="post" name="adminForm" id="adminForm" class="form-validate">
    <input type="hidden" name="option" value="com_joodb"/>
    <input type="hidden" name="task" value=""/>
    <input type="hidden" name="view" value="joodb"/>
    <input type="hidden" name="id" value="<?php echo $item->id; ?>"/>
    <input type="hidden" name="published" value="<?php echo $item->published; ?>"/>
	<?php echo JHtml::_('form.token'); ?>
    <div class="row">
        <div id="config-document" class="col-lg-9 mb-3">
            <fieldset class="adminform">
                <h3 class="mb-3"><?php echo JText::_('Database'); ?></h3>
				<?php
				echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'config-general'));
				echo JHtml::_('bootstrap.addTab', 'myTab', 'config-general', JText::_('General options'));
				?>
                <div class="card p-3">
                    <table class="paramlist admintable">
                        <tr>
                            <td style="width:250px" class="paramlist_key"><?php echo JText::_('Database Name'); ?>:</td>
                            <td class="paramlist_value">
                                <input class="form-select required" type="text" name="name"
                                       value='<?php echo str_replace("\'", "\"", $item->name); ?>' maxlength="50" size="50" />
                            </td>
                        </tr>
                        <tr>
                            <td style="width:250px" class="paramlist_key"><?php echo JText::_('Table'); ?>:</td>
                            <td class="paramlist_value">
                                <select name="table" class="form-select  required" onchange="Joomla.submitbutton('apply');" ><?php
									foreach ($this->tables as $table) {
										echo "<option" . (($table == $item->table) ? " selected" : "") . ">" . $table . "</option>";
									}
									?></select>
                            </td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td class="paramlist_value"><br/><b><?php echo JText::_("Special fields"); ?></b></td>
                        </tr>
                        <tr>
                            <td style="width:250px" class="paramlist_key"><?php echo JText::_("Primary Index"); ?>:</td>
                            <td class="paramlist_value">
								<?php
								$fselect = JoodbAdminHelper::selectFieldTypes("primary", $fields);
								echo '<select name="fid"  class="form-select" >';
								foreach ($fselect as $fname) {
									echo "<option" . (($fname == $item->fid) ? " selected" : "") . ">" . $fname . "</option>";
								}
								echo "</select>";
								if (count($fselect) < 1)
									echo '<p style="color: #d40000; font-weight: bold; clear:both;">' . JText::_("No Primary Index") . '</p>';
								?>
                            </td>
                        </tr>
                        <tr>
                            <td style="width:250px" class="paramlist_key"><?php echo JText::_("Title or Headline") ?>:</td>
                            <td class="paramlist_value">
                                <select name="ftitle" class="form-select"><?php
									$fselect = JoodbAdminHelper::selectFieldTypes("shorttext", $fields);
									foreach ($fselect as $fname) {
										echo "<option" . (($fname == $item->ftitle) ? " selected" : "") . ">" . $fname . "</option>";
									}
									?></select>
                            </td>
                        </tr>
                        <tr>
                            <td style="width:250px" class="paramlist_key"><?php echo JText::_("Main Content"); ?>:</td>
                            <td class="paramlist_value">
                                <select name="fcontent" class="form-select"><?php
									foreach ($fselect as $fname) {
										echo "<option" . (($fname == $item->fcontent) ? " selected" : "") . ">" . $fname . "</option>";
									}
									?>    </select>
                            </td>
                        </tr>
                        <tr>
                            <td style="width:250px" class="paramlist_key"><?php echo JText::_("Abstract"); ?>:</td>
                            <td class="paramlist_value">
                                <select name="fabstract" class="form-select">
                                    <option value="">...</option><?php
									foreach ($fselect as $fname) {
										echo "<option" . (($fname == $item->fabstract) ? " selected" : "") . ">" . $fname . "</option>";
									}
									?>    </select>
                            </td>
                        </tr>
                        <tr>
                            <td style="width:250px" class="paramlist_key"><?php echo JText::_("ALIAS_FIELD"); ?>:</td>
                            <td class="paramlist_value">
                                <select name="falias" class="form-select">
                                    <option value="">...</option><?php
									foreach ($fselect as $fname) {
										echo "<option" . (($fname == $item->getSubdata('falias')) ? " selected" : "") . ">" . $fname . "</option>";
									}
									?></select>
                            </td>
                        </tr>
                        <tr>
                            <td style="width:250px" class="paramlist_key"><?php echo JText::_("Main Date"); ?>:</td>
                            <td class="paramlist_value">
                                <select name="fdate" class="form-select">
                                    <option value="">...</option><?php
									$fselect = JoodbAdminHelper::selectFieldTypes("date", $fields);
									foreach ($fselect as $fname) {
										echo "<option" . (($fname == $item->fdate) ? " selected" : "") . ">" . $fname . "</option>";
									}
									?>    </select>
                            </td>
                        </tr>
                        <tr>
                            <td style="width:250px" class="paramlist_key"><?php echo JText::_("Status Field"); ?>:</td>
                            <td class="paramlist_value">
                                <select name="fstate" class="form-select">
                                    <option value="">...</option><?php
									$fselect = JoodbAdminHelper::selectFieldTypes("number", $fields);
									foreach ($fselect as $fname) {
										echo "<option" . (($fname == $item->fstate) ? " selected" : "") . ">" . $fname . "</option>";
									}
									?>    </select>
                            </td>
                        </tr>
                        <tr>
                            <td style="width:250px" class="paramlist_key"><?php echo JText::_("User ID Field"); ?>:</td>
                            <td class="paramlist_value">
                                <select name="fuser" class="form-select">
                                    <option value="">...</option><?php
									$fselect = JoodbAdminHelper::selectFieldTypes("number", $fields);
									foreach ($fselect as $fname) {
										echo "<option" . (($fname == $item->getSubdata('fuser')) ? " selected" : "") . ">" . $fname . "</option>";
									}
									?>    </select>
                            </td>
                        </tr>
                    </table>
                </div>
				<?php
				echo JHtml::_('bootstrap.endTab');
				echo JHtml::_('bootstrap.addTab', 'myTab', 'config-cattmpl', JText::_('Catalog template'));
				?>
                <table class="paramlist admintable">
                    <tr>
                        <td class="paramlist_value">
							<?php
							echo $editor->display('tpl_list', stripslashes($item->tpl_list), '95%', '500', '40', '6', false);
							JoodbAdminHelper::printTemplateFooter('tpl_list', $fields, 'catalog');
							?>
                        </td>
                    </tr>
                </table>
				<?php
				echo JHtml::_('bootstrap.endTab');
				echo JHtml::_('bootstrap.addTab', 'myTab', 'config-sngltmpl', JText::_('Singleview template'));
				?>
                <table class="paramlist admintable">
                    <tr>
                        <td class="paramlist_value">
							<?php
							echo $editor->display('tpl_single', stripslashes($item->tpl_single), '95%', '500', '40', '6', false);
							JoodbAdminHelper::printTemplateFooter('tpl_single', $fields, 'single');
							?>
                        </td>
                    </tr>
                </table>
				<?php
				echo JHtml::_('bootstrap.endTab');
				echo JHtml::_('bootstrap.addTab', 'myTab', 'config-prnttmpl', JText::_('Print template'));
				?>
                <table class="paramlist admintable">
                    <tr>
                        <td class="paramlist_value">
							<?php // 	Load the JEditor object
							echo $editor->display('tpl_print', stripslashes($item->tpl_print), '95%', '500', '40', '6', false);
							JoodbAdminHelper::printTemplateFooter('tpl_print', $fields, 'print');
							?>
                        </td>
                    </tr>
                </table>
				<?php
				echo JHtml::_('bootstrap.endTab');
				echo JHtml::_('bootstrap.addTab', 'myTab', 'config-frmtmpl', JText::_('Form template'));
				?>
                <table class="paramlist admintable">
                    <tr>
                        <td class="paramlist_value">
							<?php // 	Load the JEditor object
							echo $editor->display('tpl_form', stripslashes($item->tpl_form), '95%', '500', '40', '6', false);
							JoodbAdminHelper::printTemplateFooter('tpl_form', $fields, 'form');
							?>
                        </td>
                    </tr>
                </table>
				<?php
				echo JHtml::_('bootstrap.endTab');
				echo JHtml::_('bootstrap.addTab', 'myTab', 'config-subtmpl', JText::_('Linked Tables'));
				?>
                <div class="card p-3">
                    <div style="padding: 10px;">
                        <button type="button" class="button btn btn-success" onclick="openSubtemplate('');"><i
                                    class="icon icon-plus"></i> <?php echo JText::_('Add linked Table'); ?></button>
                    </div>
                    <table class="adminlist table table-striped">
                        <thead>
                        <tr>
                            <th><?php echo JText::_('Existing Links'); ?></th>
                            <th style="width: 10%;"><?php echo JText::_('Remove Link'); ?></th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <td colspan="2">&nbsp;</td>
                        </tr>
                        </tfoot>
                        <tbody id="subitems">
                        <tr>
                            <td colspan="2">...</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
				<?php
				echo JHtml::_('bootstrap.endTab');
				echo JHtml::_('bootstrap.endTabSet');
				?>
            </fieldset>
        </div>
        <div class="col-lg-3">
            <fieldset class="adminform">
                <h3 class="mb-3"><?php echo JText::_('Parameters'); ?></h3>
				<?php
				echo JHtml::_('bootstrap.startAccordion', 'menu-accordion', array('useCookie' => 1));
				$fieldSets = $params->getFieldsets();
				foreach ($fieldSets as $name => $fieldSet) :
					echo JHtml::_('bootstrap.addSlide', 'menu-accordion', JText::_($fieldSet->description), $name);
					echo '<div class="block">';
					foreach ($params->getFieldset($name) as $field):
						echo '<div class="control-group">';
						echo '<div class="control-label">' . $field->label . '</div>';
						echo '<div>' . $field->input . '</div>';
						echo '</div>';
					endforeach;
					echo '</div>';
					echo JHtml::_('bootstrap.endSlide');
				endforeach;
				echo JHtml::_('bootstrap.endAccordion');
				?>
            </fieldset>
        </div>
    </div>
</form>
<script type="text/javascript">

    var itemid = '<?php echo $item->id ?>'

    /* Send the Form */
    Joomla.submitbutton = function (task) {
        var frm = document.adminForm;
        if (task == 'cancel') {
            Joomla.submitform(task, frm);
            return true;
        }

        // do field validation
        if (frm.name.value == "") {
            alert('<?php echo JText::_( "Name Your DB" ); ?>');
            frm.title.focus();
            return false;
        } else {
            if ((frm.table.value == "") && (!document.formvalidator.isValid(frm))) return false;
            // Tinymce wont because the Joomla Developers disabled autosave.
            if (typeof tinyMCE != "undefined") window.onbeforeunload = function() {};
            if (window.Joomla) {
                Joomla.submitform(task, frm);
            } else {
                frm.submit();
            }
        }
        return false;
    }

    /**
     * Calculate center for PopUp Window
     */
    function centerPopup(width, height) {
        var padding = (navigator.appName == "Microsoft Internet Explorer") ? (padding = 10) : (padding = 0);
        var screenw = screen.availWidth;
        var screenh = screen.availHeight;
        var winw = (width + 15 + padding);
        var winh = (height + 15 + padding);
        var posx = (screenw / 2) - (winw / 2);
        var posy = (screenh / 2) - (winh / 2);
        return ",top=" + posy + ",left=" + posx + ",width=" + winw + ",height=" + winh;
    }

    /**
     * Open Subtemplate popup
     */
    function openSubtemplate(id) {
        pRC = window.open("index.php?option=com_joodb&view=subitem&tmpl=component&jbid=" + itemid + "&id=" + id, "Subtemplate", "Toolbar=0,location=1,directories=0,status=0,menubar=0,scrollbars=1,resizable=1,dependent=1" + centerPopup(960, 680));
        if (pRC)
            if (pRC.opener == null)
                pRC.opener = self;
    }

    /**
     * Load sublinks table
     */
    function refreshSubitems(id) {
        jQuery("#subitems").load("index.php?option=com_joodb&task=subitems.getList&format=xml", {'jbid': itemid, 'id': id}, function () { });
    }

    /**
     * Remove Subitem
     */
    function rmSubitem(id) {
        if (confirm("<?php echo JText::_('Really Delete') ?>")) {
            jQuery("#subitems").load("index.php?option=com_joodb&task=subitems.removeLine&format=xml", {'jbid': itemid, 'id': id}, function () { });
        }
    }


    (function() {
        refreshSubitems(false);
    })();

</script>
