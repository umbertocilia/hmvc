<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once ("Secure_area_common.php");
class Active_sessions_manage extends Secure_area_common 
{
	public function __construct()
	{
		parent::__construct('active_sessions');
	}
	
	public function index()
	{
		 
 
	}
	
    
     public function actve_sessions_save()
    {
         $user_id=$this->User->get_logged_in_user_info()->user_id;
         $actve_user_sessions_id=$this->session->userdata('actve_user_sessions_id');
         $ip_address=$this->input->ip_address();
         
             $active_session_data=array(
            'user_id'=> $user_id,
            'actve_user_sessions_id'=> $actve_user_sessions_id,
            'ip_address'=> $ip_address,
			'user_agent'=>$this->input->user_agent(),
			'log_time'=>date('Y-m-d H:i:s'),
			);
            $this->User->actve_sessions_save_data($active_session_data,$actve_user_sessions_id);
 
        
    }
    
	 
}
