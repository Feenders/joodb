// Initialize Codemirror

var editors = Array();
var active_editor=false;
var ew = 0;

(function() {
    "use strict";
    var WRAP_CLASS = "CodeMirror-activeline";
    var BACK_CLASS = "CodeMirror-activeline-background";

    CodeMirror.defineOption("styleActiveLine", false, function(cm, val, old) {
        var prev = old && old != CodeMirror.Init;
        if (val && !prev) {
            updateActiveLine(cm);
            cm.on("cursorActivity", updateActiveLine);
        } else if (!val && prev) {
            cm.off("cursorActivity", updateActiveLine);
            clearActiveLine(cm);
            delete cm.state.activeLine;
        }
    });

    function clearActiveLine(cm) {
        if ("activeLine" in cm.state) {
            cm.removeLineClass(cm.state.activeLine, "wrap", WRAP_CLASS);
            cm.removeLineClass(cm.state.activeLine, "background", BACK_CLASS);
        }
    }

    function updateActiveLine(cm) {
        var line = cm.getLineHandle(cm.getCursor().line);
        if (cm.state.activeLine == line) return;
        clearActiveLine(cm);
        cm.addLineClass(line, "wrap", WRAP_CLASS);
        cm.addLineClass(line, "background", BACK_CLASS);
        cm.state.activeLine = line;
    }
})();


// special mode - mark joodb brackets
CodeMirror.defineMode("mustache", function(config, parserConfig) {
    var mustacheOverlay = {
        token: function(stream, state) {
            if (stream.match("{")) {
                while ((ch = stream.next()) != null)
                    if (ch == "}") break;
                return "mustache";
            }
            while (stream.next() != null && !stream.match("{", false)) {}
            return null;
        }
    };
    return CodeMirror.overlayMode(CodeMirror.getMode(config, parserConfig.backdrop || "text/html"), mustacheOverlay);
});


(function ($) {

    $(document).ready(function () {
        $('.cmeditor').each(function() {
            editors[this.name] = CodeMirror.fromTextArea(this, {
                mode: "mustache",
                lineNumbers: true,
                lineWrapping: true,
                styleActiveLine: true,
                onFocus: function(){ active_editor=this },
                onBlur: function(){ active_editor=false }
            });
        });

        $('#myTabTabs > li > a').on('shown.bs.tab', function() {
            ea = $('#'+this.getAttribute("aria-controls")+" .cmeditor:first").attr('name');
            if (ea != undefined) editors[ea].refresh();
        });

    });
}) (jQuery);

function toggleFullscreenEditing()  {
    if (!active_editor) return;

    var editorDiv = jQuery('.CodeMirror-scroll:first');
    if (!editorDiv.hasClass('fullscreen')) {
        toggleFullscreenEditing.beforeFullscreen = { height: editorDiv.height(), width: editorDiv.width() }
        editorDiv.addClass('fullscreen');
        editorDiv.height('100%');
        editorDiv.width('100%');
        editor.refresh();
    }
    else {
        editorDiv.removeClass('fullscreen');
        editorDiv.height(toggleFullscreenEditing.beforeFullscreen.height);
        editorDiv.width(toggleFullscreenEditing.beforeFullscreen.width);
        editor.refresh();
    }
}


function jInsertEditorText(text, editor) {
    editors[editor].replaceSelection(text);
}

