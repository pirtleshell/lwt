<?php

namespace Lwt\Ajax;

require_once 'session_utility.php';
require_once __DIR__ . '/simterms.php';
require_once '../do_test_test.php';
require_once __DIR__ . '/ajax_add_term_transl.php';
require_once __DIR__ . '/ajax_check_regexp.php';
require_once __DIR__ . '/ajax_chg_term_status.php';
require_once __DIR__ . '/ajax_save_impr_text.php';
require_once __DIR__ . '/ajax_save_text_position.php';
require_once __DIR__ . '/ajax_show_imported_terms.php';
require_once __DIR__ . '/ajax_edit_impr_text.php';


// -------------------------- GET REQUESTS -------------------------

/**
 * Return the API version.
 * 
 * @param array $get_req GET request, unnused
 * 
 * @return string JSON-encoded version
 */
function rest_api_version($get_req)
{
    return (string)json_encode(
        array(
        "version"      => "0.0.1",
        "release_date" => "2023-09-01"
        )
    );
}

/**
 * Retun the next word to test as JSON
 * 
 * @param string $testsql   SQL projection query
 * @param bool   $nosent    Test is in word mode
 * @param int    $lgid      Language ID
 * @param string $wordregex Word selection regular expression
 * @param int    $testtype  Test type
 * 
 * @return array Next word formatted as an array.
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
    
    return array(
        "word_id" => $word_record['WoID'],
        "solution" => get_test_solution($testtype, $word_record, $nosent, $save),
        "word_text" => $save,
        "group" => $r 
    );
}


/**
 * Return the next word to test.
 * 
 * @param array $get_req Array with the fields {test_sql: string, 
 *                       test_nosent: bool, test_lgid: int, 
 *                       test_wordregex: string, test_type: int}
 * 
 * @return string Next word formatted as JSON.
 */
function word_test_ajax($get_req)
{
    return json_encode(
        get_word_test_ajax(
            $get_req['test_sql'], $get_req['test_nosent'], 
            $get_req['test_lgid'], 
            $get_req['test_wordregex'], $get_req['test_type']
        )
    );
}

/**
 * Return the number of tests for tomorrow by using the supllied query.
 * 
 * @param array $get_req Array with the field "test_sql"
 * 
 * @return string JSON-encoded result
 */
function tomorrow_test_count($get_req) 
{
    $output = array(
        "test_count" => do_test_get_tomorrow_tests_count($get_req['test_sql'])
    );
    return json_encode($output);
}

/**
 * Get the phonetic reading of a word based on it's language.
 * 
 * @param array $get_req Array with the fields "text" and "lang" (short language name)
 * 
 * @return string JSON-encoded result
 */
function get_phonetic_reading($get_req)
{
    $data = phonetic_reading($get_req['text'], $get_req['lang']);
    return json_encode(array("phonetic_reading" => $data));
}

    
/**
 * Get the file path using theme.
 * 
 * @param array $get_req Get request with field "path", relative filepath using theme.
 * 
 * @return string JSON-encoded result
 */
function get_theme_path($get_req)
{
    chdir('..');
    return json_encode(
        array("theme_path" => get_file_path($get_req['path']))
    );
}

/**
 * Return statistics about a group of text.
 * 
 * @param array $get_req Get request with field "texts_id", texts ID.
 */
function get_texts_statistics($get_req)
{
    return json_encode(return_textwordcount($get_req["texts_id"]));
}

/**
 * List the audio files in the media folder.
 */
function media_paths($get_req) 
{
    chdir("..");
    return json_encode(get_media_paths());
}

/**
 * Return the example sentences containing an input word.
 * 
 * @param array $get_req Get request with fields "lid", "word_lc" and "wid".
 */
function example_sentences($get_req)
{
    chdir("..");
    return json_encode(
        sentences_with_word(
            (int) $get_req["lid"],
            $get_req["word_lc"],
            (int) $get_req["wid"]
        )
    );
}

/**
 * Return the list of imported terms.
 * 
 * @param array $get_req Get request with fields "last_update", "page" and "count".
 */
function imported_terms($get_req)
{
    return json_encode(
        imported_terms_list(
            $get_req["last_update"], $get_req["page"], $get_req["count"]
        )
    );
}


/**
 * Translations for a term to choose an annotation.
 * 
 * @param array $get_req Get request with fields "text_id" and "page" and "count".
 */
function term_translations($get_req)
{
    return json_encode(
        \Lwt\Ajax\Improved_Text\get_term_translations(
            (string)$get_req["term_lc"], (int)$get_req["text_id"]
        )
    );
}


/**
 * Error message when the provided action_type does not match anything known.
 */
function unknown_get_action_type($get_req)
{
    $message = 'Action type of type "' . $get_req["action_type"] . 
    '" with action "' . $get_req["action"] . '" does not exist!';
    return json_encode(array("error" => $message)); 
}

// --------------------------------- POST REQUESTS ---------------------


/**
 * Set text reading position.
 * 
 * @param array $post_req Array with the fields "tid" (int) and "tposition"
 * 
 * @return void
 */
function set_text_position($post_req) 
{
    return json_encode(array("text" => save_text_position(
        (int)$post_req["tid"], (int)$post_req["tposition"]
    )));
}

/**
 * Set audio position.
 * 
 * @param array $post_req Array with the fields "tid" (int) and "audio_position"
 * 
 * @return void
 */
function set_audio_position($post_req) 
{
    return json_encode(
        array(
            "audio" => save_audio_position(
                (int)$post_req["tid"], (int)$post_req["audio_position"]
            )
        )
    );
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
    return json_encode(array("similar_terms" => print_similar_terms(
        (int)$post_req["simterms_lgid"], 
        (string) $post_req["simterms_word"]
    )));
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
    $text = trim($post_req['text']);
    $result = add_new_term_transl(
        $text, (int)$post_req['lang'], trim($post_req['translation'])
    );
    $raw_answer = array();
    if ($result == mb_strtolower($text, 'UTF-8')) {
        $raw_answer["add"] = $result;
    } else {
        $raw_answer["error"] = $result;
    }
    return json_encode($raw_answer);
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
    $result = do_ajax_check_update_translation(
        (int)$post_req['id'], trim($post_req['translation'])
    );
    $raw_answer = array();
    if ($result == "") {
        $raw_answer["error"] = $result;
    } else {
        $raw_answer["update"] = $result;
    }
    return json_encode($raw_answer);
}

/**
 * Check if a regexp is correctly recognized.
 * 
 * @param array $post_req Array with the field "regexp"
 */
function check_regexp($post_req)
{
    $result = do_ajax_check_regexp(trim($post_req['regexp'])); 
    return json_encode(array("check_regexp" => $result));
}

/**
 * Change the status of a term by one unit.
 * 
 * @param array $post_req Array with the fields "wid" (int) and "status_up" (1 or 0)
 */
function increment_term_status($post_req)
{
    $result = ajax_increment_term_status(
        (int)$post_req['wid'], (bool)$post_req['status_up']
    );
    $raw_answer = array();
    if ($result == '') {
        $raw_answer["error"] = '';
    } else {
        $raw_answer["increment"] = $result;
    }
    return json_encode($raw_answer);
}

/**
 * Set the status of a term.
 * 
 * @param array $post_req Array with the fields "wid" (int) and "status" (0-5|98|99)
 */
function set_term_status($post_req)
{
    $result = set_word_status((int)$post_req['wid'], (int)$post_req['status']);
    $raw_answer = array();
    if (is_numeric($result)) {
        $raw_answer["set"] = (int)$result;
    } else {
        $raw_answer["error"] = $result;
    }
    return json_encode($raw_answer);
}

/**
 * Save the annotation for a term.
 * 
 * @param array $post_req Post request with keys "tid", "elem" and "data".
 * 
 * @return string JSON-encoded result
 */
function set_annotation($post_req)
{
    $result = save_impr_text(
        (int)$post_req["tid"], $post_req['elem'], 
        json_decode($post_req['data'])
    );
    $raw_answer = array();
    if (array_key_exists("error", $result)) {
        $raw_answer["error"] = $result["error"];
    } else {
        $raw_answer["save_impr_text"] = $result["success"];
    }
    return json_encode($raw_answer);
}


/**
 * Save a setting to the database.
 * 
 * @param array $post_req Array with the fields "k" (key, setting name) and "v" (value)
 * 
 * @return void
 */
function save_setting($post_req) 
{
    $status = saveSetting($post_req['k'], $post_req['v']);
    $raw_answer = array();
    if (str_starts_with($status, "OK: ")) {
        $raw_answer["save_setting"] = substr($status, 4);
    } else {
        $raw_answer["error"] = $status;
    }
    return json_encode($raw_answer);
}

/**
 * Notify of an error on POST method.
 */
function unknown_post_action_type($post_req, $action_exists=false)
{
    if ($action_exists) {
        return 'action_type of type "' . $post_req["action_type"] . 
        '" with action "' . $post_req["action"] . '" does not exist!'; 
    }
    return 'action_type of type "' . $post_req["action_type"] . 
    '" with default action (' . $post_req["action"] . ') does not exist'; 
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
        case 'phonetic_reading':
            echo get_phonetic_reading($_GET);
            break;
        case 'theme_path':
            echo get_theme_path($_GET);
            break;
        case "texts_statistics":
            echo get_texts_statistics($_GET);
            break;
        case "media_paths":
            echo media_paths($_GET);
            break;
        case "example_sentences":
            echo example_sentences($_GET);
            break;
        case "imported_terms":
            echo imported_terms($_GET);
            break;
        case "term_translations":
            echo term_translations($_GET);
            break;
        default:
            echo unknown_get_action_type($_GET);
            break;
        }
    }
} else if (isset($_POST['action'])) {
    switch ($_POST['action']) {
    case "reading_position":
        switch ($_POST['action_type']) {
        case "text":
            echo set_text_position($_POST);
            break;
        case "audio":
            echo set_audio_position($_POST);
            break;
        default:
            echo unknown_post_action_type($_POST, true);
            break;
        }
        break;
    case "change_translation":
        switch ($_POST['action_type']) {
            case "add":
                echo add_translation($_POST);
                break;
            case "update":
                echo update_translation($_POST);
                break;
            default:
                echo unknown_post_action_type($_POST, true);
                break;
        }
        break;
    case "term_status":
        switch ($_POST['action_type']) {
            case "increment":
                echo increment_term_status($_POST);
                break;
            case "set":
                echo set_term_status($_POST);
                break;
            default:
                echo unknown_post_action_type($_POST, true);
                break;
        }
        break;
    default:
        switch ($_POST['action_type']) {
        case "similar_terms":
            echo similar_terms($_POST); // 2.9.0: really on POST?
            break;
        case 'check_regexp':
            echo check_regexp($_POST);
            break;
        case 'set_annotation':
            echo set_annotation($_POST);
            break;
        case 'save_setting':
            echo save_setting($_POST);
            break;
        default:
            echo unknown_post_action_type($_POST);
            break;
        }
        break;
    }
}

?>
