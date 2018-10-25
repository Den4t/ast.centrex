//Hide footer
$(document).ready(function() {
        setTimeout(function() {
                //$('#footer').hide("fast");
                $('#footer').fadeOut();
                $('.masonry-container').fadeOut();
        }, 2000);
        console.log('== Hide function injected.');
});
