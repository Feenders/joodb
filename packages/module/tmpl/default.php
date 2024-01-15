<?php
/**
* @file default.php created 22.08.2012
* @package joodatabase
* @subpackage module
* @author computer :: daten :: netze - feenders - Dirk Hoeschen
* @link http://joodb.dirk-hoeschen.de
* @copyright (C) 2012 feenders.de. all rights reserved
* @license	GNU General Public License version 2 or later; see LICENSE.txt
*/
// no direct access
defined('_JEXEC') or die;
?>
<?php if (!empty($items)) : ?>
<div class="joodb_module<?php echo $moduleclass_sfx ?>" >
<?php if (!empty($pretext)) echo "<p>".nl2br($pretext)."</p>"; ?>
<ul>
	<?php foreach($items as $n => $item) :?>
		<li>
			<a class="joodb_module_link" href="<?php echo modJoodbHelper::getRoute($jb,$item); ?>" ><?php echo $item->{$jb->ftitle} ?></a><br/>
			<?php if ($params->get('show_date')=="1" && !empty($jb->fdate)) 
				echo '<small class="small">'.JHTML::_('date', $item->{$jb->fdate}, JText::_('DATE_FORMAT_LC3')).'</small><br/>'; ?>		
			<?php if ($params->get('show_teaser')=="1") 
				echo (!empty($jb->fabstract)) ? strip_tags($item->{$jb->fabstract}) : JoodbHelper::wrapText($item->{$jb->fcontent},60);
			?>
		</li>
	<?php endforeach; ?>
</ul>	
</div>
<?php endif; ?>