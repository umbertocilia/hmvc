<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once ("Secure_area.php");
class Config extends Secure_area 
{
	public function __construct()
	{
		parent::__construct('config');
	}
	
	public function index()
	{
		$data['controller_name']=strtolower(get_class());
		$data['content_view']='config/config/manage';
		$this->load->module("template");
		$this->template->manage_tables_template($data);
	 
	}
	
	public function save()
	{
		$this->form_validation->set_rules('company', $this->lang->line('config_company'), 'required');
		$this->form_validation->set_rules('phone', $this->lang->line('config_phone'), 'required');
		$this->form_validation->set_rules('email', $this->lang->line('common_email'), 'required|valid_email');
		$this->form_validation->set_error_delimiters('<div class="error" ><h5 align="center" style="color: #DA1111;">', '</h5></div>');
		if ($this->form_validation->run() == FALSE) {
		    $this->index();
        }
        else {
		    $confic_data=array(
		    'company'=>$this->input->post('company'),
		    'phone'=>$this->input->post('phone'),
		    'email'=>$this->input->post('email'),
		    'website'=>$this->input->post('website'),
		    'pagination_limit'=>$this->input->post('pagination_limit'),
		    'throttle_limit'=>$this->input->post('throttle_limit'),
		    'throttle_timeout'=>$this->input->post('throttle_timeout'),
		    'language'=>$this->input->post('language'),
		    'reg_mail_send'=>$this->input->post('_status'),
		    'address'=>$this->input->post('address'),
		    'facebook_login'=>$this->input->post('fb_status'),
		    'facebook_appid'=>$this->input->post('facebook_appid'),
		    'facebook_secret'=>$this->input->post('facebook_secret'),
		    'twitter_login'=>$this->input->post('twitter_status'),
		    'twitter_appid'=>$this->input->post('twitter_appid'),
		    'twitter_secret'=>$this->input->post('twitter_secret'),
		    'google_login'=>$this->input->post('google_status'),
		    'google_appid'=>$this->input->post('google_appid'),
		    'google_secret'=>$this->input->post('google_secret')
		    );
 
		
		    if ($this->Appconfig->batch_save( $confic_data )) {
                 save_user_log($this->lang->line("user_log_app_config_update") );
			    echo json_encode(array('success'=>true,'message'=>$this->lang->line('config_saved_successfully')));
		    }
	    }
	}
	
	public function edit_logo( )
	{
		 $data=array();
		$this->load->view("config/config/edit_logo",$data);
	}
	
	public function save_logo()
	{  

			$imageData = file_get_contents('php://input');
			if (isset($imageData))
			{

			$filter_filename=substr($imageData,0, strpos($imageData, ","));
			$filteredData=substr($imageData, strrpos($imageData, ",")+1);
			$gamer_details = explode("_", $filter_filename);
			$targetDir = './uploads/logo';
			
	
			 $confic_data=array(
		    'app_logo'=>"logo.png"
		    );

			if ($this->Appconfig->batch_save( $confic_data )) {
				
				$filePath = $targetDir ;
				// Need to decode before saving since the data we received is already base64 encoded
				$unencodedData=base64_decode($filteredData);
				if ($this->is_writable_r('./uploads/')) {
				$fp = fopen($filePath.'.png', 'w' );
				fwrite( $fp, $unencodedData);
				fclose( $fp );
                    
                save_user_log($this->lang->line("user_log_app_avatar_updated") );    
				echo json_encode(array('success'=>true,'message'=>$this->lang->line('profiles_avatar_updated'),'new_image'=>"logo.png"));
				}
				else{
					 echo json_encode(array('success'=>false,'message'=>$this->lang->line('profiles_avatar_not_writable'))); //echo "Not writeable"; 
				}
				
				 
			}
		}
		
		
	}
	
	public function is_writable_r($dir)
	{
		if (is_dir($dir)) {
            if(is_writable($dir)){
               
                return true;    
              }else{
              return false;
        }
        
       }
	   else if(file_exists($dir)){
           return (is_writable($dir));
        
       }
	}
	
}
