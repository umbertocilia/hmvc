<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?> <!-- Main Footer -->
      <footer class="main-footer">
        <!-- To the right -->
        <div class="pull-right hidden-xs">
         ©<?php echo $this->config->item('company'); ?>
        </div>
     
      </footer>
<?php 
//Update active session
Modules::run('users/active_sessions_manage/actve_sessions_save');
 
?>
<!-- add website URL in every page to access in js files -->
 <input type="hidden" value="<?php echo base_url();?>" id="base_url" />