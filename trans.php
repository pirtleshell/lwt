<?php

/**
 * \file
 * \brief Get a translation from Web Dictionary
 * 
 * Call 1: trans.php?x=1&t=[textid]&i=[textpos]
 *         GTr translates sentence in Text t, Pos i
 * Call 2: trans.php?x=2&t=[text]&i=[dictURI]
 *         translates text t with dict via dict-url i
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/trans_8php.html
 * @since   1.0.3
 */

require_once 'inc/session_utility.php';

$x = $_REQUEST["x"];
$i = $_REQUEST["i"];
$t = $_REQUEST["t"];

if ($x == 1) {
    $sql = "SELECT SeText, LgGoogleTranslateURI 
    FROM {$tbpref}languages, {$tbpref}sentences, {$tbpref}textitems2 
    WHERE Ti2SeID = SeID AND Ti2LgID = LgID AND Ti2TxID = $t AND Ti2Order = $i";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    if ($record) {
        $satz = $record['SeText'];
        $trans = isset($record['LgGoogleTranslateURI']) ? 
        $record['LgGoogleTranslateURI'] : "";
        if (str_starts_with($trans, "libretranslate ")) {
            $trans = substr($trans, strlen("libretranslate "));
        }
        if (substr($trans, 0, 1) == '*') { 
            $trans = substr($trans, 1); 
        }
    } else {
        my_die("No results: $sql"); 
    }
    mysqli_free_result($res);
    if ($trans != '') {
        if (substr($trans, 0, 7) == 'ggl.php') {
            $trans = str_replace('?', '?sent=1&', $trans);
        }
        header("Location: " . createTheDictLink($trans, $satz));
    }
    exit();
}

if ($x == 2) {
    header("Location: " . createTheDictLink($i, $t));
    exit();
}    

?>
