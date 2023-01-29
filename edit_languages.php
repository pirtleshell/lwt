<?php

/**
 * \file
 * \brief Manage languages
 * 
 * Call: edit_languages.php?....
 *      ... refresh=[langid] ... reparse all texts in lang
 *      ... del=[langid] ... do delete
 *      ... op=Save ... do insert new 
 *      ... op=Change ... do update 
 *      ... new=1 ... display new lang. screen 
 *      ... chg=[langid] ... display edit screen 
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/edit__languages_8php.html
 * @since   1.0.3
 * @since   2.4.0 Refactored with functional paradigm  
 */

require_once 'inc/session_utility.php';
require_once 'inc/classes/Language.php';


/**
 * Prepare a JavaScript code that checks for duplicate names in languages.
 * 
 * @return void
 */
function edit_languages_alert_duplicate()
{
  
    ?>

<script type="text/javascript">
    //<![CDATA[
    var LANGUAGES = <?php echo json_encode(get_languages()); ?>;

    /// Check if langname exists and its lang# != curr
    function check_dupl_lang(curr) {
        const l = $('#LgName').val();
        if (l in LANGUAGES) {
            if (curr != LANGUAGES[l]) {
                alert('Language "' + l + 
                '" already exists. Please change the language name!');
                $('#LgName').focus();
                return false;
            }
        }
        return true;
    }

    //]]>
</script>

    <?php  
}


/**
 * Refresh sentence and text items from a specific language.
 *
 * @param int $lid Language ID
 *
 * @return string Number of sentences and textitems refreshed
 *
 * @global string $tbpref Database table prefix
 */
function edit_languages_refresh($lid): string
{
    global $tbpref;
    $message2 = runsql(
        'delete from ' . $tbpref . 'sentences where SeLgID = ' . $lid, 
        "Sentences deleted"
    );
    $message3 = runsql(
        'delete from ' . $tbpref . 'textitems2 where Ti2LgID = ' . $lid, 
        "Text items deleted"
    );
    adjust_autoincr('sentences', 'SeID');
    $sql = "select TxID, TxText from " . $tbpref . "texts 
    where TxLgID = " . $lid . " 
    order by TxID";
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        $txtid = (int)$record["TxID"];
        $txttxt = $record["TxText"];
        splitCheckText($txttxt, $lid, $txtid);
    }
    mysqli_free_result($res);
    $message = $message2 . 
    " / " . $message3 . 
    " / Sentences added: " . get_first_value(
        'select count(*) as value 
        from ' . $tbpref . 'sentences 
        where SeLgID = ' . $lid
    ) . 
    " / Text items added: " . get_first_value(
        'select count(*) as value 
        from ' . $tbpref . 'textitems2 
        where Ti2LgID = ' . $lid
    );
    return $message;
}



/**
 * Delete a language.
 *
 * @param int $lid Language ID
 * 
 * @return string Info on the number of languages deleted
 * 
 * @global string $tbpref Database table prefix
 */
function edit_languages_delete($lid): string
{
    global $tbpref;
    $anztexts = get_first_value(
        'select count(TxID) as value 
        from ' . $tbpref . 'texts 
        where TxLgID = ' . $lid
    );
    $anzarchtexts = get_first_value(
        'select count(AtID) as value 
        from ' . $tbpref . 'archivedtexts 
        where AtLgID = ' . $lid
    );
    $anzwords = get_first_value(
        'select count(WoID) as value 
        from ' . $tbpref . 'words 
        where WoLgID = ' . $lid
    );
    $anzfeeds = get_first_value(
        'select count(NfID) as value 
        from ' . $tbpref . 'newsfeeds 
        where NfLgID = ' . $lid
    );
    if ($anztexts > 0 || $anzarchtexts > 0 || $anzwords > 0 || $anzfeeds > 0) {
        $message = 'You must first delete texts, archived texts, newsfeeds and words with this language!';
    } else {
        $message = runsql(
            'UPDATE ' . $tbpref . 'languages 
            SET LgName = "", LgDict1URI = "", LgDict2URI = "", 
            LgGoogleTranslateURI = "", LgExportTemplate = "", LgTextSize = DEFAULT, 
            LgCharacterSubstitutions = "", LgRegexpSplitSentences = "", 
            LgExceptionsSplitSentences = "", LgRegexpWordCharacters = "", 
            LgRemoveSpaces = DEFAULT, LgSplitEachChar = DEFAULT, 
            LgRightToLeft = DEFAULT where LgID = ' . $lid, 
            "Deleted"
        );
    }
    return $message;
}


/**
 * Save a new language to the database.
 * 
 * @return string Success or error message
 * 
 * @global string $tbpref Database table prefix
 */
function edit_languages_op_save(): string
{
    global $tbpref;
    $val = get_first_value(
        'select min(LgID) as value 
        from ' . $tbpref . 'languages 
        where LgName=""'
    );
    if (!isset($val)) {
        $message = runsql(
            'insert into ' . $tbpref . 'languages (
                LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, 
                LgExportTemplate, LgTextSize, LgCharacterSubstitutions, 
                LgRegexpSplitSentences, LgExceptionsSplitSentences, 
                LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, 
                LgRightToLeft
            ) values(' . 
                convert_string_to_sqlsyntax($_REQUEST["LgName"]) . ', ' .
                convert_string_to_sqlsyntax($_REQUEST["LgDict1URI"]) . ', '. 
                convert_string_to_sqlsyntax($_REQUEST["LgDict2URI"]) . ', '.
                convert_string_to_sqlsyntax($_REQUEST["LgGoogleTranslateURI"]) . ', '.
                convert_string_to_sqlsyntax($_REQUEST["LgExportTemplate"]) . ', '.
                $_REQUEST["LgTextSize"] . ', '.
                convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgCharacterSubstitutions"]) . ', '.
                convert_string_to_sqlsyntax($_REQUEST["LgRegexpSplitSentences"]) . ', '.
                convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgExceptionsSplitSentences"]) . ', '.
                convert_string_to_sqlsyntax($_REQUEST["LgRegexpWordCharacters"]) . ', '.
                $_REQUEST["LgRemoveSpaces"] . ', '.
                $_REQUEST["LgSplitEachChar"] . ', '.
                $_REQUEST["LgRightToLeft"] . 
            ')', 
            'Saved'
        );
    } else {
        $message = runsql(
            'update ' . $tbpref . 'languages set ' . 
            'LgName = ' . convert_string_to_sqlsyntax($_REQUEST["LgName"]) . ', ' . 
            'LgDict1URI = ' . convert_string_to_sqlsyntax($_REQUEST["LgDict1URI"]) . ', ' .
            'LgDict2URI = ' . convert_string_to_sqlsyntax($_REQUEST["LgDict2URI"]) . ', ' .
            'LgGoogleTranslateURI = ' . convert_string_to_sqlsyntax($_REQUEST["LgGoogleTranslateURI"]) . ', ' .
            'LgExportTemplate = ' . convert_string_to_sqlsyntax($_REQUEST["LgExportTemplate"]) . ', ' .
            'LgTextSize = ' . $_REQUEST["LgTextSize"] . ', ' .
            'LgCharacterSubstitutions = ' . convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgCharacterSubstitutions"]) . ', ' .
            'LgRegexpSplitSentences = ' . convert_string_to_sqlsyntax($_REQUEST["LgRegexpSplitSentences"]) . ', ' .
            'LgExceptionsSplitSentences = ' . convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgExceptionsSplitSentences"]) . ', ' .
            'LgRegexpWordCharacters = ' . convert_string_to_sqlsyntax($_REQUEST["LgRegexpWordCharacters"]) . ', ' .
            'LgRemoveSpaces = ' . $_REQUEST["LgRemoveSpaces"] . ', ' .
            'LgSplitEachChar = ' . $_REQUEST["LgSplitEachChar"] . ', ' . 
            'LgRightToLeft = ' . $_REQUEST["LgRightToLeft"] . 
            ' where LgID = ' . $val, 
            'Saved'
        );
    }
    return $message;
}

/**
 * Edit an existing text in the database.
 *
 * @param int $lid Language ID
 *
 * @return string Number of texts updated and items reparsed.
 *
 * @global string $tbpref Database table prefix
 */
function edit_languages_op_change($lid): string 
{
    global $tbpref;
    // Get old values
    $sql = "select * from " . $tbpref . "languages where LgID=" . $lid;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    if ($record == false) { 
        my_die("Cannot access language data: $sql"); 
    }
    $oldCharacterSubstitutions = $record['LgCharacterSubstitutions'];
    $oldRegexpSplitSentences = $record['LgRegexpSplitSentences'];
    $oldExceptionsSplitSentences = $record['LgExceptionsSplitSentences'];
    $oldRegexpWordCharacters = $record['LgRegexpWordCharacters'];
    $oldRemoveSpaces = $record['LgRemoveSpaces'];
    $oldSplitEachChar = $record['LgSplitEachChar'];
    mysqli_free_result($res);

    $needReParse = (
        convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgCharacterSubstitutions"]) 
        != convert_string_to_sqlsyntax_notrim_nonull($oldCharacterSubstitutions)
    ) || (
        convert_string_to_sqlsyntax($_REQUEST["LgRegexpSplitSentences"]) != 
        convert_string_to_sqlsyntax($oldRegexpSplitSentences)
    ) || (
        convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgExceptionsSplitSentences"]) 
        != convert_string_to_sqlsyntax_notrim_nonull($oldExceptionsSplitSentences)
    ) || (
        convert_string_to_sqlsyntax($_REQUEST["LgRegexpWordCharacters"]) != 
        convert_string_to_sqlsyntax($oldRegexpWordCharacters)
    ) || ($_REQUEST["LgRemoveSpaces"] != $oldRemoveSpaces) ||
    ($_REQUEST["LgSplitEachChar"] != $oldSplitEachChar);
    

    $message = runsql(
        'update ' . $tbpref . 'languages set ' . 
        'LgName = ' . convert_string_to_sqlsyntax($_REQUEST["LgName"]) . ', ' . 
        'LgDict1URI = ' . convert_string_to_sqlsyntax($_REQUEST["LgDict1URI"]) . ', ' .
        'LgDict2URI = ' . convert_string_to_sqlsyntax($_REQUEST["LgDict2URI"]) . ', ' .
        'LgGoogleTranslateURI = ' . convert_string_to_sqlsyntax($_REQUEST["LgGoogleTranslateURI"]) . ', ' .
        'LgExportTemplate = ' . convert_string_to_sqlsyntax($_REQUEST["LgExportTemplate"]) . ', ' .
        'LgTextSize = ' . $_REQUEST["LgTextSize"] . ', ' .
        'LgCharacterSubstitutions = ' . convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgCharacterSubstitutions"]) . ', ' .
        'LgRegexpSplitSentences = ' . convert_string_to_sqlsyntax($_REQUEST["LgRegexpSplitSentences"]) . ', ' .
        'LgExceptionsSplitSentences = ' . convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgExceptionsSplitSentences"]) . ', ' .
        'LgRegexpWordCharacters = ' . convert_string_to_sqlsyntax($_REQUEST["LgRegexpWordCharacters"]) . ', ' .
        'LgRemoveSpaces = ' . $_REQUEST["LgRemoveSpaces"] . ', ' .
        'LgSplitEachChar = ' . $_REQUEST["LgSplitEachChar"] . ', ' . 
        'LgRightToLeft = ' . $_REQUEST["LgRightToLeft"] . 
        ' where LgID = ' . $lid, 
        'Updated'
    );
    
    if ($needReParse) {
        runsql(
            'delete from ' . $tbpref . 'sentences where SeLgID = ' . $lid, 
            "Sentences deleted"
        );
        runsql(
            'delete from ' . $tbpref . 'textitems2 where Ti2LgID = ' . $lid, 
            "Text items deleted"
        );
        adjust_autoincr('sentences', 'SeID');
        runsql(
            "UPDATE  " . $tbpref . "words 
            SET WoWordCount  = 0 
            where WoLgID = " . $lid, 
            ''
        );
        init_word_count();
        $sql = "select TxID, TxText 
        from " . $tbpref . "texts 
        where TxLgID = " . $lid . " 
        order by TxID";
        $res = do_mysqli_query($sql);
        $cntrp = 0;
        while ($record = mysqli_fetch_assoc($res)) {
            $txtid = (int)$record["TxID"];
            $txttxt = $record["TxText"];
            splitCheckText($txttxt, $lid, $txtid);
            $cntrp++;
        }
        mysqli_free_result($res);
        $message .= " / Reparsed texts: " . $cntrp;
    } else {
        $message .= " / Reparsing not needed";
    }
    return $message;
}


function load_language($lgid)
{
    global $tbpref;

    $language = new Language();
    $language->id = $lgid;
    if ($lgid == 0) {
        // Special case: set all to empty
        $language->dict1uri = "";
        $language->dict2uri = "";
        $language->translator = "";
        $language->exporttemplate = "";
        $language->textsize = "";
        $language->charactersubst = "";
        $language->regexpsplitsent = "";
        $language->exceptionsplitsent = "";
        $language->regexpwordchar = "";
        $language->removespaces = "";
        $language->spliteachchar = "";
        $language->rightoleft = "";
    } else {
        // Load data from database
        $sql = "SELECT * FROM {$tbpref}languages WHERE LgID = $lgid";
        $res = do_mysqli_query($sql);
        $record = mysqli_fetch_assoc($res);
        $language->name =  $record["LgName"];
        $language->dict1uri = $record["LgDict1URI"];
        $language->dict2uri = $record["LgDict2URI"];
        $language->translator = $record["LgGoogleTranslateURI"];
        $language->exporttemplate = $record["LgExportTemplate"];
        $language->textsize = $record["LgTextSize"];
        $language->charactersubst = $record["LgCharacterSubstitutions"];
        $language->regexpsplitsent = $record["LgRegexpSplitSentences"];
        $language->exceptionsplitsent = $record["LgExceptionsSplitSentences"];
        $language->regexpwordchar = $record["LgRegexpWordCharacters"];
        $language->removespaces = $record["LgRemoveSpaces"];
        $language->spliteachchar = $record["LgSplitEachChar"];
        $language->rightoleft = $record["LgRightToLeft"];
        mysqli_free_result($res);
    }
    return $language;
}

function edit_language_form($language) 
{
    ?>
<script type="text/javascript">

    function checkLanguageChanged(value) {
        if (value == "Japanese") {
            $(document.forms.lg_form.LgRegexpAlt).css("display", "block");
        } else {
            $(document.forms.lg_form.LgRegexpAlt).css("display", "none");
        }
    }

    /**
     * Handles any change on multi-words translate mode.
     */
    function multiWordsTranslateChange(value) {
        const ggtranslate = <?php echo json_encode($language->translator); ?>;
        const libretranslate = "libretranslate http://localhost:5000/translate";

        let result;
        let uses_key = false;
        switch (value) {
            case "google_translate":
                result = ggtranslate;
                break;
            case "libretranslate":
                result = libretranslate;
                uses_key = true;
                break;
            case "ggl":
                result = "ggl.php?text=";
                break;
            case "glosbe":
                result = "glosbe.php";
                break;
        }
        if (result) {
            document.forms.lg_form.LgGoogleTranslateURI.value = result;
        }
        $('#LgTranslatorKeyWrapper')
        .css("display", uses_key ? "inherit" : "none");
    }

    /**
     * Check status of the requested translation API.
     */
    function checkTranslatorStatus(url) {
        if (url.startsWith("libretranslate ")) {
            try {
                const url_parts = url.split(' ');
                checkLibreTranslateStatus(url_parts[1], key=url_parts[2]);
            } catch (error) {
                $('#translator_status')
                .html('<a href="https://libretranslate.com/">LibreTranslate</a> server seems to be unreachable.' + 
                'You can install it on your server with the <a href="">LibreTranslate installation guide</a>.' + 
                'Error: ' + error); 
            }
        }
    }

    /**
     * Check LibreTranslate translator status.
     */
    function checkLibreTranslateStatus(url, key="") {
        getLibreTranslateTranslation('ping', 'en', 'es', key, url=url)
        .then(
            function (translation) {
                if (typeof translation === "string") {
                    $('#translator_status')
                    .html('<a href="https://libretranslate.com/">LibreTranslate</a> online!')
                    .attr('class', 'msgblue'); 
                } 
            },
            function (error) {
                $('#translator_status')
                .html('<a href="https://libretranslate.com/">LibreTranslate</a> server seems to be unreachable.' + 
                'You can install it on your server with the <a href="">LibreTranslate installation guide</a>.' + 
                'Error: ' + error); 
            }
        );
    }

    function changeLanguageTextSize(value) {
        $('#LgTextSizeExample').css("font-size", value + "%");
    }

    /**
     * Handle changes to the words split method.
     */
    function wordsSplitChange(value) {
        const regex = <?php echo json_encode($language->regexpwordchar); ?>;
        const mecab = "mecab";

        let result, fixed = false;
        switch (value) {
            case "regexp":
                result = regex;
                break;
            case "mecab":
                result = mecab;
                fixed = true;
                break;
        }
        if (result) {
            document.forms.lg_form.LgRegexpWordCharacters.value = result;
            //document.forms.lg_form.LgRegexpWordCharacters.disabled = fixed;
        }
    }

    $(function () { 
        checkLanguageChanged(document.forms.lg_form.LgName.value); 
    })
</script>
<form class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" 
    method="post" onsubmit="return check_dupl_lang(<?php echo $language->id; ?>);" 
    name="lg_form">
    <input type="hidden" name="LgID" value="<?php echo $language->id; ?>" />
    <table class="tab2" cellspacing="0" cellpadding="5">
    <tr>
        <td class="td1 right">Study Language "L2":</td>
        <td class="td1">
            <input type="text" class="notempty setfocus checkoutsidebmp" 
            data_info="Study Language" name="LgName" id="LgName" 
            value="<?php echo tohtml($language->name); ?>" maxlength="40" 
            size="40" oninput="checkLanguageChanged(this.value);" /> 
            <img src="icn/status-busy.png" title="Field must not be empty" 
            alt="Field must not be empty" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Dictionary 1 URI:</td>
        <td class="td1">
            <input type="text" class="notempty checkdicturl checkoutsidebmp" 
            name="LgDict1URI" 
            value="<?php echo tohtml($language->dict1uri); ?>"  
            maxlength="200" size="60" data_info="Dictionary 1 URI" /> 
            <img src="icn/status-busy.png" title="Field must not be empty" 
            alt="Field must not be empty" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Dictionary 2 URI:</td>
        <td class="td1">
            <input type="text" class="checkdicturl checkoutsidebmp" 
            name="LgDict2URI" 
            value="<?php echo tohtml($language->dict2uri); ?>" maxlength="200"
            size="60" data_info="Dictionary 2 URI" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Sentence Translator URI:</td>
        <td class="td1">
            <select onchange="multiWordsTranslateChange(this.value);" 
            name="LgTranslatorName">
                <option value="google_translate">Google Translate URI</option>
                <option value="libretranslate">LibreTranslate API</option>
                <!-- ggl.php doesn't seem to work -->
                <option value="ggl" style="display: none;">
                    GoogleTranslate API
                </option>
                <!-- Glosbe has stopped the API -->
                <option value="glosbe" style="display: none;">
                    Glosbe API
                </option>
            </select>
            <input type="text" class="checkdicturl checkoutsidebmp" 
            name="LgGoogleTranslateURI" 
            value="<?php echo tohtml($language->translator); ?>" 
            maxlength="200" size="60" data_info="GoogleTranslate URI" 
            oninput="checkTranslatorStatus(this.value);"/>
            <div id="LgTranslatorKeyWrapper" style="display: none;">
                <label for="LgTranslatorKey">Key :</label>
                <input type="text" id="LgTranslatorKey" name="LgTranslatorKey"/>
            </div>
            <div id="translator_error" class="red" ></div>
        </td>
    </tr>
    <tr>
        <td class="td1 right">Text Size (%):</td>
        <td class="td1">
            <input name="LgTextSize" type="number" min="100" max="250" 
            value="<?php echo $language->textsize; ?>" step="50" 
            onchange="changeLanguageTextSize(this.value);"/>
            <input type="text" 
            style="font-size: <?php echo $language->textsize ?>%;" 
            id="LgTextSizeExample" 
            value="Text will be this size" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Character Substitutions:</td>
        <td class="td1">
            <input type="text" class="checkoutsidebmp" 
            data_info="Character Substitutions" name="LgCharacterSubstitutions" 
            value="<?php echo tohtml($language->charactersubst); ?>" 
            maxlength="500" size="60" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">RegExp Split Sentences:</td>
        <td class="td1">
            <input type="text" class="notempty checkoutsidebmp" 
            name="LgRegexpSplitSentences" 
            value="<?php echo tohtml($language->regexpsplitsent); ?>" 
            maxlength="500" size="60" 
            data_info="RegExp Split Sentences" /> 
            <img src="icn/status-busy.png" title="Field must not be empty" 
            alt="Field must not be empty" />
        </td>
    </tr>
    <tr>
    <td class="td1 right">Exceptions Split Sentences:</td>
    <td class="td1">
        <input type="text" class="checkoutsidebmp" 
        data_info="Exceptions Split Sentences" 
        name="LgExceptionsSplitSentences" 
        value="<?php echo tohtml($language->exceptionsplitsent); ?>" 
        maxlength="500" size="60" />
    </td>
    </tr>
    <tr>
        <td class="td1 right">RegExp Word Characters:</td>
        <td class="td1">
            <select onchange="wordsSplitChange(this.value);" style="display: none;" name="LgRegexpAlt">
                <option value="regexp">Regular Expressions (demo)</option>
                <option value="mecab">MeCab (recommended)</option>
            </select>
            <input type="text" class="notempty checkoutsidebmp" 
            data_info="RegExp Word Characters" name="LgRegexpWordCharacters" 
            value="<?php echo tohtml($language->regexpwordchar); ?>" 
            maxlength="500" size="60" /> 
            <img src="icn/status-busy.png" title="Field must not be empty" 
            alt="Field must not be empty" />
            <div style="display: none;" class="red" id="mecab_not_installed">
                <a href="https://en.wikipedia.org/wiki/MeCab">MeCab</a> does 
                not seem to be installed on your server. 
                Please read the <a href="">MeCab installation guide</a>.
            </div>
        </td>
    </tr>
    <tr>
    <td class="td1 right">Make each character a word:</td>
    <td class="td1">
        <select name="LgSplitEachChar">
            <?php echo get_yesno_selectoptions($language->spliteachchar); ?>
        </select>
        (e.g. for Chinese, Japanese, etc.)</td>
    </tr>
    <tr>
    <td class="td1 right">Remove spaces:</td>
    <td class="td1">
        <select name="LgRemoveSpaces">
            <?php echo get_yesno_selectoptions($language->removespaces); ?>
        </select>
        (e.g. for Chinese, Japanese, etc.)</td>
    </tr>
    <tr>
    <td class="td1 right">Right-To-Left Script:</td>
    <td class="td1">
        <select name="LgRightToLeft">
            <?php echo get_yesno_selectoptions($language->rightoleft); ?>
        </select>
        (e.g. for Arabic, Hebrew, Farsi, Urdu,  etc.)</td>
    </tr>
    <tr>
    <td class="td1 right">
        Export Template 
        <img class="click" src="icn/question-frame.png" title="Help" alt="Help" onclick="oewin('export_template.html');" /> :
    </td>
    <td class="td1">
        <input type="text" class="checkoutsidebmp" data_info="Export Template" name="LgExportTemplate" 
        value="<?php echo tohtml($language->exporttemplate); ?>" maxlength="1000" size="60" />
    </td>
    </tr>
    <tr>
    <td class="td1 right" colspan="2">
        <input type="button" value="Cancel" onclick="{resetDirty(); location.href='edit_languages.php';}" /> 
        <?php 
        if ($language->id == 0) {
            echo '<input type="submit" name="op" value="Save" />';
        } else {
            echo '<input type="submit" name="op" value="Change" />';
        }
        ?>
        </td>
    </tr>
    </table>
</form>
    <?php

}

/**
 * Display a form to create a new language.
 * 
 * @return void
 */
function edit_languages_new() 
{
    ?>
    <h2>
        New Language <a target="_blank" href="docs/info.html#howtolang">
        <img src="icn/question-frame.png" title="Help" alt="Help" /></a>
    </h2>

    <script type="text/javascript" charset="utf-8">
        $(document).ready(ask_before_exiting);
    </script>
    <div class="td1 center backlightyellow" style="border-top-left-radius:inherit;border-top-right-radius:inherit;" colspan="2">
        <img src="icn/wizard.png" title="Language Settings Wizard" alt="Language Settings Wizard" class="click" onclick="window.open('select_lang_pair.php', 'wizard', 'width=400, height=400, scrollbars=yes, menubar=no, resizable=yes, status=no');" /><br />
        <span class="click" onclick="window.open('select_lang_pair.php', 'wizard', 'width=400, height=400, scrollbars=yes, menubar=no, resizable=yes, status=no');">
            <img src="icn/arrow-000-medium.png" title="-&gt;" alt="-&gt;" /> 
            <b>Language Settings Wizard</b> 
            <img src="icn/arrow-180-medium.png" title="&lt;-" alt="&lt;-" />
        </span><br />
        <span class="smallgray">
            Select your native (L1) and study (L2) languages, and let the 
            wizard set all language settings marked in yellow!<br />
            (You can adjust the settings afterwards.)
        </span>
    </div>
    <?php
    $language = load_language(0);
    edit_language_form($language);
    ?>
    <p class="smallgray">
        <b>Important:</b>
        <br />
        The placeholders "••" for the from/sl and dest/tl language codes in the 
        URIs must be <b>replaced</b> by the actual source and target language 
        codes!<br />
        <a href="docs/info.html#howtolang" target="_blank">Please read the documentation</a>. 
        Languages with a <b>non-Latin alphabet need special attention</b>, 
        <a href="docs/info.html#langsetup" target="_blank">see also here</a>.
    </p>    
    <?php
}

/**
 * Display a form to edit an existing language.
 * 
 * @param {int} $lid Language ID
 * 
 * @return void
 */
function edit_languages_change($lid)
{
    global $tbpref;
    $sql = 'select * from ' . $tbpref . 'languages where LgID = ' . $lid;
    $res = do_mysqli_query($sql);
    if (mysqli_fetch_assoc($res)) {
    ?>
    <script type="text/javascript" charset="utf-8">
        $(document).ready(ask_before_exiting);
    </script>
    <h2>Edit Language 
        <a target="_blank" href="docs/info.html#howtolang">
            <img src="icn/question-frame.png" title="Help" alt="Help" />
        </a>
    </h2>
        <?php
        $language = load_language($lid);
        edit_language_form($language);
        ?>
    <p class="smallgray">
        <b>Warning:</b> Changing certain language settings 
        (e.g. RegExp Word Characters, etc.)<br />
        may cause partial or complete loss of improved annotated texts!
    </p>
    <?php
    }
    mysqli_free_result($res);
}

/**
 * Display the standard page of saved languages.
 * 
 * @param {string} $message An information message to display.
 * 
 * @global {string} $tbpref Database table prefix
 * @global {int}    $debug 1 to display debugging data
 * 
 * @return void
 */
function edit_languages_display($message)
{
    global $tbpref, $debug;

    echo error_message_with_hide($message, 0);
    
    $current = (int) getSetting('currentlanguage');
    
    $recno = get_first_value(
        'SELECT COUNT(*) AS value 
        FROM ' . $tbpref . 'languages 
        WHERE LgName<>""'
    ); 
    
    ?>

<p>
    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?new=1">
        <img src="icn/plus-button.png" title="New" alt="New" /> New Language ...
    </a>
</p>

    <?php
    if ($recno==0) {
        ?>
<p>No languages found.</p>
        <?php
        return;
    }
    ?>

<table class="sortable tab2" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1 sorttable_nosort">Curr.<br />Lang.</th>
        <th class="th1 sorttable_nosort">Test<br />↓↓↓</th>
        <th class="th1 sorttable_nosort">Actions</th>
        <th class="th1 clickable">Language</th>
        <th class="th1 sorttable_numeric clickable">Texts,<br />Reparse</th>
        <th class="th1 sorttable_numeric clickable">Arch.<br />Texts</th>
        <th class="th1 sorttable_numeric clickable">Newsfeeds<br />(Articles)</th>
        <th class="th1 sorttable_numeric clickable">Terms</th>
        <th class="th1 sorttable_nosort">Export<br />Template?</th>
    </tr>

    <?php

    $sql = 'SELECT LgID, LgName, LgExportTemplate 
    FROM ' . $tbpref . 'languages 
    WHERE LgName<>"" ORDER BY LgName';
    if ($debug) { 
        echo $sql; 
    }
    // May be refactored with KISS principle
    $res = do_mysqli_query(
        'select NfLgID, count(*) as value 
        from ' . $tbpref . 'newsfeeds 
        group by NfLgID'
    );
    $newsfeedcount = null;
    while ($record = mysqli_fetch_assoc($res)) {
        $newsfeedcount[$record['NfLgID']] = $record['value'];
    }
    // May be refactored with KISS principle
    $res = do_mysqli_query(
        'SELECT NfLgID, count(*) AS value 
        FROM ' . $tbpref . 'newsfeeds, ' . $tbpref . 'feedlinks 
        WHERE NfID=FlNfID 
        GROUP BY NfLgID'
    );
    $feedarticlescount = null;
    while ($record = mysqli_fetch_assoc($res)) {
        $feedarticlescount[$record['NfLgID']] = $record['value'];
    }
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        $lid = (int)$record['LgID'];
        $foo = get_first_value(
            'select count(TxID) as value 
            from ' . $tbpref . 'texts 
            where TxLgID=' . $lid
        );
        $textcount = is_numeric($foo) ? (int)$foo : 0;
        $foo = get_first_value(
            'select count(AtID) as value 
            from ' . $tbpref . 'archivedtexts 
            where AtLgID=' . $lid
        );
        $archtextcount = is_numeric($foo) ? (int)$foo : 0;
        $foo = get_first_value(
            'select count(WoID) as value 
            from ' . $tbpref . 'words 
            where WoLgID=' . $lid
        );
        $wordcount = is_numeric($foo) ? (int)$foo : 0;
        if (is_null($newsfeedcount) || empty($newsfeedcount)) {
            $nfcount = 0;
        } else if (isset($newsfeedcount[$lid])) {
            $nfcount = (int)$newsfeedcount[$lid];
        } else {
            $nfcount = 0;
        }
        if (is_null($feedarticlescount) || empty($feedarticlescount)) {
            $fartcount = 0;
        } else if (isset($feedarticlescount[$lid])) {
            $fartcount = (int)$feedarticlescount[$lid];
        } else {
            $fartcount = 0;
        }
        echo '<tr>';
        if ($current == $lid) {
            $tdth = 'th';
            echo '<th class="th1" style="border-top-left-radius:0;">
                <img src="icn/exclamation-red.png" title="Current Language" alt="Current Language" />
                </th>';
        } else {
            $tdth = 'td';
            echo '<td class="td1 center">
                <a href="inc/save_setting_redirect.php?k=currentlanguage&amp;v=' . $lid . '&amp;u=edit_languages.php">
                <img src="icn/tick-button.png" title="Set as Current Language" alt="Set as Current Language" />
                </a>
                </td>';
        }
        echo '<' . $tdth . ' class="' . $tdth . '1 center"><a href="do_test.php?lang=' . $lid . '">
            <img src="icn/question-balloon.png" title="Test" alt="Test" /></a></' . $tdth . '>';
        echo '<' . $tdth . ' class="' . $tdth . '1 center" nowrap="nowrap">&nbsp;<a href="' . $_SERVER['PHP_SELF'] . '?chg=' . $lid . '">
            <img src="icn/document--pencil.png" title="Edit" alt="Edit" /></a>';
        if ($textcount == 0 && $archtextcount == 0 && $wordcount == 0 && $nfcount == 0) { 
            echo '&nbsp; <span class="click" onclick="if (confirmDelete()) location.href=\'' . $_SERVER['PHP_SELF'] . '?del=' . $lid . '\';">
                <img src="icn/minus-button.png" title="Delete" alt="Delete" /></span>'; 
        } else { 
            echo '&nbsp; <img src="icn/placeholder.png" title="Delete not possible" alt="Delete not possible" />'; 
        }
        echo '&nbsp;</' . $tdth . '>';
        echo '<' . $tdth . ' class="' . $tdth . '1 center">' . tohtml((string)$record['LgName']) . '</' . $tdth . '>';
        if ($textcount > 0) { 
            echo '<' . $tdth . ' class="' . $tdth . '1 center">
                <a href="edit_texts.php?page=1&amp;query=&amp;filterlang=' . $lid . '">' . 
            $textcount . '</a> &nbsp;&nbsp; <a href="' . $_SERVER['PHP_SELF'] . '?refresh=' . $lid . '">
                <img src="icn/lightning.png" title="Reparse Texts" alt="Reparse Texts" /></a>'; 
        } else {
            echo '<' . $tdth . ' class="' . $tdth . '1 center">0 &nbsp;&nbsp; <img src="';
            print_file_path('icn/placeholder.png');
            echo'" title="No texts to reparse" alt="No texts to reparse" />';
        }
        echo '</' . $tdth . '>';
        echo '<' . $tdth . ' class="' . $tdth . '1 center">' . 
        ($archtextcount > 0 ? '<a href="edit_archivedtexts.php?page=1&amp;query=&amp;filterlang=' . $lid . '">' . 
        $archtextcount . '</a>' : '0' ) . '</' . $tdth . '>';
        echo '<' . $tdth . ' class="' . $tdth . '1 center">' . 
        ($nfcount > 0 ? '<a href="do_feeds.php?query=&amp;selected_feed=&amp;check_autoupdate=1&amp;filterlang=' . $lid . '">' . 
        $nfcount . ' (' . $fartcount . ')</a>' : '0' ) . '</' . $tdth . '>';
        echo '<' . $tdth . ' class="' . $tdth . '1 center">' . 
        ($wordcount > 0 ? '<a href="edit_words.php?page=1&amp;query=&amp;text=&amp;status=&amp;filterlang=' . 
        $lid . '&amp;status=&amp;tag12=0&amp;tag2=&amp;tag1=">' . $wordcount . '</a>' : '0' ) . '</' . $tdth . '>';
        echo '<' . $tdth . ' class="' . $tdth . '1 center" style="border-top-right-radius:0;">' . 
        (isset($record['LgExportTemplate']) ? '<img src="icn/status.png" title="Yes" alt="Yes" />' : 
        '<img src="icn/status-busy.png" title="No" alt="No" />' ) . '</' . $tdth . '>';
        echo '</tr>';
    }
    mysqli_free_result($res);

    ?>

</table>

        <?php
    
}

/**
 * Display a variation of the edit_language page.
 * 
 * @return void
 */
function edit_languages_do_page() 
{
    pagestart('My Languages', true);
    edit_languages_alert_duplicate();
    $message = '';
    if (isset($_REQUEST['refresh'])) {
        $message = edit_languages_refresh($_REQUEST['refresh']);
    }
    if (isset($_REQUEST['del'])) {
        $message = edit_languages_delete((int)$_REQUEST['del']);
    } elseif (isset($_REQUEST['op'])) {
        // Insert new text or change an existing one 
        if ($_REQUEST['op'] == 'Save') {
            // INSERT
            $message = edit_languages_op_save();
        } elseif ($_REQUEST['op'] == 'Change') {
            // UPDATE
            $message = edit_languages_op_change((int)$_REQUEST["LgID"]);
        }
    }

    // Display part

    if (isset($_REQUEST['new'])) {
        edit_languages_new();
    } elseif (isset($_REQUEST['chg'])) {
        edit_languages_change((int)$_REQUEST['chg']);
    } else {
        edit_languages_display($message);
    }
    pageend();
}

edit_languages_do_page();
?> 
