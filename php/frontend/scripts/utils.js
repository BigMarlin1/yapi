jQuery(function($)
{
	$("table.data a.modal_nfo").colorbox({	// NFO modal
		href: function(){ return $(this).attr('href') +'&modal'; },
		title: function(){ return $(this).parent().parent().children('a.title').text(); },
		innerWidth:"800px", innerHeight:"90%", initialWidth:"800px", initialHeight:"90%", speed:0, opacity:0.7
	});
});
