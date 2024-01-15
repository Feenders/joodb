<?php

// no direct access
defined('_JEXEC') or die('Restricted access');
$app = JFactory::getApplication();

$fields = &$this->fields;
echo $this->loadTemplate('header');

?>
<div class="content-box container-fluid" id="element-box">
	<form name="adminForm" action="index.php" method="post"  class="form-validate">
		<input type="hidden" name="table" value="<?php echo $this->dbtable; ?>"  />
		<input type="hidden" name="name" value="<?php echo $this->dbname; ?>"  />
		<input type="hidden" name="server" value="<?php echo $app->input->getString("server");?>" />
		<input type="hidden" name="user" value="<?php echo $app->input->getString("user");?>" />
		<input type="hidden" name="pass" value="<?php echo $app->input->getString("pass");?>" />
		<input type="hidden" name="database" value="<?php echo $app->input->getString("database");?>" />
		<input type="hidden" name="option" value="com_joodb" />
		<input type="hidden" name="view" value="joodbentry" />
		<input type="hidden" name="tmpl" value="component" />
		<input type="hidden" name="layout" value="step3" />
		<input type="hidden" name="task" value="addnew" />
		<table cellpadding="5"><tr><td>
		    <label for="jform_fid"><?php echo JText::_( "Primary Index" ); ?></label>
		</td><td>
		<select name="fid" id="jform_fid" style="width: 250px"  class="form-select form-select-sm required"  >
		<?php
			$fselect = JoodbAdminHelper::selectFieldTypes("primary",$fields);
			foreach ($fselect as $fname) {
				echo "<option>".$fname."</option>";
			}
		 ?>
		</select>
		<?php
			if (count($fselect)<1)
				echo '<div style="color: #d40000; font-weight: bold; font-size:10px;">'.JText::_( "No Primary Index" ).'</div>';
		?>
		</td></tr><tr><td>
               <label for="jform_ftitle"><?php echo JText::_( "Title or Headline" ); ?></label>
		</td><td>
		<select name="ftitle" id="jform_ftitle"  style="width: 250px" class="form-select form-select-sm required"  >
		<?php
			$fselect = JoodbAdminHelper::selectFieldTypes("shorttext",$fields);
			foreach ($fselect as $fname) {
				echo "<option>".$fname."</option>";
			}
		 ?>
		</select>
		<?php
			if (count($fselect)<1)
				echo '<div style="color: #d40000; font-weight: bold; font-size:10px;">'.JText::_( "No Text Field" ).'</div>';
		?>
		</td></tr><tr><td>
               <label for="jform_fcontent"><?php echo JText::_( "Main Content" ); ?></label>
		</td><td>
		<select name="fcontent" id="jform_fcontent"  style="width: 250px" class="form-select form-select-sm required" >
		<?php
			$fselect = JoodbAdminHelper::selectFieldTypes("shorttext",$fields);
			foreach ($fselect as $fname) {
				echo "<option>".$fname."</option>";
			}
		 ?>
		</select>
		<?php
			if (count($fselect)<1)
				echo '<div style="color: #d40000; font-weight: bold; font-size:10px;">'.JText::_( "No Text Field" ).'</div>';
		?>
		</td></tr><tr><td>
		<?php echo JText::_( "Abstract" ); ?>
		</td><td>
		<select name="fabstract" style="width: 250px" >
		 <option value="">...</option>
		<?php
			foreach ($fselect as $fname) {
				echo "<option>".$fname."</option>";
			}
		 ?>
		</select>
		</td></tr><tr><td>
		<?php echo JText::_( "Main Date" ); ?>
		</td><td>
		<select name="fdate" style="width: 250px" >
		 <option value="">...</option>
		<?php
			$fselect = JoodbAdminHelper::selectFieldTypes("date",$fields);
			foreach ($fselect as $fname) {
				echo "<option>".$fname."</option>";
			}
		 ?>
		</select>
		</td></tr></table>
	</form>
	<br/>
	</div>
</div>
<script type="text/javascript">
//Send Form
Joomla.submitbutton = function(task) {
		var frm = document.adminForm;
		if (!document.formvalidator.isValid(frm)) {
			alert('<?php echo JText::_('ERROR DEFINE FIELDS'); ?>');
			return false;
		}
		Joomla.submitform(task,frm);		
	}
	
</script>

