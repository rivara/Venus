<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class View{

  private $_CI;
  private $_unic;
  private $_controller;
  private $_action;
  private $_has_layout = FALSE;
  private $_layout; // layout path
  private $_uni_title = TRUE;
  private $_data = array(
    'metas' => array()
  );

  // public --------------------------------------------------------------------

  public function __construct()
  {
    $this->_CI =& get_instance();
    $this->_controller = $this->_CI->router->fetch_class();
    $this->_action = $this->_CI->router->fetch_method();
    $this->_unic = "{$this->_controller}_{$this->_action}";
    $this->_yaml_configs();
    log_message('debug', 'View: Library initialized.');
  }

  // print out all css or js tag
  public function asset($type) // css, js
  {
    $asset = $this->_data[$type]; // ex. css
    foreach($asset as $k => $v){ // ex. default => array
      $prefix = $k;
      foreach($v as $ik => $iv){ // ex. cdn => array
        $group = "{$prefix}_{$ik}"; // ex. default_cdn
        // make sure it is not an empty array
        if( !empty($iv)){
          if($ik == 'cdn'){
            foreach($iv as $iik => $iiv){ // ex. cdn => array
              $this->_CI->carabiner->{$type}("$iiv.{$type}", '', FALSE, TRUE, $group);
            }
          }else{
            $_asset = array();
            foreach($iv as $iik => $iiv){ // ex. cdn => array
              array_push($_asset, array("$iiv.{$type}")); // ex someting.css
            }
            $this->_CI->carabiner->group($group, array($type=>$_asset));
          }
          $this->_CI->carabiner->display($group, $type);
        }
      }
    }
    log_message('debug', "View: function asset executed with {$type}.");
    return $this;
  }

  // modify or add config in controllers
  public function config($configs)
  {
    $this->_merge('css', $configs)
         ->_merge('js', $configs)
         ->_walk_through('metas', $configs)
         ->_set('title', $configs);
    return $this;
  }

  // print out all meta tags
  public function metas()
  {
    if(isset($this->_data['metas']['https'])){
      $https = $this->_data['metas']['https']; // cache it
      foreach($https as $k => $v){
        echo meta($k, $v, 'equiv');
      }
      log_message('debug', 'View: render https metas');
    }

    if(isset($this->_data['metas']['name'])){
      $name = $this->_data['metas']['name']; // cache it
      foreach($name as $k => $v){
        if($k=='robots' && ENVIRONMENT=='development') {$v = 'Index / NoIndex';}
        echo meta($k, $v);
      }
      log_message('debug', 'View: render metas');
    }

    return $this;
  }

  // parse action views using codeigniter Template Parser Class
  // with parse, data to be use in partial can only be set in controller
  public function parse($data=null)
  {
    if(isset($this->_layout)){
      $this->_CI->parser->parse("layouts/{$this->_layout}", $this->_data($data));
      log_message('debug', 'View: parse view');
    }
    return $this;
  }

  // render partial in action view
  public function partial($partial_path, $data=null)
  {
    echo $this->_CI->load->view($partial_path, $data, true);
    log_message('debug', 'View: render partial');
    return $this;
  }

  //render action views
  public function render($data=null)
  {
    if(isset($this->_layout)){
      $this->_CI->load->view("layouts/{$this->_layout}", $this->_data($data), false);
      log_message('debug', 'View: render view');
    }
    return $this;
  }

  // set `library` configs.
  // template, uni title ... etc
  public function set($prop, $val)
  {
    $this->{'_'.$prop} = $val;
    return $this;
  }

  // print out the title tag
  public function title()
  {
    echo "<title>{$this->_data['title']}</title>" ;
    return $this;
  }

  // private -------------------------------------------------------------------

  private function _assign($type, $configs, $sub)
  {
    if(isset($configs["{$type}"])){
      $this->_data["{$type}"]["{$sub}"] = $configs["{$type}"];
    }
    return $this;
  }

  private function _asset($configs, $sub)
  {
    if($configs){
      $this->_assign('css', $configs, $sub)
           ->_assign('js', $configs, $sub)
           ->_walk_through('metas', $configs)
           ->_set('title', $configs);
    }
    return $this;
  }

  private function _configs($path='common')
  {
    $config = APPPATH."views/{$path}/config.yml";
    return @file_exists($config) ?
      $this->_CI->yaml->load($config) :
      NULL;
  }

  private function _data($data)
  {
    if( $this->_uni_title == TRUE && isset($this->_data['metas']['name']['description'])){
      $this->_data['title'] = $this->_data['title'];
    }
    $path = "{$this->_controller}/{$this->_action}";

    $data['title'] = $this->_data['title'];
    $data['yield'] = $this->_CI->load->view($path, $data, true);
    return $data;
  }

  private function _layout($config)
  {
    if(isset($config['has_layout'])){
      $this->_has_layout = $config['has_layout'];
    }
    if($this->_has_layout == TRUE){
      if(isset($config['layout'])){
        $this->_layout = $config['layout'];
      }
    }
    return $this;
  }

  private function _merge($type, $configs)
  {
    if(isset($configs["{$type}"])){
      if(! isset($this->_data["{$type}"]["{$this->_unic}"])){
        $this->_data["{$type}"]["{$this->_unic}"] = array();
      }
      $this->_data["{$type}"]["{$this->_unic}"] =
      array_merge_recursive_distinct($configs["{$type}"], $this->_data["{$type}"]["{$this->_unic}"]);
    }
    return $this;
  }

  private function _set($type, $configs)
  {
    if(isset($configs["{$type}"])){
      $this->_data["{$type}"] = $configs["{$type}"];
    }
    return $this;
  }

  private function _walk_through($type, $configs)
  {
    if(isset($configs["{$type}"])){
      $this->_data["{$type}"] = array_merge_recursive_distinct($this->_data["{$type}"], $configs["{$type}"]);
    }
    return $this;
  }

  private function _yaml_configs()
  {
    // get configs
    $default_configs = $this->_configs();
    $controller_configs = $this->_configs($this->_controller);

    if($controller_configs){
      $common_configs = isset($controller_configs['common']) ?
        $controller_configs['common'] :
        NULL;
      $action_configs = isset($controller_configs[$this->_action]) ?
        $controller_configs[$this->_action] :
        NULL;
    }else{
      $common_configs = NULL;
      $action_configs = NULL;
    }

    // set layout
    $this->_layout($default_configs)
         ->_layout($common_configs)
         ->_layout($action_configs);

    // if has layout
    if($this->_has_layout){
      // set css, js, merge metas and concat title
      $this->_asset($default_configs[$this->_layout], 'default')
           ->_asset($common_configs, 'controller')
           ->_asset($action_configs, $this->_unic);
    }
    return $this;
  }

}
// End of View class

/* End of file view.php */
/* Location: ./app/libraries/view.php */