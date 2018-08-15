<?php
/**
 *
 * Group Subscription. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018, Steve Guidetti, https://github.com/stevotvr
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace stevotvr\groupsub\entity;

/**
 * Group Subscription price option entity interface.
 */
interface price_interface extends entity_interface
{
	/**
	 * @return int|null The ID of the product associated with this price option
	 */
	public function get_product();

	/**
	 * @param int $product_id The ID of the product associated with this price option
	 *
	 * @return price_interface This object for chaining
	 *
	 * @throws \stevotvr\groupsub\exception\out_of_bounds
	 */
	public function set_product($product_id);

	/**
	 * @return int|null The price of this option in the currency subunit
	 */
	public function get_price();

	/**
	 * @param int $price The price of this option in the currency subunit
	 *
	 * @return price_interface This object for chaining
	 *
	 * @throws \stevotvr\groupsub\exception\out_of_bounds
	 */
	public function set_price($price);

	/**
	 * @return string The currency code of the price of this option
	 */
	public function get_currency();

	/**
	 * @param string $currency The currency code of the price of this option
	 *
	 * @return price_interface This object for chaining
	 *
	 * @throws \stevotvr\groupsub\exception\unexpected_value
	 */
	public function set_currency($currency);

	/**
	 * @return int|null The subscription length of this option in days
	 */
	public function get_length();

	/**
	 * @param int $length The subscription length of this option in days
	 *
	 * @return price_interface This object for chaining
	 *
	 * @throws \stevotvr\groupsub\exception\out_of_bounds
	 */
	public function set_length($length);

	/**
	 * @return int The sorting order of this option
	 */
	public function get_order();

	/**
	 * @param int $order The sorting order of this option
	 *
	 * @return price_interface This object for chaining
	 *
	 * @throws \stevotvr\groupsub\exception\out_of_bounds
	 */
	public function set_order($order);
}