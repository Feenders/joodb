<?php

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Editor\Editor;
use Feenders\Component\Joodb\Administrator\Helper\JoodbAdminHelper;

$params = &$this->params;
$item = &$this->item;
$fields = &$this->fields;
$app = Factory::getApplication();

// 	Load the JEditor object
if ($this->config->get('internal_editor', 1) == 0) {
	$editor = new Editor($app->get('editor'));
} else {
	require_once(JPATH_ROOT . '/media/com_joodb/editor.php');
	$editor = new JDBEditor();
}

HtmlHelper::_('jquery.framework');
HtmlHelper::_('behavior.formvalidator');
HtmlHelper::_('behavior.keepalive');
HtmlHelper::_('bootstrap.tooltip', '.hasTooltip');
HtmlHelper::_('behavior.multiselect');

Factory::getApplication()->getDocument()->getWebAssetManager()
	->usePreset('choicesjs')
	->useScript('webcomponent.field-fancy-select');

?>
<form action="<?php echo Route::_('index.php?option=com_joodb&view=joodb'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
    <input type="hidden" name="option" value="com_joodb"/>
    <input type="hidden" name="task" value=""/>
    <input type="hidden" name="view" value="joodb"/>
    <input type="hidden" name="id" value="<?php echo $item->id; ?>"/>
    <input type="hidden" name="published" value="<?php echo $item->published; ?>"/>
	<?php echo HtmlHelper::_('form.token'); ?>
    <div class="row">
        <div id="config-document" class="col-lg-9 mb-3">
            <fieldset class="adminform">
                <h3 class="mb-3"><?php echo Text::_("JDB_DATABASE"); ?></h3>
				<?php
				echo HtmlHelper::_('bootstrap.startTabSet', 'myTab', array('active' => 'config-general'));
				echo HtmlHelper::_('bootstrap.addTab', 'myTab', 'config-general', Text::_("JDB_GENERAL_OPTIONS"));
				?>
                <div class="card p-3">
                    <table class="paramlist admintable">
                        <tr>
                            <td style="width:250px" class="paramlist_key"><?php echo Text::_("JDB_DATABASE_NAME"); ?>:</td>
                            <td class="paramlist_value">
                                <input class="form-select required" type="text" name="name"
                                       value='<?php echo str_replace("\'", "\"", $item->name); ?>' maxlength="50" size="50" />
                            </td>
                        </tr>
                        <tr>
                            <td style="width:250px" class="paramlist_key"><?php echo Text::_("JDB_TABLE"); ?>:</td>
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
                            <td class="paramlist_value"><br/><b><?php echo Text::_("JDB_SPECIAL_FIELDS"); ?></b></td>
                        </tr>
                        <tr>
                            <td style="width:250px" class="paramlist_key"><?php echo Text::_("JDB_PRIMARY_INDEX"); ?>:</td>
                            <td class="paramlist_value">
								<?php
								$fselect = JoodbAdminHelper::selectFieldTypes("primary", $fields);
								echo '<select name="fid"  class="form-select" >';
								foreach ($fselect as $fname) {
									echo "<option" . (($fname == $item->fid) ? " selected" : "") . ">" . $fname . "</option>";
								}
								echo "</select>";
								if (count($fselect) < 1)
									echo '<p style="color: #d40000; font-weight: bold; clear:both;">' . Text::_("JDB_NO_PRIMARY_INDEX") . '</p>';
								?>
                            </td>
                        </tr>
                        <tr>
                            <td style="width:250px" class="paramlist_key"><?php echo Text::_("JDB_TITLE_OR_HEADLINE") ?>:</td>
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
                            <td style="width:250px" class="paramlist_key"><?php echo Text::_("JDB_MAIN_CONTENT"); ?>:</td>
                            <td class="paramlist_value">
                                <select name="fcontent" class="form-select"><?php
									foreach ($fselect as $fname) {
										echo "<option" . (($fname == $item->fcontent) ? " selected" : "") . ">" . $fname . "</option>";
									}
									?>    </select>
                            </td>
                        </tr>
                        <tr>
                            <td style="width:250px" class="paramlist_key"><?php echo Text::_("JDB_ABSTRACT"); ?>:</td>
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
                            <td style="width:250px" class="paramlist_key"><?php echo Text::_("JDB_ALIAS_FIELD"); ?>:</td>
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
                            <td style="width:250px" class="paramlist_key"><?php echo Text::_("JDB_MAIN_DATE"); ?>:</td>
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
                            <td style="width:250px" class="paramlist_key"><?php echo Text::_("JDB_STATUS_FIELD"); ?>:</td>
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
                            <td style="width:250px" class="paramlist_key"><?php echo Text::_("JDB_USER_ID_FIELD"); ?>:</td>
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
				echo HtmlHelper::_('bootstrap.endTab');
				echo HtmlHelper::_('bootstrap.addTab', 'myTab', 'config-cattmpl', Text::_("JDB_CATALOG_TEMPLATE"));
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
				echo HtmlHelper::_('bootstrap.endTab');
				echo HtmlHelper::_('bootstrap.addTab', 'myTab', 'config-sngltmpl', Text::_("JDB_SINGLEVIEW_TEMPLATE"));
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
				echo HtmlHelper::_('bootstrap.endTab');
				echo HtmlHelper::_('bootstrap.addTab', 'myTab', 'config-prnttmpl', Text::_("JDB_PRINT_TEMPLATE"));
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
				echo HtmlHelper::_('bootstrap.endTab');
				echo HtmlHelper::_('bootstrap.addTab', 'myTab', 'config-frmtmpl', Text::_("JDB_FORM_TEMPLATE"));
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
				echo HtmlHelper::_('bootstrap.endTab');
				echo HtmlHelper::_('bootstrap.addTab', 'myTab', 'config-subtmpl', Text::_("JDB_LINKED_TABLES"));
				?>
                <div class="card p-3">
                    <div style="padding: 10px;">
                        <button type="button" class="button btn btn-success" onclick="openSubtemplate('');"><i
                                    class="icon icon-plus"></i> <?php echo Text::_("JDB_ADD_LINKED_TABLE"); ?></button>
                    </div>
                    <table class="adminlist table table-striped">
                        <thead>
                        <tr>
                            <th><?php echo Text::_("JDB_EXISTING_LINKS"); ?></th>
                            <th style="width: 10%;"><?php echo Text::_("JDB_REMOVE_LINK"); ?></th>
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
				echo HtmlHelper::_('bootstrap.endTab');
				echo HtmlHelper::_('bootstrap.endTabSet');
				?>
            </fieldset>
        </div>
        <div class="col-lg-3">
            <fieldset class="adminform">
                <h3 class="mb-3"><?php echo Text::_("JDB_PARAMETERS"); ?></h3>
				<?php
				echo HtmlHelper::_('bootstrap.startAccordion', 'menu-accordion', array('useCookie' => 1));
				$fieldSets = $params->getFieldsets();
				foreach ($fieldSets as $name => $fieldSet) :
					echo HtmlHelper::_('bootstrap.addSlide', 'menu-accordion', Text::_($fieldSet->description), $name);
					echo '<div class="block">';
					foreach ($params->getFieldset($name) as $field):
						echo '<div class="control-group">';
						echo '<div class="control-label">' . $field->label . '</div>';
						echo '<div>' . $field->input . '</div>';
						echo '</div>';
					endforeach;
					echo '</div>';
					echo HtmlHelper::_('bootstrap.endSlide');
				endforeach;
				echo HtmlHelper::_('bootstrap.endAccordion');
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
            alert('<?php echo Text::_("JDB_NAME_YOUR_DB"); ?>');
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
        var screenw = screen.availWidth;
        var screenh = screen.availHeight;
        var winw = (width + 15);
        var winh = (height + 15);
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
        if (confirm("<?php echo Text::_("JDB_REALLY_DELETE") ?>")) {
            jQuery("#subitems").load("index.php?option=com_joodb&task=subitems.removeLine&format=xml", {'jbid': itemid, 'id': id}, function () { });
        }
    }


    (function() {
        refreshSubitems(false);
    })();

</script>
