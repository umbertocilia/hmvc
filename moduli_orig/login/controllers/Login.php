<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Login extends EUM_Controller 
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('cookie');
		$this->load->helper('social');
	}
	
	public function index($errorMessage="")
	{
		$config=load_social();//loading social application keys
		$this->load->library('HybridAuthLib',$config);//library for social authentication
	    if ($user_id=$this->verifyCookie()) { 
            if($this->User->check_active($user_id)==1){
			    $this->session->set_userdata('user_id', $user_id) ;
			    
				if ($this->User->has_permission("dashboards",$user_id)) {
		            redirect('dashboard/dashboards');
		        }
		        else{
			        redirect('dashboard/home');
			    }
			}
		}
		  
	    if ($this->User->is_logged_in()) {
		    if ($this->User->has_permission("dashboards",$this->User->get_logged_in_user_info()->user_id)) {
		        redirect('dashboard/dashboards');
		    }
		    else {
			    redirect('dashboard/home');
			}
		}
		else {
			$this->form_validation->set_rules('username', $this->lang->line('login_username'), 'required|callback_login_check');
			$this->form_validation->set_rules('password', $this->lang->line('login_password'), 'required');
    	    $this->form_validation->set_error_delimiters('<div class="error" ><h5 align="center" style="color: #DA1111;">', '</h5></div>');
			
			if ($this->form_validation->run($this) == FALSE){
				
			        $this->hybridauthlib->logoutAllProviders();
					
					// load all permitted services
		            $login_data['providers'] = $this->hybridauthlib->getProviders();
		            $login_data['error_messages'] = $errorMessage;

		        foreach($login_data['providers'] as $provider=>$d) {
			        if ($d['connected'] == 1) {
				    $login_data['providers'][$provider]['user_profile'] = $this->hybridauthlib->authenticate($provider)->getUserProfile();
			        }
		        }
		
			$login_data['content_view']='login/login';
			$this->load->module("template");
		    $this->template->login_template($login_data);
			}
			else {
                save_user_log($this->lang->line("user_log_login"));
			    if ($this->User->has_permission("dashboards",$this->User->get_logged_in_user_info()->user_id)) {
		            redirect('dashboard/dashboards');
		        }
				else {
				    redirect('dashboard/home');
				}
			}
		}  
	}
    
    public function page_routes()
    {
        $query=$this->Page->get_default_page();
        if ($query->num_rows()==1) {
            $result=$query->row();
            $page_url=$result->page_url;
            $this->load->helper('url');
            redirect('/pages/common_pages/page/'.$page_url, 'refresh');
 
		}
		else {
			  $this->index();
		}
       
    }

	 
    public function get_dob_date($data,$selected_month="",$selected_day="",$selected_year="")
	{
		$months = array();
	    for ($k=1;$k<=12;$k++) {
		    $cur_month = mktime(0, 0, 0, $k, 1, 2000);
		    $months[date("m", $cur_month)] = date("M",$cur_month);
	    }
	    $days = array();
	    for ($k=1;$k<=31;$k++) {
		    $cur_day = mktime(0, 0, 0, 1, $k, 2000);
		    $days[date('d',$cur_day)] = date('j',$cur_day);
	    }
	    $years = array();
	    for ($k=0;$k<70;$k++) {
            $y=date("Y");
	        $years[$y-$k] = $y-$k;
	    }
	    $months['00'] = "00";
	    $days['00'] = "00";
  	    $years['00'] = "0000";
	    $data['dmonths'] = $months;
	    $data['ddays'] = $days;
	    $data['dyears'] = $years;
	    if ($selected_month=="") {
	        $data['dselected_month']=date('n');
		    $data['dselected_day']=date('d');
		    $data['dselected_year']=date('Y');
		}
		else {
		    $data['dselected_month']=$selected_month;
		    $data['dselected_day']=$selected_day;
		    $data['dselected_year']=$selected_year;
		}
		return $data;
	}
	
	public function forget_password()
	{
		$data['content_view']='login/forget';
		$this->load->module("template");
		$this->template->login_template($data);
	}
		
	public function user_register()
	{
	    if ($this->User->is_logged_in()) {
		    if ($this->User->has_permission("dashboards",$this->User->get_logged_in_user_info()->user_id)) {
		        redirect('dashboard/dashboards');
		    }
		    else {
			    redirect('dashboard/home');
			}
		}
		else {
			$data=array();
		    $this->load->helper('captcha');
            // numeric random number for captcha
            $random_number = substr(number_format(time() * rand(),0,'',''),0,6);
            // setting up captcha config
            $vals = array(
                'word' => $random_number,
                'img_path' => './captcha/',
                'img_url' => base_url().'captcha/',
                'img_width' => 140,
                'img_height' => 32,
                'expiration' => 7200
            );
            $data['captcha'] = create_captcha($vals);
            $this->session->set_userdata('captchaWord',$data['captcha']['word']);
	
	        $d_o_b="0000-00-00";
            $d_o_b= date("Y-m-d", strtotime($d_o_b));
	        $split_date = explode("-", $d_o_b);
	        $year = $split_date[0];
	        $month = $split_date[1];
	        $day = $split_date[2];
	        $data=$this->get_dob_date($data,$month,$day,$year);
			$data['content_view']='login/user_register';
			$this->load->module("template");
		    $this->template->registration_template($data);
		}
	}
	public function social_login($provider)
	{
		$config=load_social();
		$this->load->library('HybridAuthLib',$config);

		try
		{

			if ($this->hybridauthlib->providerEnabled($provider))
			{
				$service = $this->hybridauthlib->authenticate($provider);

				if ($service->isUserConnected())
				{

					$user_profile = $service->getUserProfile();
					$data['user_profile'] = $user_profile;
                    $date_of_registration= date("Y-m-d");
					$identifier=$user_profile->identifier;
					$userinfo_data = array(
		                    'social_provider'=>$provider,
		                    'social_identifier'=>$user_profile->identifier,
		                    'social_profileURL'=>$user_profile->profileURL,
		                    'first_name'=>$user_profile->firstName,
		                    'last_name'=>$user_profile->lastName,
							'email'=>$user_profile->email,
		                    'phone_number'=>$user_profile->phone,
		                    'city'=>$user_profile->city,
		                    'register_date'=>$date_of_registration,
		                    'address'=>$user_profile->address
		            );
					$permission_data = array ( 0 =>'profiles');
					
					$userlog_data=array(
			            'active'=>0
			        );
				
					$email=$user_profile->email;
					$usermailcount=$this->User->check_email($email,-1);
					$social_profile=$this->User->check_social($provider,$identifier);
					if($usermailcount==0 &&  $social_profile==0){
						//Create a new user
				        if($this->User->save($userinfo_data,$userlog_data,$permission_data,-1)){
							$targetDir = './uploads/'.$userinfo_data['user_id'].'.jpg';
							$updated_userinfo_data = array(
		                    'profile_image'=>$userinfo_data['user_id'].'.jpg'
		                     );
							 //Save profile image
							if(file_put_contents($targetDir, file_get_contents($user_profile->photoURL))){
								$this->User->update_user_info($updated_userinfo_data,$userinfo_data['user_id']);
							}
                            if($provider=="Twitter")
                            save_user_log($this->lang->line("user_log_twitter_registration")." ".$user_profile->firstName ,$userlog_data['user_id']);
                            else 
                            save_user_log($this->lang->line("user_log_google_registration")." ".$user_profile->firstName ,$userlog_data['user_id']);
							
							$this->session->set_userdata('user_id', $userlog_data['user_id']) ;
						    $this->index();
						}
						
			        }
					else{  //User already exist
						if($email!="") {
							$user_id=$this->User->get_user_id($email);
						}
						else {
							$user_id=$this->User->get_user_id_byIdentifier($identifier);
						}
						if($this->User->check_active($user_id)==1){
							$this->session->set_userdata('user_id', $user_id) ;	
                            if($provider=="Twitter")
                            save_user_log($this->lang->line("user_log_twitter_login")." ".$this->User->get_info($user_id)->first_name,$user_id);
                            else 
                            save_user_log($this->lang->line("user_log_google_login")." ".$this->User->get_info($user_id)->first_name,$user_id);
                            $this->index();							
						}
						else {
							$this->index($this->lang->line('error_inactive_account'));
						}
					}
					
				}
				else // Cannot authenticate user
				{
					show_error('Cannot authenticate user');
				}
			}
			else // This service is not enabled.
			{
				log_message('error', 'controllers.HAuth.login: This provider is not enabled ('.$provider.')');
				show_404($_SERVER['REQUEST_URI']);
			}
		}
		catch(Exception $e)
		{
			$error = 'Unexpected error';
			switch($e->getCode())
			{
				case 0 : $error = 'Unspecified error.'; break;
				case 1 : $error = 'Hybriauth configuration error.'; break;
				case 2 : $error = 'Provider not properly configured.'; break;
				case 3 : $error = 'Unknown or disabled provider.'; break;
				case 4 : $error = 'Missing provider application credentials.'; break;
				case 5 : log_message('debug', 'controllers.HAuth.login: Authentification failed. The user has canceled the authentication or the provider refused the connection.');
				         //redirect();
				         if (isset($service))
				         {
				         	log_message('debug', 'controllers.HAuth.login: logging out from service.');
				         	$service->logout();
				         }
				         show_error('User has cancelled the authentication or the provider refused the connection.');
				         break;
				case 6 : $error = 'User profile request failed. Most likely the user is not connected to the provider and he should to authenticate again.';
				         break;
				case 7 : $error = 'User not connected to the provider.';
				         break;
			}

			if (isset($service))
			{
				$service->logout();
			}

			log_message('error', 'controllers.HAuth.login: '.$error);
			show_error('Error authenticating user.');
		}
	}
        

	public function facebook_login()
    {
		require_once (APPPATH . "third_party/hybridauth/Hybrid/thirdparty/Facebook/php-graph-sdk-5.0.0/src/Facebook/autoload.php");
 
		$this->api = new Facebook\ Facebook(['app_id' => $this->config->item('facebook_appid'), 'app_secret' => $this->config->item('facebook_secret'), 'default_graph_version' => 'v2.5', ]);
		$accessToken = "";
		$helper = $this->api->getRedirectLoginHelper();
		try{
			$accessToken = $helper->getAccessToken();
			}

		catch(FacebookExceptionsFacebookResponseException $e){
			// When Graph returns an error
			echo 'Graph returned an error: ' . $e->getMessage();
			exit;
			}

		catch(FacebookExceptionsFacebookSDKException $e){
			// When validation fails or other local issues
			echo 'Facebook SDK returned an error: ' . $e->getMessage();
			exit;
			}

		if (isset($accessToken)){
			// Logged in!
			$_SESSION['facebook_access_token'] = (string)$accessToken;
			// Now you can redirect to another page and use the
			// access token from $_SESSION['facebook_access_token']
			}

		$this->api->setDefaultAccessToken((string)$accessToken);
		try{
			$response = $this->api->get('/me?fields=id,link,name,email,first_name,last_name,hometown,picture.width(200).height(200)', $accessToken);
			$userNode = $response->getGraphUser();
			}

		catch(FacebookExceptionsFacebookResponseException $e){
			// When Graph returns an error
			echo 'Graph returned an error: ' . $e->getMessage();
			exit;
			}

		catch(FacebookExceptionsFacebookSDKException $e){
			// When validation fails or other local issues
			echo 'Facebook SDK returned an error: ' . $e->getMessage();
			exit;
			}

		$user = $response->getGraphUser();
		$identifier = $user['id'];  
		$date_of_registration = date("Y-m-d");
		$userinfo_data = array(
			'social_provider' => "Facebook",
			'social_identifier' => (isset($user['id'])) ? $user['id'] : "",
			'social_profileURL' => (isset($user['link'])) ? $user['link'] : "",
			'first_name' => (isset($user['first_name'])) ? $user['first_name'] : "",
			'last_name' => (isset($user['last_name'])) ? $user['last_name'] : "",
			'email' => (isset($user['email'])) ? $user["email"] : "",
			'register_date' => $date_of_registration,
			'address' => (isset($user['hometown'])) ? $user['hometown']['name'] : ""
		);
		$permission_data = array(
			0 => 'profiles'
		);
		$userlog_data = array(
			'active' => 0
		);
		$email = (isset($user['email'])) ? $user["email"] : "";
        $usermailcount = $this->User->check_email($email, -1);
		$social_profile = $this->User->check_social("Facebook", $identifier);
		if ($usermailcount == 0 && $social_profile == 0) {

			// Create a new user
			if ($this->User->save($userinfo_data, $userlog_data, $permission_data, -1))
				{
				$targetDir = './uploads/' . $userinfo_data['user_id'] . '.jpg';
				$updated_userinfo_data = array(
					'profile_image' => $userinfo_data['user_id'] . '.jpg'
				);

				// Save profile image
				if (file_put_contents($targetDir, file_get_contents(urldecode($user['picture']['url']))))
					{
					$this->User->update_user_info($updated_userinfo_data, $userinfo_data['user_id']);
					}

				$this->session->set_userdata('user_id', $userlog_data['user_id']);
                $first_name=(isset($user['first_name'])) ? $user['first_name'] : "";
                save_user_log($this->lang->line("user_log_facebook_registration")." ".$first_name ,$userinfo_data['user_id']);
				$this->index();
				}
			}
		  else { //User already exist
			if ($email != "") {
				$user_id = $this->User->get_user_id($email);
				}
			  else {
				$user_id = $this->User->get_user_id_byIdentifier($identifier);
				}

			if ($this->User->check_active($user_id) == 1) {
				$this->session->set_userdata('user_id', $user_id);
                save_user_log($this->lang->line("user_log_facebook_login")." ".$this->User->get_info($user_id)->first_name,$user_id);
				$this->index();
				}
			  else {
				$this->index($this->lang->line('error_inactive_account'));
				}
			}
    }
 
    
	public function endpoint()
	{

		if ($_SERVER['REQUEST_METHOD'] === 'GET') {
			$_GET = $_REQUEST;
		}

		require_once APPPATH.'/third_party/hybridauth/index.php';

	}
	 
	public function check_captcha($str)
	{  
        $word = $this->session->userdata('captchaWord'); 
        if (strcmp(strtoupper($str),strtoupper($word)) == 0){
            return true;
        }
        else { 
            $this->form_validation->set_message('check_captcha', 'Please enter correct words!');
            return false;
        }
    }
	
	public function save_user($user_id)
	{
	    $this->load->library('bcrypt');
		$this->load->library('form_validation');
		//server side validation
		$this->form_validation->set_rules('first_name', $this->lang->line('profiles_first_name'), 'required');
		$this->form_validation->set_rules('last_name',  $this->lang->line('profiles_last_name'), 'max_length[250]');
		$this->form_validation->set_rules('username',  $this->lang->line('login_username'), 'required|min_length[5]|max_length[250]');
		$this->form_validation->set_rules('password', $this->lang->line('login_password'), 'required|min_length[8]|max_length[250]');
		$this->form_validation->set_rules('phone_number',  $this->lang->line('profiles_phone'), 'max_length[250]');
		$this->form_validation->set_rules('email', $this->lang->line('profiles_email'), 'required|valid_email|max_length[250]');
		$this->form_validation->set_rules('userCaptcha',  $this->lang->line('profiles_validation_code') , 'required');
		
		$this->form_validation->set_error_delimiters('<div class="error" ><h5 align="center" style="color: #DA1111;">', '</h5></div>');

		if ($this->form_validation->run() == FALSE) {
			$this->user_register();
        }
        else {
		   $captchaStr=$this->input->post('userCaptcha');
	       $dobmonth=$this->input->post('dobmonth');
	       $dobday=$this->input->post('dobday');
	       $dobyear=$this->input->post('dobyear');
	       $dob= date("Y-m-d", strtotime("$dobyear-$dobmonth-$dobday")); 
	   
	       $date_of_registration= date("Y-m-d");
		
		   $userinfo_data = array(
		   'first_name'=>$this->input->post('first_name'),
		   'last_name'=>$this->input->post('last_name'),
		   'phone_number'=>$this->input->post('phone_number'),
		   'email'=>$this->input->post('email'),
		   'register_date'=>$date_of_registration,
		   'dob'=>$dob,
		   'country_code'=>$this->input->post('country_code'),
		   'country_name'=>$this->input->post('country_name'),
		   'marital_status'=>$this->input->post('marital_status')
		   );
		
		   $to_email = $this->input->post('email');
		   
             if($this->config->item('reg_mail_send')==0){
	        $userlog_data=array(
		        'username'=>$this->input->post('username'),
		        'password'=>$this->bcrypt->hash_password($this->input->post('password')),
		        'active'=>'1'
		    );
			 }
			 else {
				$userlog_data=array(
		        'username'=>$this->input->post('username'),
		        'password'=>$this->bcrypt->hash_password($this->input->post('password')),
		        'active'=>'0'
		    );
			 }

		    $permission_data = $this->input->post("permissions")!=false ? $this->input->post("permissions"):array();
		
		    $user= $this->input->post('username');
		    $email= $this->input->post('email');
		    $usermailcount=$this->User->check_email($email,$user_id);
			$usercount=$this->User->check_username($user,$user_id);
		    if (!$this->check_captcha($captchaStr)) { 
		        echo json_encode(array('success'=>false,'message'=>$this->lang->line('profiles_validation_error')));
		    }
		    else {
				if ($usermailcount!=0) {
		            echo json_encode(array('success'=>false,'message'=>$this->lang->line('profiles_email_exist')));
		        }
		        else if ($usercount!=0) {
		            echo json_encode(array('success'=>false,'message'=>$this->lang->line('profiles_username_exist')));
		        }
		        else {
		            if($this->User->save($userinfo_data,$userlog_data,$permission_data,$user_id))
		            {
				        if($this->config->item('reg_mail_send')==0){
                            save_user_log($this->lang->line("user_log_registration_mail_send")." ".$this->User->get_info($userlog_data['user_id'])->first_name,$userlog_data['user_id']);
							$verificationcode=md5($to_email.$userlog_data['user_id']);  //Create verification code
						    if($this->User->sendVerificationEmail($userlog_data['user_id'],$to_email,$verificationcode))
						    echo json_encode(array('success'=>true,'message'=>$this->lang->line('profiles_successful_with_email_validating').' '.
				            html_escape($this->security->xss_clean($userinfo_data['first_name'])).' '.html_escape($this->security->xss_clean($userinfo_data['last_name'])),'user_id'=>$userlog_data['user_id']));
							else
							echo json_encode(array('success'=>true,'message'=>$this->lang->line('profiles_confirmation_email_failed')));
						}
						else {
                            save_user_log($this->lang->line("user_log_registration")." ".$this->User->get_info($userlog_data['user_id'])->first_name,$userlog_data['user_id']);
							echo json_encode(array('success'=>true,'message'=>$this->lang->line('profiles_successful_adding').' '.
				            html_escape($this->security->xss_clean($userinfo_data['first_name'])).' '.html_escape($this->security->xss_clean($userinfo_data['last_name'])),'user_id'=>$userlog_data['user_id']));
						}
						
		            }
		            else {	 //failure
			            echo json_encode(array('success'=>false,'message'=>$this->lang->line('profiles_error_adding_updating').' '.
			            html_escape($this->security->xss_clean($userinfo_data['first_name'])).' '.$userinfo_data['last_name'],'person_id'=>-1));
		            }
		        }
		    } 
	    }
	
	}
	
	 
	function verify($verificationText=NULL)
	{  
       $first_name=$this->User->get_info_by_verification_code($verificationText)->first_name;
       $user_id=$this->User->get_info_by_verification_code($verificationText)->user_id;
        if ($this->User->verifyEmailAddress($verificationText)){
            save_user_log($this->lang->line("user_log_registration_mail_verified")." ".$first_name,$user_id);
            $data['success']='success_message_box';
			$message =  $this->lang->line('login_mail_conformation_successful')."<br><br>".anchor('login', $this->lang->line('login_login'))." "; 
        }
		else {
            $data['success']='error_message_box';
			$message = $this->lang->line('login_mail_conformation_failed')."<br><br>".anchor('login', $this->lang->line('login_login'))." ";   
        }	
			
		$data['message']=$message;
		$data['content_view']='login/password_reset_mail';
		$this->load->module("template");
		$this->template->login_template($data);
		
 
    }
	
	public function login_check($username)
	{
        $ip_address=$this->input->ip_address();
		$password= $this->input->post('password');
	    $remember = $this->input->post('remember_me');
        
        $type = 0; 
        $limit = $this->config->item('throttle_limit');
        $timeout = $this->config->item('throttle_timeout');
        
 
        $this->User->throttle_cleanup($timeout);
        if($this->User->throttle_attempt($ip_address)+1>$limit){
            $this->form_validation->set_message('login_check', $this->lang->line('login_throttle_attempt_error')." ".$this->User->throttle_attempt_time_remain($ip_address,$timeout)." ".$this->lang->line('login_throttle_attempt_time'));
            return false;
        }
        else{
            $this->User->throttle( $limit , $timeout);
            if (!$this->User->login($username,$password)) {
             
			if ($this->User->is_login_exist($username, $password)){
				$this->form_validation->set_message('login_check', $this->lang->line('error_inactive_account'));
			}
			else{
				$this->form_validation->set_message('login_check', $this->lang->line('login_invalid_username_and_password'));
			}
			return false;
		} 
		
		 if ($remember) {
			$this->setCookie($this->User->get_username_user_id($username), false);
        }  
       
		return true;
        }
				
	}
    
 

	
	function setCookie($netid = "", $nocookie = false)
	{
		if (!$netid && !$nocookie) {
			show_error("setCookie request missing netid");
			return;
		}
			

		
		if ($nocookie) {
			// record landing page
			$cookie_id = "";
			$orig_page_requested = $this->uri->uri_string();
		}
		else {
			$cookie_id = uniqid('', true);
			$orig_page_requested = "";
			
			// delete temporary landing page record, if it exists,
			// but salvage orig_page_requested var
			$query = $this->db->get_where('cicookies', array(
				'php_session_id' => session_id()
			));
			if ($query->num_rows()) {
				$orig_page_requested = $query->row()->orig_page_requested;
			}
			 $this->db->delete('cicookies', array(
				'php_session_id' => session_id()
			)); 
		}
		
		$ip_address = ($_SERVER['SERVER_NAME'] == "localhost") ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'];
	
		$insertdata = array(
			'cookie_id' => $cookie_id,
			'ip_address' => $ip_address,
			'user_agent' => $this->agent->agent_string(),
			'netid' => $netid,
			'created_at' => date('Y-m-d H:i:s'),
			'orig_page_requested' => $orig_page_requested,
			'php_session_id' => session_id()
		);	
		$this->db->insert('cicookies', $insertdata);
		
		// set cookie for TLD, not subdomains
		$host = explode('.', $_SERVER['SERVER_NAME']);
		$segments = count($host) - 1;
		$domain = ($_SERVER['SERVER_NAME'] == "localhost") ? false : $host[($segments - 1)] . "." . $host[$segments];
		
		if (!$nocookie) {
			// set cookie for 1 year
			$cookie = array(
				'name' => 'rmtoken_' . str_replace('.', '_', $_SERVER['SERVER_NAME']),
				'value' => $cookie_id,
				'expire' => 31557600,
				'domain' => $domain,
				'path' => preg_replace('/^(http|https):\/\/(www\.)?' . $_SERVER['SERVER_NAME'] . '/', '', preg_replace('/\/$/','', base_url())),
				'secure' => isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : 0
			);
			$this->input->set_cookie($cookie);
		
			// establish session
			$this->session->set_userdata('rememberme_session', $netid);	
		}		
	}
	

	function verifyCookie() {			
		if (!$this->input->cookie('rmtoken_' . str_replace('.', '_', $_SERVER['SERVER_NAME']))) { 
			return false; 
		}
		
		$query = $this->db->get_where('cicookies', array(
			'cookie_id' => $this->input->cookie('rmtoken_' . str_replace('.', '_', $_SERVER['SERVER_NAME']))
		));

		if ($query->num_rows()) {
			$row = $query->row();
			// valid cookie
			if ($this->session->userdata('rememberme_session')) {   
				// session active, make sure cookie and session netids match
				if ($this->session->userdata('rememberme_session') !== $row->netid) {
					return false;
				}
			}
			else {
				// create new session
				$this->session->set_userdata('rememberme_session', $row->netid);
			}	
			
			// return netid
			return $row->netid;
		}
		else {
			return false;
		}
	}
	
	public function reset_forgot_password() 
	{
        $this->load->library('form_validation');
        $this->form_validation->set_rules('email',$this->lang->line('profiles_email'), 'required|valid_email|min_length[5]|max_length[125]');

        if ($this->form_validation->run() == FALSE) {
	        $this->forget_password();
        }
	    else {
            $email = $this->input->post('email');
            $count_email = $this->User->check_email($email,-1);
            $active_email = $this->User->check_active_email($email);
      
            if ($count_email == 1 && $active_email==1) {
                // Make a small string (code) to assign to the user // to indicate they've requested a change of // password
                $code = mt_rand('5000', '200000');
                $data = array(
                'forgot_password' => $code,
                );  
                if($this->User->update_forget_password($code,$email,$data)) {
                   save_user_log($this->lang->line("user_log_forget_password_mail")." ".$this->User->get_info_by_email($email)->first_name,$this->User->get_info_by_email($email)->user_id );
				     $data['message']=$this->lang->line('login_forget_password_mail_message');
				     $data['success']='success_message_box';
				}
				else {
					$data['message']=$this->lang->line('login_forget_password_mail_message_failed');
				    $data['success']='error_message_box';
				}
			    $data['content_view']='login/password_reset_mail';
		        $this->load->module("template");
		        $this->template->login_template($data);
            } 
		    else {
				$data['message']=$this->lang->line('login_forget_password_mail_failed_message');
				$data['success']='error_message_box';
				$data['content_view']='login/password_reset_mail';
		        $this->load->module("template");
		        $this->template->login_template($data);
            }
        }
    }
  
  public function new_password()
  {
  
	$this->load->library('bcrypt');
    $this->load->library('form_validation');
    $this->form_validation->set_rules('code', 'Validation Code', 'required');
    $this->form_validation->set_rules('email', 'Email', 'required|valid_email|max_length[250]');
    $this->form_validation->set_rules('password', 'Password', 'required|min_length[8]|max_length[250]');
    $this->form_validation->set_rules('repeat_password', 'Confirmation Password', 'required|min_length[8]|max_length[250]|matches[password]');
    
    // Get Code from URL or POST 
    if ($this->input->post()) {
      $data['code'] = $this->input->post('code');
    } 
	else {
      $data['code'] = $this->uri->segment(3);
    }

    if ($this->form_validation->run() == FALSE) {
		$data['content_view']='login/new_password';
		$this->load->module("template");
		$this->template->login_template($data);
    } 
	else {
      // Does code from input match the code against the  email
      $email = $this->input->post('email');
      if (!$this->User->password_code_match($data['code'], $email)) {
        // Code doesn't match
		$data['content_view']='login/password_reset_failed';
		$this->load->module("template");
		$this->template->login_template($data);

        } 
	    else { // Code does match
            $password = $this->input->post('password');
		    $userlog_data=array(
		    'password'=>$this->bcrypt->hash_password($password),
		    'forgot_password'=>0
		    );
			
			$userinfo_data=array();

            $user_id=$this->User->get_user_id($email);
            if ($this->User->update_password($userlog_data,$userinfo_data, $user_id)) {
                save_user_log($this->lang->line("user_log_password_reset")." ".$this->User->get_info($user_id)->first_name,$user_id);
				$data['content_view']='login/password_reset_success';
		        $this->load->module("template");
		        $this->template->login_template($data);
            }
        }
    }
  }
	
}
