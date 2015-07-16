jQuery(document).ready(function(){

	jQuery('.obs-comparer-container .language > .container > .toggle-container > a.toggle').click(function(){
		jQuery(this).parents('.container').first().siblings('.chapters').toggle();
		if(jQuery(this).html() == "▲")
			jQuery(this).html("▼");
		else
			jQuery(this).html("▲");
		return false;
	});

	jQuery('.obs-comparer-container .chapter > .container >  .toggle-container > a.toggle').click(function(){
		jQuery(this).parents('.container').first().siblings('.frames').toggle();
		if(jQuery(this).html() == "▲")
			jQuery(this).html("▼");
		else
			jQuery(this).html("▲");
		return false;
	});

	jQuery('.obs-comparer-container .frame > .container >  .toggle-container > a.toggle').click(function(){
		var sentences = jQuery(this).parents('.container').first().siblings('.sentences');

		sentences.toggle();

		console.log(sentences.find('img'));
		sentences.find('img').each(function(){
			if(jQuery(this).attr('tmpsrc')) {
				this.src = jQuery(this).attr('tmpsrc');
				jQuery(this).attr('tmpsrc','')
			}
		});

		if(jQuery(this).html() == "▲")
			jQuery(this).html("▼");
		else
			jQuery(this).html("▲");
		return false;
	});
});
