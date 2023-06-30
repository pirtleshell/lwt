<?php

/**
 * \file
 * \brief Get a translation from Web Dictionary
 * 
 * Call 1: trans.php?x=1&t=[textid]&i=[textpos]
 *         Display translator for sentence in Text t, Pos i
 * Call 2: trans.php?x=2&t=[text]&i=[dictURI]
 *         translates text t with dict via dict-url i
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/trans_8php.html
 * @since   1.0.3
 */

namespace Lwt;

require_once 'inc/session_utility.php';


function translator_url($term, $order)
{
    global $tbpref;
    $sql = "SELECT SeText, LgGoogleTranslateURI 
    FROM {$tbpref}languages, {$tbpref}sentences, {$tbpref}textitems2 
    WHERE Ti2SeID = SeID AND Ti2LgID = LgID AND Ti2TxID = $term AND Ti2Order = $order";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    if ($record) {
        $satz = $record['SeText'];
        $trans = isset($record['LgGoogleTranslateURI']) ? 
        $record['LgGoogleTranslateURI'] : "";
        if (substr($trans, 0, 1) == '*') { 
            $trans = substr($trans, 1); 
        }
    } else {
        my_die("No results: $sql"); 
    }
    mysqli_free_result($res);
    if ($trans != '') {
        $parsed_url = parse_url($trans, PHP_URL_PATH);
        if (
            substr($trans, 0, 7) == 'ggl.php' || 
            $parsed_url && str_ends_with($parsed_url, 'ggl.php')) {
            $trans = str_replace('?', '?sent=1&', $trans);
        }
        return createTheDictLink($trans, $satz);
    }
}


function display_page($type, $term, $order)
{
    // Translate sentence
    if ($type == 1) {
        $url = translator_url($order, $term);
        if ($url != '') {
            header("Location: " . $url);
        }
        exit();
    }
    // Translate text
    if ($type == 2) {
        header("Location: " . createTheDictLink($term, $order));
        exit();
    }
}

if (isset($_REQUEST["x"]) && is_numeric($_REQUEST["x"])) {
    display_page($_REQUEST["x"], $_REQUEST["i"], $_REQUEST["t"]);
}

?>
