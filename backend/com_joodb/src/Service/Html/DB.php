<?php
/**
 * @version    CVS: 5.0.0
 * @package    COM_Joodb
 * @author     Dirk Hoeschen - Feenders <hoeschen@feenders.de>
 * @copyright  2024 Dirk Hoeschen - Feenders
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Feenders\Component\Joodb\Administrator\Service\Html;


defined('_JEXEC') or die();

use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseDriver;
use function defined;

/**
 * Joodb HTML Service.
 *
 * @since  1.0.0
 */
class DB
{
	use DatabaseAwareTrait;

	/**
	 * Public constructor.
	 *
	 * @param   DatabaseDriver  $db  The Joomla DB driver object for the site's database.
	 */
	public function __construct(DatabaseDriver $db)
	{
		$this->setDatabase($db);
	}

}
