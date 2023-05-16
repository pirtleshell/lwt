<?php

namespace Lwt\Ajax;

require_once 'session_utility.php';
require_once '../do_test_test.php';


function get_word_test_ajax($testsql, $nosent, $lgid, $wordregex, $testtype)
{
    $word_record = do_test_get_word($testsql);
    if (empty($word_record)) {
        $output = array(
            "word_id" => 0,
            "word_text" => '',
            "group" => '' 
        );
        return json_encode($output);
    }
    $sent = repl_tab_nl($word_record['WoSentence']);
    if ($nosent) {
        $sent = "{" . $word_record['WoText'] . "}";
    } else {
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
    
    $output = array(
        "word_id" => $word_record['WoID'],
        "solution" => get_test_solution($testtype, $word_record, $nosent, $save),
        "word_text" => $save,
        "group" => $r 
    );
    return json_encode($output);
}

function get_tomorrow_test_count($testsql) {
    $output = array(
        "test_count" => do_test_get_tomorrow_tests_count($testsql)
    );
    return json_encode($output);
}

function word_test_ajax()
{
    return get_word_test_ajax(
        $_GET['test_sql'], $_GET['test_nosent'], $_GET['test_lgid'], 
        $_GET['test_wordregex'], $_GET['test_type']
    );
}

function tomorrow_test_count() 
{
    return get_tomorrow_test_count($_GET['test_sql']);
}


if (isset($_GET['action']) && $_GET['action'] == 'display') {
    if ($_GET['action_type'] == 'test') {
        echo word_test_ajax();
    } else if ($_GET['action_type'] == 'tomorrow_test_count') {
        echo tomorrow_test_count();
    }
}

?>
