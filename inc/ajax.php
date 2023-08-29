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
 * Get the phonetic reading of a word based on it's language.
 * 
 * @param array $get_req Array with the fields "text" and "lang" (short language name)
 */
function get_phonetic_reading($get_req)
{
    return phonetic_reading($get_req['text'], $get_req['lang']);
}

    
/**
 * Get the file path using theme.
 * 
 * @param array $get_req Get request with field "path", relative filepath using theme.
 */
function get_theme_path($get_req)
{
    chdir('..');
    return get_file_path($get_req['path']);
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
    return json_encode(sentences_with_word(
        (int) $get_req["lid"],
        $get_req["word_lc"],
        (int) $get_req["wid"]
    ));
}

/**
 * Return the list of imported terms.
 * 
 * @param array $get_req Get request with fields "last_update", "page" and "count".
 */
function imported_terms($get_req)
{
    return json_encode(imported_terms_list(
        $get_req["last_update"], $get_req["page"], $get_req["count"]
    ));
}


/**
 * Error message when the provided action_type does not match anything known.
 */
function unknown_get_action_type($get_req)
{
    return 'Action type of type "' . $get_req["action_type"] . '" with action "' . $get_req["action"] . '" does not exist!'; 
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
    return save_text_position(
        (int)$post_req["tid"], (int)$post_req["tposition"]
    );
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
    return save_audio_position(
        (int)$post_req["tid"], (int)$post_req["audio_position"]
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

/**
 * Check if a regexp is correctly recognized.
 * 
 * @param array $post_req Array with the field "regexp"
 */
function check_regexp($post_req)
{
    return do_ajax_check_regexp(trim($post_req['regexp']));
}

/**
 * Change the status of a term by one unit.
 * 
 * @param array $post_req Array with the fields "wid" (int) and "status_up" (1 or 0)
 */
function increase_term_status($post_req)
{
    return do_ajax_chg_term_status((int)$post_req['wid'], (bool)$post_req['status_up']);
}

/**
 * Set the status of a term.
 * 
 * @param array $post_req Array with the fields "wid" (int) and "status" (0-5|98|99)
 */
function set_term_status($post_req)
{
    return set_word_status((int)$post_req['wid'], (int)$post_req['status']);
}


function set_impr_text($post_req)
{
    return json_encode(
        save_impr_text(
            (int)$post_req["tid"], $post_req['elem'], 
            json_decode($post_req['data'])
        )
    );
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
    chdir('..');

    saveSetting($post_req['k'], $post_req['v']);
}

/**
 * Notify of an error on POST method.
 */
function unknown_post_action_type($post_req)
{
    return 'Action type of type "' . $post_req["action_type"] . '" with action "' . $post_req["action"] . '" does not exist!'; 
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
                    echo unknown_post_action_type($_POST);
                    break;
            }
            break;
        default:
            switch ($_POST['action_type']) {
                case "simterms":
                    echo similar_terms($_POST); // really on POST?
                    break;
                case "add_translation":
                    echo add_translation($_POST);
                    break;
                case "update_translation":
                    echo update_translation($_POST);
                    break;
                case 'regexp':
                    echo check_regexp($_POST);
                    break;
                case 'increase_term_status':
                    echo increase_term_status($_POST);
                    break;
                case 'set_term_status':
                    echo set_term_status($_POST);
                    break;
                case 'save_impr_text':
                    echo set_impr_text($_POST);
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
