<?php

/**
 * \file
 * \brief Change status of term while testing
 * 
 * Call: set_test_status.php?wid=[wordid]&stchange=+1/-1&[ajax=1]
 *       set_test_status.php?wid=[wordid]&status=1..5/98/99&[ajax=1]
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/do__test__header_8php.html
 * @since   1.0.3
 */

require_once 'inc/session_utility.php';
require_once 'inc/start_session.php';

/**
 * Echo the page HTML content when setting word status.
 * 
 * @param int $status    New learning status for the word
 * @param int $oldstatus Previous learning status
 * @param int $newscore  New score for the word
 * @param int $oldscore  Previous score
 * 
 * @return void
 */
function do_set_test_status_html($status, $oldstatus, $newscore, $oldscore) 
{
    if ($oldstatus == $status) {
        echo '<p>Status ' . get_colored_status_msg($status) . ' not changed.</p>'; 
    } else {
        echo '<p>
        Status changed from ' . get_colored_status_msg($oldstatus) . 
        ' to ' . get_colored_status_msg($status) . '
        .</p>';
    }

    echo "<p>Old score was $oldscore, new score is now $newscore.</p>";
}

/**
 * Increment the session progress in learning new words.
 * 
 * @param int $stchange -1, 0, or 1 if status is rising or not
 * 
 * @return void
 */
function set_test_status_change_progress($stchange)
{
    $totaltests = (int)$_SESSION['testtotal'];
    $wrong = $_SESSION['testwrong'];
    $correct = $_SESSION['testcorrect'];
    $notyettested = $totaltests - $correct - $wrong;
    if ($notyettested > 0) {
        if ($stchange >= 0) {
            $correct++;
            $_SESSION['testcorrect']++;
        } else {
            $wrong++;
            $_SESSION['testwrong']++; 
        }
        $notyettested--;
    }
    return array(
        "total" => $totaltests, "wrong" => $wrong, "correct" => $correct,
        "nottested" => $notyettested
    );
}        

/**
 * Make the JavaScript action for setting a word status.
 * 
 * @param int $wid      Word ID
 * @param int $status   New learning status for the word
 * @param int $stchange -1, 0, or 1 if status is rising or not
 * 
 * @return void
 */
function do_set_test_status_javascript(
    $wid, $status, $stchange, $tests_status=array(), $ajax=false
)
{
    ?>
<script type="text/javascript">
    const context = window.parent;
    $('.word<?php echo $wid; ?>', context.document)
    .removeClass('todo todosty')
    .addClass('done<?php echo ($stchange >= 0 ? 'ok' : 'wrong'); ?>sty')
    .attr('data_status','<?php echo $status; ?>')
    .attr('data_todo','0');
    // Waittime <= 0 causes the page to loop-reloading
    const waittime = <?php 
    echo json_encode((int)getSettingWithDefault('set-test-main-frame-waiting-time')); 
    ?> + 500;

    function page_reloader(waittime, target) {
        if (waittime <= 0) {
            target.location.reload();
        } else {
            setTimeout(window.location.reload.bind(target.location), waittime);
        }
    }

    /**
     * Update remaining words count.
     */
    function update_tests_count(tests_status, cont_document) {
        let width_divisor = .01;
        if (tests_status["total"] > 0) {
            width_divisor = tests_status["total"] / 100;
        }

        $("#not-tested-box", cont_document)
        .width(tests_status["nottested"] / width_divisor);
        $("#wrong-tests-box", cont_document)
        .width(tests_status["wrong"] / width_divisor);
        $("#correct-tests-box", cont_document)
        .width(tests_status["correct"] / width_divisor);

        $("#not-tested-header", cont_document).text(tests_status["nottested"]);
        $("#not-tested", cont_document).text(tests_status["nottested"]);
        $("#wrong-tests", cont_document).text(tests_status["wrong"]);
        $("#correct-tests", cont_document).text(tests_status["correct"]);
    }

    /**
     * Get a new word.
     */
    function ajax_reloader(waittime, target, tests_status) {
        if (waittime <= 0) {
            context.get_new_word();
        } else {
            setTimeout(target.get_new_word, waittime);
        }
    }


    if (<?php echo json_encode($ajax); ?>) {
        // Update status footer
        update_tests_count(
            <?php echo json_encode($tests_status); ?>, context.document
        );
        // Get new word
        ajax_reloader(waittime, context);
    } else {
        page_reloader(waittime, context);
    }

</script>
    <?php
}

/**
 * Make the page content of the word status page.
 * 
 * @param int $wid       Word ID
 * @param int $status    New learning status for the word
 * @param int $oldstatus Previous learning status
 * @param int $stchange  -1, 0, or 1 if status is rising or not
 * 
 * @return void
 * 
 * @global string $tbpref Database table prefix 
 */
function do_set_test_status_content($wid, $status, $oldstatus, $stchange, $ajax=false)
{
    global $tbpref;
    $word = get_first_value(
        "SELECT WoText AS value FROM {$tbpref}words 
        WHERE WoID = $wid"
    );

    $oldscore = (int)get_first_value(
        "SELECT greatest(0,round(WoTodayScore,0)) AS value 
        FROM {$tbpref}words WHERE WoID = $wid"
    );
    runsql(
        "UPDATE {$tbpref}words SET WoStatus = $status, WoStatusChanged = NOW()," . 
        make_score_random_insert_update('u') . " 
        WHERE WoID = $wid", 
        'Status changed'
    );
        
    $newscore = (int)get_first_value(
        "SELECT greatest(0,round(WoTodayScore,0)) AS value 
        FROM {$tbpref}words WHERE WoID = $wid"
    );
    pagestart("Term: " . $word, false);
    do_set_test_status_html($status, $oldstatus, $newscore, $oldscore);
    $tests = set_test_status_change_progress($stchange);
    do_set_test_status_javascript($wid, $status, $stchange, $tests, $ajax);
    pageend();
}

/**
 * Start the word status set page. 
 * 
 * @return void
 * 
 * @global string $tbpref Database table prefix
 */
function start_set_text_status()
{
    global $tbpref;

    if (!is_numeric(getreq('status')) && !is_numeric(getreq('stchange'))) {
        my_die('status or stchange should be specified!');
    }

    $wid = (int)getreq('wid');
    $oldstatus = (int)get_first_value(
        "SELECT WoStatus AS value FROM {$tbpref}words 
        WHERE WoID = $wid"
    );

    if (is_numeric(getreq('stchange'))) {
        $stchange = (int)getreq('stchange');
        $status = $oldstatus + $stchange;
        if ($status < 1) { 
            $status = 1; 
        } else if ($status > 5) { 
            $status = 5; 
        }
    } else {
        $status = (int)getreq('status');
        $stchange = $status - $oldstatus;
        if ($stchange <= 0) { 
            $stchange = -1; 
        } else if ($stchange > 0) { 
            $stchange = 1; 
        }
    }
    $use_ajax = array_key_exists("ajax", $_REQUEST);
    do_set_test_status_content($wid, $status, $oldstatus, $stchange, $use_ajax);
}

start_set_text_status();

?>