<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once ("Secure_area.php");
require_once ("interfaces/Idata_controller.php");
class User_logs extends Secure_area implements iData_controller
{
	public function __construct()
	{
		parent::__construct('user_logs');
	}
	
	public function index()
	{
		$config['base_url'] = site_url('users/user_logs/index');
		$this->load->library('pagination'); 
		$config['total_rows'] = $this->User->count_all_logs();
		$config['per_page'] = $this->config->item('pagination_limit'); //Get page limit from config settings 
		$config['uri_segment'] = 4;
		$this->pagination->initialize($config);
		
		$data['controller_name']=strtolower(get_class());
		$data['controller_path']=$this->router->fetch_module()."/".$this->router->fetch_class();;
		$data['form_width']=$this->get_form_width();
		$data['content_view']='users/user_logs/manage';
 
		$data['manage_table']=get_user_log_manage_table( $this->User->get_all_logs( $config['per_page'], $this->uri->segment( $config['uri_segment'] ) ), $this );
		$this->load->module("template");
		$this->template->manage_tables_template($data);
 
	}
	
	/*
	Returns user table data rows. This will be called with AJAX.
	*/
	public function search()
	{
		$search=$this->input->post('search');
		$data_rows=get_user_log_manage_table_data_rows($this->User->log_search($search),$this);
		echo $data_rows;
	}
	
	 
	/*
	Gives search suggestions based on what is being searched for
	*/
	public function suggest()
	{
		$suggestions = $this->User->get_user_log_search_suggestions($this->input->post('q'),$this->input->post('limit'));
		echo implode("\n",$suggestions);
	}
	
	/*
	Loads the user edit form
	*/
	public function view($user_log_id=-1)
	{
		// get all user details by user id
	    $data['user_log_info']=$this->User->get_log_info($user_log_id);
        $this->load->view("users/user_logs/user_log",$data);
	    
	}
	
	
	/*
	Inserts/updates an user
	*/
	public function save($user_id=-1)
	{
		 
	}
	
 
	
	/*
	This deletes users from the users table
	*/
	public function delete()
	{
	 
	}
	/*
	get the width for the add/edit form
	*/
	public function get_form_width()
	{
		return 650;
	}
}
