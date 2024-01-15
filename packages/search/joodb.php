<?php
/**
 *
 * Plugin to search in all active JooDB databases usins the Joomla sitesearch
 *
 * @package		JooDatabase - http://joodb.feenders.de
 * @copyright	Copyright (C)2021 : Computer - Daten - Netze : Feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 * @version 	4.0
 *
 **/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

require_once JPATH_SITE.'/components/com_joodb/router.php';

/**
 * Weblinks Search plugin
 */
class plgSearchJoodb extends JPlugin {

    /**
     * Constructor
     *
     * @access      protected
     * @param       object  $subject The object to observe
     * @param       array   $config  An array that holds the plugin configuration
     * @since       1.5
     */
    public function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();
    }

	/**
	 * Determine areas searchable by this plugin.
	 *
	 * @return  array  An array of search areas.
	 *
	 * @since   1.6
	 */
	public function onContentSearchAreas()
	{
		static $areas = array(
			'joodb' => 'PLG_SEARCH_JOODB_JOODB'
		);

		return $areas;
	}

    /**
     * Joodb Search method
     *
     * The sql must return the following fields that are used in a common display
     * routine: href, title, section, created, text, browsernav
     * @param string Target search string
     * @param string mathcing option, exact|any|all
     * @param string ordering option, newest|oldest|popular|alpha|category
     * @param mixed An array if the search it to be restricted to areas, null if search all
     */
    function onContentSearch($text, $phrase='', $ordering='', $areas=null) {
        JTable::addIncludePath(JPATH_SITE.'/administrator/components/com_joodb/tables');
        $db	= JFactory::getDBO();

        $limit = $this->params->def('search_limit', 50);

        $text = trim( $text );
        if (empty($text))
        {
            return array();
        }

        if (is_array($areas))
        {
            if (!array_intersect($areas, array_keys($this->onContentSearchAreas())))
            {
                return array();
            }
        }

        // get the enabled joodb-databases
        $db->setQuery('SELECT id FROM `#__joodb` WHERE published=1;');
        $databases = $db->loadColumn();

        $allrows = array();
        foreach ($databases as $id) {
            $joodb = JTable::getInstance( 'joodb', 'Table' );
	        if ($joodb->load($id)) {
                $wheres 	= array();
                switch ($phrase) {
                    case 'exact':
                        $qtext = $db->Quote( $db->escape( $text, true ), false );
                        $wheres[] 	= 'c.`'.$joodb->ftitle.'` LIKE '.$qtext;
                        $wheres[] 	= 'c.`'.$joodb->fcontent.'` LIKE '.$qtext;
                        if (!empty($joodb->fabstract)) $wheres[] 	= 'c.`'.$joodb->fabstract.'` LIKE '.$qtext;
                        $where 		= '(' . implode( ') OR (', $wheres ) . ')';
                        break;
                    case 'all':
                        $qtext		= $db->Quote( '%'.$db->escape( $text, true ).'%', false );
                        $wheres[] 	= 'c.`'.$joodb->ftitle.'` LIKE '.$qtext;
                        $wheres[] 	= 'c.`'.$joodb->fcontent.'` LIKE '.$qtext;
                        if (!empty($joodb->fabstract)) $wheres[] 	= 'c.`'.$joodb->fabstract.'` LIKE '.$qtext;
                        $where 		= '(' . implode( ') OR (', $wheres ) . ')';
                        break;
                    case 'any':
                    default:
                        $words 	= explode( ' ', $text );
                        foreach ($words as $word)
                        {
                            $word		= $db->Quote( '%'.$db->escape( $word, true ).'%', false );
                            $wheres2 	= array();
                            $wheres2[] 	= 'c.`'.$joodb->ftitle.'` LIKE '.$word;
                            $wheres2[] 	= 'c.`'.$joodb->fcontent.'` LIKE '.$word;
                            if (!empty($joodb->fabstract)) $wheres2[] 	= 'c.`'.$joodb->fabstract.'` LIKE '.$word;
                            $wheres[]	= '(' . implode( ') OR (', $wheres2 ) . ')';
                        }
                        $where 	= '(' . implode( ') OR (' , $wheres ) . ')';
                        break;
                }

                switch ( $ordering ){
                    case 'oldest':
                        $order = (!empty($joodb->fdate)) ?  'c.`'.$joodb->fdate.'` ASC' :  'c.`'.$joodb->fid.'` ASC';
                        break;
                    case 'newest':
                        $order = (!empty($joodb->fdate)) ?  'c.`'.$joodb->fdate.'` DESC' :  'c.`'.$joodb->fid.'` DESC';
                        break;
                    case 'alpha':
                        $order = 'c.`'.$joodb->ftitle.'` ASC';
                        break;
                    case 'category':
                    case 'popular':
                    default:
                        $order = 'c.'.$joodb->fid.' DESC';
                }

                if ($joodb->fstate) $where = "(".$where.") AND `".$joodb->fstate."`='1' ";

                $query = 'SELECT c.`'.$joodb->fid.'` AS id, c.`'.$joodb->ftitle.'` AS title, c.`'.$joodb->fcontent.'` AS text ';
                if (!empty($joodb->fdate)) $query .= ',  c.`'.$joodb->fdate.'` AS created ' ;

		        $falias = $joodb->getSubdata('falias');
	            if (!empty($falias)) $query .= ',  c.`'.$falias.'` AS alias ' ;

                $query .= ' FROM `'.$joodb->table.'` AS c '
                    .' WHERE ('. $where .') '
                    .' GROUP BY c.`'.$joodb->fid.'` ORDER BY '. $order;

                $db = $joodb->getTableDbo();
                $db->setQuery( $query, 0, $limit );
	            $app = JFactory::getApplication();

                if ($rows = $db->loadObjectList()) {
                    $titlelink = "index.php?option=com_joodb&joobase=".$joodb->id.":".JFilterOutput::stringURLSafe($joodb->name)."&view=article&id=";
                    $section = Jtext::_('Database').': '.$joodb->name;
                    foreach($rows as $row) {
	                    if (!isset($row->created)) $row->created = null;
                        $row->section = $section;
                        $row->browsernav = 0;
	                    if (!empty($app->get('sef')) && !empty($falias) && !empty($row->alias)) {
		                    $slug = $row->alias;
	                    } else {
		                    $slug = $row->id.':'.JFilterOutput::stringURLSafe($row->title);
	                    }
                        $row->href = JRoute::_($titlelink.$slug);
                        array_push($allrows,$row);
                    }
                }
            }
        }

        return $allrows;
    }
}
