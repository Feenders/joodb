<?php // no direct access
defined('_JEXEC') or die('Restricted access');

$jb = &$this->joobase;
$imgpart = "/images/joodb/db".$jb->id."/img".$this->item->{$jb->fid};

$lang = JFactory::getLanguage()->load("com_joodb");

?>
<div class="joodb database-article item-page<?php echo $this->params->get('pageclass_sfx')?>">
	<div class="page-header">
		<h1><?php echo (empty($this->item->{$jb->fid})) ? JText::_('NEW_DATABASE_ENTRY') : JText::_('EDIT_DATABASE_ENTRY'); ?></h1>
	</div>
	<div class="database-form">
		<form action="<?php echo JRoute::_( 'index.php' );?>" method="post" name="joodbForm" id="joodbForm" class="form-validate form-inline" enctype="multipart/form-data">
			<input type="hidden" name="option" value="com_joodb" />
			<input type="hidden" name="view" value="article" />
			<input type="hidden" name="Itemid" value="<?php echo $this->menu->id; ?>" />
			<input type="hidden" name="id" value="<?php echo $this->item->{$jb->fid}; ?>" />
			<input type="hidden" name="joobase" value="<?php echo $jb->id; ?>" />
			<input type="hidden" name="<?php echo $jb->fid; ?>" value="<?php echo $this->item->{$jb->fid}; ?>" />
			<input type="hidden" name="task" value="save" />
			<?php echo JHTML::_( 'form.token' ); ?>
			<fieldset>
				<dl><?php
					unset($jb->fields[$jb->fid]);
					foreach ($jb->fields as $fname=>$fcell) :  ?>
						<dt><label for="jform_<?php echo preg_replace("/[^A-Z0-9]/i","",$fname); ?>"><?php echo JText::_(ucfirst(str_replace("_"," ",$fname))); ?></label></dt>
						<dd><?php echo JoodbFormHelper::getFormField($jb,$this->item,$fcell); ?></dd>
					<?php endforeach; ?></dl>
			</fieldset><?php
			// Related fields n:m and 1:n
			$subitems = $jb->getSubitems();
			foreach ($subitems AS $subitem) {
				if ($subitem->type=="2") {
					echo '<fieldset><dl><dt><label>'.ucfirst($subitem->label).'</label></dt><dd>';
					echo JoodbFormHelper::getSubitemSelectMulti($jb,$subitem,$this->item->{$jb->fid});
					echo "</dd></dl></fieldset>";
				}
			}
			?><fieldset>
			<dl>
				<dt><label><?php echo JText::_('ITEM_IMAGE'); ?></label></dt>
				<dd>
					<input name="joodb_dataset_image" class="form-control file" type="file" accept="image/*" />
                     <label><input type="checkbox" name="delete_image" value="1" />&nbsp;<?php echo JText::_('DELETE_IMAGE'); ?></label>
                    <div style="margin: 10px 0;">
						<?php
						$thumb = JURI::root(true).(file_exists(JPATH_ROOT.$imgpart."-thumb.jpg") ? $imgpart."-thumb.jpg" : "/media/com_joodb/images/no_image-thumb.png");
						$image = JURI::root(true).(file_exists(JPATH_ROOT.$imgpart.".jpg") ? $imgpart.".jpg" : "/media/com_joodb/images/no_image.png");
						echo '<a href="'.$image.'" data-featherlight="image"><img src="'.$thumb.'" alt="thumb" class="database-thumb" /></a>';
						?>
					</div>
				</dd>
				<dt>&nbsp;</dt>
				<dd>
					<button class="btn btn-primary validate" onmousedown="validateForm();" type="submit"><span class="jicon jicon-ok"></span> <?php echo JText::_('Save') ?></button>
				</dd>
			</dl>
			</fieldset>
		</form>
	</div>
</div>
<script type="text/javascript">
	function validateForm() {
		var frm = document.joodbForm;
		var valid = document.formvalidator.isValid(frm);
		if (valid == false) {
			// do field validation
			alert( "<?php echo JText::_( 'Required fields', true ); ?>" );
			return false;
		}
		return true;
	}
</script>
