<?php

// no direct access
defined('_JEXEC') or die('Restricted access');

$fields = &$this->fields;
echo $this->loadTemplate('header');

?>
<div class="content-box container-fluid" id="element-box">
    <h4><?php echo JText::_( "Step3 Help" );   ?>
    </h4>
    <br/><br/><br/><br/>
    <div class="clr"></div>
</div>
<script type="text/javascript">

    Joomla.submitbutton = function(task) {
        window.parent.location.reload();
    }

</script>