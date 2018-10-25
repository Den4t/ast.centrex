//from http://stackoverflow.com/a/26744533 loads url params to an array
var params={};window.location.search.replace(/[?&]+([^=&]+)=([^&]*)/gi,function(str,key,value){params[key] = value;});

var hook_set=0;
$(document).ready(function() {
        console.log('Asset injection');
	///admin/config.php?display=queues&view=form
	//add
	if(params['display'] == 'ringgroups' && params['view'] == 'form') {
	   if($('input[type=text][name=account]').length) {
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
		).insertBefore( $('input[type=text][name=account]').parent().parent().parent().parent().parent() );
		hook_set=1;
		//alert('Set hook to '+hook_set);
	   }

	   //edit
	   if(params['display'] == 'ringgroups' && params['view'] == 'form' &&
		$('input[type=hidden][name=account]').length) {
		saved_q=$('input[type=hidden][name=account]').val();
		$('input[type=hidden][name=account]').val('8888888888888888888888888');
		hook_set=2;
		//alert('Set hook to '+hook_set);
		//alert('"'+saved_q+'"');
	   }
	
	   //intercept onsubmit
	   //orig_on_submit=$('form[name=editGRP').attr('onsubmit').replace(/^\s*return\s+/, '');
	   //$('form[name=editGRP').removeAttr('onsubmit');
	   orig_on_submit=$('.fpbx-submit').attr('onsubmit').replace(/^\s*return\s+/, '');
	   $('.fpbx-submit').removeAttr('onsubmit');
	}
	
});

var orig_on_submit;
$(document).submit(function() {
      //alert('hook_set='+hook_set);

      //form filled corretly - call original onsubmit
      if(! eval(orig_on_submit)) 
		return false;

      if(hook_set==1) { //new 
	//check out centrex field
	if($('#centrex').val().trim().length == 0)
	      return warnInvalid($('#centrex').get(0), 'Invalid Centrex name.'); 

        console.log('In my submit 1');
        $('#account').val($('#centrex').val()+'~'+$('#account').val());
        console.log('Val='+$('#account').val());
      }

      if(hook_set==2) { //edit
        console.log('In my submit 2');
        $('input[type=hidden][name=account]').val(saved_q);
        console.log('Val='+$('input[type=hidden][name=account]').val());
      }
});
