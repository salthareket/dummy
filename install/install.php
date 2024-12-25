<?php

use Composer\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class Install {

    public static function composer($package_name="", $remove = false) {
        try {

            $theme_root = get_template_directory();
            $composer_path = $theme_root . '/composer.json';

            if (!file_exists($composer_path)) {
                wp_send_json_error(['message' => 'composer.json is not found.']);
            }

            $args = array(
                'command' => 'update',
                '--working-dir' => get_template_directory()
            );
            if(!empty($package_name)){
                $args["command"] = $remove?"remove":"require";
                $args["packages"] = [$package_name];
            }

            $app = new Application();
            $app->setAutoExit(false);
            $input = new ArrayInput($args);
            $output = new BufferedOutput();
            $app->run($input, $output);

            $raw_output = $output->fetch();
            $lines = explode("\n", $raw_output);

            foreach ($lines as $line) {
                if (strpos(trim($line), 'Could not find package') !== false) {
                    wp_send_json_error(['message' => "Could not find package: <strong>$package_name</strong>", "action" => "error" ]);
                    exit;
                }
            }
            $result = [
                "update" => [],
                "install" => [],
                "remove" => []
            ];
            $action = "nothing";
            foreach ($lines as $line) {
                if (preg_match('/Upgrading ([^ ]+) \(([^ ]+) => ([^ ]+)\)/', $line, $matches)) {
                    $action = "update";
                    $result["update"] = sprintf('%s: %s -> %s', $matches[1], $matches[2], $matches[3]);
                }elseif (preg_match('/Installing ([^ ]+) \(([^ ]+)\)/', $line, $matches)) {
                    $action = "install";
                    $result["install"] = sprintf('%s: %s installed', $matches[1], $matches[2]);
                }elseif (preg_match('/Removing ([^ ]+) \(([^ ]+)\)/', $line, $matches)) {
                    $action = "remove";
                    $result["remove"] = sprintf('%s: %s removed', $matches[1], $matches[2]);
                }
            }
            $message = [];
            if($result["update"]){
                $message[] = $result["update"];
            }
            if($result["install"]){
                $message[] = $result["install"];
            }
            if($result["remove"]){
                $message[] = $result["remove"];
            }
            if($message){
                $message = implode(", ", $message);
            }else{
                $message = 'No updates or installations performed.';
            }
            
            wp_send_json_success(['message' => $message, "action" => $action ]);

        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage(), "action" => $action ]);
        }
    }

    public static function render_installation_page() {
        echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" type="text/css" media="all" />';
        ?>
        <div class="wrap">
            <h1>Salthareket/Theme Installation</h1>

            <div style="display:flex;flex-direction:column;align-items:center;justify-content: center;height:100vh; text-align:center;">
                <div style="width:60%;">
                    <h2 style="font-weight:600;font-size:42px;line-height:1;margin-bottom:20px;"><small style="display:block;font-size:12px;font-weight:bold;margin-bottom:10px;background-color:#111;color:#ddd;padding:8px 12px;border-radius:22px;display:inline-block;">STEP 1</small><br>Install Packages</h2>
                    <p>This theme requires some initial setup before you can start using it. Please complete the installation process below.</p>
                    <div class="alert alert-dismissible rounded-3 w-25 fade d-none" data-action="update"></div>
                    <div class="installation-status" style="text-align:center;font-size: 22px;font-weight:bold;margin-top:20px;display:none;"></div>
                    <button id="install-theme-button" class="button button-primary" style="margin-top:40px;font-size: 18px;border-radius: 22px;border: none;padding: 6px 28px;">Start Installation</button>
                </div>
            </div>
        </div>

        <?php

        self::enqueue_install_script();
    }

    public static function menu() {
        add_menu_page(
            'Theme Settings',
            'Theme Settings',
            'manage_options',
            'theme-settings',
            '', // Ana menü için bir sayfa içeriği yok
            'dashicons-admin-generic', // Menü simgesi
            90 // Menü sırası
        );

        // Theme Update alt menüsünü ekle
        add_submenu_page(
            'theme-settings', // Ana menü slug'ı
            'Theme Install',
            'Theme Install',
            'manage_options',
            'install-packages',
            ['Install', 'render_installation_page'] // Theme Update içeriğini render et
        );

        // Gereksiz alt menüyü kaldır
        add_action('admin_menu', function () {
            global $submenu;
            if (isset($submenu['theme-settings'])) {
                // İlk alt menü olan "Theme Settings" linkini kaldır
                unset($submenu['theme-settings'][0]);
            }
        }, 999); // Geç bir öncelik ile çalıştır
    }

    private static function fix(){
        if (class_exists('SaltHareket\Theme')) {
            $fixes = include get_template_directory() . "/vendor/salthareket/theme/src/fix/index.php";
            error_log(json_encode($fixes));
            if($fixes){
                foreach($fixes as $fix){
                    $file = get_template_directory() . "/vendor/salthareket/theme/src/fix/".$fix["file"];
                    $target_file = get_template_directory()."/vendor/".$fix["target"].$fix["file"];
                    if($fix["status"] && file_exists($file)){
                        self::fileCopy($file, $target_file);
                    }
                }
            }
        }        
    }

    public static function install_theme_package(){
        check_ajax_referer('install_theme_nonce', '_ajax_nonce');
        self::composer("salthareket/theme");
        self::fix();
        wp_send_json_success(['message' => 'Theme package installed successfully.', 'action' => 'install']);
    }

    private static function fileCopy($source, $destination) {
        if (!file_exists($source)) {
            return;
        }
        $destinationDir = dirname($destination);
        if (!file_exists($destinationDir)) {
            if (!mkdir($destinationDir, 0777, true)) {
                return;
            }
        }
        copy($source, $destination);
    }

    private static function enqueue_install_script() {
        wp_enqueue_script(
            'install-theme-script',
            get_template_directory_uri() . '/install/install.js',
            ['jquery'],
            '1.0',
            true
        );

        $args = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('install_theme_nonce')
        ];
        wp_localize_script('install-theme-script', 'installAjax', $args);
    }

    public static function init(){
        
        
        if (is_admin()) {
            add_action('admin_menu', [__CLASS__, 'menu']);
            add_action('wp_ajax_install_theme_package', [__CLASS__, 'install_theme_package']);
            if (!(defined('DOING_AJAX') && DOING_AJAX)) {
                $current_page = $_GET['page'] ?? '';
                if ($current_page !== 'install-packages') {
                    wp_safe_redirect(admin_url('admin.php?page=install-packages'));
                    exit;
                }
            }
        } else {
            wp_die(
                sprintf(
                    '<h2 class="text-danger">Warning</h2>The theme setup is not complete. Please complete the installation from the <a href="%s">Install Salthareket/Theme</a>.',
                     esc_url(admin_url('admin.php?page=install-packages'))
                )
            );
        }
    }
}