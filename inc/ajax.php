<?php

namespace Lwt\Ajax;

require_once 'session_utility.php';
require_once __DIR__ . '/simterms.php';
require_once '../do_test_test.php';


/**
 * Retun the next word to test as JSON
 * 
 * @param string $testsql SQL projection query
 * @param bool $nosent Test is in word mode
 * @param int $lgid Language ID
 * @param string $wordregex Word selection regular expression
 * @param int $testtype Test type
 * 
 * @return string Next word formatted as JSON.
 */
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

/**
 * Retur the number of tests for tomorrow.
 * 
 * @param string $testsql
 * 
 * @return string Tests for tomorrow as JSON
 */
function get_tomorrow_test_count($testsql) {
    $output = array(
        "test_count" => do_test_get_tomorrow_tests_count($testsql)
    );
    return json_encode($output);
}

/**
 * Return the next word to test.
 * 
 * @param array $get_req Array with the fields {test_sql, test_nosent, 
 * test_lgid, test_wordregex, test_type}
 */
function word_test_ajax($get_req)
{
    return get_word_test_ajax(
        $get_req['test_sql'], $get_req['test_nosent'], $get_req['test_lgid'], 
        $get_req['test_wordregex'], $get_req['test_type']
    );
}

/**
 * Return the number of tests for tomorrow by using the supllied query.
 * 
 * @param array $get_req Array with the field "test_sql"
 */
function tomorrow_test_count($get_req) 
{
    return get_tomorrow_test_count($get_req['test_sql']);
}

function similar_terms($post_req) {
    return print_similar_terms(
        (int)$post_req["simterms_lgid"], 
        (string) $post_req["simterms_word"]
    );
}


if (isset($_GET['action'])) {
    if ($_GET['action'] == 'query') {
        if ($_GET['action_type'] == 'test') {
            echo word_test_ajax($_GET);
        } else if ($_GET['action_type'] == 'tomorrow_test_count') {
            echo tomorrow_test_count($_GET);
        }
    }
} else if (isset($_POST['action'])) {
    if (true || $_POST['action'] == '') {
        switch ($_POST['action_type']) {
            case "simterms":
                echo similar_terms($_POST);
                break;
        }
    }
}

?>
