//Hide footer
$(document).ready(function() {
        setTimeout(function() {
                //$('#footer').hide("fast");
                //$('#footer_content').fadeOut();
                //$('#footer').css("bottom",'0px').css('height','0px');
                
/*
                $('#footer_content').fadeOut(400, function() {
			$('#footer').css('height','0px').css('bottom','0px');
			$('#action-bar').css('bottom', '10px');
	        });
*/
		$('#footer').animate({
			    height: 0,
			    bottom: 5
		}, 300, function() {
			$('#footer_content').hide();
			$('#action-bar').css('bottom', '10px');
		});

        }, 2000);

	//Hide <div class="alert alert-danger">
	$('.alert-danger').hide();
        console.log('== Hide function injected.');
});
