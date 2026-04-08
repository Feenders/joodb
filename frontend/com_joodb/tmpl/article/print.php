<?php
/**
 * @package		JooDatabase - http://joodb.feenders.de
 * @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 *
 */

use Feenders\Component\Joodb\Site\Helper\JoodbHelper;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

?><div class="joodb database-article<?php echo $this->params->get('pageclass_sfx')?>">
<?php // no direct access
defined('_JEXEC') or die('Restricted access');

	// get the parts
	$parts = JoodbHelper::splitTemplate($this->joobase->tpl_print);
	// parse the template
	$page = new stdClass();
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


