<?php

// no direct access
defined('_JEXEC') or die('Restricted access');
require_once(JPATH_COMPONENT_ADMINISTRATOR.'/helpers/form.php' );

JHtml::_('jquery.framework');
JHTML::_('behavior.formvalidator');
JHtml::_('behavior.keepalive');
JHtml::_('bootstrap.tooltip', '.hasTooltip');
JHtml::_('behavior.multiselect');

JFactory::getApplication()->getDocument()->getWebAssetManager()
	->usePreset('choicesjs')
	->useScript('webcomponent.field-fancy-select');

$item = $this->item;
$jb = & $this->jb;
?>
<form action="index.php" method="post" name="adminForm" id="adminForm"  class="form-validate form-inline" enctype="multipart/form-data">
    <input type="hidden" name="option" value="com_joodb" />
    <input type="hidden" name="joodbid" value="<?php echo $jb->id; ?>" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="view" value="listdata" />
    <input type="hidden" name="<?php echo $jb->fid; ?>" value="<?php echo $this->id?>" />
    <div class="row">
        <div class="col-sm-8">
            <!--Stammdaten -->
            <fieldset class="adminform">
                     <h3 class="mb-3"><?php echo JText::_( "Editable fields" ); ?></h3>
                    <?php foreach ($jb->fields as $fname=>$fcell) : ?>
                        <div class="mb-3 row">
                            <label class="col-sm-3 col-form-label" for="<?php echo 'jform_'.preg_replace("/[^A-Z0-9]/i","",$fname); ?>.'"><?php echo JText::_(ucfirst(str_replace("_"," ",$fname))); ?></label>
                            <div class="col-sm-9"><?php echo JoodbFormHelper::getFormField($jb,$item,$fcell,$fname); ?></div>
                        </div>
                    <?php endforeach; ?>
            </fieldset>
        </div>
        <div class="col-sm-4">
            <fieldset class="adminform">
                <h3 class="mb-3"><?php echo JText::_( "Upload image" ); ?></h3>
                <?php
                $imgpath = JPATH_ROOT."/images/joodb/db".$jb->id;
                // attach image to dataset
                if (!file_exists($imgpath)) {
                    if (!@mkdir($imgpath,0777, true)) {
                        echo '<p style="color:red; font-weight: bold;">Can not create JooDB image directory. Make sure that /images is writable</p>';
                    }
                }
                ?>
                <table class="paramlist">
                    <tr>
                        <td colspan="2">
                            <input class="form-control input-medium" name="dataset_image" type="file" maxlength="1000000" accept="*.jpg, *.jpeg, *.png" /><br/>
                        </td>
                    </tr>
                    <tr>
                        <td class="paramlist_key"><?php echo JText::_( "Existing image" ); ?></td>
                        <td><?php if (($item->{$jb->fid}) && (file_exists($imgpath."/img".$item->{$jb->fid}.".jpg"))) : ?>
                                <a href="<?php echo JUri::root(true).'/images/joodb/db'.$jb->id.'/img'.$item->{$jb->fid}?>.jpg" data-featherlight="image">
                                    <img class="img-thumb" src="<?php echo JUri::root(true).'/images/joodb/db'.$jb->id.'/img'.$item->{$jb->fid}?>-thumb.jpg" alt="*" >
                                </a>
                                <hr>
                                <label><input type="checkbox" name="delete_image" value="1" />&nbsp;<?php echo JText::_('DELETE_IMAGE'); ?></label>
                            <?php else: ?>
                                <img class="img-thumb" src="<?php echo JUri::root(true) ?>/media/com_joodb/images/no_image-thumb.png" alt="<?php echo JText::_('NONE'); ?>" >
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </fieldset>
            <?php
            // create a set of fields to replace
            $subitems = $jb->getSubitems();
            foreach ($subitems AS $subitem) {
                    if ($subitem->type=="2") {
                        echo '<fieldset class="adminform block"><legend>'.ucfirst($subitem->label).'</legend>';
                        echo JoodbFormHelper::getSubitemSelectMulti($jb,$subitem,$this->id);
                        echo "</fieldset>";
                    }
                }
            ?>
        </div>
    </div>
    <?php echo JHTML::_( 'form.token' );?>
</form>
<script type="text/javascript">


    (function() {
/*
        const element = document.querySelector('.js-choice');
        const choices = new Choices(element);
*/

    })();

    Joomla.submitbutton = function(task) {

        var frm = document.adminForm;
        if (task == 'editdata.cancel') {
            Joomla.submitform(task, frm);
            return true;
        }

        // do field validation
        var valid = document.formvalidator.isValid(frm);
        if (frm.<?php echo $jb->ftitle; ?>.value == ""){
            alert('<?php echo addslashes(JText::_( "Must have title" )); ?>');
            frm.<?php echo $jb->ftitle; ?>.focus();
            return false;
        } else  {
            if (valid == false) {
                alert("<?php echo addslashes(JText::_( "Fillout required fields" )); ?>");
                return false;
            } else {
                if (typeof tinyMCE != "undefined") window.onbeforeunload = function() {};
            }
        }
        Joomla.submitform(task, frm);
    }

</script>
