function flickrTag_insertIntoEditor(h) {
        var win = window.opener ? window.opener : window.dialogArguments;

        if (! win)
		win = top;

        tinyMCE = win.tinyMCE;

        if(typeof tinyMCE != 'undefined' && tinyMCE.getInstanceById('content')) {
		tinyMCE.selectedInstance.getWin().focus();
		tinyMCE.execCommand('mceInsertContent', false, h);
        } else
		win.edInsertContent(win.edCanvas, h);

        if(! this.ID && typeof(this.cancelView) == "function")
		this.cancelView();

        return false;
}

