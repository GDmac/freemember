<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * FreeMember add-on for ExpressionEngine
 * Copyright (c) 2012 Adrian Macneil
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class Freemember_model extends CI_Model
{
	protected $EE;

	public function __construct()
	{
		parent::__construct();
		$this->EE =& get_instance();
	}

	public function find_member_by_reset_code($reset_code)
	{
		if (empty($reset_code)) return false;

		return $this->EE->db->from('reset_password r')
			->join('members m', 'm.member_id = r.member_id')
			->where('resetcode', $reset_code)
			->where('date > UNIX_TIMESTAMP()-7200')
			->get()->row();
	}

	/**
	 * Clean password reset codes before issuing a new one, or after it has been used
	 */
	public function clean_password_reset_codes($member_id)
	{
		$this->EE->db->where('member_id', $member_id)
			->or_where('date < UNIX_TIMESTAMP()-7200')
			->delete('reset_password');
	}

	/**
	 * Fetch an array of user editable member fields
	 */
	public function member_fields()
	{
		return array(
			'username',
			'email',
			'screen_name',
			'url',
			'location',
			'occupation',
			'interests',
			'bday_d',
			'bday_m',
			'bday_y',
			'aol_im',
			'yahoo_im',
			'msn_im',
			'icq',
			'bio',
			'signature',
		);
	}

	/**
	 * Get a list of member fields. Cached for performance.
	 */
	public function member_custom_fields()
	{
		static $member_custom_fields = null;
		if (null === $member_custom_fields)
		{
			$member_custom_fields = $this->EE->db->get('member_fields')->result();
		}

		return $member_custom_fields;
	}

	/**
	 * Update data for the specified member.
	 */
	public function update_member($member_id, $data)
	{
		$update_data = array();
		foreach ($this->member_fields() as $field)
		{
			if (isset($data[$field]))
			{
				$update_data[$field] = $data[$field];
			}
		}

		if ( ! empty($data['password']))
		{
			$update_data['password'] = do_hash($data['password']);
		}

		if ( ! empty($update_data))
		{
			$this->EE->db->where('member_id', $member_id)->update('members', $update_data);
		}
	}

	/**
	 * Update custom fields for the specified member.
	 */
	public function update_member_custom($member_id, $data)
	{
		$update_data = array();
		foreach ($this->member_custom_fields() as $field)
		{
			if (isset($data[$field->m_field_name]))
			{
				$update_data['m_field_id_'.$field->m_field_id] = $data[$field->m_field_name];
			}
		}

		if ( ! empty($update_data))
		{
			$this->EE->db->where('member_id', $member_id)->update('member_data', $update_data);
		}
	}

	/**
	 * Update online user stats
	 */
	public function update_online_user_stats()
	{
		if ($this->EE->config->item('enable_online_user_tracking') == 'n' OR
			$this->EE->config->item('disable_all_tracking') == 'y')
		{
			return;
		}

		// Update stats
		$cutoff = $this->EE->localize->now - (15 * 60);
		$anon = ($this->EE->input->post('anon') == 1) ? '' : 'y';

		$in_forum = ($this->EE->input->get_post('FROM') == 'forum') ? 'y' : 'n';

		$escaped_ip = $this->EE->db->escape_str($this->EE->input->ip_address());

		$this->EE->db->where('site_id', $this->EE->config->item('site_id'))
					 ->where("(ip_address = '".$escaped_ip."' AND member_id = '0')", '', false)
					 ->or_where('date < ', $cutoff)
					 ->delete('online_users');

		$data = array(
						'member_id'		=> $this->EE->session->userdata('member_id'),
						'name'			=> ($this->EE->session->userdata('screen_name') == '') ? $this->EE->session->userdata('username') : $this->EE->session->userdata('screen_name'),
						'ip_address'	=> $this->EE->input->ip_address(),
						'in_forum'		=> $in_forum,
						'date'			=> $this->EE->localize->now,
						'anon'			=> $anon,
						'site_id'		=> $this->EE->config->item('site_id')
					);

		$this->EE->db->where('ip_address', $this->EE->input->ip_address())
					 ->where('member_id', $data['member_id'])
					 ->update('online_users', $data);
	}
}

/* End of file */