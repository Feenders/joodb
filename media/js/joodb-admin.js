/**
 * @package        JooDatabase - http://joodb.feenders.de
 * @copyright    Copyright (C) Computer - Daten - Netze : Feenders. All rights reserved.
 * @license    GNU/GPL, see LICENSE
 * @created      26.06.13 - 10:08
 * @author        Dirk Hoeschen (hoeschen@feenders.de)
 */

Joomla = window.Joomla || {};


(function($, Joomla, document) {

    $(document).ready(function () {
        $(".fbmodal").each(function () {
            $(this).fancybox({
                type: "iframe",
                iframe : {
                    css : {
                        width  : $(this).data('width'),
                        height : $(this).data('height')
                    }
                }
            });
        });
    });

})(jQuery, Joomla, document);
