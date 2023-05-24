<?php

/**
 * \file
 * \brief Show test frame
 * 
 * Call: do_test_test.php?type=[testtype]&lang=[langid]
 * Call: do_test_test.php?type=[testtype]&text=[textid]
 * Call: do_test_test.php?type=[testtype]&selection=1  
 *          (SQL via $_SESSION['testsql'])
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/do__test__test_8php.html
 * @since   1.0.3
 */

require_once 'inc/session_utility.php';
require_once 'inc/langdefs.php';


/**
 * Get the SQL string to perform tests.
 * 
 * @param bool|null   $selection    Test is of type selection
 * @param string|null $sess_testsql SQL string for test
 * @param int|null    $lang Test is of type language, for the language $lang ID
 * @param int|null    $text Testing text with ID $text
 * 
 * @return string SQL projection (selection) string
 */
function do_test_get_test_sql($selection, $sess_testsql, $lang, $text)
{
    if (isset($selection) && isset($sess_testsql)) {
        $testsql = do_test_test_from_selection($selection, $sess_testsql);
    } else if (isset($lang) && is_numeric($lang)) {
        $testsql = do_test_test_get_projection(2, (int)$lang);
    } else if (isset($text) && is_numeric($text)) {
        $testsql = do_test_test_get_projection(3, (int)$text);
    } else {
        my_die("do_test_test.php called with wrong parameters"); 
    }
    return $testsql;

}

/**
 * Get the test type clamped between 1 and 5 (included)
 * 
 * @param int $testype Initial test type value
 * 
 * @return int Clamped $testtype
 */
function do_test_get_test_type($testtype)
{
    if ($testtype < 1) { 
        $testtype = 1; 
    }
    if ($testtype > 5) { 
        $testtype = 5; 
    }
    return $testtype;
}

/**
 * Set sql request for the word test.
 * 
 * @return string SQL request string
 * 
 * @global string $tbpref 
 * 
 * @deprecated 2.9.0-fork Use do_test_get_sql instead
 */
function get_test_sql()
{
    return do_test_get_test_sql(
        $_REQUEST['selection'], $_SESSION['testsql'], 
        $_REQUEST['lang'], $_REQUEST['text']
    );
}

/**
 * Give the test type.
 * 
 * @return int<1, 5> Test type between 1 and 5 (included)
 * 
 * @deprecated 2.9.0-fork Use do_test_get_test_type instead.
 */
function get_test_type()
{
    return do_test_get_test_type((int)getreq('type'));
}

/**
 * Prepare the css code for tests.
 * 
 * @deprecated 2.6.0-fork Do not use this function since it was causing wrong 
 *                        display. Will be removed in 3.0.0. 
 * 
 * @return void
 */
function do_test_test_css()
{
    ?>
<style type="text/css">
    html, body {
        width:100%; 
        height:100%; 
    } 

</style>
    <?php
}

/**
 * Return the number of test due for tomorrow.
 * 
 * @param string $testsql Test selection string
 * 
 * @return int tomorrow tests
 */
function do_test_get_tomorrow_tests_count($testsql)
{
    return get_first_value(
        "SELECT COUNT(DISTINCT WoID) AS value 
        FROM $testsql AND WoStatus BETWEEN 1 AND 5 
        AND WoTranslation != '' AND WoTranslation != '*' AND WoTomorrowScore < 0"
    );
}

/**
 * Output a message for a finished test, with the number of tests for tomorrow.
 * 
 * @param string $testsql    Query used to select words.
 * @param int    $totaltests Total number of tests.
 * @param bool   $ajax       AJAX mode, content will not be displayed.
 * 
 * @return void
 */
function do_test_test_finished($testsql, $totaltests, $ajax=false)
{
    $tomorrow_tests = do_test_get_tomorrow_tests_count($testsql);
    echo '<p id="test-finished-area" class="center" style="display: ' . 
    ($ajax ? 'none' : 'inherit') . ';">
            <img src="img/ok.png" alt="Done!" />
            <br /><br />
            <span class="red2">
                <span id="tests-done-today">
                    Nothing ' . ($totaltests ? 'more ' : '') . 'to test here!
                </span>
                <br /><br />
                <span id="tests-tomorrow"">
                    Tomorrow you\'ll find here ' . $tomorrow_tests . ' test' . 
                    ($tomorrow_tests == 1 ? '' : 's') . '!
                </span>
            </span>
        </p>
    </div>';
}

/**
 * Get a sentence containing the word. 
 * 
 * The sentence should contain at least 70% of known words.
 * 
 * @param int    $wid    The word to test.
 * @param string $lang   ID of the language
 * @param string $wordlc 
 * 
 * @global string $tbpref Table prefix
 * @global int    $debug  Echo the passage number if 1. 
 * 
 * @return array{0: string|null, 1: int} Sentence with escaped word and not a 0 
 *                                       if sentence was found.
 * 
 * @since 2.5.3-fork Properly return sentences with at least 70% of known words.
 *                   Previously, it was supposed to be 100%, but buggy. 
 */
function do_test_test_sentence($wid, $lang, $wordlc)
{
    global $debug, $tbpref;
    $num = 0;
    $sent = null;

    // Select sentences where at least 70 % of words are known
    $sql = "SELECT DISTINCT ti.Ti2SeID AS SeID
    FROM {$tbpref}textitems2 ti
    JOIN (
      SELECT t.Ti2SeID, COUNT(*) AS c
      FROM {$tbpref}textitems2 t
      WHERE t.Ti2WordCount = 1
      GROUP BY t.Ti2SeID
    ) AS sWordCount ON sWordCount.Ti2SeID = ti.Ti2SeID
    LEFT JOIN (
      SELECT t.Ti2SeID, COUNT(*) AS c
      FROM {$tbpref}textitems2 t
      WHERE t.Ti2WordCount = 1 AND t.Ti2WoID = 0
      GROUP BY t.Ti2SeID
    ) AS sUnknownCount ON sUnknownCount.Ti2SeID = ti.Ti2SeID
    WHERE ti.Ti2WoID = $wid
    AND IFNULL(sUnknownCount.c, 0) / sWordCount.c < 0.3
    ORDER BY RAND() LIMIT 1";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    // If sentence found
    if ($record) {
        $num = 1;
        $seid = $record['SeID'];
        list($_, $sent) = getSentence(
            $seid, $wordlc, (int)getSettingWithDefault('set-test-sentence-count')
        );
        if ($debug) { 
            echo "DEBUG sent: $seid OK: $sent <br />"; 
        }
    } else {
        if ($debug) { 
            echo "DEBUG no random sent found<br />"; 
        }
    }
    mysqli_free_result($res);
        
    return array($sent, $num);
}

/**
 * Return the test relative to a word.
 * 
 * @param array  $wo_record Query from the database regarding a word.
 * @param string $sent      Sentence containing the word.
 * @param int    $testtype  Type of test
 * @param int    $nosent    1 if you want to hide sentences.
 * @param string $regexword Regex to select the desired word.
 * 
 * @return array{0: string, 1: string} HTML-escaped and raw text sentences (or word)
 */
function do_test_get_term_test($wo_record, $sent, $testtype, $nosent, $regexword)
{
    $wid = $wo_record['WoID'];
    $word = $wo_record['WoText'];
    $trans = repl_tab_nl($wo_record['WoTranslation']) . 
    getWordTagList($wid, ' ', 1, 0);
    $roman = $wo_record['WoRomanization'];
    $status = $wo_record['WoStatus'];

    $cleansent = trim(str_replace("{", '', str_replace("}", '', $sent)));
    $l = mb_strlen($sent, 'utf-8');
    $r = '';
    $save = '';
    $on_word = false;

    $preppend = ' <span style="word-break:normal;" ' . 
    'class="click todo todosty word wsty word' 
    . $wid . 
    '" data_wid="' . $wid . '" data_trans="' . tohtml($trans) . 
    '" data_text="' . tohtml($word) . '" data_rom="' . tohtml($roman) . 
    '" data_sent="' . tohtml($cleansent) . '" data_status="' . $status . 
    '" data_todo="1"';
    if ($testtype ==3) { 
        $preppend .= ' title="' . tohtml($trans) . '"'; 
    }
    $preppend .= '>';
    if ($testtype == 2) {
        // Show translation
        if ($nosent) { 
            $preppend .= tohtml($trans); 
        } else { 
            $preppend .= '<span dir="ltr">[' . tohtml($trans) . ']</span>'; 
        }
    }

    // Go through sentence characters
    for ($i = 0; $i < $l; $i++) {  
        $c = mb_substr($sent, $i, 1, 'UTF-8');
        if ($c == '}') {
            $r .= $preppend;
            if ($testtype == 3) {
                // Show word in original language in sentence
                $sentence = mask_term_in_sentence('{' . $save . '}', $regexword);
                $sentence = str_replace("{", '[', str_replace("}", ']', $sentence));
                $r .= tohtml($sentence);
            } elseif ($testtype != 2) {
                // Show word in original language, alone
                $r .= tohtml($save); 
            }
            $r .= '</span> ';
            $on_word = false;
        } elseif ($c == '{') {
            $on_word = true;
            $save = '';
        } else {
            if ($on_word) { 
                $save .= $c; 
            } else { 
                $r .= tohtml($c); 
            }
        }
    }
    return array($r, $save);
}



/**
 * Echo the test relative to a word.
 * 
 * @param array  $wo_record Query from the database regarding a word.
 * @param string $sent      Sentence containing the word.
 * @param int    $testtype  Type of test
 * @param int    $nosent    1 if you want to hide sentences.
 * @param string $regexword Regex to select the desired word.
 * 
 * @return array{0: string, 1: string} HTML-escaped and raw text sentences (or word)
 */
function print_term_test($wo_record, $sent, $testtype, $nosent, $regexword)
{
    list($word, $_) =  do_test_get_term_test(
        $wo_record, $sent, $testtype, $nosent, $regexword
    );
    return $word;
}

/**
 * Find the next word to test.
 * 
 * @param string $testsql Test selection string
 * 
 * @return array 
 */
function do_test_get_word($testsql)
{
    $pass = 0;
    while ($pass < 2) {
        $pass++;
        $sql = "SELECT DISTINCT WoID, WoText, WoTextLC, WoTranslation, 
        WoRomanization, WoSentence, WoLgID, 
        (IFNULL(WoSentence, '') NOT LIKE CONCAT('%{', WoText, '}%')) AS notvalid, 
        WoStatus, 
        DATEDIFF( NOW( ), WoStatusChanged ) AS Days, WoTodayScore AS Score 
        FROM $testsql AND WoStatus BETWEEN 1 AND 5 
        AND WoTranslation != '' AND WoTranslation != '*' AND WoTodayScore < 0 " . 
        ($pass == 1 ? 'AND WoRandom > RAND()' : '') . ' 
        ORDER BY WoTodayScore, WoRandom 
        LIMIT 1';
        $res = do_mysqli_query($sql);
        $record = mysqli_fetch_assoc($res);
        if ($record) {
            return $record;
        }
        mysqli_free_result($res);
    }
    return array();
}


/**
 * Get the solution to a test.
 * 
 * @param int $testtype Test type between 1 and 5
 * @param array $wo_record Word record element
 * @param bool $nosent Test is in word mode
 * @param string $wo_text Word text
 * 
 * @return string Solution to display.  
 */
function get_test_solution($testtype, $wo_record, $nosent, $wo_text)
{
    if ($testtype == 1) {
        $trans = repl_tab_nl($wo_record['WoTranslation']) . 
        getWordTagList($wo_record['WoID'], ' ', 1, 0);
        return $nosent ? $trans : (' [' . $trans . '] ');
    } else {
        return $wo_text;
    }
}


/**
 * Preforms the HTML of the test area, to update through AJAX.
 *
 * @param string $testsql    SQL query of for the words that should be tested.
 * @param int    $totaltests Total number of tests to do.
 * @param int    $count      Number of tests left.
 * @param int    $testtype   Type of test.
 *
 * @return int Number of tests left to do.
 *
 * @global string $tbpref Table prefix 
 * @global int    $debug  Show the SQL query used if 1.
 *
 * @psalm-return int<0, max>
 */
function do_test_prepare_ajax_test_area($testsql, $count, $testtype): int
{
    global $tbpref;
    $nosent = false;
    if ($testtype > 3) {
        $testtype -= 3;
        $nosent = true;
    }

    echo '<div id="body">';

    $lgid = get_first_value("SELECT WoLgID AS value FROM $testsql LIMIT 1");
    
    $sql = "SELECT LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, LgTextSize, 
    LgRemoveSpaces, LgRegexpWordCharacters, LgRightToLeft 
    FROM {$tbpref}languages WHERE LgID = $lgid";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $lang = array(
        'wb1' => isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "",
        'wb2' => isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "",
        'wb3' => isset($record['LgGoogleTranslateURI'])?$record['LgGoogleTranslateURI']:"",
        'textsize' => $record['LgTextSize'],
        'removeSpaces' => $record['LgRemoveSpaces'],
        'regexword' => $record['LgRegexpWordCharacters'],
        'rtlScript' => $record['LgRightToLeft']
    );
    mysqli_free_result($res);

    ?>
    <script type="text/javascript">
        /**
         * Insert a new word test.
         */
        function insert_new_word(word) {

                SOLUTION = word['solution'];
                WID = word['word_id'];

                $('#term-test').html(word['group']);

                $(document).on('keydown', keydown_event_do_test_test);
                $('.word')
                .on('click', word_click_event_do_test_test)
        }

        /**
         * Handles an ajax query for word tests.
         */
        function test_query_handler(data)
        {
            if (data['word_id'] == 0) {
                do_test_finished(<?php echo json_encode($count); ?>);
                const options = {
                    "action": "query", 
                    "action_type": "tomorrow_test_count",
                    "test_sql": <?php echo json_encode((string)$testsql); ?>
                };
                $.getJSON(
                    'inc/ajax.php?' + $.param(options)
                ).done(function (data) {
                    if (data["test_count"]) {
                        $('#tests-tomorrow').css("display", "inherit");
                        $('#tests-tomorrow').text(
                            "Tomorrow you'll find here " + data["test_count"] + 
                            ' test' + (data["test_count"] == 1 ? '' : 's') + "!"
                        );
                    }
                });
            } else {
                insert_new_word(data);
            }
        }

        /**
         * Get a new word test.
         */
        function get_new_word()
        {
            // Get new word through AJAX
            const options = {
                "action": "query", 
                "action_type": "test",
                "test_sql": <?php echo json_encode((string)$testsql); ?>,
                "test_nosent": <?php echo json_encode((string)$nosent); ?>,
                "test_lgid": <?php echo json_encode((string)$lgid); ?>,
                "test_wordregex": <?php echo json_encode((string)$lang['regexword']); ?>,
                "test_type": <?php echo json_encode((string)$testtype); ?>
            };
            $.getJSON(
                'inc/ajax.php?' + $.param(options)
            ).done(test_query_handler);

            // Close any previous tooltip
            cClick();
        }

        $(get_new_word);
    </script>

    <p id="term-test" dir="<?php echo ($lang['rtlScript'] ? 'rtl' : 'ltr'); ?>" 
    style="<?php echo ($lang['removeSpaces'] ? 'word-break:break-all;' : ''); ?>
    font-size: <?php echo $lang['textsize'] ?>%; line-height: 1.4; text-align:center; margin-bottom:300px;"
    >
    </p>
    <?php do_test_test_finished($testsql, $count, true); ?>
    </div>
    <?php
    
    do_test_test_interaction_globals($lang['wb1'], $lang['wb2'], $lang['wb3']);

    return $count;
}


/**
 * Preforms the HTML of the test area.
 *
 * @param string $testsql    SQL query of for the words that should be tested.
 * @param int    $totaltests Total number of tests to do.
 * @param int    $count      Number of tests left.
 * @param int    $testtype   Type of test.
 *
 * @return int Number of tests left to do.
 *
 * @global string $tbpref Table prefix 
 * @global int    $debug  Show the SQL query used if 1.
 *
 * @psalm-return int<0, max>
 */
function prepare_test_area($testsql, $totaltests, $count, $testtype): int
{
    global $tbpref, $debug;
    $nosent = 0;
    if ($testtype > 3) {
        $testtype -= 3;
        $nosent = 1;
    }

    echo '<div id="body">';

    if ($count <= 0) {
        do_test_test_finished($testsql, $totaltests);
        return 0;
    } 

    $lang = get_first_value("SELECT WoLgID AS value FROM $testsql LIMIT 1");
    
    $sql = 'SELECT LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, LgTextSize, 
    LgRemoveSpaces, LgRegexpWordCharacters, LgRightToLeft 
    FROM ' . $tbpref . 'languages WHERE LgID = ' . $lang;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $wb1 = isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "";
    $wb2 = isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "";
    $wb3 = isset($record['LgGoogleTranslateURI'])?$record['LgGoogleTranslateURI']:"";
    $textsize = $record['LgTextSize'];
    $removeSpaces = $record['LgRemoveSpaces'];
    $regexword = $record['LgRegexpWordCharacters'];
    $rtlScript = $record['LgRightToLeft'];
    mysqli_free_result($res);
    
    // Find the next word to test
    
    $pass = 0;
    $num = 0;
    $notvalid = null;
    $sent = null;
    $wid = null;
    $word = null;
    $wordlc = null;
    while ($pass < 2) {
        $pass++;
        $sql = "SELECT DISTINCT WoID, WoText, WoTextLC, WoTranslation, 
        WoRomanization, WoSentence, WoLgID, 
        (IFNULL(WoSentence, '') NOT LIKE CONCAT('%{', WoText, '}%')) AS notvalid, 
        WoStatus, 
        DATEDIFF( NOW( ), WoStatusChanged ) AS Days, WoTodayScore AS Score 
        FROM $testsql AND WoStatus BETWEEN 1 AND 5 
        AND WoTranslation != '' AND WoTranslation != '*' AND WoTodayScore < 0 " . 
        ($pass == 1 ? 'AND WoRandom > RAND()' : '') . ' 
        ORDER BY WoTodayScore, WoRandom 
        LIMIT 1';
        if ($debug) { 
            echo 'DEBUG TEST-SQL: ' . $sql . '<br />'; 
        }
        $res = do_mysqli_query($sql);
        $record = mysqli_fetch_assoc($res);
        if ($record) {
            $num = 1;
            $wid = $record['WoID'];
            $word = $record['WoText'];
            $wordlc = $record['WoTextLC'];
            $sent = repl_tab_nl($record['WoSentence']);
            $notvalid = $record['notvalid'];
            $pass = 2;
        }
        mysqli_free_result($res);
    }
    
    if ($num == 0) {
        // Should not occur but...
        do_test_test_finished($testsql, $totaltests);
        return 0;
    }

    if ($nosent) {
        // No sentence mode (4+5)
        $num = 0;
        $notvalid = 1;
    } else {
        // $nosent == FALSE, mode 1-3
        list($sent, $num) = do_test_test_sentence($wid, $lang, $wordlc);
        if ($sent === null) {
            $notvalid = 1;
        }
    }

    if ($num == 0) {
        // take term sent. if valid
        if ($notvalid) {
            $sent = "{" . $word . "}";
        }
        if ($debug) { 
            echo "DEBUG not found, use sent = $sent<br />"; 
        }
    }
    
    
    echo '<p ' . ($rtlScript ? 'dir="rtl"' : '') . 
    ' style="' . ($removeSpaces ? 'word-break:break-all;' : '') . 
    'font-size:' . $textsize . '%;
    line-height: 1.4; text-align:center; margin-bottom:300px;">';
    
    list($r, $save) = do_test_get_term_test(
        $record, $sent, $testtype, $nosent, $regexword
    );
    
    // Show Sentence
    echo $r;
    
    do_test_test_javascript_interaction(
        $record, $wb1, $wb2, $wb3, $testtype, $nosent, $save
    );

    echo '</p></div>';

    return $count;
}


/**
 * Prepare JavaScript code globals so that you can click on words.
 * 
 * @param array  $wo_record Word record. Associative array with keys 'WoID', 
 *                          'WoTranslation'.
 * @param string $wb1       URL of the first dictionary.
 * @param string $wb2       URL of the secondary dictionary.
 * @param string $wb3       URL of the google translate dictionary.
 * @param int    $testtype  Type of test
 * @param int    $nosent    1 to use single word instead of sentence.
 * @param string $save      Word or sentence to use for the test
 * 
 * @return void
 * 
 * @global string $tbpref  Database table prefix
 * @global string $angDefs Languages definition array
 */
function do_test_test_interaction_globals($wb1, $wb2, $wb3) 
{
    ?>
<script type="text/javascript">
    WBLINK1 = <?php echo json_encode($wb1); ?>;
    WBLINK2 = <?php echo json_encode($wb2); ?>;
    WBLINK3 = <?php echo json_encode($wb3); ?>;
    LANG = getLangFromDict(WBLINK3);
    if (LANG && LANG != WBLINK3) {
        $("html").attr('lang', LANG);
    }
    OPENED = 0;
</script>
    <?php
}


/**
 * Prepare JavaScript code so that you can click on words.
 * 
 * @param array  $wo_record Word record. Associative array with keys 'WoID', 
 *                          'WoTranslation'.
 * @param string $wb1       URL of the first dictionary.
 * @param string $wb2       URL of the secondary dictionary.
 * @param string $wb3       URL of the google translate dictionary.
 * @param int    $testtype  Type of test
 * @param int    $nosent    1 to use single word instead of sentence.
 * @param string $save      Word or sentence to use for the test
 * 
 * @return void
 * 
 * @global string $tbpref  Database table prefix
 * @global string $angDefs Languages definition array
 */
function do_test_test_javascript_clickable($wo_record, $solution) {
    global $tbpref, $langDefs;

    $wid = $wo_record['WoID'];
    $lang = get_first_value(
        'SELECT LgName AS value FROM ' . $tbpref . 'languages
        WHERE LgID = ' . $wo_record['WoLgID'] . '
        LIMIT 1'        
    );
    $abbr = $langDefs[$lang][1];
    $phoneticText = phonetic_reading($wo_record['WoText'], $abbr);
    ?>
<script type="text/javascript">
    /** 
     * Read the word aloud
     */
    function read_word() {
        if (('speechSynthesis' in window) && 
        document.getElementById('utterance-allowed').checked) {
            const text = <?php echo json_encode($phoneticText); ?>;
            const lang = <?php echo json_encode($abbr); ?>;
            readRawTextAloud(text, lang);
        }
    }

    SOLUTION = <?php echo prepare_textdata_js($solution); ?>;
    WID = <?php echo $wid; ?>;

    $(document).on('keydown', keydown_event_do_test_test);
    $('.word')
    .on('click', word_click_event_do_test_test)
    .on('click', read_word);
</script>
    <?php
}

/**
 * Prepare JavaScript code so that you can click on words.
 * 
 * @param array  $wo_record Word record. Associative array with keys 'WoID', 
 *                          'WoTranslation'.
 * @param string $wb1       URL of the first dictionary.
 * @param string $wb2       URL of the secondary dictionary.
 * @param string $wb3       URL of the google translate dictionary.
 * @param int    $testtype  Type of test
 * @param int    $nosent    1 to use single word instead of sentence.
 * @param string $save      Word or sentence to use for the test
 * 
 * @return void
 * 
 * @global string $tbpref  Database table prefix
 * @global string $angDefs Languages definition array
 */
function do_test_test_javascript_interaction(
    $wo_record, $wb1, $wb2, $wb3, $testtype, $nosent, $save
) {
    do_test_test_interaction_globals($wb1, $wb2, $wb3);
    $solution = get_test_solution($testtype, $wo_record, $nosent, $save);
    do_test_test_javascript_clickable($wo_record, $solution);
}

/**
 * Get the data and echoes the footer.
 * 
 * @param int $notyettested Number of words left to be tested.
 * 
 * @return void
 */
function prepare_test_footer($notyettested)
{
    $wrong = $_SESSION['testwrong'];
    $correct = $_SESSION['testcorrect'];
    do_test_footer($notyettested, $wrong, $correct);
}

/**
 * Echoes HTML code for the footer of a words test page.
 * 
 * @param int $notyettested Number of words left to be tested
 * @param int $wrong        Number of failed tests
 * @param int $correct      Number of correct answers.
 * 
 * @return void
 */
function do_test_footer($notyettested, $wrong, $correct)
{
    $totaltests = $wrong + $correct + $notyettested;
    $totaltestsdiv = 1;
    if ($totaltests > 0) { 
        $totaltestsdiv = 1.0 / $totaltests; 
    }
    $totaltestsdiv *= 100;
    $l_notyet = round($notyettested * $totaltestsdiv, 0);
    $l_wrong = round($wrong * $totaltestsdiv, 0);
    $l_correct = round($correct * $totaltestsdiv, 0);
    ?>
<footer id="footer">
    <span style="margin-left: 15px; margin-right: 15px;">
        <img src="icn/clock.png" title="Elapsed Time" alt="Elapsed Time" />
        <span id="timer" title="Elapsed Time"></span>
    </span>
    <span style="margin-left: 15px; margin-right: 15px;">
        <img id="not-tested-box" class="borderl" 
        src="<?php print_file_path('icn/test_notyet.png');?>" 
        title="Not yet tested" alt="Not yet tested" height="10" 
        width="<?php echo $l_notyet; ?>" 
        /><img 
        id="wrong-tests-box" class="bordermiddle" 
        src="<?php print_file_path('icn/test_wrong.png');?>" 
        title="Wrong" alt="Wrong" height="10" width="<?php echo $l_wrong; ?>" 
        /><img 
        id="correct-tests-box" class="borderr" 
        src="<?php print_file_path('icn/test_correct.png');?>" 
        title="Correct" alt="Correct" height="10" width="<?php echo $l_correct; ?>" />
    </span>
    <span style="margin-left: 15px; margin-right: 15px;">
        <span title="Total number of tests" id="total_tests"><?php 
        echo $totaltests; 
        ?></span> 
        =
        <span class="todosty" title="Not yet tested" id="not-tested"><?php 
        echo $notyettested; 
        ?></span>
        +
        <span class="donewrongsty" title="Wrong" id="wrong-tests"><?php 
        echo $wrong; 
        ?></span>
        +
        <span class="doneoksty" title="Correct" id="correct-tests"><?php 
        echo $correct; 
        ?></span>
    </span>
</footer>
    <?php
}

/**
 * Prepare JavaScript code for interacting between the different frames.
 * 
 * @param int $count 1 for timer.
 * 
 * @return void
 */
function do_test_test_javascript($count)
{
    ?>
<script type="text/javascript">
    const waitTime = <?php 
    echo json_encode((int)getSettingWithDefault('set-test-edit-frame-waiting-time')) 
    ?>;

    /**
     * Prepare the different frames for a test.
     */
    function prepare_test_frames()
    {
        window.parent.frames['ru'].location.href='empty.html';
        if (waitTime <= 0) {
            window.parent.frames['ro'].location.href='empty.html';
        } else {
            setTimeout(
                'window.parent.frames["ro"].location.href="empty.html";', 
                waitTime
            );
        }
        new CountUp(
            <?php echo time(); ?>, 
            <?php echo $_SESSION['teststart']; ?>, 
            'timer', <?php echo ($count ? 0 : 1); ?>
        );
    }

    /**
     * Make a custom display when tests are finished for today.
     */
    function do_test_finished(total_tests)
    {
        $('#term-test').css("display", "none");
        $('#test-finished-area').css("display", "inherit");
        $('#tests-done-today').text(
            "Nothing " + (total_tests > 0 ? 'more ' : '') + "to test here!"
        );

        $('#tests-tomorrow').css("display", "none");
    }

    $(document).ready(prepare_test_frames);
</script>
    <?php
}

/**
 * Do the main content of a test page.
 * 
 * @global int $debug Show debug informations
 * 
 * @return void
 */
function do_test_test_content()
{
    global $debug;
    
    $testsql = do_test_get_test_sql(
        $_REQUEST['selection'], $_SESSION['testsql'], $_REQUEST['lang'], $_REQUEST['text']
    );
    $totaltests = $_SESSION['testtotal'];
    $testtype = do_test_get_test_type((int)getreq('type'));
    $count = get_first_value(
        "SELECT COUNT(DISTINCT WoID) AS value 
        FROM $testsql AND WoStatus BETWEEN 1 AND 5 
        AND WoTranslation != '' AND WoTranslation != '*' AND WoTodayScore < 0"
    );
    if ($debug) { 
        echo "DEBUG - COUNT TO TEST: $count<br />"; 
    }
    if (!is_numeric($count)) {
        my_die("The number of words left to test is not an integer: \"$count\"!");
    }
    $notyettested = (int) $count;

    $count2 = prepare_test_area($testsql, $totaltests, $notyettested, $testtype);
    prepare_test_footer($notyettested);
    do_test_test_javascript($count2);
}

/**
 * Do the main content of a test page.
 * 
 * @global int $debug Show debug informations
 * 
 * @return void
 */
function do_test_test_content_ajax($test_sql)
{
    global $debug;
    
    $testtype = do_test_get_test_type((int)getreq('type'));
    $count = get_first_value(
        "SELECT COUNT(DISTINCT WoID) AS value 
        FROM $test_sql AND WoStatus BETWEEN 1 AND 5 
        AND WoTranslation != '' AND WoTranslation != '*' AND WoTodayScore < 0"
    );
    if ($debug) { 
        echo "DEBUG - COUNT TO TEST: $count<br />"; 
    }
    if (!is_numeric($count)) {
        my_die("The number of words left to test is not an integer: \"$count\"!");
    }
    $notyettested = (int) $count;

    $count2 = do_test_prepare_ajax_test_area($test_sql, $notyettested, $testtype);
    prepare_test_footer($notyettested);
    do_test_test_javascript($count2);
}


?>
