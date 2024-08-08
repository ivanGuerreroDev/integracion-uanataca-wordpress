<?php
/*
Plugin Name: Integración Uanataca
Description: Integracion con Uanataca y gestion de certificados.
Version: 1.0
Author: Businet
Author URI: https://businet.dev
*/

/* AGREGANDO CSS */
function BNIU_styles()
{
    wp_enqueue_style('formulario_css', plugins_url('/assets/css/styles.css', __FILE__), array(), time(), 'all');
    wp_enqueue_style('intlTelInput_css', 'https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/css/intlTelInput.css', array(), time(), 'all');
    wp_enqueue_script('intlTelInput_js', 'https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/intlTelInput.min.js', array('jquery'), time(), true);
}
add_action('wp_enqueue_scripts', 'BNIU_styles');

/* AGREGANDO ARCHIVO JS*/
function BNIU_js()
{
    wp_register_script('formulariojs', plugins_url('assets/js/script.js', __FILE__), array('jquery'), '1.0', true);
    wp_enqueue_script('formulariojs');
    wp_register_script('jquery-validation', plugins_url('lib/jquery-validation/jquery.validate.min.js', __FILE__), array('jquery'), '1.0', true);
    wp_enqueue_script('jquery-validation');
    wp_register_script('jquery-validation-additional', plugins_url('lib/jquery-validation/additional-methods.min.js', __FILE__), array('jquery'), '1.0', true);
    wp_enqueue_script('jquery-validation-additional');
    wp_register_script('jquery-validation-messages', plugins_url('lib/jquery-validation/localization/messages_es.min.js', __FILE__), array('jquery'), '1.0', true);
    wp_enqueue_script('jquery-validation-messages');
}
add_action('wp_enqueue_scripts', 'BNIU_js');

// Agregar reglar de validacion y control de llenado de formulario por orden.
function renderCertForm()
{
    ob_start();
    // Obtener el ID de la orden desde la URL
    $order_id = isset($_GET['orderid']) ? intval($_GET['orderid']) : 0;
    // Verificar si ya se ha completado el formulario para esta orden
    $formulario_completado = get_post_meta($order_id, '_formulario_completado', true);
    // Si ya se ha completado, muestra un mensaje o realiza alguna acción adicional.
    if ($formulario_completado) {
        echo '<p>El formulario ya ha sido completado para esta orden.</p>';
    } else {
        include_once(plugin_dir_path(__FILE__) . '/views/shortcodes/certRequestFormSC.php');
    }
    return ob_get_clean();
}

function BNIU_shortcode_cert_form()
{
    add_shortcode('cert_request_form_sc', 'renderCertForm');
}
add_action('init', 'BNIU_shortcode_cert_form');

// wp ajax for get states
add_action('wp_ajax_get_states', 'get_states');
add_action('wp_ajax_nopriv_get_states', 'get_states');
function get_states()
{
    global $wpdb;
    $country_id = $_POST['country'];
    $table_states = $wpdb->prefix . 'bniu_states';
    $states = $wpdb->get_results("SELECT * FROM $table_states WHERE country_id = $country_id");
    echo json_encode($states);
    wp_die();
}
// wp ajax for get cities
add_action('wp_ajax_get_cities', 'get_cities');
add_action('wp_ajax_nopriv_get_cities', 'get_cities');
function get_cities()
{
    global $wpdb;
    $state_id = $_POST['state'];
    $table_cities = $wpdb->prefix . 'bniu_cities';
    $cities = $wpdb->get_results("SELECT * FROM $table_cities WHERE state_id = $state_id");
    echo json_encode($cities);
    wp_die();
}

register_activation_hook(__FILE__, 'uanataca_integration_activate');
function uanataca_integration_activate()
{
    global $wpdb;
    $json_cities_file = file_get_contents(plugin_dir_path(__FILE__) . 'lib/geodata/cities.json');
    $json_states_file = file_get_contents(plugin_dir_path(__FILE__) . 'lib/geodata/states.json');
    $json_countries_file = file_get_contents(plugin_dir_path(__FILE__) . 'lib/geodata/countries.json');
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $table_countries = $wpdb->prefix . 'bniu_countries';
    $table_states = $wpdb->prefix . 'bniu_states';
    $table_cities = $wpdb->prefix . 'bniu_cities';
    $charset_collate = $wpdb->get_charset_collate();
    $sql_countries = "CREATE TABLE IF NOT EXISTS $table_countries (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `sortname` varchar(3) NOT NULL,
        `name` varchar(150) NOT NULL,
        `phonecode` int(11) NOT NULL,
        PRIMARY KEY (`id`)
    ) $charset_collate;";
    $sql_states = "CREATE TABLE IF NOT EXISTS $table_states (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(30) NOT NULL,
        `country_id` int(11) NOT NULL DEFAULT '1',
        PRIMARY KEY (`id`)
    ) $charset_collate;";
    $sql_cities = "CREATE TABLE IF NOT EXISTS $table_cities (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(30) NOT NULL,
        `state_id` int(11) NOT NULL,
        PRIMARY KEY (`id`)
    ) $charset_collate;";
    dbDelta($sql_countries);
    dbDelta($sql_states);
    dbDelta($sql_cities);
    $countries_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_countries");
    if ($countries_count < 1) {
        $countries = json_decode($json_countries_file, true);
        foreach ($countries["countries"] as $country) {
            $wpdb->insert($table_countries, $country);
        }
    }
    $states_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_states");
    if ($states_count < 1) {
        $states = json_decode($json_states_file, true);
        foreach ($states["states"] as $state) {
            $wpdb->insert($table_states, $state);
        }
    }
    $cities_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_cities");
    if ($cities_count < 1) {
        $cities = json_decode($json_cities_file, true);
        foreach ($cities["cities"] as $city) {
            $wpdb->insert($table_cities, $city);
        }
    }

    $table_certificate_requests = $wpdb->prefix . 'bniu_certificate_requests';
    $sql_certificate_requests = "CREATE TABLE IF NOT EXISTS $table_certificate_requests (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order` int(11) NOT NULL,
        `order_item_id` int(11) NOT NULL,
        `user` int(11) NOT NULL,
        `step` int(11) NOT NULL,
        `flow_type` int(11) NOT NULL,
        `status` varchar(55) NOT NULL,
        `request` varchar(55) NOT NULL,
        PRIMARY KEY (`id`)
    ) $charset_collate;";
    dbDelta($sql_certificate_requests);
    $table_certificate_requests_meta = $wpdb->prefix . 'bniu_certificate_requests_meta';
    $sql_certificate_requests_meta = "CREATE TABLE IF NOT EXISTS $table_certificate_requests_meta (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `request` varchar(55) NOT NULL,
        `flow_type` int(11) NOT NULL,
        `step` int(11) NOT NULL,
        `meta_key` varchar(100) NOT NULL,
        `meta_value` longtext NOT NULL,
        PRIMARY KEY (`id`)
    ) $charset_collate;";
    dbDelta($sql_certificate_requests_meta);
    $table_certificate_requests_logs = $wpdb->prefix . 'bniu_certificate_requests_logs';
    $sql_certificate_requests_logs = "CREATE TABLE IF NOT EXISTS $table_certificate_requests_logs (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `request` varchar(55) NOT NULL,
        `date` datetime NOT NULL,
        `type` varchar(55) NOT NULL,
        `data` mediumtext NOT NULL,
        PRIMARY KEY (`id`)
    ) $charset_collate;";
    dbDelta($sql_certificate_requests_logs);
}

// add metabox type text named "profile" to products if have term selected on option "uanataca_product_category".
add_action('add_meta_boxes', 'add_profile_metabox');
function add_profile_metabox()
{
    add_meta_box('profile', 'Profile', 'profile_metabox', 'product', 'side', 'high');
}

function profile_metabox($post)
{
    $product = wc_get_product($post->ID);
    $product_id = $product->get_id();
    $product_categories = wp_get_post_terms($product_id, 'product_cat');
    $uanataca_product_category = get_option('certificate_products_term');
    // if $uanataca_product_category is in key "term_id" of $product_categories, show the metabox.
    if (in_array($uanataca_product_category, array_column($product_categories, 'term_id'))) {
        $profile = get_post_meta($post->ID, 'profile', true);
        echo '<label for="profile">Profile</label>';
        echo '<select name="profile" id="profile" class="postbox">';
        echo '<option value="" ' . selected($profile, '', false) . ' disabled>Seleccione perfil</option>';
        echo '<option value="software" ' . selected($profile, 'software', false) . '>Software</option>';
        echo '<option value="cloud" ' . selected($profile, 'cloud', false) . '>Cloud</option>';
        echo '</select>';
    }
}
add_action('save_post', 'save_profile_metabox');
function save_profile_metabox($post_id)
{
    if (array_key_exists('profile', $_POST)) {
        update_post_meta($post_id, 'profile', $_POST['profile']);
    }
}


add_action('admin_post_add_to_cart_cert_data', 'add_to_cart_cert_data');
add_action('admin_post_nopriv_add_to_cart_cert_data', 'add_to_cart_cert_data');
function add_to_cart_cert_data()
{
    include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
    include_once WC_ABSPATH . 'includes/class-wc-cart.php';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart_cert_data') {
        //var_dump($_POST);
        //exit;
        // Personal data
        $applicant_type = sanitize_text_field($_POST['applicant_type']);
        $document_type = sanitize_text_field($_POST['document_type']);
        $document_number = sanitize_text_field($_POST['document_number']);
        $document_country_name = sanitize_text_field($_POST['document_country']);
        if(strtoupper($document_country_name) == 'PANAMA'){
            $document_country ="PA";
        }else{
            $document_country ="PA";    
        }
        //$document_country = sanitize_text_field($_POST['document_country']);
        $applicant_name = sanitize_text_field($_POST['applicant_name']);
        $applicant_second_name = sanitize_text_field($_POST['applicant_second_name']);
        $applicant_lastname = sanitize_text_field($_POST['applicant_lastname']);
        $applicant_lastname_two = sanitize_text_field($_POST['applicant_lastname_two']);
        $applicant_birthdate = sanitize_text_field($_POST['applicant_birthdate']);
        $applicant_email = sanitize_email($_POST['applicant_email']);
        $applicant_number = sanitize_text_field($_POST['applicant_number']);
        if ($applicant_type == 'pfbusiness') {
            $business_ruc = sanitize_text_field($_POST['business_ruc']);
            $business_name = sanitize_text_field($_POST['business_name']);
            $business_area = sanitize_text_field($_POST['business_area']);
            $business_charge = sanitize_text_field($_POST['business_charge']);
            $rep_document_type = sanitize_text_field($_POST['rep_document_type']);
            $rep_document_number = sanitize_text_field($_POST['rep_document_number']);
            $rep_name = sanitize_text_field($_POST['rep_name']);
            $rep_lastname = sanitize_text_field($_POST['rep_lastname']);
            $rep_lastname2 = sanitize_text_field($_POST['rep_lastname2']);
        }
        if ($applicant_type == 'rep') {
            $business_ruc = sanitize_text_field($_POST['business_ruc']);
            $business_name = sanitize_text_field($_POST['business_name']);
            $business_charge = sanitize_text_field($_POST['business_charge']);
        }

        //business documents
        if ($applicant_type == 'pfbusiness' || $applicant_type == "rep") {
            $business_photo_ruc = $_POST['business_photo_ruc'];
            $business_photo_constitution = $_POST['business_photo_constitution'];
            $business_photo_representative_appointment = $_POST['business_photo_representative_appointment'];
            if ($applicant_type == 'pfbusiness') {
                $business_photo_representative_dni = $_POST['business_photo_representative_dni'];
                $business_photo_representative_autorization = $_POST['business_photo_representative_autorization'];
            }
        }
        $product_variation_id = intval($_POST['product_variation_id']);
        $product_id = wc_get_product($product_variation_id)->get_parent_id();
        // add product to cart
        // save data into cart item meta data.
        $cart_item_data = array(
            '_applicant_type' => $applicant_type,
            '_applicant_document_type' => $document_type,
            '_applicant_document_number' => $document_number,
            '_applicant_document_country' => $document_country,
            '_applicant_name' => $applicant_name,
            '_applicant_second_name' => $applicant_second_name,
            '_applicant_lastname' => $applicant_lastname,
            '_applicant_lastname_two' => $applicant_lastname_two,
            '_applicant_birthdate' => $applicant_birthdate,
            '_applicant_email' => $applicant_email,
            '_applicant_number' => $applicant_number
        );
        if ($applicant_type == 'pfbusiness') {
            $cart_item_data['_business_ruc'] = $business_ruc;
            $cart_item_data['_business_name'] = $business_name;
            $cart_item_data['_business_area'] = $business_area;
            $cart_item_data['_business_charge'] = $business_charge;
            $cart_item_data['_rep_document_type'] = $rep_document_type;
            $cart_item_data['_rep_document_number'] = $rep_document_number;
            $cart_item_data['_rep_name'] = $rep_name;
            $cart_item_data['_rep_lastname'] = $rep_lastname;
            $cart_item_data['_rep_lastname2'] = $rep_lastname2;
        }
        if ($applicant_type == 'rep') {
            $cart_item_data['_business_ruc'] = $business_ruc;
            $cart_item_data['_business_name'] = $business_name;
            $cart_item_data['_business_charge'] = $business_charge;
        }
        if(isset($applicant_photo_id_front)){ $cart_item_data['_applicant_photo_id_front'] = $applicant_photo_id_front;}
        if(isset($applicant_photo_id_back)){ $cart_item_data['_applicant_photo_id_back'] = $applicant_photo_id_back;}
        if(isset($applicant_photo_selfie)){ $cart_item_data['_applicant_photo_selfie'] = $applicant_photo_selfie;}
        if ($applicant_type == 'pfbusiness' || $applicant_type == "rep") {
            $cart_item_data['_business_photo_ruc'] = $business_photo_ruc;
            $cart_item_data['_business_photo_constitution'] = $business_photo_constitution;
            $cart_item_data['_business_photo_representative_appointment'] = $business_photo_representative_appointment;
            if ($applicant_type == 'pfbusiness') {
                $cart_item_data['_business_photo_representative_dni'] = $business_photo_representative_dni;
                $cart_item_data['_business_photo_representative_autorization'] = $business_photo_representative_autorization;
            }
        }

        if (is_null(WC()->cart)) {
            wc_load_cart();
        }
        WC()->cart->add_to_cart($product_id, 1, $product_variation_id, array(), $cart_item_data);
        // redirect to checkout page
        wp_redirect( wc_get_checkout_url() );
        exit;
    }
}

// Save all item cart meta data on order item meta data
add_action('woocommerce_checkout_create_order_line_item', 'save_cart_item_meta_data_as_order_item_meta_data', 10, 4);
function save_cart_item_meta_data_as_order_item_meta_data($item, $cart_item_key, $values, $order)
{
    global $wpdb;
    $table_countries = $wpdb->prefix . 'bniu_countries';
    $table_states = $wpdb->prefix . 'bniu_states';
    $table_cities = $wpdb->prefix . 'bniu_cities';
    if (isset($values['_applicant_type'])) {
        $item->add_meta_data('_applicant_type', $values['_applicant_type']);
    }
    if (isset($values['_applicant_document_type'])) {
        $item->add_meta_data('_applicant_document_type', $values['_applicant_document_type']);
    }
    if (isset($values['_applicant_document_number'])) {
        $item->add_meta_data('_applicant_document_number', $values['_applicant_document_number']);
    }
    if (isset($values['_applicant_document_country'])) {
        $item->add_meta_data('_applicant_document_country', $values['_applicant_document_country']);
    }
    if (isset($values['_applicant_name'])) {
        $item->add_meta_data('_applicant_name', $values['_applicant_name']);
    }
    if (isset($values['_applicant_second_name'])) {
        $item->add_meta_data('_applicant_second_name', $values['_applicant_second_name']);
    }
    if (isset($values['_applicant_lastname'])) {
        $item->add_meta_data('_applicant_lastname', $values['_applicant_lastname']);
    }
    if (isset($values['_applicant_lastname_two'])) {
        $item->add_meta_data('_applicant_lastname_two', $values['_applicant_lastname_two']);
    }
    if (isset($values['_applicant_birthdate'])) {
        $item->add_meta_data('_applicant_birthdate', $values['_applicant_birthdate']);
    }
    if (isset($values['_applicant_email'])) {
        $item->add_meta_data('_applicant_email', $values['_applicant_email']);
    }
    if (isset($values['_applicant_number'])) {
        $item->add_meta_data('_applicant_number', $values['_applicant_number']);
    }
    if (isset($values['_business_ruc'])) {
        $item->add_meta_data('_business_ruc', $values['_business_ruc']);
    }
    if (isset($values['_business_name'])) {
        $item->add_meta_data('_business_name', $values['_business_name']);
    }
    if (isset($values['_business_area'])) {
        $item->add_meta_data('_business_area', $values['_business_area']);
    }
    if (isset($values['_business_charge'])) {
        $item->add_meta_data('_business_charge', $values['_business_charge']);
    }
    if (isset($values['_rep_document_type'])) {
        $item->add_meta_data('_rep_document_type', $values['_rep_document_type']);
    }
    if (isset($values['_rep_document_number'])) {
        $item->add_meta_data('_rep_document_number', $values['_rep_document_number']);
    }
    if (isset($values['_rep_name'])) {
        $item->add_meta_data('_rep_name', $values['_rep_name']);
    }
    if (isset($values['_rep_lastname'])) {
        $item->add_meta_data('_rep_lastname', $values['_rep_lastname']);
    }
    if (isset($values['_rep_lastname2'])) {
        $item->add_meta_data('_rep_lastname2', $values['_rep_lastname2']);
    }
    /*if (isset($values['_business_address'])) {
        $item->add_meta_data('_business_address', $values['_business_address']);
    }
    if (isset($values['_business_country'])) {
        $item->add_meta_data('_business_country', $values['_business_country']);
    }
    if (isset($values['_business_state'])) {
        $state_name = $wpdb->get_var("SELECT name FROM " . $table_states . " WHERE id = " . $values['_business_state']);
        $item->add_meta_data('_business_state', $state_name);
    }
    if (isset($values['_business_city'])) {
        $city_name = $wpdb->get_var("SELECT name FROM " . $table_cities . " WHERE id = " . $values['_business_city']);
        $item->add_meta_data('_business_city', $city_name);
    }*/
    if (isset($values['_applicant_photo_id_front'])) {
        $item->add_meta_data('_applicant_photo_id_front', $values['_applicant_photo_id_front']);
    }
    if (isset($values['_applicant_photo_id_back'])) {
        $item->add_meta_data('_applicant_photo_id_back', $values['_applicant_photo_id_back']);
    }
    if (isset($values['_applicant_photo_selfie'])) {
        $item->add_meta_data('_applicant_photo_selfie', $values['_applicant_photo_selfie']);
    }
    if (isset($values['_business_photo_ruc'])) {
        $item->add_meta_data('_business_photo_ruc', $values['_business_photo_ruc']);
    }
    if (isset($values['_business_photo_constitution'])) {
        $item->add_meta_data('_business_photo_constitution', $values['_business_photo_constitution']);
    }
    if (isset($values['_business_photo_representative_appointment'])) {
        $item->add_meta_data('_business_photo_representative_appointment', $values['_business_photo_representative_appointment'], true);
    }
    if (isset($values['_business_photo_representative_dni'])) {
        $item->add_meta_data('_business_photo_representative_dni', $values['_business_photo_representative_dni'], true);
    }
    if (isset($values['_business_photo_representative_autorization'])) {
        $item->add_meta_data('_business_photo_representative_autorization', $values['_business_photo_representative_autorization'], true);
    }
}
// hide meta data from order item in order details page
/*
add_filter('woocommerce_hidden_order_itemmeta', 'filter_hidden_order_itemmeta', 10, 1);
function filter_hidden_order_itemmeta($keys)
{
    $hide_meta = array(
        'type',
        'document_type',
        'document_number',
        'applicant_name',
        'applicant_lastname',
        'applicant_lastname2',
        'applicant_birthdate',
        'applicant_nacionality',    
        'applicant_email',
        'applicant_number',
        'applicant_address',
        'applicant_country',
        'business_ruc',
        'business_name',
        'business_area',
        'business_charge',
        'rep_document_type',
        'rep_document_number',
        'rep_name',
        'rep_lastname',
        'rep_lastname2',
        'business_address',
        'business_country',
        'business_state',
        'business_city',
        'applicant_photo_id_front',
        'applicant_photo_id_back',
        'applicant_photo_selfie',
        'business_photo_ruc',
        'business_photo_constitution',
        'business_photo_representative_appointment',
        'business_photo_representative_dni',
        'business_photo_representative_autorization'
    );
    return array_merge($keys, $hide_meta);
}*/

/*
This call simply requires a Registration Authority (RA) id number. Scratchcards must be available for this RA for successful response.

curl -i -X GET https://api.uanataca.comscratchcards/get_first_unused/ \
-H 'Content-Type: application/json' \
--cert 'cer.pem' --key 'key.pem'
-d '{
  "ra": "121"
}'
The response is a JSON object containing the single-use Scratchcard associated data. The scratchcard number sn must be added to the Create Request call.

{
  "pk": 1193,
  "sn": "1256948",
  "secrets": "{\"erc\": \"6292998123\", \"enrollment_code\": \"_,463vt:\", \"pin\": \"08695572\", \"puk\": \"52351291\"}",
  "registration_authority": 121
}
*/
function getScratchCard($ra)
{
    $uanataca_dev_active = get_option('uanataca_dev_active');
    if ($uanataca_dev_active) {
        $url = get_option('uanataca_api_url_dev') . 'scratchcards/get_first_unused/';
        $ch = curl_init($url);
        $key = dirname(__FILE__) . '/lib/certificates/key-dev.pem';
        $cert = dirname(__FILE__) . '/lib/certificates/cer-dev.pem';
        curl_setopt($ch, CURLOPT_SSLCERT, $cert);
        curl_setopt($ch, CURLOPT_SSLKEY, $key);
    } else {
        $url = get_option('uanataca_api_url') . 'scratchcards/get_first_unused/';
        $ch = curl_init($url);
        $key = dirname(__FILE__) . '/lib/certificates/key.pem';
        $cert = dirname(__FILE__) . '/lib/certificates/cer.pem';
        curl_setopt($ch, CURLOPT_SSLCERT, $cert);
        curl_setopt($ch, CURLOPT_SSLKEY, $key);
    }
    $data = array('ra' => $ra);
    $data_string = json_encode($data);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    $result = curl_exec($ch);
    $result = json_decode($result, true);
    $log_data = array(
        'request' => $result['pk'],
        'date' => date('Y-m-d H:i:s'),
        'type' => 'scratchCard',
        // $result into stringify json.
        'data' => json_encode($result)
    );
    global $wpdb;
    $table_requests_logs = $wpdb->prefix . 'bniu_certificate_requests_logs';
    $wpdb->insert($table_requests_logs, $log_data);
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        $log_data = array(
            'request' => 0,
            'date' => date('Y-m-d H:i:s'),
            'type' => 'scratchCard',
            // $result into stringify json.
            'data' => $error_msg
        );
        $wpdb->insert($table_requests_logs, $log_data);
        $error_log = fopen(plugin_dir_path(__FILE__) . 'error_log.txt', 'ab+');
        fwrite($error_log, $error_msg.'-> getScratchCard'. "\n");
        fclose($error_log);
    }
    curl_close($ch);
    return $result;
}
/*
This call must include enough information to identify the requester user. The full description of the arguments accepted by this endpoint can be found in the API call detailed documentation.
curl -i -X POST 'https://api.uanataca.comrequests/' \
-H 'Content-Type: application/json' \
--cert 'cer.pem' --key 'key.pem'
-d '{
  "profile": "PFnubeAFCiudadano",
  "scratchcard": "5053311",
  "secure_element": "2",
  "registration_authority": "116",
  "country_name": "ES",
  "serial_number": "12345678A",
  "id_document_country": "ES",
  "id_document_type": "IDC",
  "given_name": "Name",
  "surname_1": "Surname1",
  "surname_2" "Surname2"
  "email": "mail@domain.com",
  "mobile_phone_number": "+34611223344",
  "paperless_mode": 1
}'
The response is a JSON containing info from the created request. One of the most important parameters from this JSON is the pk which represents the request unique identifier and is used for every operation related to this request.

{
  "pk": 11223,
  "given_name": "Name",
  "surname_1": "Surname1",
  "surname_2": "Surname2",
  "sex": null,
  "id_document_type": "IDC",
  "id_document_country": "ES",
  "serial_number": "12345678A",
  "country_name": "ES",
  "citizenship": null,
  "residence": null,
  "organization_email": null,
  "email": "mail@domain.com",
  "title": null,
  "organization_name": null,
  "organizational_unit_1": null,
  ...
}
*/
function createRequest(
    $videoid_mode,
    $validity,
    $paperless_mode,
    $type,
    $profile,
    $scratchcard,
    $secure_element,
    $registration_authority,
    $id_document_country,
    $id_document_type,
    $document_number,
    $given_name,
    $surname_1,
    $surname_2,
    $email,
    $birthdate,
    $mobile_phone_number,
    $business_ruc,
    $business_name,
    $business_area,
    $business_charge,
    $rep_document_type,
    $rep_document_number,
    $rep_name,
    $rep_lastname,
    $rep_lastname2
) {
    $uanataca_dev_active = get_option('uanataca_dev_active');
    $site_url = get_site_url();
    $webhook_url = $site_url . '/wp-json/uanataca/v1/webhook';
    if ($uanataca_dev_active) {
        $url = get_option('uanataca_api_url_dev') . 'requests/';
        $ch = curl_init($url);
        $key = dirname(__FILE__) . '/lib/certificates/key-dev.pem';
        $cert = dirname(__FILE__) . '/lib/certificates/cer-dev.pem';
        curl_setopt($ch, CURLOPT_SSLCERT, $cert);
        curl_setopt($ch, CURLOPT_SSLKEY, $key);
    } else {
        $url = get_option('uanataca_api_url') . 'requests/';
        $ch = curl_init($url);
        $key = dirname(__FILE__) . '/lib/certificates/key.pem';
        $cert = dirname(__FILE__) . '/lib/certificates/cer.pem';
        curl_setopt($ch, CURLOPT_SSLCERT, $cert);
        curl_setopt($ch, CURLOPT_SSLKEY, $key);
    }
    $documentType = '';
    if ($id_document_type == 'pasaporte') {
        $documentType =  'PAS';
    }
    if ($id_document_type == 'cedula') {
        $documentType = 'IDC';
    }
    $birthdate = date('d/m/Y', strtotime($birthdate));
    $data = array(
        'validity_time' => $validity,
        'profile' => $profile,
        'scratchcard' => $scratchcard,
        'secure_element' => $secure_element,
        'registration_authority' => $registration_authority,
        'country_name' => 'PA',
        'id_document_country' => $id_document_country,
        'id_document_type' => $documentType,
        'serial_number' => $document_number,
        'given_name' => $given_name,
        'surname_1' => $surname_1,
        'surname_2' => $surname_2,
        'birth_date' => $birthdate,
        'email' => $email,
        'mobile_phone_number' => $mobile_phone_number,
        'paperless_mode' => $paperless_mode,
        'videoid_mode' => $videoid_mode,
        'webhook_url' => $webhook_url
    );
    if ($type == 'rep' || $type == 'pfbusiness') {
        $data['organization_identifier'] = $business_ruc;
        $data['organization_name'] = $business_name;
        $data['organizational_unit_1'] = $business_charge;
        if ($type == 'pfbusiness') {
            $data['responsible_legal_documents'] = $rep_document_type;
            $data['responsible_serial'] = $rep_document_number;
            $data['responsible_name'] = $rep_name;
            $data['responsible_first_surname'] = $rep_lastname;
            $data['responsible_second_surname'] = $rep_lastname2;
            $data['responsible_position'] = $business_area;
        }
    }

    $data_string = json_encode($data);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 0);
    $result = curl_exec($ch);
    $result = json_decode($result, true);
    // save result into wp_bniu_requests_logs table
    $log_data = array(
        'request' => $result['pk'],
        'date' => date('Y-m-d H:i:s'),
        'type' => 'createRequest',
        'data' => json_encode($result)
    );
    global $wpdb;
    $table_requests_logs = $wpdb->prefix . 'bniu_certificate_requests_logs';
    $wpdb->insert($table_requests_logs, $log_data);
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        $log_data = array(
            'request' => 0,
            'date' => date('Y-m-d H:i:s'),
            'type' => 'createRequest',
            // $result into stringify json.
            'data' => $error_msg
        );
        print_r($error_msg);
        $wpdb->insert($table_requests_logs, $log_data);
        $error_log = fopen(plugin_dir_path(__FILE__) . 'error_log.txt', 'ab+');
        fwrite($error_log, $error_msg.' -> createRequest'. "\n");
        fclose($error_log);
    }
    curl_close($ch);
    return $result;
}

function createRequestExternalFlow($data){


    $uanataca_dev_active = get_option('uanataca_dev_active');

    if ($uanataca_dev_active) {
        $url = get_option('external_api_url_dev') . 'create/';
    }else{
        $url = get_option('external_api_url') . 'create/';
    }   
    
    $ch = curl_init($url);

    $data_string = json_encode($data);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 0);
    $result = curl_exec($ch);
    $result = json_decode($result, true);

    // save result into wp_bniu_requests_logs table
    $log_data = array(
        'request' => $result["token"],
        'date' => date('Y-m-d H:i:s'),
        'type' => 'createRequest',
        'data' => $data_string
    );
    global $wpdb;
    $table_requests_logs = $wpdb->prefix . 'bniu_certificate_requests_logs';
    $wpdb->insert($table_requests_logs, $log_data);
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        $log_data = array(
            'request' => 0,
            'date' => date('Y-m-d H:i:s'),
            'type' => 'createRequestExternalFlow',
            // $result into stringify json.
            'data' => $error_msg
        );
        //print_r($error_msg);
        $wpdb->insert($table_requests_logs, $log_data);
        $error_log = fopen(plugin_dir_path(__FILE__) . 'error_log.txt', 'ab+');
        fwrite($error_log, $error_msg.' -> createRequestExternalFlow'. "\n");
        fclose($error_log);
    }

    curl_close($ch);
    return $result;

}

function updateRequest(
    $request,
    $scratchcard,
    $registration_authority,
    $order_id,
    $order_item_id
) {
    if ($uanataca_dev_active) {
        $url = get_option('uanataca_api_url_dev') . 'requests/' . $request . '/';
        $ch = curl_init($url);
        $key = dirname(__FILE__) . '/lib/certificates/key-dev.pem';
        $cert = dirname(__FILE__) . '/lib/certificates/cer-dev.pem';
        curl_setopt($ch, CURLOPT_SSLCERT, $cert);
        curl_setopt($ch, CURLOPT_SSLKEY, $key);
    } else {
        $url = get_option('uanataca_api_url') . 'requests/' . $request . '/';
        $ch = curl_init($url);
        $key = dirname(__FILE__) . '/lib/certificates/key.pem';
        $cert = dirname(__FILE__) . '/lib/certificates/cer.pem';
        curl_setopt($ch, CURLOPT_SSLCERT, $cert);
        curl_setopt($ch, CURLOPT_SSLKEY, $key);
    }
    $documentType = '';
    $order = wc_get_order($order_id);
    if(!$order) {
        return;
    }
    $items = $order->get_items();
    $order_item = $items[$order_item_id];
    $type = $order_item->get_meta('_applicant_type');
    $id_document_type = $order_item->get_meta('_applicant_document_type');
    $birthdate = $order_item->get_meta('_applicant_birthdate');
    if ($id_document_type == 'pasaporte') {
        $documentType =  'PAS';
    }
    if ($id_document_type == 'cedula') {
        $documentType = 'IDC';
    }
    $birthdate = date('d/m/Y', strtotime($birthdate));
    $id_document_country = $order_item->get_meta('_applicant_document_country');
    $document_number = $order_item->get_meta('_applicant_document_number');
    $given_name = $order_item->get_meta('_applicant_name');
    if($order_item->get_meta('_applicant_second_name')){
        $given_name .= ' ' . $order_item->get_meta('_applicant_second_name');
    }
    $surname_1 = $order_item->get_meta('_applicant_lastname');
    $surname_2 = $order_item->get_meta('_applicant_lastname_two');
    $email = $order_item->get_meta('_applicant_email');
    $mobile_phone_number = $order_item->get_meta('_applicant_number');
    $data = array(
        'scratchcard' => $scratchcard,
        'registration_authority' => $registration_authority,
        'country_name' => 'PA',
        'id_document_country' => $id_document_country,
        'id_document_type' => $documentType,
        'serial_number' => $document_number,
        'given_name' => $given_name,
        'surname_1' => $surname_1,
        'surname_2' => $surname_2,
        'birth_date' => $birthdate,
        'email' => $email,
        'mobile_phone_number' => $mobile_phone_number
    );
    if ($type == 'pf') {
        /*$address = $order_item->get_meta('_applicant_address');
        $country_name = $order_item->get_meta('_applicant_country');
        $data['residence_address'] = $address;
        $data['country_name'] = $country_name;*/
    }
    if ($type == 'rep' || $type == 'pfbusiness') {
        $business_ruc = $order_item->get_meta('_business_ruc');
        $business_name = $order_item->get_meta('_business_name');
        $business_charge = $order_item->get_meta('_business_charge');
        /*$business_address = $order_item->get_meta('_business_address');
        $business_state = $order_item->get_meta('_business_state');
        $business_city = $order_item->get_meta('_business_city');*/
        $data['organization_identifier'] = $business_ruc;
        $data['organization_name'] = $business_name;
        $data['organizational_unit_1'] = $business_charge;
        /*$data['organization_address'] = $business_address;
        $data['organization_country'] = $business_country;
        $data['organization_state'] = $business_state;
        $data['organization_city'] = $business_city;*/
        if ($type == 'pfbusiness') {
            $rep_document_type = $order_item->get_meta('_rep_document_type');
            $rep_document_number = $order_item->get_meta('_rep_document_number');
            $rep_name = $order_item->get_meta('_rep_name');
            $rep_lastname = $order_item->get_meta('_rep_lastname');
            $rep_lastname2 = $order_item->get_meta('_rep_lastname2');
            $business_area = $order_item->get_meta('_business_area');
            $data['responsible_legal_documents'] = $rep_document_type;
            $data['responsible_serial'] = $rep_document_number;
            $data['responsible_name'] = $rep_name;
            $data['responsible_first_surname'] = $rep_lastname;
            $data['responsible_second_surname'] = $rep_lastname2;
            $data['responsible_position'] = $business_area;
        }
    }

    $data_string = json_encode($data);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 0);
    $result = curl_exec($ch);
    $result = json_decode($result, true);
    // save result into wp_bniu_requests_logs table
    $log_data = array(
        'request' => $request,
        'date' => date('Y-m-d H:i:s'),
        'type' => 'updateRequest',
        'data' => json_encode($result)
    );
    global $wpdb;
    $table_requests_logs = $wpdb->prefix . 'bniu_certificate_requests_logs';
    $wpdb->insert($table_requests_logs, $log_data);
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        $log_data = array(
            'request' => $request,
            'date' => date('Y-m-d H:i:s'),
            'type' => 'updateRequest',
            // $result into stringify json.
            'data' => $error_msg
        );
        print_r($error_msg);
        $wpdb->insert($table_requests_logs, $log_data);
        $error_log = fopen(plugin_dir_path(__FILE__) . 'error_log.txt', 'ab+');
        fwrite($error_log, $error_msg.' -> updateRequest'. "\n");
        fclose($error_log);
    }
    curl_close($ch);
    return $result;
}

/*
The created request needs documents, so we can query with an HTTP POST request to upload the files.

The required documents for every request are:
document_front : The photo of the front side of the requester ID card
document_rear : The photo of the rear side of the requester ID card
extra_document : If necessary, it is possibile to upload extra documents that represents additional requester information

Additionally a selfie of the requester showing the ID card under the chin can be uploaded as an evidence under the type document_owner.

Note that this endpoint has to be queried for every document type that the Request needs.

curl -i -X POST 'https://api.uanataca.comrequests/11223/pl_upload_document/' \
--cert 'cer.pem' --key 'key.pem'
  -H 'Content-Type: multipart/form-data' \
  -F document=@/idc_front.jpg \
  -F type=document_front
The response contains the uploaded document unique identifier associated to the request.

{
  "pk": 11314,
  "type": "document_front"
}
*/
function uploadDocument($request_pk, $document, $type)
{
    $uanataca_dev_active = get_option('uanataca_dev_active');
    if ($uanataca_dev_active) {
        $url = get_option('uanataca_api_url_dev') . 'requests/' . $request_pk . '/pl_upload_document/';
        $ch = curl_init($url);
        $key = dirname(__FILE__) . '/lib/certificates/key-dev.pem';
        $cert = dirname(__FILE__) . '/lib/certificates/cer-dev.pem';
        curl_setopt($ch, CURLOPT_SSLCERT, $cert);
        curl_setopt($ch, CURLOPT_SSLKEY, $key);
    } else {
        $url = get_option('uanataca_api_url') . 'requests/' . $request_pk . '/pl_upload_document/';
        $ch = curl_init($url);
        $key = dirname(__FILE__) . '/lib/certificates/key.pem';
        $cert = dirname(__FILE__) . '/lib/certificates/cer.pem';
        curl_setopt($ch, CURLOPT_SSLCERT, $cert);
        curl_setopt($ch, CURLOPT_SSLKEY, $key);
    }
    // extract extension from base64 string.
    $extension = explode('/', mime_content_type($document))[1];
    // $document is a base64 string, create a temporal file to send it.
    $data = array(
        'document' => new CURLFile($document, mime_content_type($document), 'document.' . $extension),
        'type' => $type
    );
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    $result = curl_exec($ch);
    $result = json_decode($result, true);
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        $error_log = fopen(plugin_dir_path(__FILE__) . 'error_log.txt', 'ab+');
        fwrite($error_log, $error_msg.' -> uploadDocument - '.$type. "\n");
        fclose($error_log);
    }
    curl_close($ch);
    return $result;
}

/*
A Registration Authority Officer must first validate the request data and documentation. If the information is correct, the RAO will approve the request by signing the receipt and contract with his or her own cloud certificate.

API Reference: Generate RAO Declaration

curl -i -X POST https://api.uanataca.comrequests/25139/generates_tbs_receipt/ \
-H 'Content-Type: application/json' \
-d '{
  "rao": "1400",
  "type": "APPROVE"
}'
The following JSON object contains the receipt:

{
  "serial_number": "3ef3696d2939241d",
  "receipt": "El operador RAO_Name RAO_Surname1 con número de identificación 12345678P\r\nactuando en calidad de operador autorizado de registro del prestador de servicios\r\n
  de confianza UANATACA, S.A. con NIF A66721499, (UANATACA en lo sucesivo)\r\n\r\nDECLARA\r\n\r\nQue previa verificación de acuerdo a la Declaración de Prácticas de
  UANATACA\r\npublicadas en www.uanataca.com, la información detallada a continuación es\r\ncorrecta y será incluida (donde aplicable) en la solicitud de 
  certificados\r\ncualificados:\r\n\r\n- Datos de Identificación de la solicitud de certificados: 36893\r\n- Nombre y Apellidos del Firmante: Name Surname1 Surname2\r\n- DNI/
  NIE/PASAPORTE del Firmante: 11111111B\r\n- Dirección de correo electrónico del Firmante: mail@domain.com\r\n\r\n\r\n18/03/
  2021\r\n\r\n\r\n\r\n--------------------------------------------------------------------\r\nFdo. User Admin\r\nOperador autorizado de registro"
}
*/
function generateTbsReceipt($request_pk, $rao, $type)
{
    $uanataca_dev_active = get_option('uanataca_dev_active');
    if ($uanataca_dev_active) {
        $url = get_option('uanataca_api_url_dev') . 'requests/' . $request_pk . '/generates_tbs_receipt/';
        $ch = curl_init($url);
        $key = dirname(__FILE__) . '/lib/certificates/key-dev.pem';
        $cert = dirname(__FILE__) . '/lib/certificates/cer-dev.pem';
        curl_setopt($ch, CURLOPT_SSLCERT, $cert);
        curl_setopt($ch, CURLOPT_SSLKEY, $key);
    } else {
        $url = get_option('uanataca_api_url') . 'requests/' . $request_pk . '/generates_tbs_receipt/';
        $ch = curl_init($url);
        $key = dirname(__FILE__) . '/lib/certificates/key.pem';
        $cert = dirname(__FILE__) . '/lib/certificates/cer.pem';
        curl_setopt($ch, CURLOPT_SSLCERT, $cert);
        curl_setopt($ch, CURLOPT_SSLKEY, $key);
    }
    $data = array(
        'rao' => $rao,
        'type' => $type
    );
    $data_string = json_encode($data);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    $result = curl_exec($ch);
    $result = json_decode($result, true);
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        $error_log = fopen(plugin_dir_path(__FILE__) . 'error_log.txt', 'ab+');
        fwrite($error_log, $error_msg.' -> generateTbsReceipt'. "\n");
        fclose($error_log);
    }
    curl_close($ch);
    return $result;
}
/*
Similarly, it is necessary to retrieve the service contract and present it to the RAO before approval.

API Reference: Generate Contract (use type: contract in body)

curl -i -X POST https://api.uanataca.comrequests/25139/pl_get_document/ \
-H 'Content-Type: application/json' \
-d '{
  "type": "contract"
  "rao_id": "1400"    
}'
The response consists in a JSON structure containing the contract in Base64 format.

[
  {
    "document": "JVBERi0xLjQKJZOMi54gUmVwb3J0TGFiIEdlbmVyYXRlZCBQREYgZG9jdW1lbnQgaHR0cDovL3d3\ndy5yZXBvcnRsYWIuY29tCjEgMCBvYmoKPDwKL0YxIDIgMCBSCj4 (...)\n",
    "type": "contract"
  }
]
*/
function getContract($request_pk, $rao_id)
{
    $uanataca_dev_active = get_option('uanataca_dev_active');
    if ($uanataca_dev_active) {
        $url = get_option('uanataca_api_url_dev') . 'requests/' . $request_pk . '/pl_get_document/';
        $ch = curl_init($url);
        $key = dirname(__FILE__) . '/lib/certificates/key-dev.pem';
        $cert = dirname(__FILE__) . '/lib/certificates/cer-dev.pem';
        curl_setopt($ch, CURLOPT_SSLCERT, $cert);
        curl_setopt($ch, CURLOPT_SSLKEY, $key);
    } else {
        $url = get_option('uanataca_api_url') . 'requests/' . $request_pk . '/pl_get_document/';
        $ch = curl_init($url);
        $key = dirname(__FILE__) . '/lib/certificates/key.pem';
        $cert = dirname(__FILE__) . '/lib/certificates/cer.pem';
        curl_setopt($ch, CURLOPT_SSLCERT, $cert);
        curl_setopt($ch, CURLOPT_SSLKEY, $key);
    }
    $data = array(
        'type' => 'contract',
        'rao_id' => $rao_id
    );
    $data_string = json_encode($data);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    $result = curl_exec($ch);
    $result = json_decode($result, true);
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        $error_log = fopen(plugin_dir_path(__FILE__) . 'error_log.txt', 'ab+');
        fwrite($error_log, $error_msg.' -> getContract'. "\n");        
        fclose($error_log);
    }
    curl_close($ch);
    return $result;
}
/*
In order to approve a Request, this must be in the status of CREATED and must have at least the required documents (document_front and document_rear).

curl -i -X POST 'https://api.uanataca.comrequests/' \
-H 'Content-Type: application/json' \
--cert 'cer.pem' --key 'key.pem'
-d '{
  "username": "1000279",
  "password": "3DPTm:N4",
  "pin": "23bYQq9a",
  "rao_id": 123
}'
*/
function approveRequest($request_pk, $username, $password, $pin, $rao_id)
{
    $uanataca_dev_active = get_option('uanataca_dev_active');
    if ($uanataca_dev_active) {
        $url = get_option('uanataca_api_url_dev') . 'requests/' . $request_pk . '/approve/';
        $ch = curl_init($url);
        $key = dirname(__FILE__) . '/lib/certificates/key-dev.pem';
        $cert = dirname(__FILE__) . '/lib/certificates/cer-dev.pem';
        curl_setopt($ch, CURLOPT_SSLCERT, $cert);
        curl_setopt($ch, CURLOPT_SSLKEY, $key);
    } else {
        $url = get_option('uanataca_api_url') . 'requests/' . $request_pk . '/approve/';
        $ch = curl_init($url);
        $key = dirname(__FILE__) . '/lib/certificates/key.pem';
        $cert = dirname(__FILE__) . '/lib/certificates/cer.pem';
        curl_setopt($ch, CURLOPT_SSLCERT, $cert);
        curl_setopt($ch, CURLOPT_SSLKEY, $key);
    }
    $data = array(
        'username' => $username,
        'password' => $password,
        'pin' => $pin,
        'rao_id' => $rao_id
    );
    $data_string = json_encode($data);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    $result = curl_exec($ch);
    $result = json_decode($result, true);
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        $error_log = fopen(plugin_dir_path(__FILE__) . 'error_log.txt', 'ab+');
        fwrite($error_log, $error_msg.' -> approveRequest '. "\n");
        fclose($error_log);
    }
    curl_close($ch);
    return $result;
}
/*
One-Shot API requires a Webhook implemented on customer business side to manage our service callbacks. Every request status change will trigger a simple event-notification via HTTP POST, consisting on a JSON object to an URL that must be explicitly included as a parameter in the Create Video ID Request call. Keep in mind that the webhook via parameter is just for demo purposes. You will have to contact your officer to set a webhook in your RA.

The following is a sample view of the JSON object that is sent as a callback at every status change:

{
    "status": "VIDEOINCOMPLETE", 
    "date": "2021-07-20T08:08:21.132394", 
    "previous_status": "VIDEOPENDING", 
    "request": 46760, 
    "registration_authority": 455
}
*/
function webhook()
{
    $data = json_decode(file_get_contents('php://input'), true);

    global $wpdb;
    $table_name = $wpdb->prefix . 'bniu_certificate_requests_logs';
    $wpdb->insert(
        $table_name,
        array(
            'request' => $data['request'] ? $data['request'] : 0,
            'type' => 'webhook',
            'date' => date('Y-m-d H:i:s'),
            'data' => json_encode($data)
        )
    );

    $table_name = $wpdb->prefix . 'bniu_certificate_requests';
    $wpdb->update(
        $table_name,
        array('status' => $data['status']),
        array('request' => $data['request'])
    );
    handleVideoRequest($data);
}

function handleVideoRequest($data)
{
    global $wpdb;
    $status = $data['status'];
    $ra = get_option('registration_authority');
    switch ($status) {
        case 'ENROLLREADY' || 'CREATED':
            // send email to user
            $scratchcard = getScratchCard($ra);
            $scratchcard = $scratchcard["sn"];
            $table_name = $wpdb->prefix . 'bniu_certificate_requests';
            $request = $wpdb->get_row("SELECT * FROM $table_name WHERE request = " . $data['request']);
            if ($request) {
                $order_item_id = $request->order_item_id;
                $order_id = $request->order;
                $update_request = updateRequest(
                    $data['request'],
                    $scratchcard,
                    $ra,
                    $order_id,
                    $order_item_id
                );
                $order = wc_get_order($order_id);
                if(!$order) {
                    return;
                }
                $items = $order->get_items();
                $order_item = $items[$order_item_id];
                $type = trim($order_item->get_meta('_applicant_type'));
                
                if ($type != 'pf') {
                    $business_ruc = $order_item->get_meta('_business_ruc');
                    $upload_business_ruc = uploadDocument($data['request'], $business_ruc, 'extra_document');
                    $wpdb->insert(
                        'wp_bniu_certificate_requests_meta',
                        array(
                            'request' => $data['request'],
                            'step' => 2,
                            'meta_key' => 'business_ruc',
                            'meta_value' => $upload_business_ruc["pk"]
                        )
                    );
                    $business_photo_constitution = $order_item->get_meta('_business_photo_constitution');
                    $upload_business_photo_constitution = uploadDocument($data['request'], $business_photo_constitution, 'extra_document');
                    $wpdb->insert(
                        'wp_bniu_certificate_requests_meta',
                        array(
                            'request' => $data['request'],
                            'step' => 2,
                            'meta_key' => 'business_photo_constitution',
                            'meta_value' => $upload_business_photo_constitution["pk"]
                        )
                    );
                    $business_photo_representative = $order_item->get_meta('_business_photo_representative_appointment');
                    $upload_business_photo_representative = uploadDocument($data['request'], $business_photo_representative, 'extra_document');
                    $wpdb->insert(
                        'wp_bniu_certificate_requests_meta',
                        array(
                            'request' => $data['request'],
                            'step' => 2,
                            'meta_key' => 'business_photo_representative',
                            'meta_value' => $upload_business_photo_representative["pk"]
                        )
                    );
                    if ($type == 'pfbusiness') {
                        $rep_document = $order_item->get_meta('_rep_document');
                        $upload_rep_document = uploadDocument($data['request'], $rep_document, 'extra_document');
                        $wpdb->insert(
                            'wp_bniu_certificate_requests_meta',
                            array(
                                'request' => $data['request'],
                                'step' => 2,
                                'meta_key' => 'rep_document',
                                'meta_value' => $upload_rep_document["pk"]
                            )
                        );
                        $rep_photo = $order_item->get_meta('_rep_photo');
                        $upload_rep_photo = uploadDocument($data['request'], $rep_photo, 'extra_document');
                        $wpdb->insert(
                            'wp_bniu_certificate_requests_meta',
                            array(
                                'request' => $data['request'],
                                'step' => 2,
                                'meta_key' => 'rep_photo',
                                'meta_value' => $upload_rep_photo["pk"]
                            )
                        );
                    }
                }
            }
            break;
        default:
            $table_name = $wpdb->prefix . 'bniu_certificate_requests_logs';
            $wpdb->insert(
                $table_name,
                array(
                    'request' => $data['request'] ? $data['request'] : 0,
                    'type' => 'webhook',
                    'date' => date('Y-m-d H:i:s'),
                    'data' => json_encode($data)
                )
            );
            break;
    }
}

add_action('rest_api_init', function () {
    register_rest_route('uanataca/v1', '/webhook', array(
        'methods' => 'POST',
        'callback' => 'webhook',
        'permission_callback' => '__return_true'
    ));
});


/*
// Check if ACF plugin is active and if not, show a notice in the admin dashboard with a button to install it.
add_action('admin_notices', 'bniu_plugin_admin_notice');
function bniu_plugin_admin_notice()
{
    if (!is_plugin_active('advanced-custom-fields/acf.php')) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e('Uanataca Integration requires the Advanced Custom Fields plugin to be installed and active. Please install and activate the plugin.'); ?></p>
            <a href="<?php echo admin_url('plugin-install.php?s=advanced+custom+fields&tab=search&type=term'); ?>" class="button button-primary"><?php _e('Install Advanced Custom Fields') ?></a>
        </div>
        <?php
    }
}*/
// create admin menu uanataca-integration
add_action('admin_menu', 'bniu_plugin_admin_menu');
function bniu_plugin_admin_menu()
{
    add_menu_page('Uanataca Integration', 'Uanataca Integration', 'manage_options', 'uanataca-integration', 'bniu_plugin_admin_page', 'dashicons-admin-generic', 100);
}
// create admin page uanataca-integration
function bniu_plugin_admin_page()
{
    acf_enqueue_scripts();
?>
    <div class="wrap">
        <h1>Uanataca Integration</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('uanataca-integration');
            do_settings_sections('uanataca-integration');
            submit_button();
            ?>
        </form>
    </div>
<?php
}

// ajax for get variations of product action named get_variations.
add_action('wp_ajax_get_variations', 'get_variations');
add_action('wp_ajax_nopriv_get_variations', 'get_variations');
function get_variations()
{
    $product_id = intval($_POST['product_id']);
    $product = wc_get_product($product_id);
    $variations = $product->get_available_variations();
    wp_send_json($variations);
}

// register settings for uanataca-integration
add_action('admin_init', 'bniu_plugin_admin_init');
function bniu_plugin_admin_init()
{
    register_setting('uanataca-integration', 'uanataca_api_key');
    register_setting('uanataca-integration', 'uanataca_api_secret');
    
    register_setting('uanataca-integration', 'uanataca_api_url');
    register_setting('uanataca-integration', 'uanataca_api_url_dev');

    register_setting('uanataca-integration', 'external_api_url');
    register_setting('uanataca-integration', 'external_api_url_dev');

    register_setting('uanataca-integration', 'uanataca_dev_active');
    register_setting('uanataca-integration', 'certificate_products_term');
    register_setting('uanataca-integration', 'registration_authority');
    register_setting('uanataca-integration', 'registration_authority_officer');
    register_setting('uanataca-integration', 'unataca_rao_username');
    register_setting('uanataca-integration', 'unataca_rao_password');
    register_setting('uanataca-integration', 'unataca_rao_pin');
    register_setting('uanataca-integration', 'unataca-workflow');

    register_setting('uanataca-integration', 'certificado_pf_firma_electronica_calificada');
    register_setting('uanataca-integration', 'certificado_pf_firma_electronica_facturar');

    add_settings_field('uanataca_dev_active', 'Uanataca Dev Active', 'bniu_plugin_setting_checkbox', 'uanataca-integration', 'uanataca-integration', array('option_name' => 'uanataca_dev_active'));
    
    add_settings_field('uanataca_api_url_dev', 'Uanataca API URL Dev', 'bniu_plugin_setting_input', 'uanataca-integration', 'uanataca-integration', array('option_name' => 'uanataca_api_url_dev'));
    add_settings_field('uanataca_api_url', 'Uanataca API URL', 'bniu_plugin_setting_input', 'uanataca-integration', 'uanataca-integration', array('option_name' => 'uanataca_api_url'));

    add_settings_field('external_api_url_dev', 'External API URL Dev', 'bniu_plugin_setting_input', 'uanataca-integration', 'uanataca-integration', array('option_name' => 'external_api_url_dev'));
    add_settings_field('external_api_url', 'External API URL', 'bniu_plugin_setting_input', 'uanataca-integration', 'uanataca-integration', array('option_name' => 'external_api_url'));
    
    
    add_settings_section('uanataca-integration', 'Uanataca Integration', 'bniu_plugin_section_text', 'uanataca-integration');
    add_settings_field('uanataca_api_key', 'Uanataca API Key', 'bniu_plugin_setting_input', 'uanataca-integration', 'uanataca-integration', array('option_name' => 'uanataca_api_key'));
    add_settings_field('uanataca_api_secret', 'Uanataca API Secret', 'bniu_plugin_setting_input', 'uanataca-integration', 'uanataca-integration', array('option_name' => 'uanataca_api_secret'));
    add_settings_field('certificate_products_term', 'Certificate products term', 'bniu_plugin_setting_select', 'uanataca-integration', 'uanataca-integration', array('option_name' => 'certificate_products_term'));
    add_settings_field('registration_authority', 'Registration Authority', 'bniu_plugin_setting_input', 'uanataca-integration', 'uanataca-integration', array('option_name' => 'registration_authority'));
    add_settings_field('registration_authority_officer', 'Registration Authority Officer', 'bniu_plugin_setting_input', 'uanataca-integration', 'uanataca-integration', array('option_name' => 'registration_authority_officer'));
    add_settings_field('unataca_rao_username', 'Uanataca RAO Username', 'bniu_plugin_setting_input', 'uanataca-integration', 'uanataca-integration', array('option_name' => 'unataca_rao_username'));
    add_settings_field('unataca_rao_password', 'Uanataca RAO Password', 'bniu_plugin_setting_input', 'uanataca-integration', 'uanataca-integration', array('option_name' => 'unataca_rao_password'));
    add_settings_field('unataca_rao_pin', 'Uanataca RAO Pin', 'bniu_plugin_setting_input', 'uanataca-integration', 'uanataca-integration', array('option_name' => 'unataca_rao_pin'));
    add_settings_field('unataca-workflow', 'Workflow', 'bniu_plugin_setting_radio', 'uanataca-integration', 'uanataca-integration', array('options' => array('Unataca Classic' => '1', 'Unataca Video' => '2', 'Roshka Video API' => '3'), 'option_name' => 'unataca-workflow'));

    add_settings_field('certificado_pf_firma_electronica_calificada', 'ID Firma Electrónica Calificada', 'bniu_plugin_setting_input', 'uanataca-integration', 'uanataca-integration', array('option_name' => 'certificado_pf_firma_electronica_calificada'));
    add_settings_field('certificado_pf_firma_electronica_facturar', 'ID Firma Electrónica para Factura', 'bniu_plugin_setting_input', 'uanataca-integration', 'uanataca-integration', array('option_name' => 'certificado_pf_firma_electronica_facturar'));
    

}
// section text for uanataca-integration
function bniu_plugin_section_text()
{
    echo '<p>Enter your Uanataca API Key, Secret and URL</p>';
}
// input field for uanataca-integration
function bniu_plugin_setting_input($args)
{
    $option_name = $args['option_name'];
    $option = get_option($option_name);
    echo "<input type='text' name='$option_name' value='$option' />";
}
// select field for uanataca-integration
function bniu_plugin_setting_select($args)
{
    // get all terms of products
    $terms = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
    ));
    $option_name = $args['option_name'];
    $option = get_option($option_name);
    echo "<select name='$option_name'>";
    echo '<option value="" selected disabled>Seleccione categoria de certificado</option>';
    foreach ($terms as $term) {
        echo "<option value='$term->term_id' " . selected($option, $term->term_id, false) . ">$term->name</option>";
    }
    echo "</select>";
}

// radio field for uanataca-integration
function bniu_plugin_setting_radio($args)
{
    $options = $args['options'];
    $option_name = $args['option_name'];
    foreach ($options as $key => $value) {
        echo "<label>$key </label>";
        echo "<input type='radio' name='$option_name' value='$value' " . checked(get_option($option_name), $value, false) . " />";
    }
}

// checkbox field for uanataca-integration
function bniu_plugin_setting_checkbox($args)
{
    $option_name = $args['option_name'];
    $option = get_option($option_name);
    echo "<input type='checkbox' name='$option_name' value='1' " . checked($option, 1, false) . " />";
}


/*
// create acf fields group for uanataca-integration
add_action('acf/init', 'bniu_plugin_acf_fields_group');
function bniu_plugin_acf_fields_group()
{
    
    if (function_exists('acf_add_local_field_group')) {
        // if group not exists, create it.
        if (!acf_get_field_group('group_5f9e3e3e3e3e3e')) {
            acf_add_local_field_group(array(
                'key' => 'group_5f9e3e3e3e3e3e',
                'title' => 'Uanataca Integration',
                'fields' => array(
                    array(
                        'key' => 'field_5f9e3e3e3e3e3e',
                        'label' => 'Uanataca API Key',
                        'name' => 'uanataca_api_key',
                        'type' => 'text',
                        'instructions' => 'Enter your Uanataca API Key',
                        'required' => 1,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => ''
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => ''
                    ),
                    array(
                        'key' => 'field_5f9e3e3e3e3e3e2',
                        'label' => 'Uanataca API Secret',
                        'name' => 'uanataca_api_secret',
                        'type' => 'text',
                        'instructions' => 'Enter your Uanataca API Secret',
                        'required' => 1,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => ''
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => ''
                    ),
                    array(
                        'key' => 'field_5f9e3e3e3e3e3e3',
                        'label' => 'Uanataca API URL',
                        'name' => 'uanataca_api_url',
                        'type' => 'text',
                        'instructions' => 'Enter your Uanataca API URL',
                        'required' => 1,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => ''
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => ''
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'options_page',
                            'operator' => '==',
                            'value' => 'uanataca-integration',
                        ),
                    ),
                ),
                'menu_order' => 0,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'active' => true,
                'description' => '',
            ));
        }
        if (!acf_get_field_group('group_5f9e3e3e3e3e3e4')) {
            acf_add_local_field_group(array(
                'key' => 'group_5f9e3e3e3e3e3e4',
                'title' => 'Certificate product',
                'fields' => array(
                    array(
                        'key' => 'field_5f9e3e3e3e3e3e5',
                        'label' => 'Certificate product',
                        'name' => 'certificate_product',
                        'type' => 'post_object',
                        'instructions' => 'Select the product that represents the certificate',
                        'required' => 1,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => ''
                        ),
                        'post_type' => array(
                            0 => 'product',
                        ),
                        'taxonomy' => '',
                        'allow_null' => 0,
                        'multiple' => 0,
                        'return_format' => 'id',
                        'ui' => 1,
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'options_page',
                            'operator' => '==',
                            'value' => 'uanataca-integration',
                        ),
                    ),
                ),
                'menu_order' => 1,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'active' => true,
                'description' => '',
            ));
        }
    }
}*/


// add page for accoun page for list in a table all requests of certificates.
add_action('woocommerce_account_menu_items', 'uanataca_account_menu_items');
function uanataca_account_menu_items($items)
{
    $new_menu_item = array('certificados' => 'Mis certificados');
    $new_menu_item_position = 2;
    $items_menu = array_slice($items, 0, $new_menu_item_position, true);
    $items_menu = array_merge($items_menu, $new_menu_item);
    $items_menu = array_merge($items_menu, array_slice($items, $new_menu_item_position, NULL, true));
    return $items_menu;
}
add_action('init', 'uanataca_add_endpoint');
function uanataca_add_endpoint()
{
    add_rewrite_endpoint('certificados', EP_ROOT | EP_PAGES);
}
add_action('woocommerce_account_certificados_endpoint', 'uanataca_account_certificados_endpoint');
function uanataca_account_certificados_endpoint()
{
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        // get $request from table wp_bniu_certificate_requests
        global $wpdb;
        $requests = $wpdb->get_results("SELECT * FROM wp_bniu_certificate_requests WHERE user = $user_id");
        echo '<h2>Mis solicitudes de certificado</h2>';
        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>Orden</th>';
        echo '<th>Nombre</th>';
        echo '<th>Estado</th>';
        echo '<th>Solicitud</th>';
        echo '<th>Accion</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        if ($requests) {
            foreach ($requests as $request) {
                echo '<tr>';
                echo '<td>' . $request->id . '</td>';
                // get product name and type from order_item_id

                $order_item_id = $request->order_item_id;
                $item = new WC_Order_Item_Product($order_item_id);
                $product_id = $item->get_product_id();
                $product = wc_get_product($product_id);
                echo '<td>' . $request->order . '</td>';
                echo '<td>' . $product->get_name() . '</td>';
                echo '<td>' . $request->status . '</td>';
                echo '<td>' . $request->request . '</td>';
                echo '<td></td>';
                echo '</tr>';
            }
        }
        echo '</tbody>';
        echo '</table>';
    }
}

// on order is status completed, create a request of certificate.
add_action('woocommerce_order_status_completed', 'uanataca_create_request', 10, 1);
function uanataca_create_request($order_id)
{  

    try {
        global $wpdb;
        $order = wc_get_order($order_id);
        $items = $order->get_items();
        $flow_type = get_option('unataca-workflow');

        $link_external_flow ="";
       
        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            $product = wc_get_product($product_id);
            $term = get_the_terms($product_id, 'product_cat');
            $term_id = $term[0]->term_id;
            $certificate_products_term = get_option('certificate_products_term');

            if ($term_id == (int)$certificate_products_term) {
                $given_name = $item->get_meta('_applicant_name');

                $name_1 = $item->get_meta('_applicant_name');
                $name_2 = $item->get_meta('_applicant_second_name');

                if($item->get_meta('_applicant_second_name')){
                    $given_name .= ' ' . $item->get_meta('_applicant_second_name');
                }
                $surname_1 = $item->get_meta('_applicant_lastname');
                $surname_2 = $item->get_meta('_applicant_lastname_two');
                $email = $item->get_meta('_applicant_email');
                $mobile_phone_number = $item->get_meta('_applicant_number');
                $birthdate = $item->get_meta('_applicant_birthdate');
                $id_document_country =  $item->get_meta('_applicant_document_country');
                $id_document_type = $item->get_meta('_applicant_document_type');
                $document_number = $item->get_meta('_applicant_document_number');
                // get order mete "type".
                $type = trim($item->get_meta('_applicant_type'));
                // get profile from product meta "profile".
                $profile_meta = get_post_meta($product_id, 'profile', true);
                $profile = '';

                if ($type == "pf") {
                    if ($profile_meta == 'software') {
                        $profile = 'PFsoftFTECiudadano';
                    } else if ($profile_meta == 'cloud') {
                        $profile = 'PFnubeFTECiudadano';
                    }
                    /*$address = $item->get_meta('_applicant_address');
                    $country = $item->get_meta('_applicant_country');*/
                } else if ($type == "pfbusiness") {
                    if ($profile_meta == 'software') {
                        $profile = 'PFsoftTEPerteneciente';
                    } else if ($profile_meta == 'cloud') {
                        $profile = 'PFnubeFTEPerteneciente';
                    }
                    $rep_document_type = $item->get_meta('_rep_document_type');
                    $rep_document_number = $item->get_meta('_rep_document_number');
                    $rep_name = $item->get_meta('_rep_name');
                    $rep_lastname = $item->get_meta('_rep_lastname');
                    $rep_lastname2 = $item->get_meta('_rep_lastname2');

                    $business_ruc = $item->get_meta('_business_ruc');
                    $business_name = $item->get_meta('_business_name');
                    $business_area = $item->get_meta('_business_area');
                    $business_charge = $item->get_meta('_business_charge');
                    /*$business_address = $item->get_meta('_business_address');
                    $business_country = $item->get_meta('_business_country');
                    $business_state = $item->get_meta('_business_state');
                    $business_city = $item->get_meta('_business_city');*/
                } else if ($type == "rep") {
                    if ($profile_meta == 'software') {
                        $profile = 'REPPJsoftFTE';
                    } else if ($profile_meta == 'cloud') {
                        $profile = 'REPPJnubeFTE';
                    }
                    $business_ruc = $item->get_meta('_business_ruc');
                    $business_name = $item->get_meta('_business_name');
                    $business_charge = $item->get_meta('_business_charge');
                    /*$business_address = $item->get_meta('_business_address');
                    $business_country = $item->get_meta('_business_country');
                    $business_state = $item->get_meta('_business_state');
                    $business_city = $item->get_meta('_business_city');*/
                }

                $scratchcard = getScratchCard(get_option('registration_authority'));
                $scratchcard = $scratchcard["sn"];
                $registration_authority = get_option('registration_authority');
                $secure_element = '';
                if ($profile_meta == 'software') {
                    $secure_element = '0';
                } else if ($profile_meta == 'cloud') {
                    $secure_element = '2';
                }
                $user_id = $order->get_user_id();
                $validity = $item->get_meta('vigencia');
                $validity = explode(' ', $validity);
                $validity = $validity[0];
                $validity = (int)$validity * 365;                

                if ((int)$flow_type == 2) {
                    $paperless_mode = 0;
                    $videoid_mode = 1;
                    $request = createRequest(
                        $videoid_mode,
                        $validity,
                        $paperless_mode,
                        $type,
                        $profile,
                        $scratchcard,
                        $secure_element,
                        $registration_authority,
                        $id_document_country,
                        $id_document_type,
                        $document_number,
                        $given_name,
                        $surname_1,
                        $surname_2,
                        $email,
                        $birthdate,
                        $mobile_phone_number,
                        $business_ruc,
                        $business_name,
                        $business_area,
                        $business_charge,
                        $rep_document_type,
                        $rep_document_number,
                        $rep_name,
                        $rep_lastname,
                        $rep_lastname2
                    );

                    $request_pk = $request["pk"];
                    $wpdb->insert(
                        'wp_bniu_certificate_requests',
                        array(
                            'order' => $order_id,
                            'order_item_id' => $item->get_id(),
                            'user' => $user_id,
                            'flow_type' => $flow_type,
                            'step' => 1,
                            'status' => 'Solicitud creada',
                            'request' => $request_pk
                        )
                    );
                } else {

                    if((int)$flow_type == 1){

                            $paperless_mode = 1;
                            $videoid_mode = 0;
                            $request = createRequest(
                            $videoid_mode,
                            $validity,
                            $paperless_mode,
                            $type,
                            $profile,
                            $scratchcard,
                            $secure_element,
                            $registration_authority,
                            $id_document_country,
                            $id_document_type,
                            $document_number,
                            $given_name,
                            $surname_1,
                            $surname_2,
                            $email,
                            $birthdate,
                            $mobile_phone_number,
                            $business_ruc,
                            $business_name,
                            $business_area,
                            $business_charge,
                            $rep_document_type,
                            $rep_document_number,
                            $rep_name,
                            $rep_lastname,
                            $rep_lastname2
                        );
                        $request_pk = $request["pk"];
                        $wpdb->insert(
                            'wp_bniu_certificate_requests',
                            array(
                                'order' => $order_id,
                                'order_item_id' => $item->get_id(),
                                'user' => $user_id,
                                'flow_type' => $flow_type,
                                'step' => 1,
                                'status' => 'Solicitud creada',
                                'request' => $request_pk
                            )
                        );
                        // upload documents
                        $applicant_photo_id_front = uploadDocument($request_pk, $item->get_meta('_applicant_photo_id_front'), 'document_front');
                        $wpdb->insert(
                            'wp_bniu_certificate_requests_meta',
                            array(
                                'request' => $request_pk,
                                'step' => 2,
                                'meta_key' => 'applicant_photo_id_front',
                                'meta_value' => $applicant_photo_id_front["pk"]
                            )
                        );
                        $applicant_photo_id_back = uploadDocument($request_pk, $item->get_meta('_applicant_photo_id_back'), 'document_rear');
                        $applicant_photo_id_back = json_decode($applicant_photo_id_back);
                        $wpdb->insert(
                            'wp_bniu_certificate_requests_meta',
                            array(
                                'request' => $request_pk,
                                'step' => 2,
                                'meta_key' => 'applicant_photo_id_back',
                                'meta_value' => $applicant_photo_id_back["pk"]
                            )
                        );
                        $applicant_photo_selfie = uploadDocument($request_pk, $item->get_meta('_applicant_photo_selfie'), 'document_owner');
                        $wpdb->insert(
                            'wp_bniu_certificate_requests_meta',
                            array(
                                'request' => $request_pk,
                                'step' => 2,
                                'meta_key' => 'applicant_photo_selfie',
                                'meta_value' => $applicant_photo_selfie["pk"]
                            )
                        );
                        $photo_ruc = $item->get_meta('_business_photo_ruc');
                        if ($photo_ruc) {
                            $business_photo_ruc = uploadDocument($request_pk, $photo_ruc, 'extra_document');
                            $wpdb->insert(
                                'wp_bniu_certificate_requests_meta',
                                array(
                                    'request' => $request_pk,
                                    'step' => 2,
                                    'meta_key' => 'business_photo_ruc',
                                    'meta_value' => $business_photo_ruc["pk"]
                                )
                            );
                        }
                        $photo_business_constitution = $item->get_meta('_business_photo_constitution');
                        if ($photo_business_constitution) {
                            $business_photo_constitution = uploadDocument($request_pk, $photo_business_constitution, 'extra_document');
                            $wpdb->insert(
                                'wp_bniu_certificate_requests_meta',
                                array(
                                    'request' => $request_pk,
                                    'step' => 2,
                                    'meta_key' => 'business_photo_constitution',
                                    'meta_value' => $business_photo_constitution["pk"]
                                )
                            );
                        }
                        $photo_business_representative_appointment = $item->get_meta('_business_photo_representative_appointment');
                        if ($photo_business_representative_appointment) {
                            $business_photo_representative_appointment = uploadDocument($request_pk, $photo_business_representative_appointment, 'extra_document');
                            $wpdb->insert(
                                'wp_bniu_certificate_requests_meta',
                                array(
                                    'request' => $request_pk,
                                    'step' => 2,
                                    'meta_key' => 'business_photo_representative_appointment',
                                    'meta_value' => $business_photo_representative_appointment["pk"]
                                )
                            );
                        }
                        $business_photo_representative_dni = $item->get_meta('_business_photo_representative_dni');
                        if ($business_photo_representative_dni) {
                            $business_photo_representative_dni = uploadDocument($request_pk, $business_photo_representative_dni, 'extra_document');
                            $wpdb->insert(
                                'wp_bniu_certificate_requests_meta',
                                array(
                                    'request' => $request_pk,
                                    'step' => 2,
                                    'meta_key' => 'business_photo_representative_dni',
                                    'meta_value' => $business_photo_representative_dni["pk"]
                                )
                            );
                        }
                        $business_photo_representative_authorization = $item->get_meta('_business_photo_representative_authorization');
                        if ($business_photo_representative_authorization) {
                            $business_photo_representative_authorization = uploadDocument($request_pk, $business_photo_representative_authorization, 'extra_document');
                            $wpdb->insert(
                                'wp_bniu_certificate_requests_meta',
                                array(
                                    'request' => $request_pk,
                                    'step' => 2,
                                    'meta_key' => 'business_photo_representative_authorization',
                                    'meta_value' => $business_photo_representative_authorization["pk"]
                                )
                            );
                        }

                    }

                    if((int)$flow_type == 3){

                        
                        /*
                        {
                            "onboardingType": "PRELOAD",
                            "documentType": "CEDULA",
                            "issuanceCountry": "PANAMA",
                            "documentNumber": "123456789",
                            "firstName": "Melissa",
                            "secondName": "Denisse",
                            "firstSurname": "Castillo",
                            "secondSurname": "Juarez de Campos",
                            "birthDate": "31/12/1984",
                            "phoneNumber": "555-555-5555",
                            "email": "melissa@example.com",
                            "additionalInfo": {
                                "perfil": "PFnubeFTECiudadano"
                            }
                        }
                       */

                        $onboardingType = "PRELOAD";

                        // Tu fecha original
                        $original_date = $birthdate;
                        $new_date = DateTime::createFromFormat('Y-m-d', $original_date);

                        // Formatear la fecha a una cadena en el formato deseado
                        $formatted_new_date = (string)$new_date->format('d/m/Y');                        

                        $dataRequest = [
                            "OnboardingType" => $onboardingType,
                            "DocumentType" =>  strtoupper($id_document_type),
                            "IssuanceCountry" =>  "PANAMA",
                            "DocumentNumber" =>   $document_number,
                            "FirstName" =>  $name_1 ,
                            "SecondName" =>  $name_2 ,
                            "FirstSurname" =>  $surname_1,
                            "SecondSurname" => $surname_2,
                            "BirthDate" =>  $formatted_new_date,
                            "PhoneNumber" =>   $mobile_phone_number,
                            "Email" =>  $email,
                            "AdditionalInfo" => array(
                                "perfil" =>  $profile
                            )
                        ];

                        $responseRequest = createRequestExternalFlow($dataRequest);
                        
                        $request_pk = $responseRequest["token"];

                        $link_external_flow = "https://firmatech.digiyo.id/".$request_pk ;
                        
                        $wpdb->insert(
                            'wp_bniu_certificate_requests',
                            array(
                                'order' => $order_id,
                                'order_item_id' => $item->get_id(),
                                'user' => $user_id,
                                'flow_type' => $flow_type,
                                'step' => 1,
                                'status' => 'Solicitud creada',
                                'request' => $request_pk
                            )
                        );
                        
                        if((int)$flow_type == 3 && $link_external_flow != ""){

                            // Configurar el asunto del correo
                            $subject = 'Completa el proceso para obtener tu Firma Electrónica';
                
                            // Configurar el contenido del correo
                            $message = '<p>Estimad@ ' . $name_1.' '.$name_2.' '.$surname_1.' '.$surname_2 . ',</p>';
                            $message .= '<p>Gracias por tu compra. Para completar el proceso de adquisición de tu Firma Electrónica, por favor ingresa al siguiente enlace:</p>';
                            $message .= '<p><a href="'.$link_external_flow.'">Completar Firma Electrónica</a></p>';
                            $message .= '<p>Saludos,<br>El equipo de firmatech</p>';
                
                            // Configurar las cabeceras del correo
                            $headers = array('Content-Type: text/html; charset=UTF-8');
                
                            // Enviar el correo
                            wp_mail($email, $subject, $message, $headers);
                
                        }

                    }
                    
                }
                /*
                    $request_pk = $wpdb->get_var("SELECT request FROM wp_bniu_certificate_requests WHERE 'order' = $order_id");
                    $rao = get_option('registration_authority_officer');
                    $rao_declaration = generateTbsReceipt($request_pk, $rao, 'APPROVE');
                    $wpdb->insert(
                        'wp_bniu_certificate_requests_meta', 
                        array(
                            'request' => $request_pk,
                            'step' => 3,
                            'meta_key' => 'rao_declaration',
                            'meta_value' => $rao_declaration["receipt"]
                        )
                    );
                    $contract = getContract($request_pk, $rao);
                    $wpdb->insert(
                        'wp_bniu_certificate_requests_meta', 
                        array(
                            'request' => $request_pk,
                            'step' => 3,
                            'meta_key' => 'contract',
                            'meta_value' => $contract[0]->document
                        )
                    );
                    
                    // approve request
                    $username = get_option('uanataca_rao_username');
                    $password = get_option('uanataca_rao_password');
                    $pin = get_option('uanataca_rao_pin');
                    $approve = approveRequest($request_pk, $username, $password, $pin, $rao);
                    $wpdb->update(
                        'wp_bniu_certificate_requests', 
                        array(
                            'step' => 4,
                            'status' => 'APPROVED'
                        ),
                        array(
                            'order_id' => $order_id,
                            'order_item_id' => $item->get_id()
                        )
                    );*/
            }
        }

    } catch (Exception $e) {
        $error_log = fopen(plugin_dir_path(__FILE__) . 'error_log.txt', 'ab+');
        fwrite($error_log, date("Y-m-d H:i:s").' - OrderID: '.$order_id.' - '.$e->getMessage() . "\n");
        fclose($error_log);
    }
}
