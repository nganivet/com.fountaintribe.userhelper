<?php

require_once 'CRM/Contact/Form/Task.php';


class fountaintribe_Contact_Form_Task_CreateUsers extends CRM_Contact_Form_Task{


/**
   * Build the form
   *
   * @access public
   * @return void
   */
  function buildQuickForm( ) {
    //$count = $this->getLabelRows(TRUE);
    CRM_Utils_System::setTitle( ts('Add Users for Contacts') );
    
    $count = ""; 
    $notify_options = array();
    
   // $notify_options[''] = "-- select --";
    $notify_options['1'] = "Yes";
   // $notify_options['0'] = "No";
            
     $this->add ( 'select', 'notify_user_of_password', ts('Email new users now?' ), $notify_options,  true);    
     
      $verify_options = array();
    
   // $verify_options[''] = "-- select --";
   // $verify_options['verify_only'] = "Verification Only, do NOT create anything";
    $verify_options['create'] = "Create Users";             
                     
     $this->add ( 'select', 'verification_only', ts('Create Users?' ), $verify_options,  true);    
                     
     $age_options = array();
     $age_options[''] = "-- select --";
     $age_options['any'] = "Any Age";
     $age_options['8'] = "8 or older";
     $age_options['9'] = "9 or older";
     $age_options['10'] = "10 or older";
     $age_options['11'] = "11 or older";
    
     
    $this->addDefaultButtons( ts('Create Users Now') );
    $this->assign('found_rows', $count);
  }



 /**
     * process the form after the input has been submitted and validated
     *
     * @access public
     * @return None
     */
    public function postProcess() {
    
    	$status = array(); 
    
    	
    	
    	if (!is_array($this->_contactIds) || empty($this->_contactIds)) {
     		 $this->_contactIds = array(0);
     		 $status[] = ts('No Contacts selected. Nothing to do.');
   	}else{
   		
   		
   		// Get user-selected choices from form. 
   		$params = $this->controller->exportValues( );
        	
        	$tmp_notify_user = CRM_Utils_Array::value( 'notify_user_of_password', $params, null );
        	
        	
        	
        	$verification_only = CRM_Utils_Array::value( 'verification_only', $params, null );
        	
        	if( $tmp_notify_user == "1"){
        		$send_pwd_email_to_user = "true";
        		 
        	}else{
        		$send_pwd_email_to_user = "false"; 
        	}
        	
	        
   		//
   		$user_input_valid = true; 
   		
   		
   		
   		
   		if( !($user_input_valid)){
   			$status = implode( '<br/>', $status );
        	   CRM_Core_Session::setStatus( $status );  
        	   return ;     
        	   
   		
   		}
   		
   		// At this point we have gathered all the user form input.
   	
	//$user_count = 0; 
	//$contacts_skipped = 0 ; 
	// $users_emailed = 0; 
	//$users_validated = 0; 
	
	
	//
	 $message = "";
 $tmp_msg = ""; 
$tried_count = 0;  
$created_count = 0;
$skipped_count = 0; 

	
	//$age_cutoff_date = "now()";
	
    		foreach($this->_contactIds as $cur_cid){
    		 
    		 
    		 	
			  $user_result = civicrm_api3('User', 'create', array(
			  'sequential' => 1,
			  'contact_id' => $cur_cid,
			  'send_email_user' => $send_pwd_email_to_user
			));
			
			 
			   $tmp_msg_arr = $user_result['values'][0];
			
			    if( $user_result['is_error']  == 0 ){
			          $tried_count = $tried_count + 1;
			          if( isset( $tmp_msg_arr['created_count'] ) && $tmp_msg_arr['created_count'] <> 0   ){
			               $created_count = $created_count + $tmp_msg_arr['created_count']; 
			          }
			
			          if( isset( $tmp_msg_arr['skipped_count']) && $tmp_msg_arr['skipped_count'] <> 0   ){
			               $skipped_count = $skipped_count + $tmp_msg_arr['skipped_count']; 
			          }
			
			
			    }else{
			
			    }
			  
			   $tmp_msg = $tmp_msg." ; ".$tmp_msg_arr['details']; 
			


 			
                  	
        	
    		}
    		$status[] = "\n\n";
    		if( $skipped_count > 0 ){
	    	   $status[] = "Could not create $skipped_count users"; 
	    	}
	    	if( $verification_only == "create" ){
	    	  if( $send_pwd_email_to_user ){
	    	   $status[] = "Emails sent to $created_count new user(s)"; 
	    	   }
	    	     
	    	  $status[] = "Successfully created $created_count new user(s)";
	    	}else{
	    	 // in Validation mode, just report on number of contacts validated
	    	    if( $users_validated == 0){
	    	    	$status[] = "Could NOT validate ANY contacts; ie system would not not generate any users for these contacts";
	    	    	
	    	    }else{
	    		$status[] = "Successfully validated $users_validated contacts, ie system can generate users for these contacts";
	    		}
	    	}
	    	    
        }   
        
         $status[]  = $message; 
         
         $status_str = implode( '<br/>', $status );
         
         $status_str_plaintext = implode( '\n', $status); 
         
          $session         = CRM_Core_Session::singleton();
    	  $current_user_contactID       = $session->get('userID');
         
         
         /*
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
      
      $status_str =  $status_str."<br>Summary of results were emailed to you, also recorded as an activity.";
    }
    
      */  
    	
        CRM_Core_Session::setStatus( $status_str );
    
    
    
    }
 

  
 

  



}






?>