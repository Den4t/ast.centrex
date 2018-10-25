//from http://stackoverflow.com/a/26744533 loads url params to an array
var params={};window.location.search.replace(/[?&]+([^=&]+)=([^&]*)/gi,function(str,key,value){params[key] = value;});

var hook_set=0;
$(document).ready(function() {
        console.log('Asset injection');
	///admin/config.php?display=miscapps
	//add
	if(params['display'] == 'miscapps' && 
		(params['action'] == 'edit' || params['action'] == 'add') ) {
		//insert CENTREX field
		$(
		'<div class="element-container">'+
		'  <div class="row">'+
		'     <div class="col-md-12">'+
		'       <div class="row">'+
		'         <div class="form-group">'+
		'           <div class="col-md-3">'+
		'             <label for="centrex">Centrex group</label>'+
		'             <i class="fa fa-question-circle fpbx-help-icon" data-for="centrex">'+
		'	      </i>'+
		'           </div>'+
		'           <div class="col-md-9"><input type="text" name="centrex" class="form-control " id="centrex" size="35" tabindex="" value="">'+
		'	    </div>'+
		'         </div>'+
		'        </div>'+
		'     </div>'+
		'  </div>'+
		'  <div class="row">'+
		'     <div class="col-md-12">'+
		'        <span id="centrex-help" class="help-block fpbx-help-block" style="">Centrex group name.</span>'+
		'     </div>'+
		'  </div>'+
		'</div>'
		).insertBefore( $('input[type=text][name=description]').parent().parent().parent().parent().parent() );
		hook_set=1;
		//alert('Set hook to '+hook_set);
		//alert('Set hook'+params['extdisplay']);

		var ext=$('#ext').val();
                var centrex='';
                if(ext.match(/^[^~]+~/)) {
                        centrex=ext.replace(/^([^~]+)~\s*(.*)$/,'$1');
                        ext=ext.replace(/^([^~]+)~\s*(.*)$/,'$2');
                        $('#ext').val(ext);
                        $('#centrex').val(centrex);
                }
	
		//intercept onsubmit
		//orig_on_submit=$('form[name=editGRP').attr('onsubmit').replace(/^\s*return\s+/, '');
		//$('form[name=editGRP').removeAttr('onsubmit');

		//if($('form').attr('onsubmit')) {
		//	orig_on_submit=$('form').attr('onsubmit').replace(/^\s*return\s+/, '');
		//	$('form').removeAttr('onsubmit');
		//}
	}
	
	
});

var orig_on_submit;
$(document).submit(function() {
      //alert('hook_set='+hook_set);

      //form filled corretly - call original onsubmit
//      if(! eval(orig_on_submit)) 
//		return false;

     if(hook_set==1) {
	//check out centrex field
	if($('#centrex').val().trim().length == 0)
	      return warnInvalid($('#centrex').get(0), 'Invalid Centrex name.'); 

        console.log('In my submit 1');
        $('#ext').val($('#centrex').val()+'~'+$('#ext').val());
        console.log('Val='+$('#ext').val());
      }

});
