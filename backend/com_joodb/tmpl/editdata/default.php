<?php

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Feenders\Component\Joodb\Administrator\Helper\JoodbAdminHelper;
use Feenders\Component\Joodb\Administrator\Helper\FormHelper;

HtmlHelper::_('jquery.framework');
HtmlHelper::_('behavior.formvalidator');
HtmlHelper::_('behavior.keepalive');
HtmlHelper::_('bootstrap.tooltip', '.hasTooltip');
HtmlHelper::_('behavior.multiselect');

Factory::getApplication()->getDocument()->getWebAssetManager()
        ->usePreset('choicesjs')
        ->useScript('webcomponent.field-fancy-select');

$item = $this->item;
$jb = & $this->jb;
?>
<form action="<?php echo Route::_('index.php?option=com_joodb&view=joodb'); ?>" method="post" name="adminForm" id="adminForm"  class="form-validate form-inline" enctype="multipart/form-data">
    <input type="hidden" name="option" value="com_joodb" />
    <input type="hidden" name="joodbid" value="<?php echo $jb->id; ?>" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="view" value="listdata" />
    <input type="hidden" name="<?php echo $jb->fid; ?>" value="<?php echo $this->id?>" />
    <div class="row">
        <div class="col-sm-8">
            <!--Stammdaten -->
            <fieldset class="adminform">
                <h3 class="mb-3"><?php echo Text::_("JDB_EDITABLE_FIELDS"); ?></h3>
                <?php foreach ($jb->fields as $fname=>$fcell) : ?>
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label" for="<?php echo 'jform_'.preg_replace("/[^A-Z0-9]/i","",$fname); ?>"><?php echo Text::_(ucfirst(str_replace("_"," ",$fname))); ?></label>
                        <div class="col-sm-9"><?php echo FormHelper::getFormField($jb,$item,$fcell,$fname); ?></div>
                    </div>
                <?php endforeach; ?>
            </fieldset>
        </div>
        <div class="col-sm-4">
            <fieldset class="adminform">
                <h3 class="mb-3"><?php echo Text::_("JDB_UPLOAD_IMAGE"); ?></h3>
                <?php
                $imgpath = JPATH_ROOT."/images/joodb/db".$jb->id;
                // attach image to dataset
                if (!file_exists($imgpath)) {
                    if (!@mkdir($imgpath,0777, true)) {
                        echo '<p style="color:red; font-weight: bold;">Can not create JooDB image directory. Make sure that /images is writable</p>';
                    }
                }
                ?>
                <input class="form-control mb-3" name="dataset_image" type="file" maxlength="1000000" accept="*.jpg, *.jpeg, *.png, *.webp" /><br/>
                <p><?php echo Text::_("JDB_EXISTING_IMAGE"); ?></p>
                <?php if (($item->{$jb->fid}) && (file_exists($imgpath."/img".$item->{$jb->fid}.".jpg"))) : ?>
                    <a href="<?php echo Uri::root(true).'/images/joodb/db'.$jb->id.'/img'.$item->{$jb->fid}?>.jpg" data-featherlight="image">
                        <img class="img-thumb" src="<?php echo Uri::root(true).'/images/joodb/db'.$jb->id.'/img'.$item->{$jb->fid}?>-thumb.jpg" alt="*" >
                    </a>
                    <hr>
                    <label><input type="checkbox" class="form-check-inline" name="delete_image" value="1" />&nbsp;<?php echo Text::_("JDB_DELETE_IMAGE"); ?></label>
                <?php else: ?>
                    <img class="img-thumb" src="<?php echo Uri::root(true) ?>/media/com_joodb/images/no_image-thumb.png" alt="<?php echo Text::_('NONE'); ?>" >
                <?php endif; ?>
            </fieldset>
            <?php
            // create a set of fields to replace
            $subitems = $jb->getSubitems();
            foreach ($subitems AS $subitem) {
                if ($subitem->type=="2") {
                    echo '<fieldset class="adminform block"><legend>'.ucfirst($subitem->label).'</legend>';
                    echo FormHelper::getSubitemSelectMulti($jb,$subitem,$this->id);
                    echo "</fieldset>";
                }
            }
            ?>
        </div>
    </div>
    <?php echo HtmlHelper::_( 'form.token' );?>
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
            alert('<?php echo addslashes(Text::_("JDB_MUST_HAVE_TITLE")); ?>');
            frm.<?php echo $jb->ftitle; ?>.focus();
            return false;
        } else  {
            if (valid == false) {
                alert("<?php echo addslashes(Text::_("JDB_FILLOUT_REQUIRED_FIELDS")); ?>");
                return false;
            } else {
                if (typeof tinyMCE != "undefined") window.onbeforeunload = function() {};
            }
        }
        Joomla.submitform(task, frm);
    }

</script>
