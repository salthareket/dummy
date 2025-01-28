<?php
ini_set("display_errors", 0);
error_reporting(~0);

if (file_exists(__DIR__ . "/vendor/autoload.php")) {
    require_once __DIR__ . "/vendor/autoload.php";
}

if (class_exists('Timber\Timber')) {
   Timber\Timber::init();
}

if (file_exists(__DIR__ . "/vendor/salthareket/theme/src/theme.php")) {
    if (!class_exists('SaltHareket\Theme')) {
        require __DIR__ . "/vendor/salthareket/theme/bootstrap.php";
    }
    $theme = new SaltHareket\Theme();
    $theme->init();
}else{
    update_option('sh_theme_status', false);
    update_option('sh_theme_tasks_status', []);
    require_once __DIR__ . "/install/install.php";
    Install::init();
    add_filter("template_include", function ($template) {
       return get_template_directory() . '/static/no-theme.html';
    });
}  

ini_set('max_execution_time', 1000);
//remove_action("shutdown", "wp_ob_end_flush_all", 1);