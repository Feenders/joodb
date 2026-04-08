<?php

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$app = Factory::getApplication();

HtmlHelper::_('script', 'com_joodb/jquery.form.min.js', array('version' => 'auto', 'relative' => true));

?>
<header class="header">
    <div class="header-inside p-3">
        <h1 class="page-title"><span class="icon-upload"></span>&nbsp;<?php echo HtmlHelper::_('string.truncate', $app->JComponentTitle, 0, false, false); ?></h1>
    </div>
</header>
<div class="subhead m-0 px-3" id="toolbar-box">
	<?php echo $this->bar->render(); ?>
</div>
<div class="p-3">
    <div id="content">
        <form action="index.php" method="post" name="importForm" id="importForm" class="form-validate form-inline" enctype="multipart/form-data">
            <input type="hidden" name="tmpl" value="component"/>
            <input type="hidden" name="option" value="com_joodb"/>
            <input type="hidden" name="task" value="import.import"/>
			<?php echo HtmlHelper::_('form.token'); ?>
            <table class="paramlist table table-borderless table-responsive-sm">
                <tbody>
                <tr>
                    <td class="paramlist_key"><label for="tablename"><?php echo Text::_("JDB_DESTINATION_TABLENAME"); ?></label></td>
                    <td><input type="text" class="form-control form-control-sm required" id="tablename" name="tablename" value="" ></td>
                </tr>
                <tr>
                    <td class="paramlist_key"><label for="tablefile"><?php echo Text::_("JDB_FILE"); ?></label></td>
                    <td>
                        <input class="form-control form-control-sm" name="tablefile" id="tablefile" type="file" size="28" accept=".xls,.xlsx,.xlsm,.xlsb,.xlm,.csv,.ods">
						<?php echo "<i class='text-muted small'>" . Text::_("JDB_UPLOAD_VALID_EXCEL_FILE") . "</i>" ?>
                    </td>
                </tr>
                <tr>
                    <td class="paramlist_key"><label for="tablefile"><?php echo Text::_("JDB_CSV_SETTINGS"); ?></label>
                    </td>
                    <td>
                        <label><?php echo Text::_("JDB_CSV_DELIMETER"); ?></label>
                        <select class="form-select form-select-sm" name="delimeter" style="width: 80px;">
                            <option>;</option>
                            <option>,</option>
                        </select>&nbsp;&nbsp;
                        <label><?php echo Text::_("JDB_CSV_ENCLOSURE"); ?></label>
                        <select class="form-select form-select-sm" name="enclosure" style="width: 80px;">
                            <option>"</option>
                            <option>'</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="paramlist_key"><label><?php echo Text::_("JDB_COLUMN_NAMES_IN_FIRST_LINE"); ?></label></td>
                    <td>
                        <label class="checkbox"><input type="radio" name="has_column_names" value="1"
                                                       checked="checked"> <?php echo Text::_("JDB_YES"); ?></label>
                        <label class="checkbox"><input type="radio" name="has_column_names"
                                                       value="0"> <?php echo Text::_("JDB_NO"); ?></label>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
    </div>
    <div id="loadmsg">
        <h4><?php echo Text::_("JDB_PROCESSING"); ?></h4>
        <div class="card p-2">
            <div class="progress">
                <div class="progress-bar progress-bar-striped bg-info" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
        <div class="lead"><?php echo Text::_("JDB_UPLOADING"); ?></div>
    </div>
</div>
<style>

    body.contentpane {
        padding: 0!important;
    }

    #loadmsg {
        display: none; text-align: center; margin: 20px 0;
    }

    #loadmsg img {
        width: 112px; height: 12px;
    }

    #loadmsg div.lead {
        margin: 10px 0;
    }

</style>
<script type="text/javascript">

    // Jquery encapsulation
    (function ($) {
        Joomla.submitbutton = function (task) {
            var frm = document.importForm;
            // do field validation
            if (frm.tablefile.value == "") {
                alert('<?php echo Text::_("JDB_FILLOUT_REQUIRED_FIELDS"); ?>');
                return false;
            }
            // do field validation
            if (document.formvalidator.isValid(frm)) {
                // Test if table exists
                $("#toolbar-box").hide();
                $.ajaxSetup({ async: false });
                $.getJSON("index.php?option=com_joodb&task=testtable",
                    {'table': frm.tablename.value},
                    function (response) {
                        if (response == true) {
                            check = confirm("<?php  echo Text::_("JDB_TABLE_EXIST"); ?>");
                            if (check == false) return false;
                        }
                        $("#content").hide();
                        $("#loadmsg").show();
                        // send the form
                        $('#importForm').ajaxForm(function(r) {
                            $("#loadmsg>h4:first-of-type").html(r.header);
                            $("#loadmsg>div:first-of-type").html(r.message);
                            $("#loadmsg").trigger("getChunk");
                        });
                        $('#importForm').submit();
                        return true;
                    });
            } else alert('<?php echo Text::_("JDB_FILLOUT_REQUIRED_FIELDS"); ?>');
            return false;
        }

        $("#loadmsg").bind('getChunk',function () {
            $.getJSON("index.php?option=com_joodb&task=import.importchunk", function (r) {
                $("#loadmsg>legend:first-of-type").html(r.header);
                $("#loadmsg>div:first-of-type").html(r.message);
                if (r.error==false && r.finished==false) {
                    $("#loadmsg").trigger("getChunk");
                } else {
                    $("#loadmsg>img:first-of-type").hide();
                }
            });
        });

    })(jQuery);
</script>


