<?php

/**
 * \file
 * \brief Edit/New Multi-word term (expression)
 * 
 * Call: edit_mword.php?....
 *              ... op=Save ... do insert new 
 *              ... op=Change ... do update
 *              ... tid=[textid]&ord=[textpos]&wid=[wordid] ... edit  
 *              ... tid=[textid]&ord=[textpos]&txt=[word] ... new or edit
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/edit__mword_8php.html
 * @since   1.0.3-fork
 */

require_once 'inc/session_utility.php';
require_once 'inc/simterms.php';

/**
 * A term (word or mutli-word) represented as an object.
 * 
 * This structure is experimental and subject to change.
 */
class Term {
    /**
     * @var int Term ID.
     */
    public $id;
    /**
     * @var int Language ID.
     */
    public $lgid;
    /**
     * @var string Associated text.
     */
    public $text;
    /**
     * @var string Associated text in lower case.
     */
    public $textlc;
    /**
     * @var int Term status.
     */
    public $status;
    /**
     * @var string Term translation.
     */
    public $translation;
    /**
     * @var string Sentence containing the term. 
     */
    public $sentence;
    /**
     * @var string Romanization.
     */
    public $roman;
    /**
     * @var int Number of words in the term.
     */
    public $wordcount;
    /**
     * @var int Last status change date.
     */
    public $statuschanged;

    /**
     * Export word data as a JSON dictionnary.
     * 
     * @return string JSON disctionnary. 
     */
    public function export_js_dict() {
        return json_encode(array(
            "woid" => $this->id,
            "text" =>  $this->text,
            "romanization" => $this->roman,
            "translation" => prepare_textdata_js(
                $this->translation . getWordTagList($this->id, ' ', 1, 0)
            ),
            "status" => $this->status
        ));
    }
}


/**
 * Do a server operation for multiwords.
 * 
 * @return void
 */
function edit_mword_do_operation($text, $translation) {

    $textlc = prepare_textdata(mb_strtolower($text));
    $text = prepare_textdata($text);
    $term = new Term();
    $term->translation = $translation;
    $term->text = $text;
    $term->roman = $_REQUEST["WoRomanization"];
    $term->word_count = (int) $_REQUEST["len"];
    $term->sentence = $_REQUEST["WoSentence"];


    if ($_REQUEST['op'] == 'Save') {
        // INSERT
        $term->status = (int) $_REQUEST["WoStatus"];
        $message = edit_mword_do_insert($term, (int) $_REQUEST["WoLgID"]);
        $term->id = get_last_key();
    } else {  
        // UPDATE
        $term->id = (int) $_REQUEST["WoID"];
        $term->status = (int) $_REQUEST["WoOldStatus"];
        $message = edit_mword_do_update($term, (int) $_REQUEST["WoStatus"]);
        $term->status = (int) $_REQUEST["WoStatus"];
    } 
    saveWordTags($term->id);

    if ($_REQUEST['op'] == 'Save') {
        insertExpressions(
            $textlc, $_REQUEST["WoLgID"], $term->id, $term->word_count, 0
        );
    } else {
        ?>
    <script type="text/javascript">
    //<![CDATA[
        function update_mword(mword, oldstatus) {
            const context = window.parent.document;
            let title = '';
            if (window.parent.JQ_TOOLTIP) 
                title = make_tooltip(
                    mword.text, mword.trans, mword.roman, mword.status
                );
            $('.word' + mword.woid, context)
            .attr('data_trans', mword.trans)
            .attr('data_rom', mword.roman)
            .attr('title', title)
            .removeClass('status' + oldstatus)
            .addClass('status' + mword.status)
            .attr('data_status', mword.status);
        }

        /*update_mword(
            <?= prepare_textdata_js($term->id); ?>,
            <?= prepare_textdata_js($term->text); ?>,
            <?= prepare_textdata_js($term->roman); ?>, 
            <?= prepare_textdata_js(
                $translation . getWordTagList($term->id, ' ', 1, 0)
            ); ?>, 
            <?= prepare_textdata_js($term->status); ?>,
            <?= $_REQUEST['WoOldStatus']; ?>
            );*/
        update_mword($term->export_js_dict(), <?= (int) $_REQUEST['WoOldStatus']; ?>);
    //]]>
    </script>
        <?php
    }
    ?>
    <script type="text/javascript">
        window.parent.document.getElementById('frame-l').focus();
        window.parent.setTimeout('cClick()', 100);
    </script>

    <?php
    /*
     * Unreachable code, at least since 2.3.0-fork.
     */
    if (isset($sqltext)) {
        flush();
        do_mysqli_query($sqltext);
        echo '<p>OK: ',tohtml($message),'</p>';
    }
}

/**
 * Insert a multi-word to the database.
 * 
 * @param Term $term Multi-word to be inserted.
 * @param int  $lgid Language ID for the inserted multi-word.
 * 
 * @return void
 * 
 * @global string $tbpref Database table prefix.
 */
function edit_mword_do_insert($term, $lgid) {
    global $tbpref;
    $textlc = mb_strtolower($term->text);
    $titletext = "New Term: " . tohtml($textlc);
    pagestart_nobody($titletext);
    echo '<h4><span class="bigger">' . $titletext . '</span></h4>';

    $message = runsql(
        "INSERT INTO {$tbpref}words (
            WoLgID, WoTextLC, WoText, WoStatus, WoTranslation, WoSentence, 
            WoRomanization, WoWordCount, WoStatusChanged," 
            .  make_score_random_insert_update('iv') . '
        ) VALUES( ' . 
            $lgid . ', ' .
            convert_string_to_sqlsyntax($textlc) . ', ' .
            convert_string_to_sqlsyntax($term->text) . ', ' .
            $term->status . ', ' .
            convert_string_to_sqlsyntax($term->translation) . ', ' .
            convert_string_to_sqlsyntax(repl_tab_nl($term->sentence)) . ', ' .
            convert_string_to_sqlsyntax($term->roman) . ', ' . 
            convert_string_to_sqlsyntax($term->word_count) . ', 
            NOW(), ' .  
            make_score_random_insert_update('id') . 
        ')', 
        "Term saved"
    );
    init_word_count();
    // strToClassName($textlc);
    return $message;
}


/**
 * Update a multi-word.
 * 
 * @param Term $term      Multi-word to be inserted.
 * @param int  $newstatus New multi-word status
 * 
 * @return void
 * 
 * @global string $tbpref Database table prefix.
 */
function edit_mword_do_update($term, $newstatus) {
    global $tbpref;
    $textlc = $term->text;
    $titletext = "Edit Term: " . tohtml($textlc);
    pagestart_nobody($titletext);
    echo '<h4><span class="bigger">' . $titletext . '</span></h4>';

    $oldstatus = $term->status;
    $status_change = '';
    if ($oldstatus != $newstatus) { 
        $status_change = ', WoStatus = ' . $newstatus . ', WoStatusChanged = NOW()'; 
    }

    $message = runsql(
        'update ' . $tbpref . 'words set 
        WoText = ' . convert_string_to_sqlsyntax($term) . ', 
        WoTranslation = ' . convert_string_to_sqlsyntax($term->translation) . ', 
        WoSentence = ' . convert_string_to_sqlsyntax(
            repl_tab_nl($term->sentence)
        ) . ', 
        WoRomanization = ' . convert_string_to_sqlsyntax($term->roman) . 
        $status_change . ',' . 
        make_score_random_insert_update('u') . ' 
        where WoID = ' . $term->id,
        "Updated"
    );

    return $message;
}

/**
 * Make the main display for editing mutli-words.
 * 
 * @return void
 * 
 * @global string $tbpref Database table prefix.
 */
function edit_mword_display() {
    global $tbpref;

    $lang = null;
    $term = null;
    $wid = getreq('wid');

    if ($wid == '') {
        $lang = get_first_value(
            "select TxLgID as value 
            from " . $tbpref . "texts 
            where TxID = " . $_REQUEST['tid']
        );
        $term = prepare_textdata(getreq('txt'));
        $termlc = mb_strtolower($term, 'UTF-8');

        $wid = get_first_value(
            "select WoID as value 
            from " . $tbpref . "words 
            where WoLgID = " . $lang . 
            " and WoTextLC = " . convert_string_to_sqlsyntax($termlc)
        );
        if (isset($wid)) { 
            $term = get_first_value(
                "select WoText as value 
                from " . $tbpref . "words 
                where WoID = " . $wid
            ); 
        }
    } else {

        $sql = 'select WoText, WoLgID from ' . $tbpref . 'words where WoID = ' . $wid;
        $res = do_mysqli_query($sql);
        $record = mysqli_fetch_assoc($res);
        if ($record ) {
            $term = $record['WoText'];
            $lang = $record['WoLgID'];
        } else {
            my_die("Cannot access Term and Language in edit_mword.php");
        }
        mysqli_free_result($res);
        $termlc =    mb_strtolower($term, 'UTF-8');

    }

    $new = empty($wid);

    $titletext = ($new ? "New Term" : "Edit Term") . ": " . $term;
    pagestart_nobody($titletext);
    ?>
    <script type="text/javascript">
        $(document).ready(ask_before_exiting);
        $(window).on('beforeunload',function() {
            setTimeout(function() {
                window.parent.frames['ru'].location.href = 'empty.html';
            }, 0);
        });
    </script>
    <?php
    $scrdir = getScriptDirectionTag($lang);


    if ($new) {
        // NEW
        edit_mword_new($lang, $termlc, $term, $scrdir);
    } else {
        // CHG
        edit_mword_change($wid, $lang, $termlc, $term, $scrdir);
    }
}

function edit_mword_new($lang, $termlc, $term, $scrdir) {
    global $tbpref;
    $seid = get_first_value(
        "select Ti2SeID as value 
        from " . $tbpref . "textitems2 
        where Ti2TxID = " . $_REQUEST['tid'] . " and Ti2Order = " . $_REQUEST['ord']
    );
    $sent = getSentence($seid, $termlc, (int) getSettingWithDefault('set-term-sentence-count'));

    ?>

    <form name="newword" class="validate" action="<?= $_SERVER['PHP_SELF']; ?>" method="post">
    <input type="hidden" name="WoLgID" id="langfield" value="<?= $lang; ?>" />
    <input type="hidden" name="WoTextLC" value="<?= tohtml($termlc); ?>" />
    <input type="hidden" name="tid" value="<?= $_REQUEST['tid']; ?>" />
    <input type="hidden" name="ord" value="<?= $_REQUEST['ord']; ?>" />
    <input type="hidden" name="len" value="<?= $_REQUEST['len']; ?>" />
    <table class="tab2" cellspacing="0" cellpadding="5">
        <tr title="Only change uppercase/lowercase!">
            <td class="td1 right"><b>New Term:</b></td>
            <td class="td1">
                <input <?= $scrdir; ?> class="notempty checkoutsidebmp" data_info="New Term" type="text" name="WoText" id="wordfield" value="<?= tohtml($term); ?>" maxlength="250" size="35" /> 
                <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
            </td>
        </tr>
        <?php print_similar_terms_tabrow(); ?>
        <tr>
            <td class="td1 right">Translation:</td>
            <td class="td1">
                <textarea name="WoTranslation" class="setfocus textarea-noreturn checklength checkoutsidebmp" data_maxlength="500" data_info="Translation" cols="35" rows="3"></textarea></td>
        </tr>
        <tr>
            <td class="td1 right">Tags:</td>
            <td class="td1">
                <?= getWordTags(0); ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Romaniz.:</td>
            <td class="td1">
                <input type="text" class="checkoutsidebmp" data_info="Romanization" name="WoRomanization" value="" maxlength="100" size="35" />
            </td>
        </tr>
        <tr>
            <td class="td1 right">Sentence<br />Term in {...}:</td>
            <td class="td1">
                <textarea <?= $scrdir; ?> name="WoSentence" class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="1000" data_info="Sentence" cols="35" rows="3"><?= tohtml(repl_tab_nl($sent[1])); ?></textarea>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Status:</td>
            <td class="td1">
                <?= get_wordstatus_radiooptions(1); ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right" colspan="2">
                <?= createDictLinksInEditWin($lang, $term, 'document.forms[0].WoSentence', isset($_GET['nodict'])?0:1); ?>
                &nbsp; &nbsp; &nbsp; 
                <input type="submit" name="op" value="Save" />
            </td>
        </tr>
    </table>
    </form>
    <div id="exsent">
        <span class="click" onclick="do_ajax_show_sentences(<?= $lang; ?>, <?= prepare_textdata_js($termlc) ?>, <?= prepare_textdata_js("document.forms['newword'].WoSentence") ?> . , -1);">
            <img src="icn/sticky-notes-stack.png" title="Show Sentences" alt="Show Sentences" /> 
            Show Sentences
        </span>
    </div>
    <?php
}

function edit_mword_change($wid, $lang, $termlc, $term, $scrdir) {
    global $tbpref;
    $sql = 'select WoTranslation, WoSentence, WoRomanization, WoStatus 
    from ' . $tbpref . 'words where WoID = ' . $wid;
    $res = do_mysqli_query($sql);
    if ($record = mysqli_fetch_assoc($res)) {
        $status = $record['WoStatus'];
        if ($status >= 98) { 
            $status = 1; 
        }
        $sentence = repl_tab_nl($record['WoSentence']);
        if ($sentence == '') {
            $seid = get_first_value(
                "select Ti2SeID as value 
                from " . $tbpref . "textitems2 
                where Ti2TxID = " . $_REQUEST['tid'] . " 
                and Ti2Order = " . $_REQUEST['ord']
            );
            $sent = getSentence(
                $seid, $termlc, (int) getSettingWithDefault('set-term-sentence-count')
            );
            $sentence = repl_tab_nl($sent[1]);
        }
        $transl = repl_tab_nl($record['WoTranslation']);
        if($transl == '*') { 
            $transl=''; 
        }
        ?>

    <form name="editword" class="validate" action="<?= $_SERVER['PHP_SELF']; ?>" method="post">
    <input type="hidden" name="WoLgID" id="langfield" value="<?= $lang; ?>" />
    <input type="hidden" name="WoID" value="<?= $wid; ?>" />
    <input type="hidden" name="WoOldStatus" value="<?= $record['WoStatus']; ?>" />
    <input type="hidden" name="WoStatus" value="<?= $status; ?>" />
    <input type="hidden" name="WoTextLC" value="<?= tohtml($termlc); ?>" />
    <input type="hidden" name="tid" value="<?= $_REQUEST['tid']; ?>" />
    <input type="hidden" name="ord" value="<?= $_REQUEST['ord']; ?>" />
    <table class="tab2" cellspacing="0" cellpadding="5">
        <tr title="Only change uppercase/lowercase!">
            <td class="td1 right"><b>Edit Term:</b></td>
            <td class="td1">
                <input <?= $scrdir; ?> class="notempty checkoutsidebmp" data_info="Term" type="text" name="WoText" id="wordfield" value="<?= tohtml($term); ?>" maxlength="250" size="35" /> 
                <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
            </td>
        </tr>
        <?php print_similar_terms_tabrow(); ?>
        <tr>
            <td class="td1 right">Translation:</td>
            <td class="td1">
                <textarea name="WoTranslation" class="setfocus textarea-noreturn checklength checkoutsidebmp" data_maxlength="500" data_info="Translation" cols="35" rows="3"><?= tohtml($transl); ?></textarea>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Tags:</td>
            <td class="td1">
                    <?= getWordTags($wid); ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Romaniz.:</td>
            <td class="td1">
                <input type="text" class="checkoutsidebmp" data_info="Romanization" name="WoRomanization" maxlength="100" size="35" 
                value="<?= tohtml($record['WoRomanization']); ?>" />
            </td>
        </tr>
        <tr>
            <td class="td1 right">Sentence<br />Term in {...}:</td>
            <td class="td1">
                <textarea <?= $scrdir; ?> name="WoSentence" class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="1000" data_info="Sentence" cols="35" rows="3"><?= tohtml($sentence); ?></textarea>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Status:</td>
            <td class="td1">
                    <?= get_wordstatus_radiooptions($record['WoStatus']); ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right" colspan="2">
                <?= createDictLinksInEditWin($lang, $term, 'document.forms[0].WoSentence', isset($_GET['nodict'])?0:1); ?>
                &nbsp; &nbsp; &nbsp; 
                <input type="submit" name="op" value="Change" />
            </td>
        </tr>
    </table>
    </form>
    <div id="exsent">
        <span class="click" onclick="do_ajax_show_sentences(<?= $lang; ?>, <?= prepare_textdata_js($termlc) ?>, <?= prepare_textdata_js("document.forms['editword'].WoSentence") ?>, <?= $wid; ?>);">
            <img src="icn/sticky-notes-stack.png" title="Show Sentences" alt="Show Sentences" /> 
            Show Sentences
        </span>
    </div>
        <?php
    }
    mysqli_free_result($res);
}

function edit_mword_page() {
    if (isset($_REQUEST['op'])) {
        // INS/UPD
        $translation_raw = repl_tab_nl(getreq("WoTranslation"));
        if ($translation_raw == '' ) { 
            $translation = '*';
        } else { 
            $translation = $translation_raw; 
        }
        $textlc = trim(getreq("WoTextLC"));
        if (mb_strtolower(trim(getreq("WoText")), 'UTF-8') != $textlc) {
            $titletext = "New/Edit Term: " . tohtml($textlc);
            pagestart_nobody($titletext);
            echo '<h4><span class="bigger">' . $titletext . '</span></h4>';
            $message = 'Error: Term in lowercase must be exactly = "' . $textlc . 
            '", please go back and correct this!';
            echo error_message_with_hide($message, 0);
            pageend();
            exit();
        }
        edit_mword_do_operation(
            $_REQUEST["WoText"], $translation
        );
    } else {  
        // edit_mword.php?tid=..&ord=..&wid=..  ODER  edit_mword.php?tid=..&ord=..&txt=..
        edit_mword_display();
    }
    pageend();
}

edit_mword_page();

?>
