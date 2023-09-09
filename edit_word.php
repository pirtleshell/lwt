<?php

/**
 * \file
 * \brief Create or edit single word
 * 
 * Call: edit_word.php?....
 *  ... op=Save ... do insert new
 *  ... op=Change ... do update
 *  ... fromAnn=recno ... calling from impr. annotation editing
 *  ... tid=[textid]&ord=[textpos]&wid= ... new word  
 *  ... tid=[textid]&ord=[textpos]&wid=[wordid] ... edit word 
 * 
 * @since  1.0.3
 * @author LWT Project <lwt-project@hotmail.com>
 */

require_once 'inc/session_utility.php';
require_once 'inc/simterms.php';
require_once 'inc/langdefs.php';

/**
 * Insert a new word to the database
 * 
 * @param string $textlc      The word to insert, in lowercase
 * @param string $translation Translation of this term
 * 
 * @return array{0: int, 1: string} Word id, and then an insertion message 
 */
function insert_new_word($textlc, $translation)
{
    global $tbpref;

    $titletext = "New Term: " . tohtml(prepare_textdata($_REQUEST["WoTextLC"]));
    pagestart_nobody($titletext);
    echo '<h1>' . $titletext . '</h1>';

    $message = runsql(
        'INSERT INTO ' . $tbpref . 'words 
        (
            WoLgID, WoTextLC, WoText, WoStatus, WoTranslation, 
            WoSentence, WoWordCount, WoRomanization, WoStatusChanged,' 
            .  make_score_random_insert_update('iv') . '
        ) VALUES( 
            ' . $_REQUEST["WoLgID"] . ', ' .
            convert_string_to_sqlsyntax($_REQUEST["WoTextLC"]) . ', ' .
            convert_string_to_sqlsyntax($_REQUEST["WoText"]) . ', ' .
            $_REQUEST["WoStatus"] . ', ' .
            convert_string_to_sqlsyntax($translation) . ', ' .
            convert_string_to_sqlsyntax(repl_tab_nl($_REQUEST["WoSentence"])) . ', 1, ' .
            convert_string_to_sqlsyntax($_REQUEST["WoRomanization"]) . ', NOW(), ' .  
            make_score_random_insert_update('id') . 
        ')', 
        "Term saved"
    );
    $wid = get_last_key();
    do_mysqli_query(
        'UPDATE ' . $tbpref . 'textitems2 SET Ti2WoID = ' . $wid . ' 
        WHERE Ti2LgID = ' . $_REQUEST["WoLgID"] . ' AND LOWER(Ti2Text) =' . 
        convert_string_to_sqlsyntax_notrim_nonull($textlc)
    );
    return array($wid, $message);
}

/**
 * Edit an existing term.
 * 
 * @param string $translation New translation for this term
 * 
 * @return array{0: string, 1: string} Word id, and then an insertion message 
 */
function edit_term($translation)
{
    global $tbpref;

    $titletext = "Edit Term: " . tohtml(prepare_textdata($_REQUEST["WoTextLC"]));
    pagestart_nobody($titletext);
    echo '<h1>' . $titletext . '</h1>';
    
    $oldstatus = $_REQUEST["WoOldStatus"];
    $newstatus = $_REQUEST["WoStatus"];
    $xx = '';
    if ($oldstatus != $newstatus) { 
        $xx = ', WoStatus = ' .    $newstatus . ', WoStatusChanged = NOW()'; 
    }

    $message = runsql(
        'update ' . $tbpref . 'words set WoText = ' . 
        convert_string_to_sqlsyntax($_REQUEST["WoText"]) . ', WoTranslation = ' . 
        convert_string_to_sqlsyntax($translation) . ', WoSentence = ' . 
        convert_string_to_sqlsyntax(repl_tab_nl($_REQUEST["WoSentence"])) . ', WoRomanization = ' .
        convert_string_to_sqlsyntax($_REQUEST["WoRomanization"]) . $xx . ',' . 
        make_score_random_insert_update('u') . 
        ' where WoID = ' . $_REQUEST["WoID"], "Updated"
    );
    $wid = $_REQUEST["WoID"];
    return array($wid, $message);
}

/**
 * Use this function if the lowercase version of the word does not correspond.
 * It will echo an error message.
 *
 * @param string $textlc The lowercase version of the word we want.
 */
function lowercase_term_not_equal($textlc): void
{
    $titletext = "New/Edit Term: " . tohtml(prepare_textdata($_REQUEST["WoTextLC"]));
    pagestart_nobody($titletext);
    echo '<h1>' . $titletext . '</h1>';        
    $message = 
    'Error: Term in lowercase must be exactly = "' . 
    $textlc . '", please go back and correct this!'; 
    echo error_message_with_hide($message, 0);
}

/**
 * Echoes a JavaScript element, that will edit terms diplay
 */
function change_term_display($wid, $translation, $hex): void
{
    ?>
<script type="text/javascript">
    const context = window.parent.document.getElementById('frame-l');
    const contexth = window.parent.document.getElementById('frame-h');
    const woid = <?php echo prepare_textdata_js($wid); ?>;
    const status = <?php echo prepare_textdata_js($_REQUEST["WoStatus"]); ?>;
    const trans = <?php 
    echo prepare_textdata_js($translation . getWordTagList($wid, ' ', 1, 0)); 
    ?>;
    const roman = <?php echo prepare_textdata_js($_REQUEST["WoRomanization"]); ?>;
    let title;
    if (window.parent.document.getElementById('frame-l').JQ_TOOLTIP) {
        title = '';
    } else {
        title = make_tooltip(
            <?php echo prepare_textdata_js($_REQUEST["WoText"]); ?>, 
            trans, roman, status
        );
    }
    <?php
    if ($_REQUEST['op'] == 'Save') {
        ?>
        $('.TERM<?php echo $hex; ?>', context)
        .removeClass('status0')
        .addClass('word' + woid + ' ' + 'status' + status)
        .attr('data_trans', trans)
        .attr('data_rom', roman)
        .attr('data_status', status)
        .attr('title', title)
        .attr('data_wid', woid);
        <?php
    } else {
        ?>
        $('.word' + woid, context)
        .removeClass('status<?php echo $_REQUEST['WoOldStatus']; ?>')
        .addClass('status' + status)
        .attr('data_trans', trans)
        .attr('data_rom', roman)
        .attr('data_status', status)
        .attr('title', title);
        <?php
    }
    ?>
    $('#learnstatus', contexth)
    .html('<?php echo addslashes(texttodocount2($_REQUEST['tid'])); ?>');

    cleanupRightFrames();
</script>
    <?php
}

// INS/UPD
function edit_word_do_operation($translation, $fromAnn)
{
    $hex = null;
    $textlc = trim(prepare_textdata($_REQUEST["WoTextLC"]));
    $text = trim(prepare_textdata($_REQUEST["WoText"]));

    if (mb_strtolower($text, 'UTF-8') != $textlc) {
        lowercase_term_not_equal($textlc);
        pageend();
        exit();
    }
    
    if ($_REQUEST['op'] == 'Save') {
        // Insert new term
        $output = insert_new_word($textlc, $translation);
        $hex = strToClassName(prepare_textdata($_REQUEST["WoTextLC"]));
    } else {
        // Update existing term
        $output = edit_term($translation);
    }
    $wid = $output[0];
    $message = $output[1];
    saveWordTags($wid);
    
    echo '<p>OK: ' . tohtml($message) . '</p>';

    if ($fromAnn === "") {
        // Word was edited from reading screen
        change_term_display($wid, $translation, $hex);
    } else {
        // Word is from the annotation editing window 
        ?>
<script type="text/javascript">
    window.opener.do_ajax_edit_impr_text(
        <?php echo $fromAnn; ?>, <?php echo prepare_textdata_js($textlc); ?>
        );
</script>
        <?php
    }
    
}


function edit_word_do_form($fromAnn)
{
    global $tbpref, $langDefs;
    $lang = null;
    $term = null;
    $wid = getreq('wid');
    
    if ($wid == '') {    
        $sql = 
        'SELECT Ti2Text, Ti2LgID FROM ' . $tbpref . 'textitems2 
        WHERE Ti2TxID = ' . $_REQUEST['tid'] . ' AND Ti2WordCount = 1 AND Ti2Order = ' . $_REQUEST['ord'];
        $res = do_mysqli_query($sql);
        $record = mysqli_fetch_assoc($res);
        if (!$record) {
            my_die("Cannot access Term and Language in edit_word.php");
        }
        $term = $record['Ti2Text'];
        $lang = $record['Ti2LgID'];
        mysqli_free_result($res);
        
        $termlc = mb_strtolower($term, 'UTF-8');
        
        $wid = get_first_value(
            "SELECT WoID AS value 
            FROM {$tbpref}words 
            WHERE WoLgID = $lang AND WoTextLC = " . convert_string_to_sqlsyntax($termlc)
        );
    } else {
        $sql = "SELECT WoText, WoLgID FROM {$tbpref}words WHERE WoID = $wid";
        $res = do_mysqli_query($sql);
        $record = mysqli_fetch_assoc($res);
        if (!$record) {
            my_die("Cannot access Term and Language in edit_word.php");
        }
        $term = $record['WoText'];
        $lang = $record['WoLgID'];
        mysqli_free_result($res);
        $termlc = mb_strtolower($term, 'UTF-8');
        
    }
    
    $new = (isset($wid) == false);

    $titletext = ($new ? "New Term" : "Edit Term") . ": " . tohtml($term);
    pagestart_nobody($titletext);
    ?>
<script type="text/javascript">
    $(document).ready(ask_before_exiting);
    $(window).on('beforeunload',function() {
        setTimeout(function() {window.parent.frames['ru'].location.href = 'empty.html';}, 0);
    });
</script>
    <?php
    $scrdir = getScriptDirectionTag($lang);

    // NEW
    
    if ($new) {
        $seid = get_first_value(
            "SELECT Ti2SeID AS value FROM " . $tbpref . "textitems2 
            WHERE Ti2TxID = " . $_REQUEST['tid'] . " AND Ti2WordCount = 1 AND Ti2Order = " . $_REQUEST['ord']
        );
        $sent = getSentence($seid, $termlc, (int) getSettingWithDefault('set-term-sentence-count'));
            
        ?>
    
 <form name="newword" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
 <input type="hidden" name="fromAnn" value="<?php echo $fromAnn; ?>" />
 <input type="hidden" name="WoLgID" id="langfield" value="<?php echo $lang; ?>" />
 <input type="hidden" name="WoTextLC" value="<?php echo tohtml($termlc); ?>" />
 <input type="hidden" name="tid" value="<?php echo $_REQUEST['tid']; ?>" />
 <input type="hidden" name="ord" value="<?php echo $_REQUEST['ord']; ?>" />
 <table class="tab2" cellspacing="0" cellpadding="5">
    <tr title="Only change uppercase/lowercase!">
        <td class="td1 right"><b>New Term:</b></td>
        <td class="td1">
            <input <?php echo $scrdir; ?> class="notempty checkoutsidebmp" data_info="New Term" type="text" 
            name="WoText" id="wordfield" value="<?php echo tohtml($term); ?>" maxlength="250" size="35" />
            <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
            <?php print_similar_terms_tabrow(); ?>
    <tr>
        <td class="td1 right">Translation:</td>
        <td class="td1">
            <textarea name="WoTranslation" 
            class="setfocus textarea-noreturn checklength checkoutsidebmp" data_maxlength="500" 
            data_info="Translation" cols="35" rows="3"></textarea>
        </td>
    </tr>
    <tr>
        <td class="td1 right">Tags:</td>
        <td class="td1">
            <?php echo getWordTags(0); ?>
        </td>
    </tr>
    <tr>
        <td class="td1 right">Romaniz.:</td>
        <td class="td1">
            <input type="text" class="checkoutsidebmp" data_info="Romanization" name="WoRomanization" 
            value="" maxlength="100" size="35" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Sentence<br />Term in {...}:</td>
        <td class="td1">
            <textarea <?php echo $scrdir; ?> name="WoSentence" 
            class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="1000" data_info="Sentence" cols="35" 
            rows="3"><?php echo tohtml(repl_tab_nl($sent[1])); ?></textarea>
        </td>
    </tr>
            <?php print_similar_terms_tabrow(); ?>
    <tr>
        <td class="td1 right">Status:</td>
        <td class="td1">
            <?php echo get_wordstatus_radiooptions(1); ?>
        </td>
    </tr>
    <tr>
        <td class="td1 right" colspan="2">
            <?php echo createDictLinksInEditWin(
                $lang, $term, 'document.forms[0].WoSentence', isset($_GET['nodict'])?0:1
            ); ?>
        &nbsp; &nbsp; &nbsp; 
        <input type="submit" name="op" value="Save" /></td>
    </tr>
 </table>
 </form>
    <script type="text/javascript">
        const TRANS_URI = <?php 
        echo json_encode(
            get_first_value(
                "SELECT LgGoogleTranslateURI AS value FROM {$tbpref}languages 
                WHERE LgID = $lang"
            )
        );
        ?> 
        const LANG_SHORT = <?php 
        $lgname = get_first_value(
            "SELECT LgName AS value FROM {$tbpref}languages WHERE LgID = $lang"
        );
        echo json_encode(
            array_key_exists($lgname, $langDefs) ? $langDefs[$lgname][1] : ''
        );
        ?> || getLangFromDict(TRANS_URI);

        /**
         * Sets the translation of a term.
         */
        const autoTranslate = function () {
            const translator_url = new URL(TRANS_URI);
            const urlParams = translator_url.searchParams;
            if (urlParams.get("lwt_translator") == "libretranslate") {
                const term = $('#wordfield').val();
                getLibreTranslateTranslation(
                    translator_url, term, 
                    (urlParams.has("source") ? 
                    urlParams.get("source") : LANG_SHORT), 
                    urlParams.get("target")
                )
                .then(function (translation) {
                    newword.WoTranslation.value = translation;
                });
            }
        }

        /**
         * Sets the romanization of a term.
         */
        const autoRomanization = function () {
            const term = $('#wordfield').val();
            getPhoneticTextAsync(term, LANG_SHORT)
            .then(function (phonetic) {
                newword.WoRomanization.value = phonetic;
            });
        }

        autoTranslate();
        autoRomanization();

    </script>
        <?php
        // Display example sentence button
        example_sentences_area($lang, $termlc, 'document.forms.newword.WoSentence', 0);
    } else {
        // CHG
        $sql = "SELECT WoTranslation, WoSentence, WoRomanization, WoStatus 
        FROM {$tbpref}words WHERE WoID = $wid";
        $res = do_mysqli_query($sql);
        if ($record = mysqli_fetch_assoc($res)) {
            $status = $record['WoStatus'];
            if ($fromAnn == '' && $status >= 98) {
                $status = 1;
            }
            $sentence = repl_tab_nl($record['WoSentence']);
            if ($sentence == '' && isset($_REQUEST['tid']) && isset($_REQUEST['ord'])) {
                $seid = get_first_value(
                    "select Ti2SeID as value from " . $tbpref . "textitems2 
                    where Ti2TxID = " . $_REQUEST['tid'] . " and Ti2WordCount = 1 and Ti2Order = " . $_REQUEST['ord']
                );
                $sent = getSentence($seid, $termlc, (int) getSettingWithDefault('set-term-sentence-count'));
                $sentence = repl_tab_nl($sent[1]);
            }
            $transl = repl_tab_nl($record['WoTranslation']);
            if ($transl == '*') { 
                $transl=''; 
            }
            ?>
        
     <form name="editword" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
     <input type="hidden" name="WoLgID" id="langfield" value="<?php echo $lang; ?>" />
     <input type="hidden" name="fromAnn" value="<?php echo $fromAnn; ?>" />
     <input type="hidden" name="WoID" value="<?php echo $wid; ?>" />
     <input type="hidden" name="WoOldStatus" value="<?php echo $record['WoStatus']; ?>" />
     <input type="hidden" name="WoTextLC" value="<?php echo tohtml($termlc); ?>" />
     <input type="hidden" name="tid" value="<?php echo getreq('tid'); ?>" />
     <input type="hidden" name="ord" value="<?php echo getreq('ord'); ?>" />
     <table class="tab2" cellspacing="0" cellpadding="5">
        <tr title="Only change uppercase/lowercase!">
            <td class="td1 right"><b>Edit Term:</b></td>
            <td class="td1">
                <input <?php echo $scrdir; ?> class="notempty checkoutsidebmp" data_info="Term" type="text" 
                name="WoText" id="wordfield" value="<?php echo tohtml($term); ?>" maxlength="250" size="35" />
                <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
            </td>
        </tr>
            <?php print_similar_terms_tabrow(); ?>
        <tr>
            <td class="td1 right">Translation:</td>
            <td class="td1">
                <textarea name="WoTranslation" class="setfocus textarea-noreturn checklength checkoutsidebmp" 
                data_maxlength="500" data_info="Translation" cols="35" rows="3"><?php echo tohtml($transl); ?></textarea>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Tags:</td>
            <td class="td1">
                <?php echo getWordTags($wid); ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Romaniz.:</td>
            <td class="td1">
                <input type="text" class="checkoutsidebmp" data_info="Romanization" name="WoRomanization" maxlength="100" size="35" 
                value="<?php echo tohtml($record['WoRomanization']); ?>" />
            </td>
        </tr>
        <tr>
            <td class="td1 right">Sentence<br />Term in {...}:</td>
            <td class="td1">
                <textarea <?php echo $scrdir; ?> name="WoSentence" 
                class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="1000" 
                data_info="Sentence" cols="35" rows="3"><?php echo tohtml($sentence); ?></textarea>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Status:</td>
            <td class="td1">
                <?php echo get_wordstatus_radiooptions($status); ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right" colspan="2">  
                <?php 
                if ($fromAnn !== '') { 
                    echo createDictLinksInEditWin2(
                        $lang, 'document.forms[0].WoSentence', 'document.forms[0].WoText'
                    );
                } else {
                    echo createDictLinksInEditWin(
                        $lang, $term, 'document.forms[0].WoSentence', isset($_GET['nodict']) ? 0 : 1
                    );
                } 
                ?>
                &nbsp; &nbsp; &nbsp; 
                <input type="submit" name="op" value="Change" />
            </td>
        </tr>
     </table>
     </form>
        <?php
            // Display example sentences button
            example_sentences_area($lang, $termlc, 'document.forms.editword.WoSentence', $wid);
        }
        mysqli_free_result($res);
    }
}

$translation_raw = repl_tab_nl(getreq("WoTranslation"));
if ($translation_raw == '') { 
    $translation = '*'; 
} else { 
    $translation = $translation_raw; 
}

$fromAnn = getreq("fromAnn"); // from-recno or empty
if (isset($_REQUEST['op'])) {
    // if (isset($_REQUEST['op']))
    edit_word_do_operation($translation, $fromAnn);
} else {  
    // FORM
    // if (! isset($_REQUEST['op']))

    // edit_word.php?tid=..&ord=..&wid=..
    edit_word_do_form($fromAnn);
}

pageend();

?>
