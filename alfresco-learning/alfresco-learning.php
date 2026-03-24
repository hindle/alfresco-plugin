<?php

/*
 * Plugin Name: Alfresco Learning
 * Description: Custom functionality for Alfresco Learning.
 */

defined('ABSPATH') or die('Nobody screws with Boris Grishenko!');

require_once(plugin_dir_path(__FILE__) . '/vendor/autoload.php');
$alfresco = new Alfresco\Alfresco();
