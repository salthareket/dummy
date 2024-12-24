<?php

use Composer\Console\Application;

class Install {

	function __construct(){
        add_action('admin_menu', [$this, 'menu']);
    }

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
        // Bootstrap CSS'yi sadece bu sayfa için yükle
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_style(
                'bootstrap-css',
                'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css',
                [],
                '5.3.3'
            );
        });

        ?>
        <div class="wrap">
            <h1>Installation Required</h1>

            <div style="display:flex;flex-direction:column;align-items:center;justify-content: center;height:100vh; text-align:center;">
                <div style="width:60%;">
                    <h2 style="font-weight:600;font-size:42px;line-height:1;margin-bottom:20px;">Install Requirements</h2>
                    <p>This theme requires some initial setup before you can start using it. Please complete the installation process below.</p>
                    <div class="progress my-4" style="height: 30px;display:none;">
                        <div id="installation-progress" class="progress-bar progress-bar-striped progress-bar-animated text-end pe-3" role="progressbar" style="width: 0%;height:100%;background-color:#fff;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                    <div class="installation-status" style="text-align:center;font-size: 22px;font-weight:bold;margin-top:20px;display:none;"></div>
                    <button id="start-installation-button" class="button button-primary" style="margin-top:40px;font-size: 18px;border-radius: 22px;border: none;padding: 6px 28px;">Start Installation</button>
                </div>
            </div>
        </div>

        <?php
    }

    public static function menu() {
        // Ana menü oluştur
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
            'install-theme',
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

    public sttaic function init(){
    	self::composer("salthareket/salthareket");
    }

}