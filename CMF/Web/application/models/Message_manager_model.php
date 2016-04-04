<?php
class Message_manager_model extends CI_Model
{
	private $message_user_table_name="message_user";
	private $message_table_name="message";

	//don't use previously used ids (indexes), just increase and use
	private $departments=array(
		0=>"customers"
		,1=>"agents"
		,2=>"management"
		);

	public function __construct()
	{
		parent::__construct();
		
		return;
	}

	public function install()
	{
		$module_table=$this->db->dbprefix($this->message_table_name); 
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS $module_table (
				`message_id` BIGINT AUTO_INCREMENT NOT NULL
				,`message_parent_id` BIGINT
				,`message_sender_type` enum('customer','user')
				,`message_sender_id` BIGINT
				,`message_time_stamp` DATETIME
				,`message_receiver_type` enum('customer','user')
				,`message_receiver_id` BIGINT
				,`message_subject` VARCHAR(200)
				,`message_body` TEXT
				,`message_verifier_id` INT DEFAULT 0
				,`message_reply_id` BIGINT DEFAULT 0
				,PRIMARY KEY (message_id)	
			) ENGINE=InnoDB DEFAULT CHARSET=utf8"
		);

		$module_table=$this->db->dbprefix($this->message_user_table_name); 
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS $module_table (
				`mu_user_id` INT NOT NULL
				,`mu_departments` BIGINT DEFAULT 0
				,`mu_verifier` TINYINT NOT NULL DEFAULT 0 
				,`mu_supervisor` TINYINT NOT NULL DEFAULT 0
				,PRIMARY KEY (mu_user_id)	
			) ENGINE=InnoDB DEFAULT CHARSET=utf8"
		);

		$this->module_manager_model->add_module("message","message_manager");
		$this->module_manager_model->add_module_names_from_lang_file("message");

		$this->module_manager_model->add_module("message_access","");
		$this->module_manager_model->add_module_names_from_lang_file("message_access");
		
		return;
	}

	public function uninstall()
	{

		return;
	}

	public function get_sidebar_text()
	{
		//return " (12) ";
	}

	public function get_departments()
	{
		return $this->departments;
	}

	public function get_user_access($user_id)
	{
		$result=$this->db
			->get_where($this->message_user_table_name,array("mu_user_id"=>$user_id))
			->row_array();

		$ret=array("verifier"=>0,"supervisor"=>0);
		$deps=0;
		if($result)
		{
			$ret['verifier']=$result['mu_verifier'];
			$ret['supervisor']=$result['mu_supervisor'];
			$deps=$result['mu_departments'];
		}
		
		$departments=array();
		foreach($this->departments as $dep_index=>$dep_name)
			$departments[$dep_name]=($deps & (1<<$dep_index));

		$ret['departments']=$departments;

		return $ret;
	}

	public function set_user_access($user_id,$props)
	{
		$deps=0;
		foreach($this->departments as $dep_index=>$dep_name)
			if($props['departments'][$dep_name])
				$deps+=(1<<$dep_index);

		$rep=array(
			"mu_user_id"=>$user_id
			,"mu_verifier"=>(int)($props['verifier']==1)
			,"mu_supervisor"=>(int)($props['supervisor']==1)
			,"mu_departments"=>$deps
		);

		$this->db->replace($this->message_user_table_name, $rep);

		foreach($this->departments as $dep_index=>$dep_name)
			$rep['department_'.$dep_name]=(int)$props['departments'][$dep_name];

		$this->log_manager_model->info("MESSAGE_ACCESS_SET",$rep);

		return;
	}

	public function get_dashboard_info()
	{
		return "";
		$CI=& get_instance();
		$lang=$CI->language->get();
		$CI->lang->load('ae_module',$lang);		
		
		$data=array();
		$data['modules']=$this->get_all_modules_info($lang);
		$data['total_text']=$CI->lang->line("total");
		
		$CI->load->library('parser');
		$ret=$CI->parser->parse($CI->get_admin_view_file("module_dashboard"),$data,TRUE);
		
		return $ret;		
	}
}