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

// requires EE_Form_validation class
get_instance()->load->library('form_validation');

class Fm_form_validation extends EE_Form_validation
{
    public function __construct($rules = array())
    {
        parent::__construct($rules);

        // overwrite EE form validation library
        ee()->form_validation =& $this;

        // load EE_Validate class
        if ( ! class_exists('EE_Validate')) {
            require APPPATH.'libraries/Validate.php';
        }

        $this->VAL = new EE_Validate();
    }

    public function error_array()
    {
        return $this->_error_array;
    }

    /**
     * Awesome function to manually add an error to the form
     */
    public function add_error($field, $message)
    {
        // make sure we have data for this field
        if (empty($this->_field_data[$field])) {
            $this->set_rules($field, "lang:$field", '');
        }

        $this->_field_data[$field]['error'] = $message;
        $this->_error_array[$field] = $message;
    }

    /**
     * Add validation rules instead of overwriting them
     */
    public function add_rules($field, $label = '', $rules = '')
    {
        // are there any existing rules for this field?
        if ( ! empty($this->_field_data[$field]['rules'])) {
            $rules = trim($this->_field_data[$field]['rules'].'|'.$rules, '|');
        }

        $this->set_rules($field, $label, $rules);
    }

    /**
     * Field must match password of current user (e.g. update profile)
     */
    public function fm_current_password($str)
    {
        $current_member_id = ee()->session->userdata('member_id');
        if ($str == '' || $current_member_id == 0) return true;

        $this->set_message('fm_current_password', lang('invalid_password'));
        ee()->load->library('auth');

        return (bool) ee()->auth->authenticate_id($current_member_id, $str);
    }

    public function fm_valid_captcha($str)
    {
        $count = ee()->db->from('captcha')
            ->where('word', $str)
            ->where('ip_address', ee()->input->ip_address())
            ->where('date > UNIX_TIMESTAMP()-7200')
            ->count_all_results();

        // don't delete captcha here, it will be deleted when EE validates the captcha later
        $this->set_message('fm_valid_captcha', lang('captcha_incorrect'));

        return $count > 0;
    }

    /**
     * Awesome: we only add this rule if we know the selection was invalid
     */
    public function fm_invalid_selection($str)
    {
        return '' != $str;
    }
}
