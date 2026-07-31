<?php
/**
 * Plugin Name:       EseBills Agent Integration
 * Plugin URI:        https://esebills.co.zw
 * Description:       Integrates EseBills Agent API to sell airtime, data, utility tokens — all payments via Pesepay.
 * Version:           1.2.0
 * Author:            EseBills Agent Integration
 * Text Domain:       esebills-agent-integration
 * License:           GPL-2.0+
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ESEBILLS_API_BASE_URL', 'https://api.esebills.co.zw');

/**
 * Class EseBills_API_Service
 * Handles API calls to EseBills backend.
 */
class EseBills_API_Service {

    private static function get_api_key() {
        return get_option('esebills_api_key', '');
    }

    private static function request($endpoint, $method = 'GET', $body = null) {
        $api_key = self::get_api_key();

        if (empty($api_key)) {
            return new WP_Error('missing_api_key', __('EseBills API key is not configured in settings.', 'esebills-agent-integration'));
        }

        $args = array(
            'method'  => $method,
            'timeout' => 30,
            'headers' => array(
                'X-API-Key'    => $api_key,
                'Content-Type' => 'application/json',
            ),
        );

        if ($body !== null && in_array($method, array('POST', 'PUT', 'PATCH'), true)) {
            $args['body'] = wp_json_encode($body);
        }

        $url = ESEBILLS_API_BASE_URL . $endpoint;
        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $data        = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code >= 400) {
            $message = isset($data['message']) ? $data['message'] : __('An HTTP error occurred.', 'esebills-agent-integration');
            return new WP_Error('api_error', $message, array('status' => $status_code));
        }

        return $data;
    }

    /**
     * List Products
     */
    public static function get_products($country_code = '') {
        $endpoint = '/v1/agent-api/products';
        if (!empty($country_code)) {
            $endpoint = add_query_arg('countryCode', sanitize_text_field($country_code), $endpoint);
        }
        return self::request($endpoint, 'GET');
    }

    /**
     * Get Product Requirements
     */
    public static function get_product_requirements($product_code) {
        $endpoint = '/v1/agent-api/products/' . urlencode($product_code);
        return self::request($endpoint, 'GET');
    }

    /**
     * Validate Customer Details
     */
    public static function validate_customer($payload) {
        return self::request('/v1/agent-api/product-validations', 'POST', $payload);
    }

    /**
     * Sell a Product (Payment & Fulfilment)
     */
    public static function process_payment($payload) {
        return self::request('/v1/agent-api/product-payment', 'POST', $payload);
    }
}

/**
 * Admin Settings Menu
 */
class EseBills_Admin_Settings {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_menu_page() {
        add_options_page(
            __('EseBills Settings', 'esebills-agent-integration'),
            __('EseBills Agent', 'esebills-agent-integration'),
            'manage_options',
            'esebills-agent-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('esebills_options_group', 'esebills_api_key', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ));
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('EseBills Agent Settings', 'esebills-agent-integration'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('esebills_options_group');
                do_settings_sections('esebills_options_group');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e('Agent API Key (X-API-Key)', 'esebills-agent-integration'); ?></th>
                        <td>
                            <input type="password" name="esebills_api_key" value="<?php echo esc_attr(get_option('esebills_api_key')); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('Enter your live API key generated from your EseBills agent dashboard.', 'esebills-agent-integration'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
new EseBills_Admin_Settings();

/**
 * Enqueue frontend styles.
 */
function esebills_enqueue_styles() {
    $post = get_post();
    if ($post && (has_shortcode($post->post_content, 'esebills_checkout') || has_shortcode($post->post_content, 'esebills_products'))) {
        wp_enqueue_style('esebills-brand', plugin_dir_url(__FILE__) . 'assets/brand.css', array(), '1.1.0');
    }
}
add_action('wp_enqueue_scripts', 'esebills_enqueue_styles');

/**
 * Shortcode to Render Product Purchase Form
 * Usage: [esebills_checkout product_code="ECONET_BUNDLES_USD"]
 *
 * When product_code is omitted, the shortcode reads it from the URL (?product_code=XXXX).
 * Add this shortcode to your checkout page. Link to it from [esebills_products].
 */
function esebills_checkout_shortcode($atts) {
    $attributes = shortcode_atts(array(
        'product_code' => '',
    ), $atts, 'esebills_checkout');

    $product_code = sanitize_text_field($attributes['product_code']);

    if (empty($product_code) && isset($_GET['product_code'])) {
        $product_code = sanitize_text_field(wp_unslash($_GET['product_code']));
    }

    if (empty($product_code)) {
        $back = '';
        if (isset($_SERVER['HTTP_REFERER'])) {
            $referer = sanitize_text_field(wp_unslash($_SERVER['HTTP_REFERER']));
            $back = '<a href="' . esc_url($referer) . '" class="esebills-back-link">&larr; ' . esc_html__('Back to products', 'esebills-agent-integration') . '</a>';
        }
        return '<div class="esebills-checkout esebills-checkout--empty"><div class="esebills-checkout-header"><p>' . esc_html__('No product selected.', 'esebills-agent-integration') . '</p></div><div class="esebills-form">' . $back . '</div></div>';
    }

    $output = '';
    $request_method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : '';
    if ($request_method === 'POST' && isset($_POST['esebills_submit_payment'])) {
        if (!isset($_POST['esebills_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['esebills_nonce'])), 'esebills_payment_action')) {
            $output .= '<div class="esebills-notice esebills-error"><p>' . esc_html__('Security check failed.', 'esebills-agent-integration') . '</p></div>';
        } else {
            $email          = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
            $phone          = isset($_POST['phone_number']) ? sanitize_text_field(wp_unslash($_POST['phone_number'])) : '';
            $amount         = isset($_POST['amount']) ? floatval(wp_unslash($_POST['amount'])) : 0;
            $currency       = isset($_POST['currency_code']) ? sanitize_text_field(wp_unslash($_POST['currency_code'])) : '';

            $req_fields = array();
            if (isset($_POST['required_fields']) && is_array($_POST['required_fields'])) {
                $posted_req = array_map('sanitize_text_field', wp_unslash($_POST['required_fields']));
                foreach ($posted_req as $key => $val) {
                    $req_fields[sanitize_text_field($key)] = $val;
                }
            }

            $payload = array(
                'email'                         => $email,
                'phoneNumber'                   => $phone,
                'amount'                        => $amount,
                'currencyCode'                  => $currency,
                'productCode'                   => $product_code,
                'paymentMethodCode'             => 'PESEPAY',
                'returnUrl'                     => esc_url_raw(get_permalink()),
                'paymentNotificationSms'        => true,
                'productPaymentNotificationSms' => true,
                'paymentMethodRequiredFields'   => new stdClass(),
                'productRequiredFields'         => $req_fields
            );

            $result = EseBills_API_Service::process_payment($payload);

            if (is_wp_error($result)) {
                $output .= '<div class="esebills-notice esebills-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
            } else {
                if (isset($result['status']) && $result['status'] === 'SUCCESS') {
                    $output .= '<div class="esebills-notice esebills-success"><p>' . esc_html__('Transaction successful! Ref: ', 'esebills-agent-integration') . esc_html($result['transactionReference']) . '</p></div>';
                    if (!empty($result['fulfilment']['message'])) {
                        $output .= '<blockquote class="esebills-fulfilment">' . esc_html($result['fulfilment']['message']) . '</blockquote>';
                    }
                } elseif (!empty($result['redirectUrl'])) {
                    wp_safe_redirect(esc_url_raw($result['redirectUrl']));
                    exit;
                } else {
                    $output .= '<div class="esebills-notice esebills-warning"><p>' . esc_html__('Transaction submitted, status: ', 'esebills-agent-integration') . esc_html($result['status']) . '</p></div>';
                }
            }
        }
    }

    $data = EseBills_API_Service::get_product_requirements($product_code);

    if (is_wp_error($data)) {
        return '<p>' . esc_html($data->get_error_message()) . '</p>';
    }

    $product         = $data['product'];
    $required_fields = isset($data['requiredFields']) ? $data['requiredFields'] : array();
    $options         = isset($data['purchaseOptions']) ? $data['purchaseOptions'] : array();

    ob_start();
    ?>
    <div class="esebills-checkout">
        <div class="esebills-checkout-header">
            <h3><?php echo esc_html($product['name']); ?></h3>
            <p><?php echo esc_html($product['description']); ?></p>
        </div>

        <?php echo wp_kses_post($output); ?>

        <form method="post" class="esebills-form">
            <?php wp_nonce_field('esebills_payment_action', 'esebills_nonce'); ?>

            <div class="esebills-field">
                <label for="esebills-email"><?php esc_html_e('Email Address', 'esebills-agent-integration'); ?></label>
                <input type="email" id="esebills-email" name="email" required />
            </div>

            <div class="esebills-field">
                <label for="esebills-phone"><?php esc_html_e('Phone Number', 'esebills-agent-integration'); ?></label>
                <input type="text" id="esebills-phone" name="phone_number" placeholder="263771234567" required />
                <span class="esebills-hint"><?php esc_html_e('International format, e.g. 263771234567', 'esebills-agent-integration'); ?></span>
            </div>

            <?php if (!empty($options)) : ?>
                <div class="esebills-field">
                    <label for="esebills-option"><?php esc_html_e('Select Option', 'esebills-agent-integration'); ?></label>
                    <select id="esebills-option" name="required_fields[ProductCode]" required>
                        <?php foreach ($options as $opt) : ?>
                            <option value="<?php echo esc_attr($opt['code']); ?>"
                                    data-amount="<?php echo esc_attr($opt['amount']); ?>"
                                    data-currency="<?php echo esc_attr($opt['currencyCode']); ?>">
                                <?php echo esc_html($opt['name'] . ' — ' . $opt['currencyCode'] . ' ' . number_format($opt['amount'], 2)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" name="amount" id="esebills-amount" value="<?php echo esc_attr($options[0]['amount']); ?>" />
                <input type="hidden" name="currency_code" id="esebills-currency" value="<?php echo esc_attr($options[0]['currencyCode']); ?>" />
            <?php else : ?>
                <div class="esebills-field">
                    <label for="esebills-amount"><?php esc_html_e('Amount', 'esebills-agent-integration'); ?></label>
                    <input type="number" id="esebills-amount" step="0.01" min="<?php echo esc_attr($product['minimumAmount']); ?>" name="amount" required />
                </div>
                <input type="hidden" name="currency_code" value="<?php echo esc_attr($product['currencyCode']); ?>" />
            <?php endif; ?>

            <?php foreach ($required_fields as $field) : ?>
                <?php if ($field['name'] === 'ProductCode') continue; ?>
                <div class="esebills-field">
                    <label for="esebills-field-<?php echo esc_attr($field['name']); ?>">
                        <?php echo esc_html($field['displayName']); ?>
                        <?php if (!$field['optional']) : ?><span class="esebills-required">*</span><?php endif; ?>
                    </label>
                    <input type="text" id="esebills-field-<?php echo esc_attr($field['name']); ?>"
                           name="required_fields[<?php echo esc_attr($field['name']); ?>]"
                           <?php echo $field['optional'] ? '' : 'required'; ?> />
                    <?php if (!empty($field['hint'])) : ?>
                        <span class="esebills-hint"><?php echo esc_html($field['hint']); ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="esebills-payment-info">
                <p><?php esc_html_e('Payment is processed securely via Pesepay (card / mobile money).', 'esebills-agent-integration'); ?></p>
            </div>

            <div class="esebills-submit">
                <button type="submit" name="esebills_submit_payment" class="esebills-btn">
                    <?php esc_html_e('Pay with Pesepay', 'esebills-agent-integration'); ?>
                </button>
            </div>
        </form>
    </div>

    <script>
    (function() {
        var select = document.getElementById('esebills-option');
        if (select) {
            select.addEventListener('change', function() {
                var opt = this.options[this.selectedIndex];
                document.getElementById('esebills-amount').value = opt.getAttribute('data-amount');
                document.getElementById('esebills-currency').value = opt.getAttribute('data-currency');
            });
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('esebills_checkout', 'esebills_checkout_shortcode');

/**
 * Shortcode to list all available products grouped by category.
 * Usage: [esebills_products country="ZW" checkout_url="https://example.com/checkout/"]
 *
 * Each product links to the checkout page with ?product_code=CODE in the URL.
 * When checkout_url is omitted, links point to the current page (works if
 * [esebills_checkout] is on the same page).
 *
 * When this shortcode is on the same page as [esebills_checkout] and the URL
 * contains ?product_code=XXXX, the catalog is replaced with the checkout form
 * so the customer sees the product purchase form instead of the product grid.
 */
function esebills_products_shortcode($atts) {
    $attributes = shortcode_atts(array(
        'country'       => '',
        'checkout_url'  => '',
    ), $atts, 'esebills_products');

    $country      = sanitize_text_field($attributes['country']);
    $checkout_url = esc_url($attributes['checkout_url']);

    if (empty($checkout_url) && isset($_SERVER['REQUEST_URI']) && false !== strpos($_SERVER['REQUEST_URI'], 'product_code=')) {
        return esebills_checkout_shortcode(array());
    }

    $products = EseBills_API_Service::get_products($country);

    if (is_wp_error($products)) {
        return '<div class="esebills-notice esebills-error"><p>' . esc_html($products->get_error_message()) . '</p></div>';
    }

    if (empty($products)) {
        return '<div class="esebills-notice esebills-warning"><p>' . esc_html__('No products available at this time.', 'esebills-agent-integration') . '</p></div>';
    }

    if (empty($checkout_url)) {
        $checkout_url = get_permalink();
    }

    $categories = array();
    foreach ($products as $product) {
        $cat = !empty($product['category']) ? $product['category'] : __('Other', 'esebills-agent-integration');
        $categories[$cat][] = $product;
    }

    ob_start();
    ?>
    <div class="esebills-catalog">
        <?php foreach ($categories as $category => $items) : ?>
            <div class="esebills-category">
                <div class="esebills-category-header">
                    <h3><?php echo esc_html($category); ?></h3>
                    <span class="esebills-category-count"><?php echo count($items); ?></span>
                </div>
                <div class="esebills-category-items">
                    <?php foreach ($items as $product) : ?>
                        <?php
                        $url = add_query_arg('product_code', urlencode($product['productCode']), $checkout_url);
                        ?>
                        <a href="<?php echo esc_url($url); ?>" class="esebills-product-card">
                            <div class="esebills-product-card-body">
                                <h4><?php echo esc_html($product['name']); ?></h4>
                                <p><?php echo esc_html($product['description']); ?></p>
                            </div>
                            <div class="esebills-product-card-footer">
                                <span class="esebills-product-price">
                                    <?php echo esc_html($product['currencyCode'] . ' ' . number_format($product['minimumAmount'], 2)); ?>+
                                </span>
                                <span class="esebills-product-select"><?php esc_html_e('Buy', 'esebills-agent-integration'); ?> &rarr;</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('esebills_products', 'esebills_products_shortcode');
