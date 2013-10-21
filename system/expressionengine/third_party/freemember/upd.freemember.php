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

require_once(PATH_THIRD.'freemember/config.php');

class Freemember_upd
{
    public $version = FREEMEMBER_VERSION;

    public function install()
    {
        $this->uninstall();

        // register module
        ee()->db->insert('modules', array(
            'module_name' => FREEMEMBER_CLASS,
            'module_version' => $this->version,
            'has_cp_backend' => 'n',
            'has_publish_fields' => 'n'));

        $this->_register_action('act_login');
        $this->_register_action('act_logout');
        $this->_register_action('act_register');
        $this->_register_action('act_update_profile');
        $this->_register_action('act_forgot_password');
        $this->_register_action('act_reset_password');

        return true;
    }

    public function update($current = '')
    {
        return true;
    }

    public function uninstall()
    {
        ee()->db->where('class', FREEMEMBER_CLASS);
        ee()->db->delete('actions');

        ee()->db->where('module_name', FREEMEMBER_CLASS);
        ee()->db->delete('modules');

        return true;
    }

    protected function _register_action($method)
    {
        ee()->db->where('class', FREEMEMBER_CLASS);
        ee()->db->where('method', $method);
        if (ee()->db->count_all_results('actions') == 0) {
            ee()->db->insert('actions', array(
                'class' => FREEMEMBER_CLASS,
                'method' => $method
            ));
        }
    }
}
