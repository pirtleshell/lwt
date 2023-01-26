<?php

/**
 * \file
 * 
 * \brief Analyse DB tables, select Table Set, start LWT
 * 
 * Call: start.php
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/start_8php.html
 * @since   1.5.4
 */

require_once 'inc/session_utility.php';


function start_save_prefix($req) {
    $tbpref = $req;
    LWTTableSet("current_table_prefix", $tbpref);
    header("Location: index.php");
    exit(); 
}

if (isset($_REQUEST['prefix']) && $_REQUEST['prefix'] !== '-') {
    start_save_prefix(getreq('prefix'));
}


function start_do_page() {
    global $tbpref, $fixed_tbpref;
    $prefix = getprefixes();

    pagestart('Select Table Set', false);

?>

<table class="tab1" style="width: auto;" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1">
            <form name="f1" class="inline" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
            <p>
                Select: 
                <select name="prefix" <?php 
                if ($fixed_tbpref) {
                    echo 'disabled title="Database prefix is fixed and cannot be changed!"';
                }?> >
                    <option value="" <?php echo ($tbpref == '' ? 'selected="selected"': ''); ?>>
                        Default Table Set
                    </option>
                    <?php foreach ($prefix as $value) { ?>
                    <option value="<?php echo tohtml($value); ?>" <?php echo (substr($tbpref, 0, -1) == $value ? 'selected="selected"': ''); ?>>
                        <?php echo tohtml($value); ?>
                    </option>
                    <?php } ?>
                </select> 
            </p>
            <p class="center">
                <input type="submit" name="op" value="Start LWT" />
            </p>
            </form>
        </th>
    </tr>
</table>

<?php
    pageend();
}

start_do_page();

?>