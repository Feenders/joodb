<?php
/**
 * Displays a number of JooDatabase entries on a module position
 *
 * @package joodatabase
 * @subpackage module
 * @version 5.0
 * @author computer :: daten :: netze - feenders - Dirk Hoeschen
 * @link http://joodb.dirk-hoeschen.de
 * @copyright (C) 2012 - 2025 feenders.de. all rights reserved
 * @license	GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Feenders\Module\Joodb\Site\Helper\JoodbHelper as ModuleHelper;
use Feenders\Component\Joodb\Site\Helper\JoodbHelper as ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;

$pretext = $params->get('pretext','');

?>
<?php if (!empty($items)) : ?>
<div class="joodb_module<?php echo $params->get('moduleclass_sfx',''); ?>" >
<?php if (!empty($pretext)) echo "<p>".nl2br($pretext)."</p>"; ?>
<ul>
	<?php foreach($items as $n => $item) :?>
		<li>
			<a class="joodb_module_link" href="<?php echo ModuleHelper::getRoute($jb,$item); ?>" ><?php echo $item->{$jb->ftitle} ?></a><br/>
			<?php if ($params->get('show_date')=="1" && !empty($jb->fdate)) 
				echo '<small class="small">'.HTMLHelper::_('date', $item->{$jb->fdate}, Text::_('DATE_FORMAT_LC3')).'</small><br/>'; ?>
			<?php if ($params->get('show_teaser')=="1") 
				echo (!empty($jb->fabstract)) ? strip_tags($item->{$jb->fabstract}) : ComponentHelper::wrapText($item->{$jb->fcontent},60);
			?>
		</li>
	<?php endforeach; ?>
</ul>	
</div>
<?php endif; ?>