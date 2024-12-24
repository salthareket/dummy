<?php
ini_set("display_errors", 1);
error_reporting(~0);

if (file_exists(__DIR__ . "/vendor/autoload.php")) {
    require_once __DIR__ . "/vendor/autoload.php";
}else{
    require_once __DIR__ . "/composer/bootstrap.php";
    require_once __DIR__ . "/composer/install.php";
    Install::init();
}

if (class_exists('Timber\Timber')) {
   Timber\Timber::init();
}

if (class_exists('SaltHareket\Theme')) {
   $theme = new SaltHareket\Theme();
   $theme->init();
}else{
  update_option('sh_theme_status', false);
  update_option('sh_theme_tasks_status', []);
  add_filter("template_include", function ($template) {
       return get_template_directory() . '/static/no-theme.html';
  });
}

error_log("yuklendim yine...");

ini_set('max_execution_time', 1000);
//remove_action("shutdown", "wp_ob_end_flush_all", 1);