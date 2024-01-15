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
JHtml::_('bootstrap.modal');
JHtml::_('bootstrap.tooltip', '.hasTooltip');
JHtml::_('behavior.multiselect');

JHtml::_('script', 'com_joodb/jquery.fancybox.js',array('version' => 'auto', 'relative' => true));
JHtml::_('stylesheet', 'com_joodb/jquery.fancybox.css', array('version' => 'auto', 'relative' => true));

$listOrder	= $this->state->get('list.ordering');
$listDirn	= $this->state->get('list.direction');

?><form action="<?php echo JRoute::_('index.php'); ?>" method="post" name="adminForm" id="adminForm">
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
                            <th scope="col">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.name', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col">
								<?php echo JText::_('Data'); ?>
                            </th>
                            <th scope="col" class="w-10 d-none d-md-table-cell">
								<?php echo JText::_('TABLE IN DATABASE'); ?>
                            </th>
                            <th scope="col" class="w-10 d-none d-md-table-cell">
								<?php  echo JText::_('Menu item'); ?>
                            </th>
                            <th scope="col" class="w-10 d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'PUBLISHED', 'a.published', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-10 d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'CREATED', 'a.created', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-5 d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                            </th>
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

							$published 	= JHTML::_('grid.published', $row,  $i);
							$checked 	= JHTML::_('grid.id',   $i, $row->id );
							$termLink	= JRoute::_("index.php?option=com_joodb&task=edit&view=joodbentry&cid[]=$row->id");
							$editLink	= JRoute::_("index.php?option=com_joodb&task=listdata&view=listdata&joodbid=$row->id");
							?>
                            <tr class="<?php echo "row$k"; ?>">
                                <td class="center"><?php echo $checked; ?></td>
                                <td>
                                    <a href='<?php echo $termLink; ?>' class="hasTooltip" title="<?php echo htmlentities(JText::_("EDIT")." <b>".$row->name."</b>");?>" >
                                        <span class="icon-edit"></span>
										<?php  echo $this->escape($row->name); ?>
                                    </a>
                                </td>
                                <td>
				<span class="editlinktip hasTooltip" title="<?php echo JText::_( 'Edit data of' )." <b>".$row->table."</b>";?>">
					<a href="<?php echo $editLink; ?>">
						<span class="icon-database"></span>
						<?php echo JText::_( 'Edit data' ); ?>
					</a>
				</span>
                                </td>
                                <td class="d-none d-md-table-cell">
									<?php echo $row->table; ?>
                                </td>
                                <td class="d-none d-md-table-cell">
				<span class="editlinktip hasTooltip" title="<?php echo JText::_( 'Create menu item' )." <b>".$row->name."</b>";?>">
					<a class="btn btn-sm btn-light fbmodal" href="index.php?option=com_joodb&tmpl=component&view=addmenuitem&cid[]=<?php echo $row->id ?>" data-width="560px" data-height="230px" >
						<span class="icon-menu-3"></span>
					</a>
				</span>
                                </td>
                                <td class="d-none d-md-table-cell text-center">
									<?php echo JHtml::_('jgrid.published', $row->published, $i, '', true); ?>
                                </th>
                                <td class="d-none d-md-table-cell">
									<?php echo JHTML::_('date', $row->created, JText::_('DATE_FORMAT_LC3')); ?>
                                </td>
                                <td class="d-none d-md-table-cell">
									<?php echo $row->id; ?>
                                </td>
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
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="view" value="joodb" />
	<?php echo JHTML::_( 'form.token' );?>
</form>
<div id="loadModal"></div>