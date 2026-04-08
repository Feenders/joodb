/**
 * @package        JooDatabase - http://joodb.feenders.de
 * @copyright    Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license    GNU/GPL, see LICENSE
 * @created      26.06.13 - 10:08
 * @author        Dirk Hoeschen (hoeschen@feenders.de)
 */

/**
 * Ectend Joomla3 to old modal box
 * @param el
 * @param url
 * @param width
 * @param height
 * @returns {*}
 */
Joomla.modal = function(el,url,width,height){
    var o={'handler':'iframe','size':{x:width,y:height},'url':url, onOpen:function(){}};
    return SqueezeBox.fromElement(el,o);
};
