<?php
   defined('BASEPATH') OR exit('No direct script access allowed');
   ?>
 
<div id="required_fields_message"><?php echo $this->lang->line('user_log_header'); ?></div>
<ul id="error_message_box"></ul>

<fieldset  >
  
    <div id="user_log_info">

<div class="field_row clearfix">
   <?php echo form_label($this->lang->line('user_log_user').':', 'user',array('class'=>' wide')); ?>
   <div class='form_field'>
        <?php echo form_label($user_log_info->first_name, 'user',array('class'=>' wide info-label')); ?>
      
   </div>
</div>

<div class="field_row clearfix">	
<?php echo form_label($this->lang->line('user_log_ip_address').':', 'ip address',array('class'=>' wide')); ?>
	<div class='form_field'>
        
	  <?php echo form_label($user_log_info->ip_address, 'user',array('class'=>' wide info-label')); ?>
	</div>
</div>

<div class="field_row clearfix">	
<?php echo form_label($this->lang->line('user_log_user_agent').':', 'user agent',array('class'=>' wide')); ?>
	<div class='form_field'>
        <?php echo form_label($user_log_info->user_agent, 'user',array('class'=>' wide info-label')); ?>
	 
	</div>
</div>
        
<div class="field_row clearfix">	
<?php echo form_label($this->lang->line('user_log_log_time').':', 'Log time',array('class'=>' wide')); ?>
	<div class='form_field'>
        <?php echo form_label($user_log_info->log_time, 'user',array('class'=>' wide info-label')); ?>
	 
	</div>
</div>
        
 


 
 </div>
    
</fieldset>

 
 
 