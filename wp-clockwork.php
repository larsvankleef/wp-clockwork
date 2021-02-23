<?php
/*
 * Plugin Name: Clockwork for WordPress
 * Description: WordPress plugin for Clockwork.
 * Author: Lars van Kleef
 * Version: 1.0.0
 * Author URI: https://larsvankleef.nl
 */

require __DIR__ . '/vendor/autoload.php';


class WpClockWork {
  private $clockwork;
  private $clockwork_start;
  private $clockwork_end;

  function __construct() {
    $this->clockwork = Clockwork\Support\Vanilla\Clockwork::init([ 'register_helpers' => true ]);

    add_action('init', [$this, 'init']);
    add_action('shutdown', [$this, 'shutdown']);
    add_action('parse_request', [$this, 'parse_request']);
  }

  function init() {
    define('SAVEQUERIES', true);
    
    $this->clockwork_start = microtime(true);
    $this->clockwork->sendHeaders();
    $this->clockwork->addDataSource(new Clockwork\DataSource\PhpDataSource());
    $this->clockwork->notice('Application Started');
  }
  
  function shutdown() {
    global $wpdb;

    $request = $this->clockwork->getRequest();
    $queries = array_map(function ($query) {
      return [
        'query' => $query[0], 
        'duration' => $query[1],
        'bindings' => $query[2],
        'time' => $query[3]
      ];
    }, $wpdb->queries ?: []);

    $request->databaseQueries = $queries;
    $this->clockwork->notice('Shutdown');
    $this->clockwork->requestProcessed();
  }

  function parse_request() {
    $uri = $_SERVER['REQUEST_URI'];

    if (preg_match('/\/__clockwork\/.*/', $uri)) {
      $request = explode('/', $uri);
      $storage = new Clockwork\Storage\FileStorage(__DIR__."/clockwork/");
      $data = $storage->find($request[2]);

      echo $data->toJson();
      exit();
    }
  }
}

new WpClockWork();
