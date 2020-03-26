<?php

/*
Plugin Name: pk Modules
Description: Modules generator
Author: Peter Kracik / pk
Author URI: https://www.kracik.com
Version: 1.0.1
Text Domain: pk-modules
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if (! defined('WPINC')) {
    die;
}

require_once( dirname(__FILE__) . '/vendor/autoload.php' );

use pk\Modules\ModulesPlugin;
use pk\Modules\Commands\GenerateModuleCommand;

new ModulesPlugin();
new GenerateModuleCommand();
