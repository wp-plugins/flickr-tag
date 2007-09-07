// adapted from PalPal code by the same name.

function init() {
	var tt = [];
	var elems = YAHOO.util.Dom.getElementsByClassName("flickr_img", null, document);

	for(var i = 0; i < elems.length; i++){
		if(! elems[i].title)
			continue;

		tt[i] = new YAHOO.widget.Tooltip("autoTooltip" + i, { context: elems[i], 
			  				 	      preventoverlap: true,
                	                                              showdelay: 0,
//								      effect:{effect:YAHOO.widget.ContainerEffect.FADE,duration:0.25}, 
								      autodismissdelay: 5 * 1000
								     });

		tt[i].align(YAHOO.widget.Overlay.BOTTOM_LEFT, YAHOO.widget.Overlay.TOP_LEFT);
		tt[i].beforeShowEvent.subscribe(adjustPosition, { context: elems[i], tooltip: tt[i] });
	}
}

function adjustPosition(type, args, elems) {
	var tt = elems.tooltip.element;
	var context = elems.context;

	if(YAHOO.util.Dom.getY(tt) < YAHOO.util.Dom.getY(context)) { // above
		YAHOO.util.Dom.addClass(tt, "ttPosOver");
		YAHOO.util.Dom.removeClass(tt, "ttPosUnder");
				 
		YAHOO.util.Dom.setY(tt, YAHOO.util.Dom.getY(context) - tt.offsetHeight + 5);
	} else { // below
		YAHOO.util.Dom.addClass(tt, "ttPosUnder");
		YAHOO.util.Dom.removeClass(tt, "ttPosOver");
				
		YAHOO.util.Dom.setY(tt, YAHOO.util.Dom.getY(context) + context.offsetHeight - 5);
	}
			 
	// Set static position relative to context
	YAHOO.util.Dom.setX(tt, YAHOO.util.Dom.getX(context));
}

YAHOO.util.Event.addListener(window, "load", init);
