<?php
/**
 *
 * Group Subscription. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Steve Guidetti, https://github.com/stevotvr
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace stevotvr\groupsub\controller;

/**
 * Group Subscription ACP subscriptions controller interface.
 */
interface acp_subs_interface extends acp_base_interface
{
	/**
	 * Display all items.
	 */
	public function display();

	/**
	 * Add an item.
	 */
	public function add();

	/**
	 * Edit an item.
	 *
	 * @param int $id The item ID
	 */
	public function edit($id);

	/**
	 * Delete an item.
	 *
	 * @param int $id The item ID
	 */
	public function delete($id);
}
