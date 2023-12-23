<?php

namespace Lwt\Ajax;

require_once __DIR__ . '/inc/session_utility.php';
require_once __DIR__ . '/inc/simterms.php';
require_once 'do_test_test.php';
require_once __DIR__ . '/inc/ajax_add_term_transl.php';
require_once __DIR__ . '/inc/ajax_check_regexp.php';
require_once __DIR__ . '/inc/ajax_chg_term_status.php';
require_once __DIR__ . '/inc/ajax_save_impr_text.php';
require_once __DIR__ . '/inc/ajax_save_text_position.php';
require_once __DIR__ . '/inc/ajax_show_imported_terms.php';
require_once __DIR__ . '/inc/ajax_edit_impr_text.php';


/**
 * Send JSON response
 */
function send_response($status = 200, $data = null) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function endpoint_exits($method, $requestUri) {
    // Set up API endpoints
    $endpoints = [ 
        'media-files' => [ 'GET' ],

        'phonetic-reading' => [ 'GET' ],

        'review/next-word' => [ 'GET' ],
        'review/tomorrow-count' => [ 'GET' ],

        'sentences-with-term' => [ 'GET' ],
        //'sentences-with-term/(?<term-id>\d+)' => [ 'GET' ],

        'similar-terms' => [ 'GET' ],

        'settings' => [ 'POST' ],
        'settings/theme-path' => [ 'GET' ],

        'terms' => [ 'GET', 'POST' ],
        'terms/imported' => [ 'GET' ],
        'terms/new' => [ 'POST' ],

        //'terms/(?<term-id>\d+)/translations' => [ 'GET', 'POST' ],

        //'terms/(?<term-id>\d+)/status/down' => [ 'POST' ],
        //'terms/(?<term-id>\d+)/status/up' => [ 'POST' ],
        //'terms/(?<term-id>\d+)/status/(?<new-status>\d+)' => [ 'POST' ],

        'texts' => [ 'POST' ],
        
        //'texts/(?<text-id>\d+)/annotation' => [ 'POST' ],
        //'texts/(?<text-id>\d+)/audio-position' => [ 'POST' ],
        //'texts/(?<text-id>\d+)/reading-position' => [ 'POST' ],

        'texts/statistics' => [ 'GET' ],

        'version' => [ 'GET' ], 

        // 'regexp/test' => [ 'POST' ], as of LWT 2.9.0, no usage was found
    ];


    // Extract requested endpoint from URI
    $uri_query = parse_url($requestUri, PHP_URL_PATH);
    $matching = preg_match('/(.+?\/api.php\/v\d\/).+/', $uri_query, $matches);
    if (!$matching) {
        send_response(400, ['error' => 'Unrecognized URL format ' . $uri_query]);
    }
    if (count($matches) == 0) {
        send_response(404, ['error' => 'Wrong API Location: ' . $uri_query]);
    }
    // endpoint without prepending URL, like 'version'
    $req_endpoint = rtrim(str_replace($matches[1], '', $uri_query), '/');
    $methods_allowed = null; 
    if (array_key_exists($req_endpoint, $endpoints)) {
        $methods_allowed = $endpoints[$req_endpoint];
    } else { 
        $first_elem = preg_split('/\//', $req_endpoint)[0];
        if (array_key_exists($first_elem, $endpoints)) {
            $methods_allowed = $endpoints[$first_elem];
        } else {
            send_response(404, ['error' => 'Endpoint Not Found: ' . $req_endpoint]);
        }
    }

    // Validate request method for the req_endpoint
    if (!in_array($method, $methods_allowed)) {
        send_response(405, ['error' => 'Method Not Allowed']);
    }
    return $req_endpoint;
}


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
    return array(
        "version"      => "0.0.1",
        "release_date" => "2023-09-01"
    );
}

/**
 * List the audio and video files in the media folder.
 * 
 * @param array $get_req Unnused
 * 
 * @return string[] Path of media files
 */
function media_files($get_req) 
{
    return get_media_paths();
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
    return array("phonetic_reading" => $data);
}


/**
 * Retun the next word to test as JSON
 * 
 * @param string $testsql   SQL projection query
 * @param bool   $word_mode    Test is in word mode
 * @param int    $lgid      Language ID
 * @param string $wordregex Word selection regular expression
 * @param int    $testtype  Test type
 * 
 * @return array Next word formatted as an array.
 */
function get_word_test_ajax($testsql, $word_mode, $lgid, $wordregex, $testtype)
{
    $word_record = do_test_get_word($testsql);
    if (empty($word_record)) {
        $output = array(
            "word_id" => 0,
            "word_text" => '',
            "group" => '' 
        );
        return $output;
    }
    $sent = repl_tab_nl($word_record['WoSentence']);
    if ($word_mode) {
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
        $word_record, $sent, $testtype, $word_mode, $wordregex
    );
    $solution = get_test_solution($testtype, $word_record, $word_mode, $save);
    
    return array(
        "word_id" => $word_record['WoID'],
        "solution" => $solution,
        "word_text" => $save,
        "group" => $r 
    );
}


/**
 * Return the next word to test.
 * 
 * @param array $get_req Array with the fields {test_sql: string, 
 *                       word_mode: bool, lg_id: int, 
 *                       word_regex: string, type: int}
 * 
 * @return string Next word formatted as JSON.
 */
function word_test_ajax($get_req)
{
    return get_word_test_ajax(
        $get_req['test_sql'], $get_req['word_mode'], 
        $get_req['lg_id'], 
        $get_req['word_regex'], $get_req['type']
    );
}

/**
 * Return the number of reviews for tomorrow by using the suplied query.
 * 
 * @param array $get_req Array with the field "test_sql"
 * 
 * @return string JSON-encoded result
 */
function tomorrow_test_count($get_req) 
{
    $output = array(
        "count" => do_test_get_tomorrow_tests_count($get_req['test_sql'])
    );
    return $output;
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
    return array("theme_path" => get_file_path($get_req['path']));
}

/**
 * Return statistics about a group of text.
 * 
 * @param array $get_req Get request with field "texts_id", texts ID.
 */
function get_texts_statistics($get_req)
{
    return return_textwordcount($get_req["texts_id"]);
}

/**
 * Sentences containing an input word.
 * 
 * @param array $get_req Get request with fields "lg_id", "word_lc" and "word_id".
 */
function sentences_with_registred_term($get_req)
{
    return sentences_with_word(
        (int) $get_req["lg_id"],
        $get_req["word_lc"],
        (int) $get_req["word_id"]
    );
}

/**
 * Return the example sentences containing an input word.
 * 
 * @param array $get_req Get request with fields "lg_id" and "advanced_search" (optional).
 */
function sentences_with_new_term($get_req)
{
    $advanced = null;
    if (array_key_exists("advanced_search", $get_req)) {
        $advanced = -1;
    }
    return sentences_with_word(
        (int) $get_req["lg_id"],
        $get_req["word_lc"],
        $advanced
    );
}

/**
 * Get terms similar to a given term.
 * 
 * @param array $get_req Get request with fields "lg_id" and "term".
 * 
 * @return string Similar terms in HTML format.
 */
function similar_terms($get_req) 
{
    return array("similar_terms" => print_similar_terms(
        (int)$get_req["lg_id"], 
        (string) $get_req["term"]
    ));
}

/**
 * Return the list of imported terms.
 * 
 * @param array $get_req Get request with fields "last_update", "page" and "count".
 * 
 * @return array 
 */
function imported_terms($get_req)
{
    return imported_terms_list(
        $get_req["last_update"], $get_req["page"], $get_req["count"]
    );
}



/**
 * Translations for a term to choose an annotation.
 * 
 * @param array $get_req Get request with fields "text_id" and "term_lc".
 */
function term_translations($get_req)
{
    return \Lwt\Ajax\Improved_Text\get_term_translations(
        (string)$get_req["term_lc"], (int)$get_req["text_id"]
    );
}


/**
 * Error message when the provided action_type does not match anything known.
 * 
 * @param array $post_req GET request used
 * @param bool  $action_exists Set to true if the action is recognized but not 
 * the action_type
 * 
 * @return string JSON-encoded error message.
 */
function unknown_get_action_type($get_req, $action_exists=false)
{
    if ($action_exists) {
        $message = 'action_type with value "' . $get_req["action_type"] . 
        '" with action "' . $get_req["action"] . '" does not exist!';
    } else {
        $message = 'action_type with value "' . $get_req["action_type"] . 
        '" with default action (' . $get_req["action"] . ') does not exist'; 
    }
    return array("error" => $message); 
}

// --------------------------------- POST REQUESTS ---------------------

/**
 * Save a setting to the database.
 * 
 * @param array $post_req Array with the fields "key" (setting name) and "value"
 * 
 * @return string[] Setting save status
 */
function save_setting($post_req): array
{
    $status = saveSetting($post_req['key'], $post_req['value']);
    $raw_answer = array();
    if (str_starts_with($status, "OK: ")) {
        $raw_answer["message"] = substr($status, 4);
    } else {
        $raw_answer["error"] = $status;
    }
    return $raw_answer;
}

/**
 * Save the annotation for a term.
 * 
 * @param array $post_req Post request with keys "text_id", "elem" and "data".
 * 
 * @return string JSON-encoded result
 */
function set_annotation($post_req)
{
    $result = save_impr_text(
        (int)$post_req["text_id"], $post_req['elem'], 
        json_decode($post_req['data'])
    );
    $raw_answer = array();
    if (array_key_exists("error", $result)) {
        $raw_answer["error"] = $result["error"];
    } else {
        $raw_answer["save_impr_text"] = $result["success"];
    }
    return $raw_answer;
}

/**
 * Set audio position.
 * 
 * @param array $post_req Array with the fields "text_id" (int) and "position"
 * 
 * @return string
 */
function set_audio_position($post_req) 
{
    save_audio_position(
        (int)$post_req["text_id"], (int)$post_req["position"]
    );
    return array(
        "audio" => "Audio position set"
    );
}

/**
 * Set text reading position.
 * 
 * @param array $post_req Array with the fields "text_id" (int) and "position"
 * 
 * @return string
 */
function set_text_position($post_req) 
{
    save_text_position(
        (int)$post_req["text_id"], (int)$post_req["position"]
    );
    return array("text" => "Reading position set");
}


/**
 * Change the status of a term by one unit.
 * 
 * @param array $post_req Array with the fields "term_id" (int) and "status_up" (1 or 0)
 */
function increment_term_status($post_req)
{
    $result = ajax_increment_term_status(
        (int)$post_req['term_id'], (bool)$post_req['status_up']
    );
    $raw_answer = array();
    if ($result == '') {
        $raw_answer["error"] = '';
    } else {
        $raw_answer["increment"] = $result;
    }
    return $raw_answer;
}


/**
 * Set the status of a term.
 * 
 * @param array $post_req Array with the fields "term_id" (int) and "status" (0-5|98|99)
 */
function set_term_status($post_req)
{
    $result = set_word_status((int)$post_req['term_id'], (int)$post_req['status']);
    $raw_answer = array();
    if (is_numeric($result)) {
        $raw_answer["set"] = (int)$result;
    } else {
        $raw_answer["error"] = $result;
    }
    return $raw_answer;
}


/**
 * Edit the translation of an existing term.
 * 
 * @param array $post_req Array with the fields "term_id" (int) and "translation".
 * 
 * @return string Term in lower case, or "" if term does not exist
 */
function update_translation($post_req)
{
    $result = do_ajax_check_update_translation(
        (int)$post_req['term_id'], trim($post_req['translation'])
    );
    $raw_answer = array();
    if (str_starts_with($result, "Error")) {
        $raw_answer["error"] = $result;
    } else {
        $raw_answer["update"] = $result;
    }
    return $raw_answer;
}

/**
 * Create the translation for a new term.
 * 
 * @param array $post_req Array with the fields "term_text", "lg_id" (int) and "translation".
 * 
 * @return string Error message in case of failure, lowercase term otherwise
 */
function add_translation($post_req)
{
    $text = trim($post_req['term_text']);
    $result = add_new_term_transl(
        $text, (int)$post_req['lg_id'], trim($post_req['translation'])
    );
    $raw_answer = array();
    if (is_array($result)) {
        $raw_answer["term_id"] = $result[0];
        $raw_answer["term_lc"] = $result[1];
    } else if ($result == mb_strtolower($text, 'UTF-8')) {
        $raw_answer["add"] = $result;
    } else {
        $raw_answer["error"] = $result;
    }
    return $raw_answer;
}

/**
 * Notify of an error on POST method.
 * 
 * @param array $post_req POST request used
 * @param bool  $action_exists Set to true if the action is recognized but not 
 * the action_type
 * 
 * @return string JSON-encoded error message
 */
function unknown_post_action_type($post_req, $action_exists=false)
{
    if ($action_exists) {
        $message = 'action_type with value "' . $post_req["action_type"] . 
        '" with action "' . $post_req["action"] . '" does not exist!'; 
    } else {
        $message = 'action_type with value "' . $post_req["action_type"] . 
        '" with default action (' . $post_req["action"] . ') does not exist'; 
    }
    return array("error" => $message); 
}


function main_enpoint($method, $requestUri) {
    // Extract requested endpoint from URI
    $req_endpoint = endpoint_exits($method, $requestUri);
    parse_str(parse_url($requestUri, PHP_URL_QUERY), $req_param);
    $endpoint_fragments = preg_split("/\//", $req_endpoint);

    // Process endpoint request
    if ($method === 'GET') {
        // Handle GET request for each endpoint
        switch ($endpoint_fragments[0]) {
            case 'media-files':
                $answer = media_files($req_param);
                send_response(200, $answer);
                break;
            case 'phonetic-reading':
                $answer = get_phonetic_reading($req_param);
                send_response(200, $answer);
                break; 
            case 'review':
                switch ($endpoint_fragments[1]) {
                    case 'next-word':
                        $answer = word_test_ajax($req_param);
                        send_response(200, $answer);
                        break;
                    case 'tomorrow-count':
                        $answer = tomorrow_test_count($req_param);
                        send_response(200, $answer);
                        break;
                    default:
                        send_response(
                            404, 
                            ['error' => 'Endpoint Not Found' . 
                            $endpoint_fragments[1]]
                        );
                }
                break;
            case 'sentences-with-term':
                if (ctype_digit($endpoint_fragments[1])) {
                    $get_req['word_id'] = (int) $endpoint_fragments[1];
                    $answer = sentences_with_registred_term($req_param);
                } else {
                    $answer = sentences_with_new_term($req_param);
                }
                send_response(200, $answer);
                break;
            case 'similar-terms':
                $answer = similar_terms($req_param);
                send_response(200, $answer);
                break;
            case 'settings':
                switch ($endpoint_fragments[1]) {
                    case 'theme-path':
                        $answer = get_theme_path($req_param);
                        send_response(200, $answer);
                        break;
                    default:
                        send_response(
                            404, 
                            ['error' => 'Endpoint Not Found: ' . 
                            $endpoint_fragments[1]]
                        );
                }
                break;
            case 'terms':
                if ($endpoint_fragments[1] == "imported") {
                    $answer = imported_terms($req_param);
                    send_response(200, $answer);
                } else if (ctype_digit($endpoint_fragments[1])) {
                    if ($endpoint_fragments[2] == 'translations') {
                        $req_param['term_id'] = $endpoint_fragments[1];
                        $answer = term_translations($req_param);
                        send_response(200, $answer);
                    } else {
                        send_response(
                            404, 
                            ['error' => 'Expected "translation", Got ' . 
                            $endpoint_fragments[2]]
                        );
                    }
                } else {
                    send_response(
                        404, 
                        ['error' => 'Endpoint Not Found' . 
                        $endpoint_fragments[1]]
                    );
                }
                break;
            case 'texts':
                if ($endpoint_fragments[1] == 'statistics') {
                    $answer = get_texts_statistics($req_param);
                    send_response(200, $answer);
                } else {
                    send_response(
                        404, 
                        ['error' => 'Expected "statistics", Got ' . 
                        $endpoint_fragments[1]]
                    );
                }
            case 'version':
                $answer = rest_api_version($req_param);
                send_response(200, $answer);
                break;
            // Add more GET handlers for other endpoints
            default:
                send_response(
                    404, 
                    ['error' => 'Endpoint Not Found: ' . 
                    $endpoint_fragments[0]]
                );
        }
    } elseif ($method === 'POST') {
        // Handle POST request for each endpoint
        switch ($endpoint_fragments[0]) {
            case 'settings':
                $answer = save_setting($_POST);
                send_response(200, $answer);
                break;
            case 'texts':
                if (!ctype_digit($endpoint_fragments[1])) {
                    send_response(
                        404, 
                        ['error' => 'Text ID (Integer) Expected, Got ' . 
                        $endpoint_fragments[1]]
                    );
                }
                $_POST["text_id"] = (int) $endpoint_fragments[1];
                switch ($endpoint_fragments[2]) {
                    case 'annotation':
                        $answer = set_annotation($_POST);
                        send_response(200, $answer);
                        break;
                    case 'audio-position':
                        $answer = set_audio_position($_POST);
                        send_response(200, $answer);
                        break;
                    case 'reading-position':
                        $answer = set_text_position($_POST);
                        send_response(200, $answer);
                        break;
                    default:
                        send_response(
                            404, 
                            ['error' => 'Endpoint Not Found: ' . 
                            $endpoint_fragments[2]]
                        );
                }
                break;
            case 'terms':
                if (ctype_digit($endpoint_fragments[1])) {
                    $_POST['term_id'] = (int) $endpoint_fragments[1];
                    if ($endpoint_fragments[2] == "status") {
                        if ($endpoint_fragments[3] == 'down') {
                            $_POST['status_up'] = 0;
                            $answer = increment_term_status($_POST);
                            send_response(200, $answer);
                        } else if ($endpoint_fragments[3] == 'up') {
                            $_POST['status_up'] = 1;
                            $answer = increment_term_status($_POST);
                            send_response(200, $answer);
                        } else if (ctype_digit($endpoint_fragments[3])) {
                            $_POST['status'] = (int) $endpoint_fragments[3];
                            $answer = set_term_status($_POST);
                            send_response(200, $answer);
                        } else {
                            send_response(
                                404, 
                                ['error' => 'Endpoint Not Found: ' . 
                                $endpoint_fragments[3]]
                            );
                        }
                    } else if ($endpoint_fragments[2] == 'translations') {
                        $answer = update_translation($_POST);
                        send_response(200, $answer);
                    } else {
                        send_response(
                            404, 
                            [
                                'error' => 
                                '"status" or "translations"' . 
                                ' Expected, Got ' . $endpoint_fragments[2]
                            ]
                        );
                    }
                } else if ($endpoint_fragments[1] == 'new') {
                    $answer = add_translation($_POST);
                    send_response(200, $answer);
                } else {
                    send_response(
                        404, 
                        [
                            'error' => 
                            'Term ID (Integer) or "new" Expected,' . 
                            ' Got ' . $endpoint_fragments[1]
                        ]
                    );
                }
                break;
            default:
                send_response(
                    404, 
                    ['error' => 'Endpoint Not Found On POST: ' . 
                    $endpoint_fragments[0]]
                );
        }
    }
}


// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_response(405, ['error' => 'Method Not Allowed']);
} else {
    main_enpoint($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
}

?>
