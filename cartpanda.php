<?php

/**
 * Plugin Name: Cartpanda
 * Description: O checkout transparente de 1-página de mais alta conversão do mercado. Upsell de 1-clique e Order Bump nativo.
 * Version: 1.1.1
 * Author: Cartpanda
 * Author URI: https://cartpanda.com/
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Cartpanda
*/

class Cartpanda 
{
	private $cartpanda_admin_url = "https://accounts.cartpanda.com";
	
    public function __construct()
    {
        if (!class_exists('WooCommerce')) {
			return;
		}
		add_action('wp_enqueue_scripts', [$this, 'cartpanda_script']);
        // add_action('woocommerce_before_cart', [$this, 'cartpanda_add_cart_script'], 1);
		// add_action('woocommerce_before_checkout_form', [$this, 'cartpanda_add_checkout_script'], 1);
        add_action( 'admin_menu', array( $this, 'cartpanda_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'cartpanda_page_init' ) );
    }

    public function cartpanda_add_plugin_page() {
		add_menu_page(
			'Cartpanda', // page_title
			'Cartpanda', // menu_title
			'manage_options', // capability
			'cartpanda', // menu_slug
			array( $this, 'cartpanda_create_admin_page' ), // function
			'dashicons-controls-repeat', // icon_url
			70 // position
		);
	}

	public function cartpanda_create_admin_page() {
		$this->cartpanda_options = get_option( 'cartpanda_option_name' ); ?>

		<div class="wrap">
			<h2>Cartpanda</h2>
			<p>To enable Cartpanda checkout on your WooCommerce store, please add the following.</p>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'cartpanda_option_group' );
					do_settings_sections( 'cartpanda-admin' );
					submit_button();
				?>
			</form>
		</div>
	<?php }

	public function cartpanda_page_init() {
		register_setting(
			'cartpanda_option_group', // option_group
			'cartpanda_option_name', // option_name
			array( $this, 'cartpanda_sanitize' ) // sanitize_callback
		);

		add_settings_section(
			'cartpanda_setting_section', // id
			'Settings', // title
			array( $this, 'cartpanda_section_info' ), // callback
			'cartpanda-admin' // page
		);

		add_settings_field(
			'cartpanda_shop_slug_0', // id
			'Cartpanda shop slug', // title
			array( $this, 'cartpanda_shop_slug_0_callback' ), // callback
			'cartpanda-admin', // page
			'cartpanda_setting_section' // section
		);
	}

	public function cartpanda_sanitize($input) {
		$sanitary_values = array();
		if ( isset( $input['cartpanda_shop_slug_0'] ) ) {
			$sanitary_values['cartpanda_shop_slug_0'] = sanitize_text_field( $input['cartpanda_shop_slug_0'] );
		}

		return $sanitary_values;
	}

	public function cartpanda_section_info() {
		
	}

	public function cartpanda_shop_slug_0_callback() {
		printf(
			'<input class="regular-text" type="text" name="cartpanda_option_name[cartpanda_shop_slug_0]" id="cartpanda_shop_slug_0" value="%s">',
			isset( $this->cartpanda_options['cartpanda_shop_slug_0'] ) ? esc_attr( $this->cartpanda_options['cartpanda_shop_slug_0']) : ''
		);
	}


/**
    * cartpanda_add_checkout_script
    * Put the CartPanda Snippet on WC template for checkout page.
    *
    * @access        public
    * @return        void
    */
    public function cartpanda_add_checkout_script()
    {
        $this->cartpanda_script(true);
    }


    /**
    * cartpanda_add_cart_script
    * Put the CartPanda Snippet on WC template for cart page.
    *
    * @access        public
    * @return        void
    */
    public function cartpanda_add_cart_script()
    {
        $this->cartpanda_script();
    }

    /**
	 * View the loader and add script
	 *
	 * @access        public
	 * @param bool $isCheckout
	 * @return        void
	 */
	public function cartpanda_script($isCheckout = false)
	{
		$page = $this->better_is_checkout() ? 'checkout' : ($this->better_is_cart() ? 'cart' : '');
		if($page) {
			$cartpanda_options = get_option( 'cartpanda_option_name' );
			?>

			<style>
				.white-overlay {display: none;background-color: rgba(255, 255, 255, 1);z-index: 9999;top: 0;left: 0;height: 100vh;width: 100vw;position: fixed;margin: 0;}.spinner-wrapper {position: fixed;top: 50%;left: 50%;transform: translate(-50%, -50%);margin: 0 auto;min-width: 220px;}.cartpanda .spinner {border: 6px solid #e2e2e2;border-top: 6px solid #297FBC;border-radius: 50%;width: 100px;height: 100px;animation: spin 2s linear infinite;margin: auto;}.spinner-icon {position: relative;}.spinner-icon .spinner-icon-svg {position: absolute;top: 50%;left: 50%;margin-left: -19px;margin-top: -70px;width: 40px;height: 40px;}.spinner-text {text-align: center;color: #526473;font-size: 16px;}.custom_spinner.loading {padding: 50px;position: relative;text-align: center;}.custom_spinner.loading:before {content: '';height: 45px;width: 45px;margin: 0px auto auto -27px;position: absolute;border-width: 8px;border-style: solid;border-color: #2180c0 #ccc #ccc;  border-radius: 100%;animation: rotation .7s infinite linear;}@keyframes rotation {from {transform: rotate(0deg);} to {transform: rotate(359deg);}} @keyframes spin {0% {transform: rotate(0deg);}100% {transform: rotate(360deg);}} @keyframes spin {0% {transform: rotate(0deg);}100% {transform: rotate(360deg);}}
			</style>

			<div class="cartx-preloader white-overlay cartx-loader" id="cartx-preloader">
				<div class="spinner-wrapper">
					<div class=''>
						<div class="spinner-icon">
							<div class="custom_spinner loading">&nbsp;</div>
						</div>
						<div class="spinner-text">Finalizando compra</div>
					</div>
				</div>
			</div>
			
			<script type='text/javascript'>
				window.Cartpanda = {
					page: "<?php echo $page; ?>",
					shop_url: "<?php echo esc_url(get_site_url()); ?>",
					cart: <?php echo $this->cartpanda_format_cart(); ?>,
					shop_slug: "<?php echo esc_attr($cartpanda_options['cartpanda_shop_slug_0']); ?>",
				};

				(function() {
					var ch = document.createElement('script'); ch.type = 'text/javascript'; ch.async = true;
					ch.src = '<?php echo esc_url_raw($this->cartpanda_admin_url); ?>/assets/js/woocommerce_redirect.js';
					var x = document.getElementsByTagName('script')[0]; x.parentNode.insertBefore(ch, x);
				})();
			</script>
			<?php
		}
        
	}

    /**
	* cartpanda_format_cart
	*
	* Format cart payload.
	*
	* @access        public
	* @return        string
	*/
	public function cartpanda_format_cart()
	{
		$cartData = WC()->cart->get_cart();
		$cart = [];

		foreach ($cartData as $key => $item) {
			$cart['items'][] = [
				'variant_id' => $item['variation_id'] ? $item['variation_id'] : $item['product_id'],
				'quantity' => $item['quantity'],
			];
		}

		return json_encode($cart);
	}

	/**
	 * Checks if checkout is the current page.
	 *
	 * @return boolean
	 */
	function better_is_checkout() {
		$checkout_path    = wp_parse_url(wc_get_checkout_url(), PHP_URL_PATH);
		$current_url_path = wp_parse_url("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]", PHP_URL_PATH);
		if($checkout_path === '/') {
			return false;
		}
		return (
			$checkout_path !== null
			&& $current_url_path !== null
			&& trailingslashit($checkout_path) === trailingslashit($current_url_path)
		);
	}

	/**
	 * Checks if cart is the current page.
	 *
	 * @return boolean
	 */
	function better_is_cart() {
		$cart_path        = wp_parse_url(wc_get_cart_url(), PHP_URL_PATH);
		$current_url_path = wp_parse_url("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]", PHP_URL_PATH);
		if($cart_path === '/') {
			return false;
		}
		return (
			$cart_path !== null
			&& $current_url_path !== null
			&& trailingslashit($cart_path) === trailingslashit($current_url_path)
		);
	}
}

/**
* Load Cartpanda checkout plugin
*/
function cartpanda_plugins_loaded() {
    new Cartpanda();
}

add_action('plugins_loaded', 'cartpanda_plugins_loaded');

function cartpanda_settings_link($links) { 
    $settings_link = '<a href="options-general.php?page=cartpanda">Settings</a>'; 
    array_unshift($links, $settings_link); 
    return $links; 
  }

$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'cartpanda_settings_link' );
