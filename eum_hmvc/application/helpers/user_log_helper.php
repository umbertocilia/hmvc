<?php

	function save_user_log($log_message,$user_id=-1)  
	{
		$CI =& get_instance();
        $ip=$CI->input->ip_address();
		$user_agent=$CI->input->user_agent();
        if($user_id==-1)
            $user_id=$CI->User->get_logged_in_user_info()->user_id;
 
        
        $log_data=array(
			'user_id'=> $user_id,
			'ip_address'=>$ip,
			'user_agent'=>$user_agent,
			'log_time'=>date('Y-m-d H:i:s'),
			'log_message'=>$log_message
			);
			  
	  
			  if ($CI->User->save_user_log($log_data)) {
			   
				  //Success
		        }
		        else {	 
			  //failure
		        } 

	}
	
	