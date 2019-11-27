<?php

/*
Plugin Name: LOOP Modules
Description: Modules generator
Author: Peter Kracik / LOOP
Author URI: https://www.agentur-loop.com
Version: 1.0.1
Text Domain: loop-modules
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if (! defined('WPINC')) {
    die;
}

require_once( dirname(__FILE__) . '/vendor/autoload.php' );

use Loop\Modules\ModulesPlugin;
use Loop\Modules\Commands\GenerateModuleCommand;

new ModulesPlugin();
new GenerateModuleCommand();
