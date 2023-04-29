<?php

namespace Lwt\Ajax;

require_once 'session_utility.php';
require_once '../do_test_test.php';


function get_word_test_ajax($testsql, $nosent, $lgid, $wordregex, $testtype)
{
    $word_record = do_test_get_word($testsql);
    $sent = repl_tab_nl($word_record['WoSentence']);
    if (!$nosent) {
        // $nosent == FALSE, mode 1-3
        list($sent, $_) = do_test_test_sentence(
            $word_record['WoID'], $lgid, $word_record['WoTextLC']
        );
        if ($sent === null) {
            $sent = "{" . $word_record['WoText'] . "}";
        }
    }
    list($r, $save) = do_test_get_term_test(
        $word_record, $sent, $testtype, $nosent, $wordregex
    );
    return $r;
}

function word_test_ajax()
{
    return get_word_test_ajax(
        $_GET['test_sql'], $_GET['test_nosent'], $_GET['test_lgid'], 
        $_GET['test_wordregex'], $_GET['test_type']
    );
}

if (isset($_GET['action']) && $_GET['action'] == 'display') {
    if ($_GET['action_type'] == 'test') {
        echo word_test_ajax();
    }
}

?>
