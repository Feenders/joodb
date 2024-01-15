<?php

// no direct access
defined('_JEXEC') or die('Restricted access');
$app = JFactory::getApplication();
JHTML::_('behavior.formvalidator');
JHtml::_('behavior.keepalive');
JHtml::_('bootstrap.tooltip');

?>
<header class="header mb-3">
    <div class="header-inside py-3">
        <h1 class="page-title"><span class="icon-box-add"></span>&nbsp;<?php echo JHtml::_('string.truncate', $app->JComponentTitle, 0, false, false); ?></h1>
    </div>
</header>
<div class="subhead mb-3" id="toolbar-box">
	<?php echo $this->bar->render(); ?>
</div>
<style>
    .form-control-feedback {
        display: none!important;
    }
</style>
