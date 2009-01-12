function flickrTag_insertIntoEditor(h) {
	var win = window.dialogArguments || opener || parent || top;

        if ( typeof win.tinyMCE != 'undefined' && ( win.ed = win.tinyMCE.activeEditor ) && ! win.ed.isHidden() ) {
                win.ed.focus();

                if (win.tinymce.isIE)
                        win.ed.selection.moveToBookmark(win.tinymce.EditorManager.activeEditor.windowManager.bookmark);

                win.ed.execCommand('mceInsertContent', false, h);

        } else if ( typeof win.edInsertContent == 'function' ) {
                win.edInsertContent(win.edCanvas, h);

        } else {
                jQuery( win.edCanvas ).val( jQuery( win.edCanvas ).val() + h );
        }
}

