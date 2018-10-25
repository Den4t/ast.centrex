//from http://stackoverflow.com/a/26744533 loads url params to an array
var params={};window.location.search.replace(/[?&]+([^=&]+)=([^&]*)/gi,function(str,key,value){params[key] = value;});

var hook_set=0;
$(document).ready(function() {
        console.log('Asset injection');
	///admin/config.php?display=users&view=add
	if(params['display'] == 'users' && params['view'] == 'add') {
		//insert CENTREX field
		$(
		'<div class="element-container">'+
		'  <div class="row">'+
		'     <div class="col-md-12">'+
		'       <div class="row">'+
		'         <div class="form-group">'+
		'           <div class="col-md-4 control-label">'+
		'             <label for="centrex">Centrex group</label>'+
		'             <i class="fa fa-question-circle fpbx-help-icon" data-for="centrex">'+
		'	      </i>'+
		'           </div>'+
		'           <div class="col-md-8"><input type="text" name="centrex" class="form-control " id="centrex" size="35" tabindex="" value="">'+
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
		).insertBefore( $('input.extdisplay,input[type=text][name=extension],input[type=text][name=extdisplay],input[type=text][name=account]').parent().parent().parent().parent().parent() );


		//set linked user to none instead of cteate new
		$('#userman_assign').val('none');

		hook_set=1;

	}

	if(params['display'] == 'users' && params['extdisplay'] !== undefined) {
		centrex=$('#extdisplay').val().replace(/^([^~]+)~\s*(.*)$/,'$1');
		var descr=$('#newdid_name').val();
                if(descr.match(/^[^~]+~/)) {
                        descr=descr.replace(/^([^~]+)~\s*(.*)$/,'$2');
                        $('#newdid_name').val(descr);
                }
		hook_set=2;
	}
	
});


$(document).submit(function() {
      if(hook_set == 1) {
        console.log('In my submit');
        //check out centrex field
        if($('#centrex').val().trim().length == 0)
              return warnInvalid($('#centrex').get(0), 'Invalid Centrex name.');

	var full_ext=$('#centrex').val().trim()+'~'+$('#extension').val().trim();
	if(full_ext.length > 20) {
		 return warnInvalid($('#centrex').get(0),
				    'Full length of user (centrex~exten) mast be <= 20.\n'+
				    'You enter: '+full_ext+' ['+full_ext.length+']');
	}

        //$('#extension').val($('#centrex').val().trim()+'~'+$('#extension').val().trim());
        $('#extension').val(full_ext);
	if($('#newdid_name').val().length > 0)
		$('#newdid_name').val($('#centrex').val().trim()+'~ '+$('#newdid_name').val().trim());
        console.log('Val='+$('#extension').val());
        console.log('Val='+$('#newdid_name').val());
      }
      if(hook_set == 2) {
		$('#newdid_name').val(centrex+'~ '+$('#newdid_name').val());
		//alert($('#newdid_name').val());
      }

});
