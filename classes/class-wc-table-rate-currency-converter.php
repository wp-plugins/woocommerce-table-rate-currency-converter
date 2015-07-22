<?
/**
 * WC_Table_Rate_Currency_Converter class.
 *
 * @extends WC_Shipping_Method
 *
 */
class WC_Table_Rate_Currency_Converter extends WC_Shipping_Method {


	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->id                 = 'table-rate-currency-converter';
		$this->method_title       = __( 'Table Rate Currency Converter', 'wc_table_rate_currency_converter' );
		$this->method_description = __( 'The <strong>Table Rate Currency Converter</strong> extension converts Shipping Zone rates from local currencies to store currency.', 'wc_table_rate_currency_converter' );

		$this->local_rates = array();
		$this->currencies  = array();

		$this->has_settings = true;

		$this->init();

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

	}

    /**
     * init function.
     *
     * @access public
     * @return void
     */
    private function init() {
		global $woocommerce;

		// Load our settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title        = isset( $this->settings['title'] ) ? $this->settings['title'] : $this->method_title;
		$this->enabled      = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : $this->enabled;

		$this->local_rates  = isset( $this->settings['zones']['rates'] ) ? $this->settings['zones']['rates'] : $this->local_rates;
		$this->currencies   = isset( $this->settings['zones']['currencies'] ) ? $this->settings['zones']['currencies'] : $this->currencies;

		$this->has_settings = true;


	}

	/**
	 * environment_check function.
	 *
	 * @access public
	 * @return void
	 */
	private function environment_check() {

		$error_message = '';
		if ( ! class_exists( 'WC_Shipping_Table_Rate', false ) )
			$error_message = '<p>' . __( 'Plugin WooCommerce Table Rate Shipping must be installed and active.', 'wc_table_rate_currency_converter' ) . '</p>';


		if ( ! class_exists( 'WC_Currency_Converter', false ) ) {
			$error_message .= '<p>' . __( 'Plugin WooCommerce Currency Converter Widget must be installed and active.', 'wc_table_rate_currency_converter' ) . '</p>';
	    } else {
			// Check for rates available
			if ( false === ( $rates = get_transient( 'woocommerce_currency_converter_rates' ) ) ) {
				$error_message .= '<p>' . __( 'Currency converter has no exchange rates available.', 'wc_table_rate_currency_converter' ) . '</p>';
			}
		}

		if ( ! $error_message == '' ) {
			echo '<div class="error">';
			echo $error_message;
			echo '</div>';
		}

	}

	/**
	 * admin_options function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_options() {
		// Check users environment supports this method
		$this->environment_check();
		// Show settings
		parent::admin_options();
	}


    /**
     * init_form_fields function.
     *
     * @access public
     * @return void
     */
    public function init_form_fields() {
		$this->form_fields  = array(
			'enabled'          => array(
				'title'           => __( 'Enable/Disable', 'wc_table_rate_currency_converter' ),
				'type'            => 'checkbox',
				'label'           => __( 'Enable this conversion filter', 'wc_table_rate_currency_converter' ),
				'default'         => 'yes'
			),
		    'filter'           => array(
				'title'           => __( 'Filter Settings', 'wc_table_rate_currency_converter' ),
				'type'            => 'title',
				'description'     => __( 'These filters set which shipping zones use local currency.', 'wc_table_rate_currency_converter' ),
		    ),
			'zones'  => array(
				'type'            => 'zones'
			),
		);
    }



	/**
	 * generate_zones_html function.
	 *
	 * @access public
	 * @return void
	 */
	function generate_zones_html() {
		global $wpdb;
		$store_currency_code   = get_woocommerce_currency();
		$currency_code_options = get_woocommerce_currencies();
		foreach ( $currency_code_options as $code => $name ) {
			$currency_code_options[ $code ] = $name . ' (' . get_woocommerce_currency_symbol( $code ) . ')';
		}
		array_unshift( $currency_code_options, __( '-- No conversion, use WooCommerce store currency. --', 'wc_table_rate_currency_converter' ) );

		ob_start();
		?>
		<tr valign="top" id="service_options">
			<th scope="row" class="titledesc"><?php _e( 'Shipping Zone Filters', 'wc_table_rate_currency_converter' ); ?>
				<p class="description"><?php _e( 'Only enabled shipping zones are shown here.','wc_table_rate_currency_converter' ) ?></p></th>
			<td class="forminp">
				<table class="zones widefat">
					<thead>
						<th><?php _e( 'Shipping Zones & Currencies', 'wc_table_rate_currency_converter' ); ?></th>
					</thead>
					<tbody>
						<?php
							$query = "
								SELECT *
								FROM {$wpdb->prefix}woocommerce_shipping_zones
								WHERE zone_enabled = 1
								ORDER BY zone_order";
							$shipping_zones = $wpdb->get_results( $query );
							if ( 0 == $wpdb->num_rows ) {
?>
						<tr>
							<td colspan="2"><?php printf( __('There are no enabled shipping zones. <br /><a href="%s?page=shipping_zones">Create some shipping zones.</a>', 'wc_table_rate_currency_converter' ), admin_url() )?></td>
						</tr>
<?php
							} else {
								$i = 0;
								$exchange_rates = get_transient( 'wc_table_rate_currency_converter' );
								foreach( $shipping_zones as $zone ) {
									$i++;
									if ( $i % 2 == 0 ) {
										$alternate = '';
									} else {
										$alternate = ' class="alternate"';
									}

									$id    = $zone->zone_id;
									$value = $this->currencies[$id];
?>
						<tr<?php echo $alternate ?>>
							<td>
								<table class="widefat">
									<thead>
										<tr>
											<th><?php _e('Shipping Zone', 'wc_table_rate_currency_converter' ) ?></th>
											<th><?php _e('Local Currency', 'wc_table_rate_currency_converter') ?></th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td><a href="<?php echo admin_url('admin.php?page=shipping_zones&zone=' . $id)?>" title="<?php _e('Edit') ?>"><?php echo $zone->zone_name?></a></td>
											<td>
												<select name="shipping_zone_currency[<?php echo $id; ?>]">
<?php
									foreach ( $currency_code_options as $key => $val ) {
?>
														<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key );?>><?php echo $val ?></option>
<?php
									}
?>
											   </select>
											</td>
										</tr>
										<tr>
											<td colspan="2">
<?php
									$shipping_methods = $wpdb->get_results(
										$wpdb->prepare( "
											SELECT *
											FROM {$wpdb->prefix}woocommerce_shipping_zone_shipping_methods
											WHERE zone_id = %s
											ORDER BY `shipping_method_order` ASC
										", $id ) );

									if ( $shipping_methods ) {
										foreach ( $shipping_methods as $method ) {
											$shipping_method = woocommerce_get_shipping_method_table_rate( $method->shipping_method_id );
											if ( ! 'yes' == $shipping_method->enabled ) continue;
											echo '<h4><a href="'. admin_url('admin.php?page=shipping_zones&zone=' . $id .'&method=' . $method->shipping_method_id ) . '" title="' . __('Edit') . '">' . $shipping_method->title . '</a> </h4>';
											if ( $exchange_rates[$value] ) {
												echo '<span class="alignright">' . sprintf( __( 'Current exchange rate for %s is %s', 'wc_table_rate_currency_converter'), $value, $exchange_rates[$value] ) . '</span>';
											}

?>
												<table class="widefat">
													<thead>
														<tr>
															<th><?php _e('Label', 'wc_table_rate_currency_converter' ) ?></th>
															<th><?php _e('Rate Cost', 'wc_table_rate_currency_converter') ?></th>
															<th><?php _e('Local Currency Cost', 'wc_table_rate_currency_converter') ?></th>
														</tr>
													</thead>
													<tbody>
<?php
											$rates = $this->get_shipping_rates( $method->shipping_method_id );
											foreach ( $rates as $rate ) {
												if ( is_array( $this->local_rates[ $rate->rate_id ] ) ) {
													$local_currency = key( $this->local_rates[ $rate->rate_id ] );
													$value          = current( $this->local_rates[ $rate->rate_id ] );
												} else {
													$local_currency = $store_currency_code;
													$value          = $rate->rate_cost;
												}
?>
														<tr>
															<td><?php echo $rate->rate_label ?></td>
															<td><?php echo woocommerce_price( $rate->rate_cost ) ?> (<?php echo $store_currency_code ?>)</td>
															<td><input type="text" value="<?php echo $value ?>" name="shipping_zone_cost[<?php echo $id ?>][<?php echo $rate->rate_id ?>]" /> (<?php echo $local_currency ?>)</td>
														</tr>
<?php
											}
?>
													</tbody>
												</table>
											</td>
										</tr>
<?php
										}
?>
									</tbody>
								</table>
							</td>
						</tr>
<?php
									}
								}
							}
?>
					</tbody>
				</table>
			</td>
		</tr>
<?php
		return ob_get_clean();
	}


   	/**
	 * validate_zones_field function.
	 *
	 * @access public
	 * @param mixed $key
	 * @return array
	 */
	public function validate_zones_field( $key ) {
		$currencies = array();
		$rates      = array();
		if ( isset($_POST['shipping_zone_currency'] ) ) {
			foreach( $_POST['shipping_zone_currency'] as $key => $value ) {
				if ( ! empty( $value  ) ) {
					$currencies[$key] = $value;
					foreach( $_POST['shipping_zone_cost'][$key] as $rate_key => $rate_value ) {
						$rates[$rate_key] = array( $value => $rate_value );
					}
				}
			}
		}
		return array( 'currencies' => $currencies, 'rates' => $rates );
	}


	/**
     * calculate_shipping function.
     *
     * @access public
     * @param mixed $package
     * @return void
     */
    public function calculate_shipping( $void ) {
		// do we have current exchange rates?
		if ( false !== ( $exchange_rates = get_transient( 'wc_table_rate_currency_converter' ) ) ) {

			// yes, do nothing

		} else {

			// no, so we must recalculate the rates according to a new exchange rate

			if ( false === ( $exchange_rates = get_transient( 'woocommerce_currency_converter_rates' ) ) ) {

				// seems that plugin currency converter is down, alert the site manager

				$admin_email = get_option('admin_email');
				$message     = __( '
Sorry to inform, but plugin Table Rate Currency Converter needs your attention.
Your store does not have access to the current exchange rate and your shipping values can be wrong.
', 'wc_table_rate_currency_converter');
				wp_mail( $admin_email, sprintf( __('Table Rate Currency Converter Plugin requires attention at %s', 'wc_table_rate_currency_converter'), site_url() ), $message );

			} else {

				$exchange_rates = json_decode( $exchange_rates );

				$base_exchange_rate = $exchange_rates->base;
				$all_exchange_rates = $exchange_rates->rates;

				$store_base = get_woocommerce_currency();

				$exchange_rates = array();

				// let's update our shipping rates that have local currency values

				foreach( $this->currencies as $currency ) {
					$rate = $this->get_exchange_rate( $store_base, $currency, $base_exchange_rate, $all_exchange_rates );
					$exchange_rates[ $currency ] = $rate;
					foreach( $this->local_rates as $rate_id => $local_rate ) {
						if ( $currency != key( $local_rate ) )
							continue;
						$this->set_shipping_rate( $rate_id, current( $local_rate ) * $rate );
					}
				}

				// we have exchange rates, save our transient
				set_transient( 'wc_table_rate_currency_converter', $exchange_rates, 60*60*12 );

			}
		}

		// we are not a true shipping method, just a filter, so we do nothing.
		return;
	}

	/**
	 * get_exchange_rate function.
	 *
	 */
	function get_exchange_rate( $to, $from, $base, $rates ) {
		// If `from` currency === base, return the basic exchange rate for the `to` currency
		if ( $from === $base ) {
			return $rates->$to ;
		}

		// If `to` currency === base, return the basic inverse rate of the `from` currency
		if ( $to === $base ) {
			return 1 / $rates->$from;
		}

		// Otherwise, return the `to` rate multipled by the inverse of the `from` rate to get the
		// relative exchange rate between the two currencies
		return $rates->$to * ( 1 / $rates->$from );
	}


	/**
	 * get_shipping_rates function.
	 *
	 * @access public
	 * @param int $class (default: 0)
	 * @return void
	 */
	function get_shipping_rates( $id ) {
		global $wpdb;

		return $wpdb->get_results( "
			SELECT *
			FROM {$wpdb->prefix}woocommerce_shipping_table_rates
			WHERE shipping_method_id = {$id}
			ORDER BY rate_order ASC;
		" );
	}

	/**
	 * set_shipping_rate function.
	 *
	 * @access public
	 * @param int $id, float $value
	 * @return void
	 */
	function set_shipping_rate( $id, $value ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'woocommerce_shipping_table_rates',
			array(
				'rate_cost'	=> $value,
			),
			array(
				'rate_id' => $id
			),
			array(
				'%s',
			),
			array(
				'%d'
			)
		);
	}
}