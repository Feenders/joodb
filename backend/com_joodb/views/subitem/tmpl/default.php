<?php

// no direct access
defined('_JEXEC') or die('Restricted access');

$app = JFactory::getApplication();
$document = JFactory::getDocument();
JHtml::_('bootstrap.tooltip', '.hasTooltip');

if ($this->config->get('internal_editor', 1) == 0) {
	$editor = new JEditor($app->get('editor'));
} else {
	require_once(JPATH_ROOT . '/media/com_joodb/editor.php');
	$editor = new JDBEditor();
}

?>
<style>
    .CodeMirror { height: 240px; min-height: 240px; }
    .icon-forward:before { content: "\e00a"; }
    body { margin: 0!important; padding: 0!important; }
    .form-control-feedback { display: none!important; }
</style>
<header id="header" class="header mb-3">
    <div class="header-inside py-3">
        <h1 class="page-title"><span class="icon-tree-2"></span>&nbsp;<?php echo JHtml::_('string.truncate', $app->JComponentTitle, 0, false, false); ?></h1>
    </div>
</header>
<div id="wrapper">
    <div class="container-fluid ">
        <div class="subhead mb-3" id="subhead-container">
		    <?php echo $this->bar->render(); ?>
        </div>
        <form action="index.php" method="post" name="subForm" id="subForm" class="form-validate form-inline">
            <input type="hidden" name="option" value="com_joodb" />
            <input type="hidden" name="id" value="<?php echo $app->input->getInt('id'); ?>" />
            <input type="hidden" name="jb_id" value="<?php echo $app->input->getInt('jbid'); ?>" />
            <input type="hidden" name="task" value="subitems.save" />
            <input type="hidden" name="format" value="xml" />
			<?php echo JHTML::_( 'form.token' );?>
            <table class="paramlist admintable table">
                <tr>
                    <td>
                        <div class="controls controls-row">
                            <label for="label" class="inline"><?php echo JText::_( "Name" ); ?></label>
                            <input type="text" class="form-control form-control-sm required inline" id="label" name="label"  value="<?php echo $this->item->value->label ?>" style="width: 260px;" />
                            <label><?php echo JText::_( "Linktype" ); ?></label>
                            <select class="form-select  form-select-sm" name="type" onchange="changeType();" style="width: 80px;">
								<?php
								$types = array("4"=>"n:1","2"=>"n:m","1"=>"1:n","3"=>"1:1");
								foreach ($types as $v => $type) {
									echo '<option value="'.$v.'"';
									if ($this->item->value->type==$v) echo "selected";
									echo '>'.$type.'</option>';
								}
								?>
                            </select>
                            <span class="icon-warning-circle text-danger mx-2" onmouseover="jQuery('#typenote').css('display','block');" onmouseout="jQuery('#typenote').css('display','none');" >&nbsp;</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="noticebox" id="typenote" style="width: 610px;"></div>
                        <div class="controls controls-row">
                            <label class="inline"><?php echo JText::_('Subtable'); ?></label>
                            <select name="table" class="form-select form-select-sm inline"  onchange="getFieldList();" style="width: 180px" ><?php
								foreach ($this->tables as $table) {
									echo "<option".(($table==$this->item->value->table) ? " selected" : "").">".$table."</option>";
								}
								?></select>
                            <label id="islabel" for="id_field" class=""></label>
                            <select name="id_field" id="id_field" class="form-select form-select-sm inline" style="width: 80px" >
                                <option><?php echo $this->item->value->id_field; ?></option>
                            </select>
                            <label class="inline"><?php echo JText::_( "Titlefield" ) ?></label>
                            <select name="name_field" id="name_field" class="form-select form-select-sm required inline" style="width: 180px;" >
                                <option><?php echo $this->item->value->name_field; ?></option>
                            </select>
                        </div>

                        <div id="idx_container" class="elementbox" style="display: none;">
                            <div style="display: block; text-align: center; font-weight: bold; margin-bottom: 5px; "><?php echo JText::_('Index Table'); ?></div>
                            <div style="display: block; text-align: center;">
                                <span><?php echo JText::_('Left Index'); ?></span>
                                <select name="idx_id1" class="form-select form-select-sm" style="width: 120px" >
                                    <option><?php echo $this->item->value->idx_id1; ?></option>
                                </select>
                                <select name="idx_table" class="form-select form-select-sm"  onchange="getIdxFieldList();" style="width: 220px;" >
									<?php
									foreach ($this->tables as $table) {
										echo "<option".(($table==$this->item->value->idx_table) ? " selected" : "").">".$table."</option>";
									}
									?></select>
                                <select name="idx_id2" class="form-select form-select-sm" style="width: 120px" >
                                    <option><?php echo $this->item->value->idx_id2; ?></option>
                                </select>
                                <span><?php echo JText::_('Right Index'); ?></span>
                            </div>
                        </div>
                        <div id="idx_container2" class="elementbox" style="display: none;">
                            <label><?php echo JText::_( "Table" ) ?></label>
                            <select name="idx_sub" class="form-select form-select-sm" style="width: 160px" >
								<?php
								if (!isset($this->item->value->idx_sub)) $this->item->value->idx_sub = null;
								foreach ($this->fields as $fname) {
									echo "<option".(($fname->Field==$this->item->value->idx_sub) ? " selected" : "").">".$fname->Field."</option>";
								}
								?>
                            </select>
                            <span><?php echo JText::_('Right Index'); ?></span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td id="config-document">
                        <label><?php echo JText::_( "Subtemplate Content" ); ?></label>
						<?php
						echo $editor->display('content', stripslashes($this->item->value->content), '100%', '350', '60', '8',false);
						?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="row">
                            <div class="col-sm-6">
                                <span><?php echo JText::_( 'Insert field' ); ?></span>
                                <select class="form-select form-select-sm" name="fields" id="fields" onChange="insertFieldValue(this);">
                                    <option value="">...</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <span><?php echo JText::_( 'Insert function' ); ?></span>
                                <select class="form-select form-select-sm" name="functions" onChange="insertFieldValue(this);">
                                    <option>...</option>
									<?php
									$flist = array('ifis|FIELD|[value]|[cond]','ifnot|FIELD','endif');
									foreach ($flist as $f) echo "<option>{joodb ".$f."}</option>\n";
									?>
                                </select>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>
<script type="text/javascript">

    var tdesc = new Array("<?php echo JText::_( "RELATION1")?>","<?php echo JText::_( "RELATION2")?>","<?php echo JText::_( "RELATION3")?>","<?php echo JText::_( "RELATION4");?>");

    /* Send the Form */
    Joomla.submitbutton = function (task) {

        if (task=="close") {
            self.close();
            return true;
        }
        var frm = document.subForm;

        // do field validation
        if (document.formvalidator.isValid(frm)) {
            if (typeof tinyMCE != "undefined") window.onbeforeunload = function() {};
            if (editors.length!==null)  editors['content'].toTextArea();
            var fdata = jQuery("#subForm").serialize();
            jQuery.ajax({
                type: "POST",
                url: "index.php",
                data: fdata,
                dataType: "json",
                success: function(data) {
                    opener.refreshSubitems();
                    self.close();
                },
                error: function() {
                    opener.refreshSubitems();
                    self.close();
                }
            });
        }
        return false;
    }

    /**
     *  Insert selected function at the editors cursor position
     */
    function insertFieldValue(el) {
        jInsertEditorText(el.options[el.selectedIndex].value,'content');
        el.selectedIndex=0;
    }

    /**
     * Get list of fields from index to selectors
     */
    function getIdxFieldList() {
        var frm = document.subForm;
        jQuery.ajaxSetup({ async: false });
        jQuery.getJSON("index.php?option=com_joodb&task=getfieldlist", {'table': frm.idx_table.value,'jbid': frm.jb_id.value },
            function(response) {
                if (response) {
                    v1 = frm.idx_id1.value; v2 = frm.idx_id2.value;
                    frm.idx_id1.options.length = 0;frm.idx_id2.options.length = 0;
                    response.fields.forEach(function(el){
                        frm.idx_id1.options[frm.idx_id1.options.length] = new Option(el.Field);
                        frm.idx_id2.options[frm.idx_id2.options.length] = new Option(el.Field);
                        if (v1==el.Field) frm.idx_id1.selectedIndex=frm.idx_id1.options.length-1;
                        if (v2==el.Field) frm.idx_id2.selectedIndex=frm.idx_id2.options.length-1;
                    });
                }
            });
    }

    /**
     * Get field list from selected table
     */
    function getFieldList() {
        var frm = document.subForm;
        jQuery.ajaxSetup({ async: false });
        jQuery.getJSON("index.php?option=com_joodb&task=getfieldlist", {'table': frm.table.value,'jbid': frm.jb_id.value },
            function(response) {
                if (response) {
                    svalue = frm.id_field.value; nvalue = frm.name_field.value;
                    frm.id_field.options.length = 0;
                    frm.name_field.options.length = 0;
                    frm.fields.options.length = 0;
                    frm.fields.options[0] = new Option("...","");
                    response.fields.forEach(function(el){
                        frm.fields.options[frm.fields.options.length] = new Option("{joodb field|"+el.Field+"}");
                        frm.id_field.options[frm.id_field.options.length] = new Option(el.Field);
                        frm.name_field.options[frm.name_field.options.length] = new Option(el.Field);
                        if (svalue==el.Field) frm.id_field.selectedIndex=frm.id_field.options.length-1;
                        if (nvalue==el.Field) frm.name_field.selectedIndex=frm.name_field.options.length-1;
                    });
                }
            });
    }

    /**
     * Change type of relation
     */
    function changeType() {
        jQuery('#typenote').html(tdesc[document.subForm.type.value-1]);
        if (document.subForm.type.value==2) {
            jQuery('#islabel').html( "<?php echo JText::_('Primary Index'); ?>");
            jQuery('#idx_container').css('display','block');
        } else if (document.subForm.type.value==4) {
            jQuery('#islabel').html( "<?php echo JText::_('Primary Index'); ?>");
            jQuery('#idx_container2').css('display','block');
            jQuery('#idx_container').css('display','none');
        } else {
            jQuery('#islabel').html( "<?php echo JText::_('Left Index'); ?>");
            jQuery('#idx_container').css('display','none');
            jQuery('#idx_container2').css('display','none');
        }
    }


    (function() {
        getFieldList();
        getIdxFieldList();
        changeType();
    })();
</script>


