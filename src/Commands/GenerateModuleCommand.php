<?php

namespace pk\Modules\Commands;

use WP_CLI;

Class GenerateModuleCommand
{

    private const GENERATE_COMMAND_NAME = "pk modules generate";

    private $exampleFolder = __DIR__ . "/../../example-module";
    private $moduleFolder;

    private $moduleName;
    private $moduleVariables = [];

    function __construct()
    {
        if (!defined('WP_CLI') || !WP_CLI) {
            echo "WP_CLI not found";
            return false;
        }

        if (!defined('MODULES_DIR')) {
            WP_CLI::error("You need to define in the wp-config.php variable MODULE_DIR with the path for modules within your theme");
            return false;
        }
        $this->moduleFolder = get_template_directory() . "/" . MODULES_DIR . "/";
        $this->defineHooks();
    }

   /**
     * define class hooks
     */
    private function defineHooks()
    {
        add_action('init', array($this, 'addCliCommands'));
    }

    /**
     * add CLI commands
     */
    public function addCliCommands()
    {
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command(self::GENERATE_COMMAND_NAME, [$this, 'GenerateModule']);
        }
    }

    /**
     * Generate modules
     *
     * ## OPTIONS
     *
     * <name>
     * : Slug of the module
     *
     * --module_title=<value>
     * : Readable name of the module
     *
     * ## EXAMPLES
     *
     *     wp pk modules genereate test --module_title="My Title"
     *
     * @when after_wp_load
     */
    public function GenerateModule($args, $assoc_args)
    {
        // read settings
        $this->moduleName = self::camel2dashed($args[0]); // assign the name argument
        $this->moduleVariables = $assoc_args ?? []; // assigned other arguments
        $destFolder = $this->moduleFolder . $this->moduleName; // get the folder
        WP_CLI::line("Generatin module " . $this->moduleName);
        $created = $this->rcopy($this->exampleFolder, $destFolder);
        if (!$created) {
            WP_CLI::error("Module " . $this->moduleName . " couldn't be created");
        }

        $moduleName = $this->moduleName;
        $moduleVariables = $this->moduleVariables;

        // replace variables (module name, title etc) in all modules files
        $files = array_map(function($file) use ($moduleName, $moduleVariables) {
            $this->setFilesVariables($file, $moduleName, $moduleVariables);
        }, $this->getDirContents($destFolder));

        $this->createBlankAcfGroup($moduleName, $moduleVariables['module_title']); // generate acf

        WP_CLI::success("Module " . $this->moduleName . " was created in : " . $destFolder);
        WP_CLI::success('HAPPY CODING!');
    }

    /**
     * copy the whole example folder
     * @param string $src       source folder
     * @param string $dist      destination folder
     * @return bool             if succesful
     */
    private function rcopy(string $src, string $dst) :bool
    {
        if (file_exists($dst)) {
            WP_CLI::error('Folder ' . $this->moduleFolder . " already exists");
            return false;
        }
        if (is_dir($src)) {
            mkdir($dst);
            $files = scandir($src);
            foreach ($files as $file) {
                if ($file != "." && $file != "..") $this->rcopy("$src/$file", "$dst/$file");
            }

            return true;
        }
        else if (file_exists($src)) copy($src, $dst);

        return false;
    }

    /**
     * get all files within the module folder to replace variables
     * @param string $dir       directory to search in
     * @param array results     results to pass for the recursion
     * @return array            list of files
     */
    private function getDirContents(string $dir, ?array &$results = []) :?array
    {
        $files = scandir($dir);

        foreach($files as $key => $value){
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if(!is_dir($path)) {
                $results[] = $path;
            } else if($value != "." && $value != "..") {
                $this->getDirContents($path, $results);
            }
        }

        return $results;
    }

    /**
     * replace all occurencies of module specific variables - names, titles etc. in a file
     * @param string $file              file to replace variables
     * @param string $moduleName        name of the module
     * @param array $variables          additional module variables to be replaced
     * @return bool
     */
    private static function setFilesVariables(string $file, string $moduleName, ?array $variables = []) :bool
    {
        $str = file_get_contents($file);
        //replace something in the file string - this is a VERY simple example
        $str = str_replace("MODULE_NAME_ESC", str_replace("-", "_", $moduleName), $str);
        $str = str_replace("MODULE_NAME", $moduleName, $str);

        foreach($variables as $key => $value) {
            $str = str_replace(strtoupper($key), $value, $str);
        }

        //write the entire string
        file_put_contents($file, $str);
        return true;
    }

    /**
     * general basic setup
     * @params string $moduleName   slug of the module
     * @params string $moduleTitle   slug of the module
     */
    private function createBlankAcfGroup(string $moduleName, string $moduleTitle)
    {
        if( !function_exists('acf_add_local_field_group') ) return false;

        $conf = array(
            'key'       => "group_" . $moduleName,
            'title'     => 'pk Module : ' . $moduleTitle,
            'location' => array (
                array (
                    array (
                        "param"     => "block",
                        "operator"  => "==",
                        "value"     => 'acf/' . $moduleName,
                    ),
                ),
            ),
            'menu_order'            => 0,
            'position'              => 'normal',
            'style'                 => 'default',
            'label_placement'       => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen'        => '',
            'active'                => true,
            'description'           => '',
            'local'                 => 'json'
        );

        // acf_import_field_group($conf);
        // export to a json
        $path = $this->moduleFolder . $moduleName . "/acf-json";
        mkdir($path);
        add_filter( "acf/settings/save_json", function() use($path) {
            return $path;
        }, 15, 3 );
        acf_update_field_group($conf);
        // acf_write_json_field_group($conf);
    }

    /**
     * modify text from camel case to dashed
     * @param string $str       text
     * @return string           replaced
     */
    static function camel2dashed(string $str) :string
    {
        return strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $str));
    }

}

new GenerateModuleCommand();