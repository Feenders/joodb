<?php // no direct acces
defined('_JEXEC') or die('Restricted access');
$app = JFactory::getApplication();
?>
<div class="database-list<?php echo $this->params->get('pageclass_sfx')?>">
    <?php if ($this->params->get('show_page_heading') || $this->params->get( 'show_description')) : ?>
        <div class="page-header">
            <?php if ($this->params->get('show_page_heading')) : ?>
                <h1>
                    <?php echo $this->escape($this->params->get('page_heading')); ?>
                </h1>
            <?php endif; ?>
            <?php if ( $this->params->get( 'show_description') && $this->pagination->limitstart==0 ) : ?>
                <div class="introtext">
                    <?php if ($this->params->get('image')!="-1") : ?>
                        <img src="<?php echo $this->baseurl."/".$this->params->get('image');?>" style="float: <?php echo $this->params->get('image_align');?>; margin: 0 15px;" alt="*" />
                    <?php endif; ?>
                    <?php echo nl2br($this->params->get('description')); ?>
                </div>
                <div style="clear: both"></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <form name="joodbForm" id="joodbForm"  method="post" class="form-inline" action="<?php echo JRoute::_('index.php'); ?>"  >
        <input type="hidden" name="option" value="com_joodb"/>
        <input type="hidden" name="view" value="catalog"/>
        <input type="hidden" name="format" value="html"/>
        <input type="hidden" name="layout" value="<?php echo $this->params->get('list_layout','list')?>"/>
        <input type="hidden" name="reset" value="false"/>
        <input type="hidden" name="ordering" value="<?php echo $app->input->get("ordering"); ?>"/>
        <input type="hidden" name="orderby" value="<?php echo $app->input->getString("orderby"); ?>"/>
        <input type="hidden" name="Itemid" value="<?php echo $app->input->getInt("Itemid"); ?>"/>
        <input type="hidden" name="task" value=""/>
        <input type="hidden" name="start" value="0" />
        <?php

        $pageparts = preg_split("!{joodb loop}!", $this->joobase->tpl_list);
        if (count($pageparts)<3) {
	        throw new RuntimeException("Error in catalog template. Remember 2 loop declarations must be found inside catalog template!",500);
        }

        // get header text
        $parts = JoodbHelper::splitTemplate($pageparts[0]);

        $valid_functions = array("groupselect","searchbox","searchbutton","searchfield","resetbutton","translate","limitbox","groupselect");

        foreach ($parts AS $n => $part) {
            if (!empty($part->function) && !in_array($part->function,$valid_functions)) {
                $parts[$n]->function = null;
            }
        }

        $page = new JObject();
        $page->text = $this->_parseTemplate($parts);

        // render output text
        JoodbHelper::printOutput($page,$this->params,"catalog");

        ?>
    </form>
    <div style="clear: both;"></div>
</div>
<script type="text/javascript" >

	// Submit search form
	function submitSearch(task) {
		var form = document.joodbForm;
		form.format.value="html";
		if (task=="reset") {
			form.ordering.value="";
			form.orderby.value="";
            form.alpha.value="*";
			jQuery('#joodbForm select').val('');
			jQuery('#joodbForm input.check').attr('checked', false);
			jQuery('#joodbForm input[type=text]').val('');
			form.reset.value = true
		} else if (task=="xportxls") {
			form.format.value="xls";
		} else if (task=="uncheck") {
			jQuery('#joodbForm input.check').attr('checked', false);
		} else if (task=="setlimit") {
		}
		if (form.search && form.search.value=="<?php echo JText::_('search...'); ?>") {
			form.search.value="";
		}
        form.submit();
	}

    function setAlpha(letter) {
        var form = document.joodbForm;
        form.alpha.value=letter;
        form.submit();
    }

    // Check if touch device
    function isTouchDevice() {
        try {
            document.createEvent("TouchEvent");
            return true;
        } catch (e) {
            return false;
        }
    }

    // Jquery encapsulation
    (function ($) {

        $(document).ready(function () {
//            jQuery('#joodbForm select.groupselect').change(function(){ submitSearch(""); });
        });

        $(window).load(function () {
            if (isTouchDevice()) return false;
            if ($('#limit')) {
                $('#limit').change(function(){ submitSearch('setlimit'); });
            }
        });

    })(jQuery);

</script>

