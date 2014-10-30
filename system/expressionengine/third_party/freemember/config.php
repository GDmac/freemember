<?php

// don't check version inside installer, seems to break on some installs
if (defined('APP_VER') && !defined('EE_APPPATH') && version_compare(APP_VER, '2.6.0', '<')) {
    show_error('FreeMember requires ExpressionEngine version 2.6+, you have '.APP_VER);
}

if ( ! defined('FREEMEMBER_NAME')) {
    define('FREEMEMBER_NAME', 'FreeMember');
    define('FREEMEMBER_CLASS', 'Freemember');
    define('FREEMEMBER_VERSION', '2.3.3');
    define('FREEMEMBER_DOCS', 'https://github.com/expressodev/freemember');
}
