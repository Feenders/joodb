<?php

// no direct access
defined('_JEXEC') or die('Restricted access');
$app = JFactory::getApplication();

echo $this->loadTemplate('header');

?>
<div class="bg-light" id="element-box">
    <form name="adminForm" action="index.php"  class="form-validate"  method="post" >
        <input type="hidden" name="option" value="com_joodb" />
        <input type="hidden" name="server" value="<?php echo $app->input->getString("server");?>" />
        <input type="hidden" name="user" value="<?php echo $app->input->getString("user");?>" />
        <input type="hidden" name="pass" value="<?php echo $app->input->getString("pass");?>" />
        <input type="hidden" name="database" value="<?php echo $app->input->getString("database");?>" />
        <input type="hidden" name="view" value="joodbentry" />
        <input type="hidden" name="tmpl" value="component" />
        <input type="hidden" name="layout" value="step2" />
        <input type="hidden" name="task" value="addnew" />
        <table class="table table-sm">
            <tr>
                <td><label for="jform_dbname"><?php echo JText::_( "Name Your DB" ); ?></label></td>
                <td>
                    <input type="text" value="" class="form-control form-control-sm required" name="dbname" id="jform_dbname">
                </td>
            </tr>
            <tr>
                <td><label for="jform_dbtable"><?php echo JText::_( "Please choose table" ); ?></label></td>
                <td>
                    <select name="dbtable"  id="jform_dbtable" class="form-select form-select-sm required">
                        <option value="">...</option>
                        <?php
                        foreach ($this->tables as $table) {
                            echo "<option>".$table."</option>";
                        }
                        ?>
                    </select>
                </td>
            </tr>
        </table>
    </form>
</div>
<script type="text/javascript">
    //Send Form

    Joomla.submitbutton = function(task) {
        var frm = document.adminForm;
        if (task=="extern") {
            frm.layout.value="extern";
            Joomla.submitform(task,frm);
            return false;
        }
        if (!document.formvalidator.isValid(frm)) {
            if(frm.dbname.value==""){
                alert('<?php echo JText::_( "Name Your DB" ); ?>');
                return false;
            }
            if(frm.dbtable.selectedIndex<=0){
                alert('<?php echo JText::_( "Please choose table" ); ?>');
                return false;
            }
        }
        Joomla.submitform(task,frm);
    }
</script>
