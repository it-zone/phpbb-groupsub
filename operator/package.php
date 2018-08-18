<?php
/**
 *
 * Group Subscription. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Steve Guidetti, https://github.com/stevotvr
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace stevotvr\groupsub\operator;

use phpbb\group\helper;
use stevotvr\groupsub\entity\package_interface as entity;
use stevotvr\groupsub\exception\base;

/**
 * Group Subscription package operator.
 */
class package extends operator implements package_interface
{
	/**
	 * @var \phpbb\group\helper
	 */
	protected $group_helper;

	/**
	 * Set up the operator.
	 *
	 * @param \phpbb\group\helper $group_helper
	 */
	public function setup(helper $group_helper)
	{
		$this->group_helper = $group_helper;
	}

	public function get_package_list()
	{
		$packages = array();

		$sql = 'SELECT pkg_id, pkg_name
				FROM ' . $this->package_table . '
				ORDER BY pkg_name ASC';
		$this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow())
		{
			$packages[(int) $row['pkg_id']] = $row['pkg_name'];
		}
		$this->db->sql_freeresult();

		return $packages;
	}

	public function get_packages($name = false)
	{
		$packages = array();

		$where = $name ? "WHERE pkg_ident = '" . $this->db->sql_escape($name) . "'" : '';
		$sql = 'SELECT *
				FROM ' . $this->package_table . '
				' . $where . '
				ORDER BY pkg_order ASC, pkg_id ASC';
		$this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow())
		{
			$packages[(int) $row['pkg_id']] = array(
				'package'	=> $this->container->get('stevotvr.groupsub.entity.package')->import($row),
				'terms'		=> array(),
				'groups'	=> array(),
			);
		}
		$this->db->sql_freeresult();

		if (empty($packages))
		{
			return $packages;
		}

		$sql = 'SELECT *
				FROM ' . $this->term_table . '
				WHERE ' . $this->db->sql_in_set('pkg_id', array_keys($packages));
		$this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow())
		{
			$packages[(int) $row['pkg_id']]['terms'][] = $this->container->get('stevotvr.groupsub.entity.term')->import($row);
		}
		$this->db->sql_freeresult();

		$sql_ary = array(
			'SELECT'	=> 's.pkg_id, g.group_id, g.group_name',
			'FROM'		=> array($this->group_table => 's'),
			'LEFT_JOIN'	=> array(
				array(
					'FROM'	=> array(GROUPS_TABLE => 'g'),
					'ON'	=> 'g.group_id = s.group_id',
				),
			),
			'WHERE'		=> $this->db->sql_in_set('s.pkg_id', array_keys($packages)),
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_ary);
		$this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow())
		{
			$packages[(int) $row['pkg_id']]['groups'][] = array(
				'id'	=> (int) $row['group_id'],
				'name'	=> $this->group_helper->get_name($row['group_name']),
			);
		}
		$this->db->sql_freeresult();

		return $packages;
	}

	public function count_packages()
	{
		$sql = 'SELECT COUNT(pkg_id) AS pkg_count
				FROM ' . $this->package_table;
		$this->db->sql_query($sql);
		$count = $this->db->sql_fetchfield('pkg_count');
		$this->db->sql_freeresult();

		return (int) $count;
	}

	public function add_package(entity $package)
	{
		$package->insert();
		$package_id = $package->get_id();
		return $package->load($package_id);
	}

	public function delete_package($package_id)
	{
		$sql = 'DELETE FROM ' . $this->group_table . '
				WHERE pkg_id = ' . (int) $package_id;
		$this->db->sql_query($sql);

		$sql = 'DELETE FROM ' . $this->term_table . '
				WHERE pkg_id = ' . (int) $package_id;
		$this->db->sql_query($sql);

		$sql = 'DELETE FROM ' . $this->package_table . '
				WHERE pkg_id = ' . (int) $package_id;
		$this->db->sql_query($sql);

		return (bool) $this->db->sql_affectedrows();
	}

	public function move_package($package_id, $offset)
	{
		$ids = array();

		$sql = 'SELECT pkg_id
				FROM ' . $this->package_table . '
				ORDER BY pkg_order ASC, pkg_id ASC';
		$this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow())
		{
			$ids[] = $row['pkg_id'];
		}
		$this->db->sql_freeresult();

		$position = array_search($package_id, $ids);
		array_splice($ids, $position, 1);
		$position += $offset;
		array_splice($ids, $position, 0, $package_id);

		foreach ($ids as $pos => $id)
		{
			$sql = 'UPDATE ' . $this->package_table . '
					SET pkg_order = ' . $pos . '
					WHERE pkg_id = ' . (int) $id;
			$this->db->sql_query($sql);
		}
	}

	public function get_terms($package_id = false)
	{
		$entities = array();

		$where = $package_id ? 'WHERE pkg_id = ' . (int) $package_id : '';
		$sql = 'SELECT *
				FROM ' . $this->term_table . '
				' . $where . '
				ORDER BY term_order ASC, term_id ASC';
		$this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow())
		{
			$entities[(int) $row['pkg_id']][] = $this->container->get('stevotvr.groupsub.entity.term')->import($row);
		}
		$this->db->sql_freeresult();

		return $entities;
	}

	public function set_terms($package_id, array $terms)
	{
		$sql = 'DELETE FROM ' . $this->term_table . '
				WHERE pkg_id = ' . (int) $package_id;
		$this->db->sql_query($sql);

		$i = 0;
		foreach ($terms as $entity)
		{
			$entity->set_package($package_id)->set_order($i++)->insert();
		}
	}

	public function get_package_term($term_id)
	{
		$sql_ary = array(
			'SELECT'	=> '*',
			'FROM'		=> array(
				$this->term_table		=> 't',
				$this->package_table	=> 'p',
			),
			'WHERE'		=> 't.term_id = ' . (int) $term_id . '
							AND p.pkg_id = t.pkg_id',
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_ary);
		$this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow();
		$this->db->sql_freeresult();

		if (!$row)
		{
			return false;
		}

		$groups = array();
		$sql_ary = array(
			'SELECT'	=> 'g.group_id, g.group_name',
			'FROM'		=> array($this->group_table => 's'),
			'LEFT_JOIN'	=> array(
				array(
					'FROM'	=> array(GROUPS_TABLE => 'g'),
					'ON'	=> 'g.group_id = s.group_id',
				),
			),
			'WHERE'		=> 's.pkg_id = ' . (int) $row['pkg_id'],
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_ary);
		$this->db->sql_query($sql);
		while ($grouprow = $this->db->sql_fetchrow())
		{
			$groups[(int) $grouprow['group_id']] = $grouprow['group_name'];
		}
		$this->db->sql_freeresult();

		try
		{
			return array(
				'package'	=> $this->container->get('stevotvr.groupsub.entity.package')->import($row),
				'groups'	=> $groups,
				'term'		=> $this->container->get('stevotvr.groupsub.entity.term')->import($row),
			);
		}
		catch (base $e)
		{
			return false;
		}
	}

	public function get_groups($package_id)
	{
		$ids = array();

		$sql = 'SELECT group_id
				FROM ' . $this->group_table . '
				WHERE pkg_id = ' . (int) $package_id;
		$this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow())
		{
			$ids[] = (int) $row['group_id'];
		}
		$this->db->sql_freeresult();

		return $ids;
	}

	public function add_group($package_id, $group_id)
	{
		$data = array(
			'pkg_id'	=> (int) $package_id,
			'group_id'	=> (int) $group_id,
		);
		$sql = 'INSERT INTO ' . $this->group_table . '
				' . $this->db->sql_build_array('INSERT', $data);
		$this->db->sql_query($sql);
	}

	public function remove_group($package_id, $group_id)
	{
		$sql = 'DELETE FROM ' . $this->group_table . '
				WHERE pkg_id = ' . (int) $package_id . '
					AND group_id = ' . (int) $group_id;
		$this->db->sql_query($sql);
	}

	public function remove_groups($package_id)
	{
		$sql = 'DELETE FROM ' . $this->group_table . '
				WHERE pkg_id = ' . (int) $package_id;
		$this->db->sql_query($sql);
	}
}
