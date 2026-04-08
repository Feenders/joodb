<?php

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

$app = Factory::getApplication();
HtmlHelper::_('behavior.formvalidator');
HtmlHelper::_('behavior.keepalive');
HtmlHelper::_('bootstrap.tooltip');

?>
<header class="header">
    <div class="header-inside p-3">
        <h1 class="page-title"><span class="icon-box-add"></span>&nbsp;<?php echo HtmlHelper::_('string.truncate', $app->getDocument()->getTitle(), 0, false, false); ?></h1>
    </div>
</header>
<div class="subhead m-0 px-3" id="toolbar-box">
	<?php echo $this->bar->render(); ?>
</div>
<style>

    body.contentpane {
        padding: 0!important;
    }

    .form-control-feedback {
        display: none!important;
    }
</style>
