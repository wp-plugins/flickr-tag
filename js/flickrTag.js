function flickrTag_insertIntoEditor(h) {
	var win = window.dialogArguments || opener || parent || top;

        if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() ) {
                ed.focus();
                if (tinymce.isIE)
                        ed.selection.moveToBookmark(tinymce.EditorManager.activeEditor.windowManager.bookmark);

                if ( h.indexOf('[caption') === 0 ) {
                        if ( ed.plugins.wpeditimage )
                                h = ed.plugins.wpeditimage._do_shcode(h);
                } else if ( h.indexOf('[gallery') === 0 ) {
                        if ( ed.plugins.wpgallery )
                                h = ed.plugins.wpgallery._do_gallery(h);
                }

                ed.execCommand('mceInsertContent', false, h);

        } else if ( typeof edInsertContent == 'function' ) {
                edInsertContent(win.edCanvas, h);
        } else {
                jQuery( win.edCanvas ).val( jQuery( win.edCanvas ).val() + h );
        }
}

