<?php

namespace Loop\Modules;

Class ModulesPlugin 
{

    private const CATEGORY_SLUG = 'loop';
    private const CATEGORY_TITLE = 'Loop';
    private const ACF_DIR = 'acf-json';
    private $modulesFolderName = 'modules';
    private $modulesFolder;
    
    function __construct()
    {
        require_once( dirname(__FILE__) . '/acf/general.php' );

        $this->modulesFolderName = MODULES_DIR ?? $this->modulesFoder; // get folder name from the DEFINE constants
        $this->modulesFolder = get_template_directory() . "/" . $this->modulesFolderName  . "/";

        $this->defineHooks();
        $this->defineFilters();
    }

    /**
     * define class hooks
    */
    private function defineHooks()
    {
        add_action('init', array($this, 'registerModules'));
    }

    /**
     * define class filters
     */
    private function defineFilters()
    {
        add_filter( 'block_categories',  array($this, 'createBlockCategories'), 10, 2);
        add_filter( 'acf/settings/save_json', array($this, 'saveAcfForModulesAcf'), 15, 2);
        add_filter( 'acf/settings/load_json', array($this, 'loadAcfForModulesAcf'), 15, 2);
    }

    /**
     * register all modules
     */
    public function registerModules()
    {
        $dirs = array_filter(glob($this->modulesFolder . "*"), 'is_dir'); // get all subdirectories
        
        // register each module
        foreach($dirs as $module) {
            $this->registerModule($module);
        }

       
        // hide not published modules
        add_filter( 'timber/output', function($output, $data, $file)
        {
            // dont show if not publish and not preview
            if (isset($data['is_preview']) 
                && $data['is_preview'] == false 
                && isset($data['data']['is_publish']) 
                && $data['data']['is_publish'] == false) 
            {
                return null;
            }

            return $output;
        }, 10, 3);
    }

    /**
     * register module
     * @param string $module    module name
     * 
     */
    public function registerModule(string $module)
    {
        $this->moduleFolder = get_template_directory() . "/" . MODULES_DIR . "/";


        $module = str_replace($this->modulesFolder, "", $module); // get module name from the path
        if (!file_exists($this->modulesFolder . $module . "/module.php")) return false;
        require_once( $this->modulesFolder . $module . "/module.php"); // load module php file
        acf_register_block( $moduleSettings ); // register block with its settings
    }

    /**
     *  add block categories
     * @param array $categories     default categories 
     * @return array                categories containing new one
     */
    public function createBlockCategories(?array $categories) :?array
    {
        return array_merge(
            $categories,
            [
                [
                    'slug' => self::CATEGORY_SLUG,
                    'title' => self::CATEGORY_TITLE,
                ],
            ]
        );
    }

    /**
     * save ACF json to local theme
     * @param string        default location
     * @return string       new location
     */
    public function saveAcfForModulesAcf(string $path) :?string
    {
        // if blocks acf, save it to the module folder if exists
        if (isset($_POST['acf_field_group'])
            && isset($_POST['acf_field_group']['location']) 
            && sizeof($_POST['acf_field_group']['location']) == 1 
            && isset($_POST['acf_field_group']['location']['group_0'])
            && sizeof($_POST['acf_field_group']['location']['group_0']) == 1 
            && isset($_POST['acf_field_group']['location']['group_0']['rule_0'])
            && $_POST['acf_field_group']['location']['group_0']['rule_0']['param'] == 'block'
            && $_POST['acf_field_group']['location']['group_0']['rule_0']['operator'] == '=='
        ) {

            $moduleName = str_replace("acf/", "", $_POST['acf_field_group']['location']['group_0']['rule_0']['value']);
            $moduleDir = $this->modulesFolder . $moduleName;
            if (is_dir($moduleDir)) {
                $acfDir = $moduleDir . "/" . self::ACF_DIR;
                if (!is_dir($acfDir)) mkdir($acfDir); // create if doesnt exist
                return $acfDir;
            }
        }
        return $path;
    }

    /**
     * get acf of current module
     * @param array $path   default paths
     * @return array        paths         
     */
    public function loadAcfForModulesAcf(?array $path) :?array
    {
        $path = array_merge($path, $this->getModulesAcfJson( $this->modulesFolder )); // modules directories
        return $path;
    }
    
    /**
     * recursively search for .json files
     * @param string $dir       directory to search in
     * @return array            list of files
     */
    private function getModulesAcfJson(string $dir) :?array
    {
        $result = [];
        $Directory = new \RecursiveDirectoryIterator($dir);
        $Iterator = new \RecursiveIteratorIterator($Directory);
        $regex = new \RegexIterator($Iterator, '/^.+\.json$/i', \RecursiveRegexIterator::GET_MATCH);
        foreach($regex as $it) {
            $pathInfo = pathInfo($it[0]);
            $result[] = $pathInfo['dirname'];
        }
        return $result;
    }

}
