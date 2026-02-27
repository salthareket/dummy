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

    /*public static function render_installation_page() {
        echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" type="text/css" media="all" />';
        ?>
        <div class="wrap">
            <h1>Salthareket/Theme Installation</h1>

            <div style="display:flex;flex-direction:column;align-items:center;justify-content: center;height:100vh; text-align:center;">
                <div style="width:60%;">
                    <h2 style="font-weight:600;font-size:42px;line-height:1;margin-bottom:20px;"><small style="display:block;font-size:12px;font-weight:bold;margin-bottom:10px;background-color:#111;color:#ddd;padding:8px 12px;border-radius:22px;display:inline-block;">STEP 1</small><br>Install Packages</h2>
                    <p>This theme requires some initial setup before you can start using it. Please complete the installation process below.</p>
                    <div class="alert alert-dismissible rounded-3 fade d-none" data-action="update"></div>
                    <div class="installation-status" style="text-align:center;font-size: 22px;font-weight:bold;margin-top:20px;display:none;"></div>
                    <button id="install-theme-button" class="button button-primary" style="margin-top:40px;font-size: 18px;border-radius: 22px;border: none;padding: 6px 28px;">Start Installation</button>
                </div>
            </div>
        </div>

        <?php

        self::enqueue_install_script();
    }*/

    public static function render_installation_page() {
        echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" type="text/css" media="all" />';
        
        $tasks_status = get_option('sh_theme_tasks_status', []);
        // Eğer birileri true ise, yani kurulum başlamışsa recovery moduna gir
        $is_recovery = !empty(array_filter((array)$tasks_status)); 

        ?>
        <div class="wrap">
            <div style="display:flex;flex-direction:column;align-items:center;justify-content: center;min-height:100vh; text-align:center; padding: 40px 0;">
                <div style="width:70%; background:#fff; padding:40px; border-radius:20px; box-shadow:0 10px 40px rgba(0,0,0,0.05);">
                    
                    <h1 style="font-weight:800; font-size:32px; color:#111; margin-bottom:30px;">Salthareket/Theme Setup</h1>

                    <?php if ($is_recovery): ?>
                        <div id="recovery-ui">
                            <h2 style="font-weight:600;font-size:24px;margin-bottom:20px;">Kurulum Kayıtları Bulundu</h2>
                            <p>Bazı adımlar daha önce tamamlanmış. Kaldığınız yerden devam edebilir veya her şeyi sıfırlayabilirsiniz.</p>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 30px 0; text-align: left; background: #f9f9f9; padding: 20px; border-radius: 12px;">
                                <?php 
                                $all_tasks = self::get_all_tasks_list(); // Aşağıya bu yardımcı metodu ekleyeceğiz
                                foreach ($all_tasks as $key => $label): 
                                    $done = isset($tasks_status[$key]) && $tasks_status[$key] === true;
                                ?>
                                    <div style="display:flex; align-items:center; font-size:13px;">
                                        <span style="margin-right:8px;"><?php echo $done ? '✅' : '⚪'; ?></span>
                                        <span style="<?php echo $done ? 'color:#2271b1; font-weight:bold;' : 'color:#999;'; ?>"><?php echo $label; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div style="display:flex; justify-content:center; gap:15px;">
                                <button id="install-theme-button" class="btn btn-primary rounded-pill px-4">Mevcutla Devam Et</button>
                                <button id="reset-and-start" class="btn btn-outline-danger rounded-pill px-4">Sıfırla ve Baştan Kur</button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div id="fresh-install-ui">
                            <h2 style="font-weight:600;font-size:42px;line-height:1;margin-bottom:20px;">
                                <small style="display:block;font-size:12px;font-weight:bold;margin-bottom:10px;background-color:#111;color:#ddd;padding:8px 12px;border-radius:22px;display:inline-block;">STEP 1</small>
                                <br>Install Packages
                            </h2>
                            <p>This theme requires some initial setup before you can start using it. Please complete the installation process below.</p>
                            <button id="install-theme-button" class="btn btn-primary rounded-pill px-5 py-2 mt-4" style="font-size: 18px;">Start Installation</button>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-dismissible rounded-3 fade d-none mt-4" data-action="update"></div>
                    <div class="installation-status" style="text-align:center;font-size: 18px;font-weight:bold;margin-top:20px;display:none;"></div>
                </div>
            </div>
        </div>
        <?php
        self::enqueue_install_script();
    }

    // Helper: Task isimleri
    private static function get_all_tasks_list() {
        return [
            "fix_packages" => "Fix Packages",
            "update_theme_apperance" => "Theme Appearance",
            "copy_theme" => "Copy Files",
            "install_mu_plugins" => "MU Plugins",
            "install_wp_plugins" => "WP Plugins",
            "install_local_plugins" => "Local Plugins",
            "generate_files" => "Files Generation",
            "copy_fields" => "ACF Copy",
            "register_fields" => "ACF Register",
            "update_fields" => "ACF Update",
            "npm_install" => "NPM Modules",
            "compile_methods" => "Methods Compile",
            "compile_js_css" => "JS/CSS Compile",
            "defaults" => "Default Options"
        ];
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
            ['Install', 'render_installation_page'], // Theme Update içeriğini render et
            91
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

    public static function install_theme_package(){
        check_ajax_referer('install_theme_nonce', '_ajax_nonce');
        self::composer("salthareket/theme");
        wp_send_json_success(['message' => 'Theme package installed successfully.', 'action' => 'install']);
    }

    public sattic function reset_theme_installation_data(){
        check_ajax_referer('install-theme-nonce', '_ajax_nonce');
        update_option('sh_theme_tasks_status', []); // Taskları boşalt
        update_option('sh_theme_status', false);    // Statüyü kapat
        wp_send_json_success();
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
            add_action('wp_ajax_reset_theme_installation_data', [__CLASS__, 'reset_theme_installation_data']);
            if (!(defined('DOING_AJAX') && DOING_AJAX)) {
                $current_page = $_GET['page'] ?? '';
                if ($current_page !== 'install-packages') {
                    wp_safe_redirect(admin_url('admin.php?page=install-packages'));
                    exit;
                }
            }
            add_action('admin_head', function () {
                $pages = ["install-packages" ];
                if (isset($_GET['page']) && in_array($_GET['page'], $pages)) {
                    remove_all_actions('admin_notices');
                    remove_all_actions('all_admin_notices');
                }
            });
        } else {
            //$is_login_page = strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false;
            //if (!is_login_page()) {
                wp_die(
                    sprintf(
                        '<h2 class="text-danger">Warning</h2>The theme setup is not complete. Please complete the installation from the <a href="%s">Install Salthareket/Theme sss</a>.',
                         esc_url(admin_url('admin.php?page=install-packages'))
                    )
                );
            //}
        }
    }
}