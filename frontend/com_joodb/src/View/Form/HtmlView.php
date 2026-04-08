<?php
/**
 * @package		JooDatabase - http://joodb.feenders.de
 * @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 *
 */

namespace Feenders\Component\Joodb\Site\View\Form;


use Feenders\Component\Joodb\Administrator\Helper\FormHelper;
use Feenders\Component\Joodb\Site\Helper\JoodbHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Filter\InputFilter;
use function defined;


// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * HTML View class for the JooDatabase cataloges
 */
class HtmlView extends BaseHtmlView
{

	var $joobase = null;
    var $item = null;
	var $params = null;
	var $menu = null;

    public function display($tpl = null)
	{
		// Get the current menu item
        $app = Factory::getApplication();
        $menu = $app->getMenu();
		$this->menu	= $menu->getActive();
        if (empty($this->menu)) $this->menu	=  $menu->getDefault();
		$this->params= $app->getParams();

		$model = $this->getModel();
		// read database configuration from joobase table
		$this->joobase = $model->getJoobase();

		//get the data page
		$this->item = $model->getData();

		$this->_prepareDocument();

		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 */
	protected function _prepareDocument()
	{
		JoodbHelper::prepareDocument();

		$app = Factory::getApplication();
		$document = $app->getDocument();

		if (!$this->params->get( 'page_title') )
			$this->params->set('page_title',	Text::_( $this->joobase->name ));
	
		if (!$this->params->get( 'page_heading' ) )
			$this->params->set('page_heading',Text::_( $this->joobase->name ));
	
		//set document title
		$document->setTitle( $this->params->get('page_title')." - ".$app->get('sitename') );

		if ($this->params->get('menu-meta_description'))
		{
			$document->setDescription($this->params->get('menu-meta_description'));
		}
	
		if ($this->params->get('menu-meta_keywords'))
		{
			$document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		// load administrator language
		$app->getLanguage()->load('com_joodb' , JPATH_ADMINISTRATOR, $app->getLanguage()->getTag(), true);
	}
	
	/**
 	* Parse Template part and replace with view specific elements
 	*/
	protected function _parseTemplate(&$parts)
	{
		$output = "";
        $filter = new InputFilter();
		// replace item content with wildcards
    	foreach( $parts as $part ) {
		  switch ($part->function) {
   			case ('submitbutton'):
   				$output .= '<button class="btn btn-primary validate" onmousedown="validateForm();" type="submit"><span class="jicon jicon-ok"></span> '.Text::_("JDB_SEND")."</button>";
   				break;
   			case ('captcha'):
  				$output .=  JoodbHelper::getCaptcha();
   				break;
   			case ('form'):
				if (isset($this->joobase->fields[$part->parameter[0]])) {
					$p = (isset($part->parameter[1])) ? $part->parameter[1] : null;
					$output .=  FormHelper::getFormField($this->joobase, $this->item, $this->joobase->fields[$part->parameter[0]],$p);
				}
   				break;
			case ('subforms'):
				$subitems = $this->joobase->getSubitems();
				foreach ($subitems AS $subitem) {
					if ($subitem->type=="2") {
						$output .= '<dl><dt><label>'.ucfirst($subitem->label).'</label></dt><dd>';
						$output .= FormHelper::getSubitemSelectMulti($this->joobase,$subitem,$this->item->{$this->joobase->fid});
						$output .= "</dd></dl>";
					}
				}
				break;
   			case ('imageupload'):
  				$output .=  '<input name="joodb_dataset_image" class="form-control file" type="file" accept="image/*" />';
   				break;
			default:
				// plugin system find commandfile
				$plugin = JPATH_COMPONENT."/plugins/".$filter->clean($part->function,"cmd").".php";
				if (file_exists($plugin)) include $plugin;   				
		  }
   		  $output .= $part->text;
    	}
    	return $output;
	}

}
