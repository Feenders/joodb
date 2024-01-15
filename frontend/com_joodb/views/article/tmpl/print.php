<div class="joodb database-article<?php echo $this->params->get('pageclass_sfx')?>">
<?php // no direct access
defined('_JEXEC') or die('Restricted access');

	// get the parts
	$parts = JoodbHelper::splitTemplate($this->joobase->tpl_print);
	// parse the template
	$page = new JObject();
	$page->text = JoodbHelper::parseTemplate($this->joobase, $parts, $this->item);
	// render output text
	JoodbHelper::printOutput($page,$this->params);

?>
</div>
<script type="text/javascript">

    // Jquery encapsulation
(function ($) {

    $(document).ready(function () {
        printnow = confirm('Print Page');
        if (printnow) window.print();
    });

})(jQuery);

</script>


