<?php
/**
 * @package		JooDatabase - http://joodb.feenders.de
 * @copyright	Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 *
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

/**
 * HTML View class for the JooDatabase cataloges
 */
class JoodbViewCatalog extends JViewLegacy
{
	var $joobase = null;
	var $items = null;
	var $subitems = null;
	var $params = null;
	var $pagination = null;
	var $state = null;

	/**
	 * @param   null  $tpl
	 *
	 *
	 * @throws Exception
	 * @since 1.5
	 */
	public function display($tpl = null)
	{

		$app = JFactory::getApplication('site');
		$this->params = $app->getParams();

		// read database configuration from joobase table
		$this->joobase = $this->get('joobase');

		//get the data page
		$this->items = $this->get('data');
		$this->subitems = $this->get('subitems');
		$this->state = $this->get('state');

		$this->pagination = $this->get('pagination');

		$this->_prepareDocument();

		$this->params->set('search', $this->get('search'));
		$this->params->set('alphachar', $this->get('alphachar'));

		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 */
	protected function _prepareDocument()
	{
		$document = JFactory::getDocument();
		$app = JFactory::getApplication();

		if (empty($this->params->get( 'page_title')) )
			$this->params->set('page_title',	JText::_( $this->joobase->name ));

		//set document title
		$document->setTitle( $this->params->get('page_title')." - ".$app->getCfg('sitename') );

		if ($this->params->get('menu-meta_description'))
		{
			$document->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->params->get('menu-meta_keywords'))
		{
			$document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		JoodbHelper::prepareDocument();

	}

	/**
	 * Parse Template part and replace with view specific elements
	 *
	 * @param array $parts - the split parts of the template
	 * @return string with html
	 *
	 */
	protected function _parseTemplate($parts)
	{
		$filter = new JFilterInput();
		$joobase = &$this->joobase;
		$doOutput = true;
		$output = "";
		// replace item content with wildcards
		foreach( $parts as $n => $part ) {
			if ($doOutput) {
				switch ($part->function) {
					case ('pagenav'):
						$output .= $this->pagination->getPagesLinks();
						break;
					case ('pagecount'):
						if (!empty($this->items)) {
							$output .= $this->pagination->getPagesCounter();
							$output .=  $this->pagination->getResultsCounter();
						}
						break;
					case ('resultcount'):
						if (!empty($this->items)) {
							$output .= $this->pagination->getResultsCounter();
						}
						break;
					case ('limitbox'):
						$output .=  $this->pagination->getLimitBox();
						break;
					case ('searchbox'):
						$output .=  JoodbHelper::getSearchbox($this->params->get('search'),$part->parameter);
						break;
					case ('searchfield'):
						if (isset($part->parameter[0]) && isset($this->joobase->fields[$part->parameter[0]])) {
							$conditions = array("like","exact","min","max","start","end","inset");
							$cond = (isset($part->parameter[1]) && array_search(strtolower($part->parameter[1]), $conditions)!==false) ? strtolower($part->parameter[1]) : 'like';
							$app = JFactory::getApplication();
							$fs =  $app->getUserStateFromRequest("com_joodb".$this->joobase->id.'.fs', 'fs',array(), 'array');
							$field = $part->parameter[0];
							$sv = (isset($fs[$field]) && !empty($fs[$field][$cond])) ? $fs[$field][$cond] : "";
							$output .=  '<input class="form-control searchword" type="text"'
								.' value="'.htmlspecialchars(stripcslashes($sv), ENT_QUOTES, "UTF-8").'"  '
								.'id="fs_'.JFilterOutput::stringURLSafe($field).'_'.$cond.'" name="fs['.$field.']['.$cond.']" />';
						}
						break;
					case ('groupselect'):
						$model = $this->getModel();
						$use_search = (isset($part->parameter[2])) ? JoodbHelper::parameterToBoolean($part->parameter[2]) : false;
						$values = $model->getColumnVals($part->parameter[0],$use_search);
						$output .= JoodbHelper::getGroupselect($this->joobase,$part->parameter,$values);
						break;
					case ('alphabox'):
						$output .= JoodbHelper::getAlphabox($this->state->get('alphachar',''),$this->joobase);
						break;
					case ('orderlink'):
					case ('sortlink'):
						$output .= JoodbHelper::getOrderlink($part->parameter,$this->joobase);
						break;
					case ('exportbutton'):
						$output .= "<button class='btn btn-secondary btn-export' type='button' onmousedown='submitSearch(\"xportxls\");void(0);' ><span class=\"jicon jicon-download\"></span> ".((isset($part->parameter[0]) ? $part->parameter[0] : JText::_('Export XLS')))."</button>";
						break;
					case ('resetbutton'):
						$output .= "<button class='btn  btn-secondary btn-reset' type='button' onmousedown='submitSearch(\"reset\");void(0);' ><span class=\"jicon jicon-cancel\"></span> ".((isset($part->parameter[0]) ? $part->parameter[0] : JText::_('Reset...')))."</button>";
						break;
					case ('searchbutton'):
						$output .= "<button class='btn  btn-secondary btn-search' type='button' onmousedown='submitSearch();void(0);' ><span class=\"jicon jicon-search\"></span> ".((isset($part->parameter[0]) ? $part->parameter[0] : JText::_('Search...')))."</button>";
						break;
					case ('translate') :
						$output .= JText::_(addslashes($part->parameter[0]));
						break;
				}
			}
			switch ($part->function) {
				case ('else'):
					$doOutput = !$doOutput;
					break;
				case ('endif'):
					$doOutput = true;
					break;
				default:
					// plugin system find commandfile
					$plugin = JPATH_COMPONENT."/plugins/".$filter->clean($part->function,"cmd").".php";
					if (file_exists($plugin)) include $plugin;
			}
			if ($doOutput) $output .= $part->text;
		}
		return ($doOutput) ? $output : "";
	}

}
