<?php

namespace Lwt\Ajax;

require_once 'session_utility.php';


function get_word_test_ajax($testsql, $nosent, $lgid, $lang, $record, $testtype)
{
    list($word, $wid, $wordlc) = do_test_get_word($testsql);
    if (!$nosent) {
        // $nosent == FALSE, mode 1-3
        list($sent, $_) = do_test_test_sentence($wid, $lgid, $wordlc);
        if ($sent === null) {
            $sent = "{" . $word . "}";
        }
    }
    list($r, $save) = print_term_test(
        $record, $sent, $testtype, $nosent, $lang['regexword']
    );
    return $r;
}

function word_test_ajax()
{
    return get_word_test_ajax();
}

if (isset($_GET['action']) && $_GET['action'] == 'display') {
    if ($_GET['action_type'] == 'test') {
        echo word_test_ajax();
    }
}

?>
