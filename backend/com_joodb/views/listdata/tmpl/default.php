<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Button\PublishedButton;
use Joomla\CMS\Button\TransitionButton;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;

JHtml::_('jquery.framework');
JHtml::_('bootstrap.tooltip', '.hasTooltip');
JHtml::_('behavior.multiselect');

$listOrder	= $this->state->get('list.ordering','title');
$listDirn	= $this->state->get('list.direction','ASC');

$jb = $this->jb;

$fields = array();
$fields['fid'] = $jb->fid;
$fields['ftitle'] = $jb->ftitle;
$fields['fstate'] = $jb->fstate;
$fields['fcontent'] = $jb->fcontent;
$fields['fdate'] = $jb->fdate;

?>
<form action="<?php echo JRoute::_('index.php'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
				<?php
				// Search tools bar
				echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
				if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
						<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
				<?php else : ?>
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                        <tr>
                            <th  scope="col" class="w-1 text-center">
								<?php echo HTMLHelper::_('grid.checkall'); ?>
                            </th>
                            <th scope="col" class="w-20">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'title', $listDirn, $listOrder ) ?>
                            </th>
                            <th scope="col" class="w-40">
								<?php echo JText::_('Main Content'); ?>
                            </th>
							<?php if (!empty($fields['fdate'])) : ?>
                                <th scope="col" class="w-10 d-none d-md-table-cell">
									<?php echo HTMLHelper::_('searchtools.sort', 'Date', 'created', $listDirn, $listOrder ) ?>
                                </th>
							<?php endif; ?>
                            <th scope="col" class="w-10 d-none d-md-table-cell text-center"><?php
								if (!empty($fields['fstate'])) {
									echo HTMLHelper::_('searchtools.sort', 'Published', 'published', $listDirn, $listOrder );
								} else echo JText::_( 'Published' );
								?>
                            </th>
                            <th  scope="col" class="w-1 text-center"><?php echo JHTML::_('grid.sort','ID', 'id', $listDirn, $listOrder ); ?></th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <td colspan="100%">
								<?php echo $this->pagination->getListFooter(); ?>
								<?php echo $this->pagination->getResultsCounter(); ?>
                            </td>
                        </tr>
                        </tfoot>
                        <tbody>	<?php
						$k = 0;
						for ($i=0, $n=count( $this->items ); $i < $n; $i++)
						{
							$row = &$this->items[$i];
							$checked 	= JHTML::_('grid.id', $i, $row->{$fields['fid']} );
							$editLink	= JRoute::_("index.php?option=com_joodb&task=editdata&view=editdata&joodbid=".$jb->id."&cid[]=".$row->{$fields['fid']});
							if ($fields['fstate']) {
								$row->published = $row->{$fields['fstate']};
								$published 	= JHtml::_('jgrid.published', $row->published, $i, 'data_', true);
							} else {
								$published	= '<i class="icon icon-ban-circle hasTooltip" title="'.JText::_('Not availiable').'"></i>';
							}
							?>
                            <tr class="<?php echo "row$k"; ?>">
                                <td class="text-center">
                                    <?php echo $checked; ?>
                                </td>
                                <td><a href='<?php echo $editLink; ?>' class="hasTooltip" title="<?php echo jText::_("EDIT")." <b>".htmlentities($row->{$fields['ftitle']})."</b>"; ?>" >
                                        <i class="icon-edit"></i> <?php echo $this->escape($row->{$fields['ftitle']}); ?></a>
                                </td>
                                <td>
                                    <?php if (!empty($row->{$fields['fcontent']})) echo substr(strip_tags($row->{$fields['fcontent']}),0,180); ?> &hellip;
                                </td>
								<?php if (!empty($fields['fdate'])) : ?>
                                    <td class="d-none d-md-table-cell">
                                        <?php echo JHTML::_('date', $row->{$fields['fdate']} , JText::_('DATE_FORMAT_LC3')); ?>
                                    </td>
								<?php endif; ?>
                                <td class="d-none d-md-table-cell text-center">
									<?php echo $published; ?>
                                </td>
                                <td class="text-center"><?php echo $row->{$fields['fid']}; ?></td>
                            </tr>
							<?php
							$k = 1 - $k;
						}
						?>
                        </tbody>
                    </table>
				<?php endif; ?>
            </div>
        </div>
    </div>
    <input type="hidden" name="option" value="com_joodb" />
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="joodbid" value="<?php echo $jb->id; ?>" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="view" value="listdata" />
	<?php echo JHTML::_( 'form.token' );?>
</form>
<style>

    .js-stools-container-filters {
        display: none!important;
    }

</style>
