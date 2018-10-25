<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

//dbug(">>>>>>>>>>>>>>> $dirname ");
$modulename='centrex';

//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
require_once($dirname.'/modules/'.$modulename.'/libraries/runkit_hook.php');
require_once($dirname.'/modules/'.$modulename. '/libraries/namespace_hook.php');
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!



function centrex_hookGet_config($engine) {
        global $ext;
        global $astman;
	global $active_modules;

	$setvar=array('ext_set', 'ext_setvar');

        if($engine =='asterisk') {
	  //set centrex for all DIDs
	  $didlist=core_did_list();
	  if(is_array($didlist)){
		  foreach($didlist as $item) {
		      if (trim($item['destination']) == '') {
			  continue;
		      }
		      if(!preg_match('/^([^~]+~)/',trim($item['description']),$marr)) {
			  continue;
		      }
		      $centrex=$marr[1];
		      $exten = trim($item['extension']);
	  	      $priority = 1;
            	      $ext->splice('ext-did-0002', $exten, $priority,
				new ext_setvar('__CENTREX', $centrex),'',1);
		  }
	  }

	//////////  followme-check
	$context='followme-check';
        if($ext->section_exists($context)) {
		//Change pattern from [ _X. ] to [ _. ]
		$ext->_exts[$context][' _. '] = $ext->_exts[$context][' _X. '];
		unset($ext->_exts[$context][' _X. ']);

	}
	//////////  followme-sub
	$context='followme-sub';
        if($ext->section_exists($context)) {
		//Change pattern from [ _X. ] to [ _. ]
		$ext->_exts[$context][' _. '] = $ext->_exts[$context][' _X. '];
		unset($ext->_exts[$context][' _X. ']);

	}

	//////////  app-dialvm
	$context='app-dialvm';
        if($ext->section_exists($context)) {
		$fcc = new featurecode('voicemail', 'myvoicemail'); //*97
		$mvm_code=$fcc->getCode();
		unset($fcc);
		$fcc = new featurecode('voicemail', 'dialvoicemail'); //*98
		$dvm_code=$fcc->getCode();
		unset($fcc);
		
		//exten => _*98X.,hint,MWI:${EXTEN:3}@${DB(AMPUSER/${EXTEN:3}/voicemail)}
		//Change pattern from [ _*98X. ] to [ _*98. ]
		$code=' _'.$dvm_code.'X. ';
		if(isset($ext->_hints[$context][$code])) {
			$new_code=preg_replace('/X/','',$code);
			$ext->_hints[$context][$new_code] = $ext->_hints[$context][$code];
			unset($ext->_hints[$context][$code]);
		}

		//exten => *98,n(check),GotoIf($["${MAILBOX}" = ""]?hangup)
		$ext->splice($context, $dvm_code, 'check',
                        new ext_setvar('MAILBOX', '${CENTREX}${MAILBOX}'),'',1);

		//replace
		//exten => *98centrex~103,1,Goto(dvm${EXTEN:3},1)
		//to
		//exten => *98103,1,Goto(dvm${CENTREX}${EXTEN:3},1)
		$p_code=preg_replace('/\*/','\\\*',$dvm_code);
		foreach (array_keys($ext->_exts[$context]) as $exten) {
		   if(preg_match('/^ '.$p_code.'[^~]+~([0-9]+) $/', $exten, $num)) {
			   $ex=$ext->_exts[$context][$exten];
			   foreach ($ex as $dp) {
				$cmd=$dp['cmd'];
				if(get_class($dp['cmd']) == 'ext_goto' &&
					$dp['cmd']->context == '' &&
					$dp['cmd']->ext == 'dvm${EXTEN:3}') {
					$dp['cmd']->ext='dvm${CENTREX}${EXTEN:3}';
				}

			   }
			   $new_exten=' '.$dvm_code.$num[1].' ';
			   $ext->_exts[$context][$new_exten]=$ext->_exts[$context][$exten];
			   unset($ext->_exts[$context][$exten]);
		   }
                }
	}

	//////////  direct dial to vm
	foreach (array('ext-local', 'from-did-direct-ivr') as $context) {
		if($ext->section_exists($context)) {
		   foreach (array_keys($ext->_exts[$context]) as $exten) {
			//exten => *mks~103,1,Set(.....
			if(preg_match('/^ \*([^~]+~)(\d+) $/',$exten, $arr)) {
				//Change pattern from [ *xxx~ddd ] to [ xxx~*ddd ]
				$new_exten=' '.$arr[1].'*'.$arr[2].' ';
				$ext->_exts[$context][$new_exten] = $ext->_exts[$context][$exten];
				unset($ext->_exts[$context][$exten]);
			}
		   }

		}
	}

	//Blacklist hooks
	//[app-blacklist-check]
	//include => app-blacklist-check-custom
	//exten => s,1(check),GotoIf($["${BLACKLIST()}"="1"]?blacklisted)
	//exten => s,n,Set(CALLED_BLACKLIST=1)
	$section='app-blacklist-check';
	if($ext->section_exists($section)) {
		$ext->splice($section, 's', 0,
			new ext_setvar('SAVED_CALLERID_NUM', '${CALLERID(number)}'),'',1);
		$ext->splice($section, 's', '',
			new ext_setvar('CALLERID(number)','${CENTREX}${CALLERID(number)}'),'',1);
		$ext->splice($section, 's', '',
			new ext_setvar('CALLERID(number)','${SAVED_CALLERID_NUM}'),'',3);
	}
	//////////  Blacklist a number
	$context='app-blacklist-add';
        if($ext->section_exists($context)) {
		foreach ($ext->_exts[$context] as $ex) {
		   foreach ($ex as $dp) {
			//exten => 1,1,Set(DB(blacklist/${lastcaller})=1)
			if(in_array(get_class($dp['cmd']), $setvar) && 
			    $dp['cmd']->var == 'DB(blacklist/${lastcaller})') {
				$dp['cmd']->var='DB(blacklist/${CENTREX}${lastcaller})';
			}
		   }
		}
	}
	//////////  Blacklist the last caller
	$context='app-blacklist-last';
        if($ext->section_exists($context)) {
		foreach ($ext->_exts[$context] as $ex) {
		   foreach ($ex as $dp) {
			$cmd=$dp['cmd'];
			//exten => 1,1,Set(DB(blacklist/${lastcaller})=1)
			if(in_array(get_class($cmd), $setvar) && 
			    $cmd->var == 'DB(blacklist/${lastcaller})') {
				$cmd->var='DB(blacklist/${CENTREX}${lastcaller})';
			}
		   }
		}
	}

	//////////  Remove a number from the blacklist
	$context='app-blacklist-remove';
        if($ext->section_exists($context)) {
		foreach ($ext->_exts[$context] as $ex) {
		   foreach ($ex as $dp) {
			//exten => 1,1,Noop(Deleting: blacklist/${blacknr} ${DB_DELETE(blacklist/${blacknr})})
			//if(get_class($dp['cmd']) == 'ext_noop' && $dp['cmd']->data == 'Deleting: blacklist/${blacknr} ${DB_DELETE(blacklist/${blacknr})}') {
			//this noop really ext_dbdel class !!!
			if(get_class($dp['cmd']) == 'ext_dbdel' && $dp['cmd']->data == 'blacklist/${blacknr}' ) {
				$dp['cmd']->data='blacklist/${CENTREX}${blacknr}';
			}
		   }
		}	
	}







	  //IVR hooks
	  //exten => _X.,1,GotoIf($[${DIALPLAN_EXISTS(from-did-direct,${EXTEN},1)} = 0]?i,1)
	  //exten => _X.,n,Macro(blkvm-clr,)
	  //exten => _X.,n,Set(__NODEST=)
	  //exten => _X.,n,Goto(from-did-direct,${EXTEN},1)
	  if($ext->section_exists('from-did-direct-ivr')) {
		foreach ($ext->_exts['from-did-direct-ivr'] as $exten) {
		     foreach ($exten as $dp) {
			if(get_class($dp['cmd']) == 'ext_gotoif' && 
				$dp['cmd']->condition == '$[${DIALPLAN_EXISTS(from-did-direct,${EXTEN},1)} = 0]') {
				$dp['cmd']->condition='$[${DIALPLAN_EXISTS(from-did-direct,${CENTREX}${EXTEN},1)} = 0]';
			}
			if(get_class($dp['cmd']) == 'ext_goto' && 
				$dp['cmd']->context == 'from-did-direct') {
				$dp['cmd']->ext='${CENTREX}${EXTEN}';
			}
 		     }
		}	
	  }

	  //macro-user-callerid hooks
	  //change
	  //exten => s,n,Set(AMPUSERCID=${IF($["${ARG2}" != "EXTERNAL" & "${DB_EXISTS(AMPUSER/${AMPUSER}/cidnum)}" = "1"]?${DB_RESULT}:${AMPUSER})})
	  // to	
	  //exten => s,n,Set(AMPUSERCID=${IF($["${ARG2}" != "EXTERNAL" & "${DB_EXISTS(AMPUSER/${AMPUSER}/cidnum)}" = "1"]?${IF($["${REGEX("[~]" ${DB_RESULT})}" = "1"]?${CUT(DB_RESULT,~,2-)}:${DB_RESULT})}:${IF($["${REGEX("[~]" ${AMPUSER})}" = "1"]?${CUT(AMPUSER,~,2-)}:${AMPUSER})})})
	  /////////////////////////////////////////////////////////////////
	  //exten => s,n(cnum),Set(CDR(cnum)=${CALLERID(num)})
	  //to
	  //exten => s,n(cnum),Set(CDR(cnum)=${IF($["${AMPUSER}" = ""]?${CALLERID(num)}:${AMPUSER})})

	  if($ext->section_exists('macro-user-callerid')) {
		foreach ($ext->_exts['macro-user-callerid'] as $exten) {
		     foreach ($exten as $dp) {
			$class=get_class($dp['cmd']);
			if(in_array($class, $setvar)) {
			   if($dp['cmd']->var == 'CDR(cnum)' &&
			      $dp['cmd']->value=='${CALLERID(num)}') {
			      $dp['cmd']->value='${IF($["${AMPUSER}" = ""]?${CALLERID(num)}:${AMPUSER})}';
			      continue;
			   }
			   if($dp['cmd']->var == 'AMPUSERCID') {
			      $dp['cmd']->value='${IF($["${ARG2}" != "EXTERNAL" & "${DB_EXISTS(AMPUSER/${AMPUSER}/cidnum)}" = "1"]?${IF($["${REGEX("[~]" ${DB_RESULT})}" = "1"]?${CUT(DB_RESULT,~,2-)}:${DB_RESULT})}:${IF($["${REGEX("[~]" ${AMPUSER})}" = "1"]?${CUT(AMPUSER,~,2-)}:${AMPUSER})})}';
			      continue;
			   }
			}
 		     }
		}
	  }

	  //macro-outbount-callerid hooks
	  //exten => s,n,GotoIf($["foo${DB(AMPUSER/${REALCALLERIDNUM}/device)}" = "foo"]?bypass)
	  //replace REALCALLERIDNUM to AMPUSER
	  if($ext->section_exists('macro-outbound-callerid')) {
		foreach ($ext->_exts['macro-outbound-callerid'] as $exten) {
		     foreach ($exten as $dp) {
			if(get_class($dp['cmd']) == 'ext_gotoif' && 
				preg_match('/^\$\["foo\$\{DB\(AMPUSER\/\$\{REALCALLERIDNUM\}\/device\)\}"/',$dp['cmd']->condition, $arr)) {
				$dp['cmd']->condition=str_replace('REALCALLERIDNUM','AMPUSER',$dp['cmd']->condition);
			}
 		     }
		}
	  }


	//Featurecodes hooks
	//app-speakextennum
	$context='app-speakextennum';
	if($ext->section_exists($context)) {
		foreach ($ext->_exts[$context] as $exten) {	
		  foreach ($exten as $dp) {
		     //exten => fr,n,SayDigits(${AMPUSER})
		     $class=get_class($dp['cmd']);
		     if($class == 'ext_saydigits') {
			$dp['cmd']->data='SayDigits(${CUT(AMPUSER,~,2-)})';
		     }
		  }
		}
	}





	 // exten => _*72.,n,Set(DB(CF/${fromext})=${toext})
/*
[5] => Array
                (
                    [basetag] => n
                    [tag] => 
                    [addpri] => 
                    [cmd] => ext_setvar Object
                        (
                            [var] => DB(CF/${fromext})
                            [value] => ${toext}
                        )

                )
*/
//


	///////////////// Call Forward All Activate
	$context='app-cf-on';
        if($ext->section_exists($context)) {
		foreach ($ext->_exts[$context] as $ex) {
		   foreach ($ex as $dp) {
			//exten => *72,n,Set(DB(CF/${fromext})=${toext})
			if(in_array(get_class($dp['cmd']), $setvar) && $dp['cmd']->var == 'DB(CF/${fromext})') {
				//$dp['cmd']->value='${CENTREX}${toext}';
				$dp['cmd']->value='${toext}';
			}
			//exten => ja,n,SayDigits(${fromext})
			if(get_class($dp['cmd']) == 'ext_saydigits' && $dp['cmd']->data == '${fromext}') {
				$dp['cmd']->data='${CUT(fromext,~,${FIELDQTY(fromext,~)})}';
			}
		   }
		}	
	}	
	///////////////// Call Forward All Deactivate
	$context='app-cf-off';
        if($ext->section_exists($context)) {
		foreach ($ext->_exts[$context] as $ex) {
		   foreach ($ex as $dp) {
			//exten => ja,n,SayDigits(${fromext})
			if(get_class($dp['cmd']) == 'ext_saydigits' && $dp['cmd']->data == '${fromext}') {
				$dp['cmd']->data='${CUT(fromext,~,${FIELDQTY(fromext,~)})}';
			}
			//exten => _*73.,n,Set(fromext=${EXTEN:3})
			if(in_array(get_class($dp['cmd']), $setvar) && $dp['cmd']->var == 'fromext' &&
					$dp['cmd']->value=='${EXTEN:3}') {
				$dp['cmd']->value='${CENTREX}${EXTEN:3}';
			}
			
		   }
		}	
	}	


	/////////////// Call Forward All Prompting Activate
	$context='app-cf-prompting-on';
        if($ext->section_exists($context)) {
		foreach ($ext->_exts[$context] as $ex) {
		   foreach ($ex as $dp) {
			//exten => *93,n,Set(fromext=${IF($["foo${fromext}"="foo"]?${AMPUSER}:${fromext})})
			if(get_class($dp['cmd']) == 'ext_setvar' && $dp['cmd']->var == 'fromext' &&
			   $dp['cmd']->value=='${IF($["foo${fromext}"="foo"]?${AMPUSER}:${fromext})}') {
				$dp['cmd']->value='${IF($["foo${fromext}"="foo"]?${AMPUSER}:${CENTREX}${fromext})}';
			}

			//exten => *93,n,Set(DB(CF/${fromext})=${toext})
			if(get_class($dp['cmd']) == 'ext_setvar' && $dp['cmd']->var == 'DB(CF/${fromext})') {
				//$dp['cmd']->value='${CENTREX}${toext}';
				$dp['cmd']->value='${toext}';
			}

			//exten => ja,n,SayDigits(${fromext})
			if(get_class($dp['cmd']) == 'ext_saydigits' && $dp['cmd']->data == '${fromext}') {
				$dp['cmd']->data='${CUT(fromext,~,${FIELDQTY(fromext,~)})}';
			}
		   }
		}	
	}	

	/////////////// Call Forward All Prompting Deactivate
	$context='app-cf-off-any';
        if($ext->section_exists($context)) {
		foreach ($ext->_exts[$context] as $ex) {
		   foreach ($ex as $dp) {
			//exten => *93,n,Set(fromext=${IF($["foo${fromext}"="foo"]?${AMPUSER}:${fromext})})
			if(get_class($dp['cmd']) == 'ext_setvar' && $dp['cmd']->var == 'fromext' &&
			   $dp['cmd']->value=='${IF($["foo${fromext}"="foo"]?${AMPUSER}:${fromext})}') {
				$dp['cmd']->value='${IF($["foo${fromext}"="foo"]?${AMPUSER}:${CENTREX}${fromext})}';
			}

			//exten => ja,n,SayDigits(${fromext})
			if(get_class($dp['cmd']) == 'ext_saydigits' && $dp['cmd']->data == '${fromext}') {
				$dp['cmd']->data='${CUT(fromext,~,${FIELDQTY(fromext,~)})}';
			}
		   }
		}	
	}	


	///////////// Call Forward Busy Activate
	$context='app-cf-busy-on';
        if($ext->section_exists($context)) {
		foreach ($ext->_exts[$context] as $ex) {
		   foreach ($ex as $dp) {
			//exten => en,n,Set(DB(CFB/${fromext})=${toext})
			if(get_class($dp['cmd']) == 'ext_setvar' && $dp['cmd']->var == 'DB(CFB/${fromext})') {
				$dp['cmd']->var='DB(CFB/${CENTREX}${CUT(fromext,~,${FIELDQTY(fromext,~)})})';
				//$dp['cmd']->value='${CENTREX}${toext}';
				$dp['cmd']->value='${toext}';
			}

			//exten => ja,n,SayDigits(${fromext})
			if(get_class($dp['cmd']) == 'ext_saydigits' && $dp['cmd']->data == '${fromext}') {
				$dp['cmd']->data='${CUT(fromext,~,${FIELDQTY(fromext,~)})}';
			}
		   }
		}	
	}	
	
	///////////// Call Forward Busy Deactivate
	$context='app-cf-busy-off';
        if($ext->section_exists($context)) {
		foreach ($ext->_exts[$context] as $ex) {
		   foreach ($ex as $dp) {
			//exten => _*91.,n,Set(fromext=${EXTEN:3})
			if(get_class($dp['cmd']) == 'ext_setvar' && $dp['cmd']->var == 'fromext' &&
			     $dp['cmd']->value=='${EXTEN:3}') {
				$dp['cmd']->value='${CENTREX}${EXTEN:3}';
			}

			//exten => ja,n,SayDigits(${fromext})
			if(get_class($dp['cmd']) == 'ext_saydigits' && $dp['cmd']->data == '${fromext}') {
				$dp['cmd']->data='${CUT(fromext,~,${FIELDQTY(fromext,~)})}';
			}
		   }
		}	
	}	
	

	///////////// Call Forward Busy Prompting Activate
	$context='app-cf-busy-prompting-on';
        if($ext->section_exists($context)) {
		foreach ($ext->_exts[$context] as $ex) {
		   foreach ($ex as $dp) {
			//exten => en,n,Set(DB(CFB/${fromext})=${toext})
			if(get_class($dp['cmd']) == 'ext_setvar' && $dp['cmd']->var == 'DB(CFB/${fromext})') {
				$dp['cmd']->var='DB(CFB/${CENTREX}${CUT(fromext,~,${FIELDQTY(fromext,~)})})';
				//$dp['cmd']->value='${CENTREX}${toext}';
				$dp['cmd']->value='${toext}';
			}

			//exten => ja,n,SayDigits(${fromext})
			if(get_class($dp['cmd']) == 'ext_saydigits' && $dp['cmd']->data == '${fromext}') {
				$dp['cmd']->data='${CUT(fromext,~,${FIELDQTY(fromext,~)})}';
			}
		   }
		}	
	}	
	
	///////////// Call Forward Busy Prompting Deactivate
	$context='app-cf-busy-off-any';
        if($ext->section_exists($context)) {
		foreach ($ext->_exts[$context] as $ex) {
		   foreach ($ex as $dp) {
			//exten => *93,n,Set(fromext=${IF($["foo${fromext}"="foo"]?${AMPUSER}:${fromext})})
			if(get_class($dp['cmd']) == 'ext_setvar' && $dp['cmd']->var == 'fromext' &&
			   $dp['cmd']->value=='${IF($["foo${fromext}"="foo"]?${AMPUSER}:${fromext})}') {
				$dp['cmd']->value='${IF($["foo${fromext}"="foo"]?${AMPUSER}:${CENTREX}${fromext})}';
			}

			//exten => ja,n,SayDigits(${fromext})
			if(get_class($dp['cmd']) == 'ext_saydigits' && $dp['cmd']->data == '${fromext}') {
				$dp['cmd']->data='${CUT(fromext,~,${FIELDQTY(fromext,~)})}';
			}
		   }
		}	
	}	
	

	///////////// Call Forward No Answer/Unavailable Activate
	$context='app-cf-unavailable-on';
        if($ext->section_exists($context)) {
		foreach ($ext->_exts[$context] as $ex) {
		   foreach ($ex as $dp) {
			//exten => en,n,Set(DB(CFU/${fromext})=${toext})
			if(get_class($dp['cmd']) == 'ext_setvar' && $dp['cmd']->var == 'DB(CFU/${fromext})') {
				$dp['cmd']->var='DB(CFU/${CENTREX}${CUT(fromext,~,${FIELDQTY(fromext,~)})})';
				//$dp['cmd']->value='${CENTREX}${toext}';
				$dp['cmd']->value='${toext}';
			}

			//exten => ja,n,SayDigits(${fromext})
			if(get_class($dp['cmd']) == 'ext_saydigits' && $dp['cmd']->data == '${fromext}') {
				$dp['cmd']->data='${CUT(fromext,~,${FIELDQTY(fromext,~)})}';
			}
		   }
		}	
	}	

	///////////// Call Forward No Answer/Unavailable Deactivate
	$context='app-cf-unavailable-off';
        if($ext->section_exists($context)) {
		foreach ($ext->_exts[$context] as $ex) {
		   foreach ($ex as $dp) {
			//exten => _*91.,n,Set(fromext=${EXTEN:3})
			if(get_class($dp['cmd']) == 'ext_setvar' && $dp['cmd']->var == 'fromext' &&
			     $dp['cmd']->value=='${EXTEN:3}') {
				$dp['cmd']->value='${CENTREX}${EXTEN:3}';
			}

			//exten => ja,n,SayDigits(${fromext})
			if(get_class($dp['cmd']) == 'ext_saydigits' && $dp['cmd']->data == '${fromext}') {
				$dp['cmd']->data='${CUT(fromext,~,${FIELDQTY(fromext,~)})}';
			}
		   }
		}	
	}	

	///////////////// Call Forward Toggle
	$context='app-cf-toggle';
        if($ext->section_exists($context)) {
		foreach ($ext->_exts[$context] as $ex) {
		   foreach ($ex as $dp) {
			//exten => *72,n,Set(DB(CF/${fromext})=${toext})
			if(get_class($dp['cmd']) == 'ext_setvar' && $dp['cmd']->var == 'DB(CF/${fromext})') {
				//$dp['cmd']->value='${CENTREX}${toext}';
				$dp['cmd']->value='${toext}';
			}
			//exten => ja,n,SayDigits(${fromext})
			if(get_class($dp['cmd']) == 'ext_saydigits' && $dp['cmd']->data == '${fromext}') {
				$dp['cmd']->data='${CUT(fromext,~,${FIELDQTY(fromext,~)})}';
			}
		   }
		}	
	}	

	//////////  app-pickup
	$section='app-pickup';
        if($ext->section_exists($section)) {
		//exten => _**.,n,Pickup(${EXTEN:2}&${EXTEN:2}@PICKUPMARK) - direct pickup
		//exten => _***80.,n,Pickup(${EXTEN:5}&${EXTEN:5}@PICKUPMARK) - intercom
	  $fcc = new featurecode('paging', 'intercom-prefix'); //*80
	  $icode=$fcc->getCode();
	  unset($fcc);
	  $fcc = new featurecode('core', 'pickup'); //**
	  $dcode=$fcc->getCode();
	  unset($fcc);

	  if(isset($icode) and isset($dcode)) {	
	    foreach (array($dcode.$icode, $dcode) as $fcode) { 
		$flen=strlen($fcode);
		$ex_str=' _'.$fcode.'. ';
		if(isset($ext->_exts[$section][$ex_str])) {
		        $ext->splice($section, '_'.$fcode.'.', 0,
			new ext_gotoif('$["${DIALPLAN_EXISTS(${CONTEXT},${CENTREX}'.$fcode.'${EXTEN:'.$flen.'},1)}"="1"]',
					'${CONTEXT},${CENTREX}'.$fcode.'${EXTEN:'.$flen.'},1'));	

			$data='${EXTEN:'.$flen.'}&${EXTEN:'.$flen.'}@PICKUPMARK';	
			foreach ($ext->_exts[$section][$ex_str] as $dp) {
			   if(get_class($dp['cmd']) == 'ext_pickup' && $dp['cmd']->data == $data) {
				$dp['cmd']->data='${CENTREX}${EXTEN:'.$flen.'}&${CENTREX}${EXTEN:'.$flen.'}@PICKUPMARK';
			   }
			}
		}


		$regexp='/^ '.$fcode.'([^~]+~)(\d+) $/';
		$regexp=preg_replace('/\*/','\\\*',$regexp);
		foreach (array_keys($ext->_exts[$section]) as $exten) {
                        //exten => **mks~103,1,.....
                        if(preg_match($regexp,$exten, $arr)) {
				//change from-internal-xfer to $TRANSFER_CONTEXT}
				//change from-internal to $DIAL_CONTEXT}
				foreach ($ext->_exts[$section][$exten] as $dp) {
				   if(get_class($dp['cmd']) == 'ext_pickup') {
					$dp['cmd']->data=preg_replace('/@from-internal-xfer/','@${TRANSFER_CONTEXT}',$dp['cmd']->data);
					$dp['cmd']->data=preg_replace('/@from-internal/','@${DIAL_CONTEXT}',$dp['cmd']->data);
				   }
				}

                                //Change pattern from [ **xxx~ddd ] to [ xxx~**ddd ]
                                $new_exten=' '.$arr[1].$fcode.$arr[2].' ';
                                $ext->_exts[$section][$new_exten] = $ext->_exts[$section][$exten];
                                unset($ext->_exts[$section][$exten]);
                        }
		}
	   }
	  }
	}
		

//dbug("##############################\n");
//dbug($ext->_exts['app-cf-on'][' _*72. ']);


	//Add section after outbound-allroutes fnd before app-blackhole
	//spliceInclude($section, $splicesection, $splicecomment, $incsection, $comment='')
	$section='centrex-prefix';
	$ext->spliceInclude('from-internal-additional', 'app-blaclhole', '', $section, $comment='add centrex prefix');
	//add($section, $extension, $tag, $command,
	//app-blaclhole body
	$extension='_[0-9*#].';
//	$ext->add($section, $extension, '', new ext_gotoif('$["${WAS_HERE}" == "1" || "${CENTREX}" == ""]', 'abort'));
//	$ext->add($section, $extension, '', new ext_setvar('__WAS_HERE', '1'));
	$ext->add($section, $extension, '', new ext_gotoif('$["${CENTREX}" = ""]', 'abort'));
	$ext->add($section, $extension, '', new ext_gotoif('$["${CENTREX}" = "none"]', 'abort'));
	$ext->add($section, $extension, '', new ext_gotoif('$["${DIALPLAN_EXISTS(${CONTEXT},${CENTREX}${EXTEN},1)}" = "0"]', 'abort'));
	//$ext->add($section, $extension, '', new ext_setvar('CALLERID(number)', '${CUT(CALLERID(number),~,1)}'));
//	$ext->add($section, $extension, '', new ext_goto(1, '${CENTREX}${EXTEN}' , 'from-internal'));
	$ext->add($section, $extension, '', new ext_goto(1, '${CENTREX}${EXTEN}' , '${CONTEXT}'));
	$ext->add($section, $extension, 'abort', new ext_noop('=== Cant route: EXTEN=${EXTEN} CONTEXT=${CONTEXT} CENTREX=${CENTREX}'));
	$ext->add($section, $extension, '', new ext_gotoif('$["${DIALPLAN_EXISTS(${CONTEXT}_bad-number)}"="1"]', 
					'${CONTEXT}_bad-number,${EXTEN},1'));
	$ext->add($section, $extension, '', new ext_gotoif('$["${DIALPLAN_EXISTS(bad-number)}"="1"]', 
					'bad-number,${EXTEN},1'));
	$ext->add($section, $extension, '', new ext_goto(1, 'congestion' , 'app-blackhole'));


	//incoming context for centrex devices
	$section='centrex-incoming';
	$extension='_[0-9*#].';
	$ext->add($section, $extension, '', new ext_gotoif('$["${CENTREX}" = ""]', 'abort'));
	$ext->add($section, $extension, '', new ext_gotoif('$["${CENTREX}" = "none"]', 'abort'));
	$ext->add($section, $extension, '', new ext_gotoif('$["${DIAL_CONTEXT}" = ""]', 'abort'));
	$ext->add($section, $extension, '', new ext_goto(1, '${EXTEN}' , '${DIAL_CONTEXT}'));
	$ext->add($section, $extension, 'abort', new ext_noop('=== Cant route: EXTEN=${EXTEN} CONTEXT=${DIAL_CONTEXT} CENTREX=${CENTREX}'));
	$ext->add($section, $extension, '', new ext_goto(1, 'congestion' , 'app-blackhole'));
	//Not allow direct dal to centrex ext
	$extension='_.';
	$ext->add($section, $extension, '', new ext_noop('=== Direct dial with centrex prefix not allowed: ${EXTEN}'));
	$ext->add($section, $extension, '', new ext_goto(1, 'congestion' , 'app-blackhole'));

	
	//Generate subscription contexts
	$cgroups=array();
	foreach (core_devices_get_user_mappings() as $id => $device) {
		if(isset($device['user']) && preg_match('/^([^~]+)~/',$device['user'], $arr)) {
			$cgroups[$arr[1]]=1;
		}
	}

	$chints=array();
	if(is_array($ext->_hints)){
           foreach ($ext->_hints as $section => $hints) {
		if(in_array($section, array('ext-local','ext-meetme'))) {
                   foreach ($hints as $exten => $hint_val) {
		      foreach($cgroups as $key => $dummy) {
			//exten => mks~101,hint,SIP/84957483333&Custom:DNDmks~101,CustomPresence:mks~101
			if(preg_match('/^ '.$key.'~([0-9*#]+) $/', $exten, $arr)) { 
				$chints[$key.'-hints'][$arr[1]]=$hint_val;
                        }
		      }
		   }
		}
	   }
	}

	//meetme context hints *87
	$fcc = new featurecode('conferences', 'conf_status');
	$code=$fcc->getCode();
	unset($fcc);
	if($code != '') {
		$section='ext-meetme';
		if(isset($ext->_hints[$section][' '.$code.' '])) {
		      foreach(array_keys($cgroups) as $key) {
			$chints[$key.'-hints'][$code]=$ext->_hints[$section][' '.$code.' '];
		      }
		}
	}

	foreach ($chints as $c=>$hints) {
	  $ext->add($c, 's', '', new ext_Noop("$c hints"));
	  foreach ($hints as $exten=>$hint_val) {
	    foreach ($hint_val as $val) {
		$ext->addHint($c, $exten, $val);
	    }
	  }
	}

	} //if($engine =='asterisk') {
}

/**
 * Get Configuration
 */
function centrex_get_config () {
	global $core_conf,$db;

	$devices=core_devices_get_user_mappings();
	foreach ($devices as $id => $device) {
		//dbug(">>>> id=$id");
		//dbug($device);
		/*
		Array
		(
		    [0] => 84957483333
		    [1] => port2
		    [id] => 84957483333
		    [tech] => sip
		    [dial] => SIP/84957483333
		    [devicetype] => fixed
		    [user] => mks~101
		    [description] => 101
		    [emergency_cid] => 
		    [vmcontext] => novm
		)
		*/

		$device_info = core_devices_get($id);
		//dbug($device_info);
		/*
		Array
		(
		    [id] => gswave
		    [tech] => sip
		    [dial] => SIP/gswave
		    [devicetype] => fixed
		    [user] => mks~103
		    [description] => Dennis
		    [emergency_cid] => 
		    [account] => gswave
		    [accountcode] => mks~dennis
		    [allow] => 
		    [avpf] => no
		    [callerid] => Dennis <gswave>
		    [canreinvite] => no
		    [context] => Custom_1
		    [defaultuser] => 
		    [deny] => 0.0.0.0/0.0.0.0
		    [disallow] => 
		    [dtmfmode] => rfc2833
		    [encryption] => no
		    [force_avp] => no
		    [host] => dynamic
		    [icesupport] => no
		    [namedcallgroup] => 1
		    [namedpickupgroup] => 
		    [nat] => no
		    [permit] => 0.0.0.0/0.0.0.0
		    [port] => 5060
		    [qualify] => yes
		    [qualifyfreq] => 60
		    [secret] => qzfxpaxn
		    [secret_origional] => qzfxpaxn
		    [sendrpid] => pai
		    [sessiontimers] => accept
		    [sipdriver] => chan_sip
		    [transport] => udp,tcp,tls
		    [trustrpid] => yes
		    [type] => friend
		    [videosupport] => inherit
		)
		*/
			


		if ($device['devicetype'] == 'fixed' && isset($device['user'])) {

			$core_conf->addSipAdditional($device['id'],"setvar","__DIAL_CONTEXT=".$device_info['dialcontext']);

			$centrex=preg_replace('/^([^~]+~).*$/','$1', $device['user']);
			$core_conf->addSipAdditional($device['id'],"setvar","__CENTREX=".$centrex);
			if (!empty($device_info)) {
			   $core_conf->addSipAdditional($device['id'],
				//"setvar","__TRANSFER_CONTEXT=centrex-incoming");
				"setvar","__TRANSFER_CONTEXT=".$device_info['dialcontext']);
			}
			$scontext=rtrim($centrex,'~').'-hints';
			$core_conf->addSipAdditional($device['id'],'subscribecontext',$scontext);

			//Set description field in sip.conf
			$core_conf->addSipAdditional($device['id'],"description",$device['user']);
		}
	}
}

/**
 * Intercept Config Page Init for pagename
 * @param  {string} $pagename The FreePBX Pagename
 * @return {[type]}           [description]
 */
function centrex_configpageinit($pagename) {
	global $db;

	if ($pagename=="extensions" || $pagename=="devices") {
		// Only display if we are on the extensions page
		centrex_applyhooks();
	}


	//hook for hide footer
	$js = file_get_contents('js/footer_hook.js', true);
	print '<script>' . $js . '</script>';

	//hook for floating scrollbar on asteriskinfo page
	if($_REQUEST['display']=='asteriskinfo') {
		$js = file_get_contents('js/floating_scrollbar.js', true);
		print '<script>' . $js . '</script>';
	}

}

function centrex_applyhooks() {
	global $currentcomponent;

	$currentcomponent->addguifunc('centrex_configpageload');

}


function centrex_configpageload() {
	global $currentcomponent, $endpoint, $db;
	global $amp_conf;

dbug("=============BEGIN _REQUEST\n");
dbug($_REQUEST);


	//add notification if current mode is not device and user
        if ($amp_conf['AMPEXTENSIONS'] != "deviceanduser") {
		$nt = \notifications::create();
		$rawname = 'centrex';
		$uid = 'deviceuser';
		if(!$nt->exists($rawname, $uid)) {
			$nt->add_warning($rawname, $uid, _("User & Devices Mode is not \"deviceanduser\""), 
				_("Centrex module require \"deviceanduser\" mode, use \"Advanced settings\" to switch."),
				'', true, true);
		}
	}


	if ($_REQUEST["display"] == 'devices' && 
		($_REQUEST["tech_hardware"] == 'sip_generic' || isset($_REQUEST["extdisplay"]))) {
		//add new hidden field
		$dial_context='from-internal';
		if(isset($_REQUEST["extdisplay"])) {
			$deviceInfo = core_devices_get($_REQUEST["extdisplay"]);	
			$dial_context = $deviceInfo['dialcontext'];
			//dbug($deviceInfo);
		}	
		$currentcomponent->addguielem('_top', new gui_hidden('devinfo_dialcontext', $dial_context));
	}


}


function __centrex_hook_queues() {
dbug("====== acvopt_hook_queues");
$output = "This totally replaces the page<br>\n";
return $output;
}
