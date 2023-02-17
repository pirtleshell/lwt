<?php

/**
 * \file
 * \brief Save a Setting (k/v)
 * 
 * Call: inc/ajax_save_setting.php?k=[key]&v=[value]
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/ajax__save__setting_8php.html
 * @since   1.2.1
 * @since   2.2.2-fork Refactored, will use GET methods
 * @since   2.6.0-fork Use POST method in priority 
 */

require_once __DIR__ . '/session_utility.php';

/**
 * Save a setting.
 * 
 * @param string $key   Setting key
 * @param mixed  $value Setting value
 * 
 * @return void
 */
function do_ajax_save_setting($key, $value) 
{
    chdir('..');

    saveSetting($key, $value);
}

if (isset($_POST['k']) && isset($_POST['v'])) {
    do_ajax_save_setting($_POST['k'], $_POST['v']);
}

?>