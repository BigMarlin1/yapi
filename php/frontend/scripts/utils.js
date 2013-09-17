jQuery(function($)
{
	// NFO popup.
	$("table.data a.modal_nfo").colorbox({
		href: function(){ return $(this).attr('href') +'&modal'; },
		title: function(){ return $(this).parent().parent().children('a.title').text(); },
		innerWidth:"800px", innerHeight:"90%", initialWidth:"800px", initialHeight:"90%", speed:0, opacity:0.7
	});

	// file list tooltip
	$("a.modal_rar").each(function() {
		var c = $(this).attr('alt');
		var g = $(this).attr('id');
	  	$(this).qtip({
			content: {
			  title: {
				  text: 'rar archive contains...'
			  },
			  text: 'loading...', // The text to use whilst the AJAX request is loading
			  ajax: {
			     url: 'ajax_rarfilelist.php', // URL to the local file
			     type: 'GET', // POST or GET
			     data: { chash: c, group: g }, // Data to pass along with your request
			     success: function(data, status) {
			        this.set('content.text', data);
			     }
			  }
			},
			position: {
				my: 'top right',
				at: 'bottom left',
			},
			style: {
			    classes: 'qtip-dark qtip-shadow qtip-rounded',
				width: { max: 500 },
				tip: { // Now an object instead of a string
	        		corner: 'topRight', // We declare our corner within the object using the corner sub-option
	        		size: {
	                	x: 8, // Be careful that the x and y values refer to coordinates on screen, not height or width.
	                	y : 8 // Depending on which corner your tooltip is at, x and y could mean either height or width!
	             	}
				}
			}
		});
	});
});

// qtip growl
$(document).ready(function()
{
	window.createGrowl = function(tipText /*, tipTitle, persistent*/) {
		// Use the last visible jGrowl qtip as our positioning target
		var target = $('.qtip.jgrowl:visible:last');
		var tipTitle = 'Attention!';
		var persistent = false;

		// Create your jGrowl qTip...
		$(document.body).qtip({
			// Any content config you want here really.... go wild!
			content: {
				text: tipText,
				title: {
					text: tipTitle,
					button: true
					}
			},
			position: {
				my: 'top right', // Not really important...
				at: (target.length ? 'bottom' : 'top') + ' right', // If target is window use 'top right' instead of 'bottom right'
				target: target.length ? target : $(document.body), // Use our target declared above
				adjust: { y: 5 } // Add some vertical spacing
			},
			show: {
				event: false, // Don't show it on a regular event
				ready: true, // Show it when ready (rendered)
				effect: function() { $(this).stop(0,1).fadeIn(400); }, // Matches the hide effect

				// Custom option for use with the .get()/.set() API, awesome!
				persistent: persistent
			},
			hide: {
				event: false, // Don't hide it on a regular event
				effect: function(api) {
					// Do a regular fadeOut, but add some spice!
					$(this).stop(0,1).fadeOut(400).queue(function() {
						// Destroy this tooltip after fading out
						api.destroy();

						// Update positions
						updateGrowls();
					})
				}
			},
			style: {
				classes: 'jgrowl qtip-dark qtip-shadow qtip-rounded', // Some nice visual classes
				tip: false // No tips for this one (optional ofcourse)
			},
			events: {
				render: function(event, api) {
				// Trigger the timer (below) on render
				timer.call(api.elements.tooltip, event);
				}
			}
		})
		.removeData('qtip');
	};

	// Make it a window property see we can call it outside via updateGrowls() at any point
	window.updateGrowls = function() {
		// Loop over each jGrowl qTip
		var each = $('.qtip.jgrowl:not(:animated)');
		each.each(function(i) {
			var api = $(this).data('qtip');

			// Set the target option directly to prevent reposition() from being called twice.
			api.options.position.target = !i ? $(document.body) : each.eq(i - 1);
			api.set('position.at', (!i ? 'top' : 'bottom') + ' right');
		});
	};

	// Setup our timer function
	function timer(event) {
		var api = $(this).data('qtip'),
			lifespan = 5000; // 5 second lifespan

		// If persistent is set to true, don't do anything.
		if(api.get('show.persistent') === true) { return; }

		// Otherwise, start/clear the timer depending on event type
		clearTimeout(api.timer);
		if(event.type !== 'mouseover') {
			api.timer = setTimeout(api.hide, lifespan);
		}
	}

	// Utilise delegate so we don't have to rebind for every qTip!
	$(document).delegate('.qtip.jgrowl', 'mouseover mouseout', timer);
});
