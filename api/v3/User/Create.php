<?php

/**
 * User.Create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_user_Create_spec(&$spec) {
  $spec['contact_id']['api.required'] = 1;
   $spec['send_email_user']['api.required'] = 1;
}

/**
 * User.Create API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_user_Create($params) {
  if (array_key_exists('contact_id', $params) && strlen($params['contact_id']) > 0 ) {

   if( isset( $params['send_email_user'])  &&  strtolower( $params['send_email_user'] ) == 'true' ){
   $send_pwd_email_to_user = true; 
}else{
   $send_pwd_email_to_user = false; 
}


   $result = civicrm_api3('System', 'get', array(
  'sequential' => 1,
));
     
    $cms_str = $result['values'][0]['uf'];
    // print "<br>cms str: ".$cms_str;
    if( $cms_str == "Drupal"){
        $tmp_arr =  ft_create_drupal_user($params['contact_id'], $send_pwd_email_to_user);

    }else{
           throw new API_Exception(/*errorMessage*/ "This API does not yet do anything for this CMS (".$cms_str."). You should help implement this!", /*errorCode*/ 1234);
          

    }
/*
    $returnValues = array( // OK, return several data rows
      12 => array('id' => 12, 'name' => 'Twelve'),
      34 => array('id' => 34, 'name' => 'Thirty four'),
      56 => array('id' => 56, 'name' => 'Fifty six'),
    );
*/
    
   $returnValues = array($tmp_arr ); 

    // Spec: civicrm_api3_create_success($values = 1, $params = array(), $entity = NULL, $action = NULL)
    return civicrm_api3_create_success($returnValues, $params, 'NewEntity', 'NewAction');
  } else {
    throw new API_Exception(/*errorMessage*/ 'Everyone knows that the magicword is "sesame"', /*errorCode*/ 1234);
  }
}

function generate_password( $length = 15 ) {
	$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"."'0123456789``-=~!@#$%^&*()_+,./<>?;:[]{}\|'";
	$password = substr( str_shuffle( $chars ), 0, $length );
	return $password;
}


function verify_user2contact_mapping(&$account, &$cid , &$send_pwd_email_to_user){

$new_user_id = $account->uid; 
			    			
			    			// Make sure user is mapped to the correct contact ID, as its an issue if the 
					    	// email is used by more than one CiviCRM contact. 
					    	
			    			$sql_update = "UPDATE civicrm_uf_match set contact_id = '$cid' 
			    					WHERE uf_id = '$new_user_id' ";
			    					
			    			$params = array();
		
		   				$dao_update = CRM_Core_DAO::executeQuery($sql_update, $params);		
			    			$dao_update->free(); 
			    			
					    	
					    	
					    	
					    	



}



function ft_create_drupal_user(&$cid, &$send_pwd_email_to_user){

    $rtn_array = array('is_error' => '0'); 
    $status_str = "";
   
    
    if( strlen($cid) == 0){
        $rtn_array['is_error'] = 1;
        $rtn_array['error_message'] = "Error: Missing required parameter: cid" ;
        return $rtn_array ; 
    }

	
   $verification_only = "create" ;

	$user_count = 0; 
	$contacts_skipped = 0 ; 
	$users_emailed = 0; 
	$users_validated = 0; 
	
	$age_cutoff_date = "now()";
    		
         $tmp_sql_age =  "((date_format(".$age_cutoff_date.",'%Y') - date_format(c.birth_date,'%Y')) - 
    	          (date_format(".$age_cutoff_date.",'00-%m-%d') < date_format(c.birth_date,'00-%m-%d')))";
    		
    			$sql = "select ufm.uf_id, c.sort_name,  lower(c.first_name) as first_name, lower(c.last_name) as last_name , 
    				lower(e.email) as email, c.contact_type, c.is_deceased, c.is_deleted,
    				ufm.uf_id , ufe.id as uf_email_id, count(extras.contact_id) as count_contacts_with_shared_email,
    				$tmp_sql_age as age FROM
    				civicrm_contact c 
    				LEFT JOIN civicrm_email e ON c.id = e.contact_id AND e.is_primary =1
    				LEFT JOIN civicrm_uf_match ufm ON ufm.contact_id = c.id 
    				LEFT JOIN civicrm_uf_match ufe ON lower(ufe.uf_name) = lower(e.email)
    				LEFT JOIN civicrm_email extras ON extras.email = e.email AND extras.contact_id IS NOT NULL AND extras.contact_id <> c.id
    				WHERE c.id = $cid
    				GROUP BY c.id ";		
    				
			 $params = array();
     $dao = CRM_Core_DAO::executeQuery($sql, $params);

     if( $dao->fetch()){                                  	
		   		$first_name = $dao->first_name;
		   		$first_initial = substr ($first_name, 0, 1) ;
		   		$last_name = $dao->last_name; 
		   		$count_contacts_with_shared_email = $dao->count_contacts_with_shared_email; 
		   		
		   		$tmp_sort_name = $dao->sort_name;   
		   		// Need the following to validate that this contact is eligible for a user. 
		   		$contact_type = $dao->contact_type;
		   		$tmp_user_email = $dao->email; 
		   		$is_deceased = $dao->is_deceased; 
		   		$is_deleted = $dao->is_deleted; 
		   		$uf_id = $dao->uf_id;
		   		$uf_email_id = $dao->uf_email_id ; 
		   		$age = $dao->age; 
		   		
		   		
		   		// Deal with removing any invalid characters that cannot be in a user name.
		   		// only allow A-Z, a-z, or 0-9
		   		$first_name_cleaned = preg_replace("/[^A-Za-z0-9]/", '', $first_name);
		   		$last_name_cleaned =  preg_replace("/[^A-Za-z0-9]/", '', $last_name);
		   		
		   		
		   		// old pattern was first name, period, last name, then contact id. Such as sarah.gladstone2528
		   		//$tmp_user_name = $first_name_cleaned.".".$last_name_cleaned.$cid ; 
		   		
		   		// pattern is firstname, first letter of lastname, then contact id. SUch as sarahg2528 
		   		//
		   		$lastname_initial = substr( $last_name_cleaned, 0, 1);
		   		$tmp_user_name = $first_name_cleaned.$lastname_initial.$cid; 
		   		
		   	      //   print "<br>Inside dao if for: ".$tmp_user_name."<br>\n"; 
		   		
		   		// do various validations to see if this contact should get a user created or not. 
		   		$existing_user = user_load(array('name' => $tmp_user_name));
		   		
		   		$valid_user = true; 
		   		$inavlid_user_msg = ""; 
		   		if( $contact_type <> 'Individual'){
		   			$valid_user = false; 
		   			$inavlid_user_msg = "Skipped contact $tmp_sort_name (id: $cid) because its not an Individual.";
		   		}else if( $is_deceased == "1"){
		   			$valid_user = false; 
		   			$inavlid_user_msg = "Skipped contact $tmp_sort_name (id: $cid) because contact is deceased"; 
		   		}else if( $is_deleted == "1" ){
		   			$valid_user = false; 
		   			$inavlid_user_msg = "Skipped contact $tmp_sort_name (id: $cid) because contact is deleted";
		   		}else if( strlen($first_name) == 0){
		   			$valid_user = false; 
		   			$inavlid_user_msg = "Skipped contact $tmp_sort_name (id: $cid, age: $age) because first_name is empty";
		   		}else if( strlen($first_name_cleaned) == 0 ){
		   			$valid_user = false; 
		   			$inavlid_user_msg = "Skipped contact $tmp_sort_name (id: $cid, age: $age) because first_name does not include any valid characters, that is A-Z, a-z, or 0-9";
		   		}else if( strlen($last_name) == 0){
		   			$valid_user = false; 
		   			$inavlid_user_msg = "Skipped contact $tmp_sort_name (id: $cid, age: $age) because last_name is empty";
		   		}else if( strlen($last_name_cleaned) == 0 ){
		   			$valid_user = false; 
		   			$inavlid_user_msg = "Skipped contact $tmp_sort_name (id: $cid, age: $age) because last_name does not include any valid characters, that is A-Z, a-z, or 0-9";
		   		}else if( strlen($tmp_user_email) == 0){
		   			$valid_user = false; 
		   			$inavlid_user_msg = "Skipped contact $tmp_sort_name (id: $cid, age: $age) because email is empty";
		   		}else if( strlen($uf_id) > 0 ){
		   			$valid_user = false; 
		   			$inavlid_user_msg = "Skipped contact $tmp_sort_name (id: $cid, age: $age) because user already exists";
		   		}else if( strlen( $uf_email_id) > 0 ){
		   			$valid_user = false; 
		   			$inavlid_user_msg = "Skipped contact $tmp_sort_name (id: $cid, age: $age) because user already exists";
		   		}else if(filter_var($tmp_user_email, FILTER_VALIDATE_EMAIL) == false ){
		   			$valid_user = false; 
		   			$inavlid_user_msg = "Skipped contact $tmp_sort_name (id: $cid, age: $age) because email address is invalid";
		   		}else if( $count_contacts_with_shared_email <> "0" ){
		   			//$valid_user = false; 
		   			//$inavlid_user_msg = "Skipped contact $tmp_sort_name (id: $cid, age: $age) because email '$tmp_user_email' is used by $count_contacts_with_shared_email other contact(s)";
		   		
		   		}else if($existing_user->uid){
		   			$valid_user = false; 
		   			$inavlid_user_msg = "Skipped contact $tmp_sort_name (id: $cid, age: $age) because user name '$tmp_user_name' is taken";
		   		}
		   		
		   		
		   		if($valid_user) { 
		   			//print "<br>Valid for drupal user"; 
		   		
			   		$tmp_init = "mass_created-".$cid."-".$tmp_user_email; 
			   	 	 $tmp_user_pass = generate_password(); 
		    			 $userinfo = array(
					      'name' => $tmp_user_name ,
					      'init' => $tmp_init ,
					      'mail' => $tmp_user_email ,
					      'pass' => $tmp_user_pass ,
					      'status' => 1
					    );
					  

 
				           if( $verification_only == "create" ){	
					   // print "<br>Can create new user";
					    $account = user_save('', $userinfo);    
			                     
					    if (!$account){
					      $status[] = "Error saving user account for $tmp_sort_name (id: $cid, age: $age) ";
					      $contacts_skipped = $contacts_skipped + 1; 
					    }else{
			    			verify_user2contact_mapping($account, $cid, $send_pwd_email_to_user);
                                                $user_count = $user_count + 1; 
					    	$status[] = "User '$tmp_user_name' created for $tmp_sort_name (id: $cid, age: $age) "; 

                                                if($send_pwd_email_to_user){
					    	// Manually set the password so it appears in the e-mail.
			  				$account->password = $tmp_user_pass;
			 
							  // Send the e-mail through the user module.
							  $message = drupal_mail('user', 'register_admin_created', 
							  $tmp_user_email, NULL, array('account' => $account), 
							  variable_get('site_mail'));
							  
							  $users_emailed = $users_emailed + 1;
							   
							 //  print "<br><br>";
							 //  print_r( $message); 
						   }else{
						   	// $status[] = "No emails sent to users."; 
						   }
			   		    } 

			   		
               
			                  }else{
			   	             	$users_validated = $users_validated + 1; 
			   	 	        $status[] = "User '$tmp_user_name' is valid to be created for $tmp_sort_name (id: $cid, age: $age) "; 
			   	          }


			    }else{
                                //print "<br>Invalid user for drupal: ".$inavlid_user_msg;

			    	$status[] = $inavlid_user_msg;
		   		$contacts_skipped = $contacts_skipped + 1;
			    }

                         
		   	
		   	//}else{
                        //        print "<br>skipped contact id: ".$cid; 
		   	//	$status[] = "Skipped contact ID $cid";
		   	//	 $contacts_skipped = $contacts_skipped + 1; 
		   	//}
		   	
                        
 
                        }

		   	$dao->free();   
        	
    	//	}
    		

               
    		if( $contacts_skipped > 0 ){
	    	   $status[] = "Could not create $contacts_skipped users"; 
                   $rtn_array['skipped_count'] = $contacts_skipped;
	    	}
	    	if( $verification_only == "create" ){
	    	  if( $send_pwd_email_to_user ){
	    	      $status[] = "Emails sent to $users_emailed new user(s)"; 
                      $rtn_array['emailed_count'] = $users_emailed;
	    	   }
	    	     
	    	  $status[] = "Successfully created $user_count new user(s)";
                  $rtn_array['created_count'] = $user_count;
	    	}else{
	    	 // in Validation mode, just report on number of contacts validated
	    	    if( $users_validated == 0){
	    	    	$status[] = "Could NOT validate ANY contacts; ie system would not not generate any users for these contacts";	    	    	
	    	    }else{
	    		$status[] = "Successfully validated $users_validated contacts, ie system can generate users for these contacts";
	    		}

                   $rtn_array['validation_count'] = $users_validated;
	    	}
	    	    
       // }   
        
        
         $status_str = implode( '<br/>', $status );
         
         $status_str_plaintext = implode( '\n', $status); 

         $rtn_array['details'] =  $status_str_plaintext;
         
        /*
          $session         = CRM_Core_Session::singleton();
    	  $current_user_contactID       = $session->get('userID');
         
        $recipientContacts = array();
        $recipientContacts[] = array('contact_id' => $current_user_contactID);      
        $site_name = variable_get('site_name' ); 
        $subject =  $site_name." - Summary of users created from CRM action"; 
        $from = variable_get('site_mail'); 
        
        $html_summary_message = $status_str; 
        $text_summary_message = $status_str; 
        // send email of results to the user who ran this action
        list($sent, $activityId) = CRM_Activity_BAO_Activity::sendEmail(
      $recipientContacts,
      $subject,
      $text_summary_message,
      $html_summary_message,
      NULL,
      NULL,
      $from ,
      $attachments,
      $cc,
      $bcc,
      "",
      $additionalDetails
    );
   

    if ($sent) {
      
      $status_str =  $status_str."Summary of results were emailed to you, also recorded as an activity.";
    }
    */
    

   return $rtn_array; 

}