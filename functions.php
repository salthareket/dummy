<?php
ini_set("display_errors", 0);
error_reporting(E_ALL);

if (file_exists(__DIR__ . "/vendor/autoload.php")) {
    require_once __DIR__ . "/vendor/autoload.php";
}

if (class_exists('Timber\Timber')) {
   Timber\Timber::init();
}

if (file_exists(__DIR__ . "/vendor/salthareket/theme/src/theme.php")) {

    if (!class_exists('SaltHareket\Theme')) {
        require_once __DIR__ . "/vendor/salthareket/theme/bootstrap.php";
    }
    if (class_exists('SaltHareket\Theme')) {
        $theme = \SaltHareket\Theme::getInstance();
        $theme->init();
    } else {
        error_log("SaltHareket\Theme sınıfı yüklenemedi, atlanıyor.");
    }

} else {

    update_option('sh_theme_status', false); 
    if (file_exists(__DIR__ . "/install/install.php")) {
        require_once __DIR__ . "/install/install.php";
        Install::init();
    }
    add_filter("template_include", function ($template) {
        $is_install_page = isset($_GET['page']) && $_GET['page'] === 'install-packages';
        if (!is_admin() && !$is_install_page) {
            $no_theme_file = get_template_directory() . '/static/no-theme.html';
            if (file_exists($no_theme_file)) {
                return $no_theme_file;
            }
        }
        return $template;
    });
    
}