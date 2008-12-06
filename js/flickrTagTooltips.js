jQuery(document).ready(function() {
	jQuery(".flickrTag_container img.flickr").each(function(i) {
		var e = jQuery(this);

		if(e.attr("title") != "") {
			var b = jQuery("body");

			if(! this.id)
				this.id = Math.ceil(Math.random() * 100000000);

			b.after("<div class='flickrTag_tooltip' id='tooltip_" + this.id + "'><p class='text'>" + e.attr("title") + "</p></div>");
			
			var n = jQuery("#tooltip_" + this.id);

			n.hide();
			n.css("left", e.offset().left + e.scrollLeft());
			n.css("top", e.offset().top + e.height() + e.scrollTop());
		}
	});

	jQuery(".flickrTag_container img.flickr").mouseover(function(event) {
		event.preventDefault();

		jQuery("#tooltip_" + this.id).show();
	});

	jQuery(".flickrTag_container img.flickr").mouseout(function(event) {
	   	event.preventDefault();

		jQuery("#tooltip_" + this.id).hide();
	});
});

