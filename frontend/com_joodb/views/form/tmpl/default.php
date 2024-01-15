<?php // no direct access
defined('_JEXEC') or die('Restricted access');
$jb = &$this->joobase;
?>
<?php if ($this->params->get('show_page_heading')) : ?>
    <div class="page-header">
        <h1 class="<?php echo $this->params->get('pageclass_sfx')?>"><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
    </div>
<?php endif; ?>
<div class="joodb database-form">
	<?php if(isset($this->error)) : ?>
        <div class="error"><?php echo $this->error; ?></div>
	<?php endif; ?>
    <form action="<?php echo JRoute::_( 'index.php' );?>" method="post" name="joodbForm" id="joodbForm" class="form-validate form-inline" enctype="multipart/form-data">
        <input type="hidden" name="option" value="com_joodb" />
	    <?php if ($fuser=$jb->getSubdata('fuser')) : ?>
        <input type="hidden" name="<?php echo $fuser; ?>" value="<?php echo (empty($this->item->{$fuser})) ? JFactory::getUser()->id : $this->item->{$fuser}; ?>" />
		<?php endif; ?>
        <input type="hidden" name="<?php echo $jb->fid; ?>" value="<?php echo $this->item->{$jb->fid}; ?>" />
        <input type="hidden" name="id" value="<?php echo $this->item->{$jb->fid}; ?>" />
        <input type="hidden" name="Itemid" value="<?php echo $this->menu->id; ?>" />
        <input type="hidden" name="task" value="submit" />
		<?php echo JHTML::_( 'form.token' ); ?>
		<?php
		// parse the template
		$page = new JObject();
		$parts = JoodbHelper::splitTemplate($jb->tpl_form);
		$page->text = $this->_parseTemplate($parts);

		JoodbHelper::printOutput($page,$this->params,"form");
		?>
    </form>
</div>
<script type="text/javascript">
    function validateForm() {
        var frm = document.joodbForm;
        var valid = document.formvalidator.isValid(frm);
        if (valid == false) {
            // do field validation
            // alert( "<?php echo JText::_( 'Required fields', true ); ?>" );
            return false;
        }
        return true;
    }
</script>
