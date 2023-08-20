<?php

namespace Lwt\Ajax;

require_once 'session_utility.php';
require_once __DIR__ . '/simterms.php';
require_once '../do_test_test.php';
require_once __DIR__ . '/ajax_add_term_transl.php';

/**
 * Return the API version.
 * 
 * @param array $get_req GET request, unnused
 * 
 * @return string JSON-encoded version
 */
function rest_api_version($get_req)
{
    return (string)json_encode(array(
        "version"      => "0.0.1",
        "release_date" => "2023-09-01"
    ));
}

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

/**
 * Get terms similar to a given term.
 * 
 * @param array $post_req Input post request.
 * 
 * @return string Similar terms in HTML format.
 */
function similar_terms($post_req) 
{
    return print_similar_terms(
        (int)$post_req["simterms_lgid"], 
        (string) $post_req["simterms_word"]
    );
}

/**
 * Create the translation for a new term.
 * 
 * @param array $post_req Input post request.
 * 
 * @return string Error message in case of failure, lowercase term otherwise
 */
function add_translation($post_req)
{
    return add_new_term_transl(
        trim($post_req['text']), (int)$post_req['lang'], trim($post_req['translation'])
    );
}

/**
 * Edit the translation of an existing term.
 * 
 * @param array $post_req Input post request.
 * 
 * @return string Term in lower case, or "" if term does not exist
 */
function update_translation($post_req)
{
    return do_ajax_check_update_translation(
        (int)$post_req['id'], trim($post_req['translation'])
    );
}


if (isset($_GET['action'])) {
    if ($_GET['action'] == 'query') {
        switch ($_GET['action_type']) {
            case 'version':
                echo rest_api_version($_GET);
            case 'test':
                echo word_test_ajax($_GET);
                break;
            case 'tomorrow_test_count':
                echo tomorrow_test_count($_GET);
                break;
        }
    }
} else if (isset($_POST['action'])) {
    if (true || $_POST['action'] == '') {
        switch ($_POST['action_type']) {
            case "simterms":
                echo similar_terms($_POST);
                break;
            case "add_translation":
                echo add_translation($_POST);
                break;
            case "update_translation":
                echo update_translation($_POST);
                break;
        }
    }
}

?>
