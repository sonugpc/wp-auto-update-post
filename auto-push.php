<?php
/*
Plugin Name: Auto Renew Post Dates
Description: Automatically update Posts Updated Date to Current Date.
Version: 1.0
Author: Sonu Gupta
*/

// Include necessary files
include_once(plugin_dir_path(__FILE__) . 'admin-settings.php');
include_once(plugin_dir_path(__FILE__) . 'post-meta-box.php');
include_once(plugin_dir_path(__FILE__) . 'cron-job.php');
