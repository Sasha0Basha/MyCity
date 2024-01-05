<?php
/*
Plugin Name: My City Plugin
Description: Плагин для определения города посетителя сайта.
Version: 1.2
Author: Sasha0Basha
*/

// Определение констант для обновлений плагина
define('MY_CITY_PLUGIN_SLUG', 'my-city-plugin');
define('MY_CITY_PLUGIN_FILE', __FILE__);

// Хук для проверки обновлений при активации плагина
add_action('init', 'my_city_plugin_check_update');
function my_city_plugin_check_update() {
    $plugin_slug = MY_CITY_PLUGIN_SLUG;

    // Информация о текущей версии плагина на вашем сайте
    $current_version = get_option($plugin_slug . '_version', '1.2');

    $github_username = 'sasha0basha'; // Ваше имя пользователя на GitHub
    $github_repo = 'MyCity'; // Название вашего репозитория на GitHub
    $github_url = "https://api.github.com/repos/sasha0basha/mycity/releases/latest";

    $response = wp_remote_get($github_url);

    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        $github_version = $data->tag_name; // Получаем версию из релиза на GitHub

        if (version_compare($current_version, $github_version, '<')) {
            $plugin_data = get_plugin_data(MY_CITY_PLUGIN_FILE);
            $transient = get_site_transient('update_plugins');
            $transient->response[$plugin_slug] = (object) [
                'slug' => $plugin_slug,
                'plugin' => $plugin_slug.'/'.$plugin_data['Filename'],
                'new_version' => $github_version,
                'url' => $plugin_data['PluginURI'],
                'package' => "https://github.com/{$github_username}/{$github_repo}/archive/{$github_version}.zip"
            ];
            set_site_transient('update_plugins', $transient);
        }
    }
}


// Функция для определения города по IP адресу с помощью ipinfo.io
function get_user_city() {
    $user_ip = $_SERVER['REMOTE_ADDR']; // Получаем IP адрес посетителя сайта
    $api_response = wp_remote_get('https://ipinfo.io/'.$user_ip.'/json'); // Запрос к API для получения информации о местоположении по IP

    if (!is_wp_error($api_response)) {
        $body = wp_remote_retrieve_body($api_response);
        $data = json_decode($body);

        if ($data && isset($data->city)) {
            return $data->city; // Возвращаем название города
        } else {
            return 'Не удалось определить ваш город';
        }
    } else {
        return 'Произошла ошибка при получении данных о вашем местоположении';
    }
}

// Функция для вывода информации о городе посетителя
function display_user_city() {
    $user_city = get_user_city(); // Получаем город пользователя

    echo 'Ваш город - ' . $user_city; // Выводим информацию на страницу
}

// Хук для вывода информации о городе в нужном месте на сайте
function display_city_on_site() {
    ob_start();
    display_user_city();
    $output = ob_get_clean();
    echo '<div>' . $output . '</div>';
}
add_action('wp_footer', 'display_city_on_site');
