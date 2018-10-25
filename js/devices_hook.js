//from http://stackoverflow.com/a/26744533 loads url params to an array
var params={};window.location.search.replace(/[?&]+([^=&]+)=([^&]*)/gi,function(str,key,value){params[key] = value;});

var hook_set=0;
$(document).ready(function() {
        console.log('Asset injection');
	//admin/config.php?display=devices&tech_hardware=sip_generic
	if(params['display'] == 'devices' && 
		(params['tech_hardware'] == 'sip_generic' || params['tech_hardware'] == 'custom_custom')) {
		//avoid validity check
//<input type="text" name="deviceid" class="form-control " id="deviceid" size="35" tabindex="" value="">
		$(
		  '<input type="text" name="my_deviceid" class="form-control " id="my_deviceid" size="35" tabindex="" value="">'
		).insertBefore( $('#deviceid') );
		//set fake value for validity check
		$('#deviceid').hide().val('1212098612876');
		hook_set=1;
	}
	
	var acc;
	if(params['display'] == 'devices' && params['tech_hardware'] != undefined) {
		//remove this ???
		//acc=$('#devinfo_accountcode').val();	
		//if(acc.length > 0)
		//	$('#devinfo_accountcode').val(acc.replace(/^([^~]+~)(.*)$/,'$2'));

		//Set insercom disabled when device created
		$('#intercom0').prop("checked", false);
		$('#intercom1').prop("checked", true);
		//$('#intercom1').click();
	}

	//$(
	//  '<input type="hidden" name="devinfo_dialcontext" class="form-control " id="devinfo_dialcontext">'
	//).insertAfter( $('#devinfo_context') );

	if($('#devinfo_dialcontext').val() != "") {
		$('#devinfo_context').val($('#devinfo_dialcontext').val());
		$('#customcontext').val($('#devinfo_dialcontext').val());
	}

	
	$('#deviceuser').change(function() {
		descr=$('#description').val();
		//set default description
		if(descr.length == 0) {
			descr=$('#deviceuser').val().replace(/~/, ' ');
			$('#description').val(descr);
		}
		//console.log('Changed:' + $('#deviceuser').val() +' '+$('#description').val());
		//console.log('Help:' + $('#description-help').text());
	});
	var help=$('#description-help').text();
	$('#description-help').text(help+"\n"+'Leave empty to set from default user.');

});


$(document).submit(function() {
//return warnInvalid(theForm.emergency_cid, "Please enter a valid Emergency CID");
	if(hook_set ==1) {
		console.log('In my submit');
		var my_deviceid=$('#my_deviceid').val();
		if(!my_deviceid.match(/^[A-Z_0-9]+$/i)) {
			console.log('Invalid device id: '+my_deviceid);
			return warnInvalid($('#my_deviceid').get(), "Device id format: [A-Za-z_0-9]+");
		}

		$('#deviceid').val($('#my_deviceid').val());
		console.log('Val='+$('#deviceid').val());
	}
	if($('#deviceuser').val().match(/^[^~]+~/)) {
	   try {
		var centrex=$('#deviceuser').val().replace(/^([^~]+)~\s*(.*)$/,'$1');
		var acc=$('#devinfo_accountcode').val().trim();
		if(acc.length == 0) {
			$('#devinfo_accountcode').val(centrex);
		}
	   } catch(e) {
		console.log('Err1: '+e.name+":" + e.message + "\n");
	   }
	   try {
		//Set default call and pickup groups
		var group=$('#deviceuser').val().trim().replace(/^([^~]+)~\s*(.*)$/,'$1');
		if($('#devinfo_namedcallgroup').val().trim().length == 0)
			$('#devinfo_namedcallgroup').val(group);
		if($('#devinfo_namedpickupgroup').val().trim().length == 0)
			$('#devinfo_namedpickupgroup').val(group);
	   } catch(e) {
		console.log('Err2: '+e.name+":" + e.message + "\n");
	   }
	} 

	if($('#devinfo_context').val() != "") {
		$('#devinfo_dialcontext').val($('#devinfo_context').val());
		$('#devinfo_context').val('centrex-incoming');
	}

	//hook for mailbox
	if($('#devinfo_mailbox').val() == '') {
		$('#devinfo_mailbox').val($('#deviceid').val()+'@device');
		console.log(mailbox=$('#devinfo_mailbox').val());
	}
	//alert(centrex+' '+acc);
});
