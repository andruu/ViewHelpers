<?php
/*
 * This file is part of the ViewHelpers package.
 *
 * (c) Andrew Weir <andru.weir@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ViewHelpers;

/**
 * Asset Helper
 *
 * @package eGloo/ViewHelpers
 * @author Andrew Weir <andru.weir@gmail.com>
 **/
class AssetHelper {

  /**
   * Configuration
   *
   * @var array
   **/
  private $config;

  /**
   * Array of js assets
   *
   * @var array
   **/
  private $js_assets = [];

  /**
   * Array of css assets
   *
   * @var array
   **/
  private $css_assets = [];

  /**
   * Template for js html tag
   *
   * @var string
   **/
  private $js_template = '<script src="__PATH__" type="text/javascript"></script>';

  /**
   * Template for css html tag
   *
   * @var string
   **/
  private $css_template = '<link href="__PATH__" media="all" rel="stylesheet" type="text/css" />';

  /**
   * Constructor initializes config
   *
   * @param array $config Configuration
   * @return void
   **/
  public function __construct (Array $config = []) {
    $this->config = $config;
  }

  /**
   * Push js includes to array
   *
   * @param array|string $includes
   * @return void
   **/
  public function js_include ($includes) {
    $this->__include($includes, 'js');
  }

  /**
   * Push css includes to array
   *
   * @param array|string $includes
   * @return void
   **/
  public function css_include ($includes) {
    $this->__include($includes, 'css'); 
  }

  /**
   * Push css/js includes to array
   *
   * @param array|string $includes
   * @param string $type
   * @return void
   **/
  private function __include ($includes, $type) {
    $asset_type = "{$type}_assets";
    if (is_array($includes)) {
      foreach ($includes as $include) {
        if (!in_array($include, $this->{$asset_type})) {
          $this->{$asset_type}[] = $include;
        }
      }
    } else {
      $this->{$asset_type}[] = $includes;
    }
  }

  /**
   * Return js html tag
   *
   * @return string
   **/
  public function js_tag () {
    return $this->__tag('js');
  }

  /**
   * Return css html tag
   *
   * @return string
   **/
  public function css_tag () {
    return $this->__tag('css');
  }

  /**
   * Return js/css html tag and writes to $config['compile_file'] for pre-compilation
   *
   * @param string $type
   * @return string
   **/
  private function __tag ($type) {

    $asset_type = "{$type}_assets";
    $template = "{$type}_template";

    // Throw exception if no assets have been added
    if (empty($this->{$asset_type})) {
      throw new \Exception("No {$type} assets have been added.", 1);
    }

    // If in production return just the path
    if ($this->config['environment'] == "PRODUCTION") {
      
      // Check how many files in array
      if (count($this->{$asset_type}) == 1) {
        $file_name = $this->{$asset_type}[0];
        $file_name = substr($file_name, 0, -((int) strlen($type) + 1)) . '_' . getenv('LAST_DEPLOYMENT_TIMESTAMP') . '.' . $type;
      } else {
        $file_name = md5(serialize($this->{$asset_type})) . '_' . getenv('LAST_DEPLOYMENT_TIMESTAMP') . '.' . $type;
      }

    } else {

      // Compile new asset files
      $request = new \AssetManager\Request($this->config);

      // Check how many files in array
      if (count($this->{$asset_type}) == 1) {
        $file_name = $request->route($this->{$asset_type}[0]);
      } else {
        $file_name = $request->route($this->{$asset_type});
      }
    }

    $this->__write($type);

    $path = $this->config['cdn'][$this->config['environment']] . $this->config['web'][$type] . DS . $file_name;      
    return str_replace('__PATH__', $path, $this->{$template});
  }

  /**
   * Writes to $config['compile_file'] for pre-compilation
   *
   * @param string $type
   * @return void
   **/
  private function __write ($type) {
    require_once '../vendors/json_format.php';

    $asset_type = "{$type}_assets";

    $compile_file = $this->config['compile_file'];
    
    // Create file if it doesn't exist
    if (!file_exists($compile_file)) {
      file_put_contents($compile_file, null);
    }

    // Open compile file and turn into PHP array
    $assets = json_decode(file_get_contents($compile_file), true);
    $assets[$type][] = $this->{$asset_type};

    // Clean duplicates
    $assets[$type] = array_map("unserialize", array_unique(array_map("serialize", $assets[$type])));

    // Write back to file
    file_put_contents($compile_file, json_format(json_encode($assets)));
  }
}

