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
    public function find_members($params)
    {
        $defaults = array(
            'member_id' => false,
            'group_id' => false,
            'username' => false,
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'member_id',
            'sort' => false,
            'count_all_results' => false,
        );
        $params = array_merge($defaults, (array) $params);

        $member_fields = $this->member_fields();
        $custom_fields = $this->member_custom_fields();

        // clean params
        $params['sort'] = strtoupper($params['sort']) == 'DESC' ? 'DESC' : 'ASC';
        if ('CURRENT_USER' == $params['member_id']) {
            $params['member_id'] = ee()->session->userdata('member_id');
        }

        // validate orderby clause
        if ('member_id' == $params['orderby']) {
            $params['orderby'] = 'm.member_id';
        } elseif ( ! in_array($params['orderby'], $member_fields)) {
            // orderby must be a custom member field
            $custom_order = $params['orderby'];
            $params['orderby'] = 'm.member_id';

            foreach ($custom_fields as $field) {
                if ($custom_order == $field->m_field_name) {
                    $params['orderby'] = 'm_field_id_'.$field->m_field_id;
                    break;
                }
            }
        }

        // these fields must not be an empty string (e.g. missing segment variable)
        if ('' === $params['member_id']) return false;
        if ('' === $params['group_id']) return false;
        if ('' === $params['username']) return false;

        // build select clause
        $sql_select = $member_fields;
        array_unshift($sql_select, 'm.member_id');
        array_unshift($sql_select, 'm.group_id');
        $custom_fields = $this->member_custom_fields();
        foreach ($custom_fields as $field) {
            $sql_select[] = ee()->db->protect_identifiers('m_field_id_'.$field->m_field_id).
                ' AS '.ee()->db->protect_identifiers($field->m_field_name);
        }

        // build where clause
        $sql_where = '1=1 ';
        if (false !== $params['member_id']) {
            $sql_where .= $this->functions->sql_andor_string((string) $params['member_id'], 'm.member_id');
        }
        if (false !== $params['group_id']) {
            $sql_where .= $this->functions->sql_andor_string((string) $params['group_id'], 'm.group_id');
        }
        if (false !== $params['username']) {
            $sql_where .= $this->functions->sql_andor_string((string) $params['username'], 'm.username');
        }

        // run query
        ee()->db->select(implode(', ', $sql_select))
            ->from('members m')
            ->join('member_data md', 'md.member_id = m.member_id', 'left')
            ->where($sql_where, null, false)
            ->order_by($params['orderby'], $params['sort']);

        if ($params['count_all_results']) {
            return ee()->db->count_all_results();
        }

        ee()->db->limit((int) $params['limit'], (int) $params['offset']);

        return ee()->db->get()->result_array();
    }

    public function find_member_by_reset_code($reset_code)
    {
        if (empty($reset_code)) return false;
        return ee()->db->from('reset_password r')
            ->join('members m', 'm.member_id = r.member_id')
            ->where('resetcode', $reset_code)
            ->where('date > UNIX_TIMESTAMP()-7200')
            ->get()->row();
    }

    public function find_member_by_username($username)
    {
        return ee()->db->where('username', $username)->get('members')->row();
    }

    public function find_member_by_email($email)
    {
        return ee()->db->where('email', $email)->get('members')->row();
    }

    /**
     * Clean password reset codes before issuing a new one, or after it has been used
     */
    public function clean_password_reset_codes($member_id)
    {
        ee()->db->where('member_id', $member_id)
            ->or_where('date < UNIX_TIMESTAMP()-7200')
            ->delete('reset_password');
    }

    /**
     * Fetch an array of user editable member fields
     */
    public function member_fields()
    {
        return array(
            'group_id',
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
        if (null === $member_custom_fields) {
            $member_custom_fields = ee()->db->get('member_fields')->result();
        }

        return $member_custom_fields;
    }

    /**
     * Update data for the specified member.
     */
    public function update_member($member_id, $data)
    {
        if (ee()->extensions->active_hook('freemember_update_member_start')) {
            $data = ee()->extensions->call('freemember_update_member_start', $member_id, $data);
            if (ee()->extensions->end_script) return;
        }

        $update_data = array();
        foreach ($this->member_fields() as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }

        if ( ! empty($data['password'])) {
            $update_data['password'] = sha1($data['password']);
        }

        if ( ! empty($update_data)) {
            ee()->load->model('member_model');
            ee()->member_model->update_member($member_id, $update_data);
        }
    }

    /**
     * Update custom fields for the specified member.
     */
    public function update_member_custom($member_id, $data)
    {
        if (ee()->extensions->active_hook('freemember_update_member_custom_start')) {
            $data = ee()->extensions->call('freemember_update_member_custom_start', $member_id, $data);
            if (ee()->extensions->end_script) return;
        }

        $update_data = array();
        foreach ($this->member_custom_fields() as $field) {
            if (isset($data[$field->m_field_name])) {
                $update_data['m_field_id_'.$field->m_field_id] = $data[$field->m_field_name];
            }
        }

        if ( ! empty($update_data)) {
            ee()->load->model('member_model');
            ee()->member_model->update_member_data($member_id, $update_data);
        }
    }

    /**
     * Update online user stats
     */
    public function update_online_user_stats()
    {
        if (ee()->config->item('enable_online_user_tracking') == 'n' OR
            ee()->config->item('disable_all_tracking') == 'y')
        {
            return;
        }

        // Update stats
        $cutoff = ee()->localize->now - (15 * 60);
        $anon = (ee()->input->post('anon') == 1) ? '' : 'y';

        $in_forum = (ee()->input->get_post('FROM') == 'forum') ? 'y' : 'n';

        $escaped_ip = ee()->db->escape_str(ee()->input->ip_address());

        ee()->db->where('site_id', ee()->config->item('site_id'))
                     ->where("(ip_address = '".$escaped_ip."' AND member_id = '0')", '', false)
                     ->or_where('date < ', $cutoff)
                     ->delete('online_users');

        $data = array(
                        'member_id'		=> ee()->session->userdata('member_id'),
                        'name'			=> (ee()->session->userdata('screen_name') == '') ? ee()->session->userdata('username') : ee()->session->userdata('screen_name'),
                        'ip_address'	=> ee()->input->ip_address(),
                        'in_forum'		=> $in_forum,
                        'date'			=> ee()->localize->now,
                        'anon'			=> $anon,
                        'site_id'		=> ee()->config->item('site_id')
                    );

        ee()->db->where('ip_address', ee()->input->ip_address())
                     ->where('member_id', $data['member_id'])
                     ->update('online_users', $data);
    }
}
