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
require_once 'inc/langdefs.php';
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
        "SELECT MIN(LgID) AS value FROM {$tbpref}languages WHERE LgName=''"
    );
    if (!isset($val)) {
        $message = runsql(
            "INSERT INTO {$tbpref}languages (
                LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, 
                LgExportTemplate, LgTextSize, LgCharacterSubstitutions, 
                LgRegexpSplitSentences, LgExceptionsSplitSentences, 
                LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, 
                LgRightToLeft
            ) VALUES(" . 
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
                ((int)isset($_REQUEST["LgRemoveSpaces"])) . ', '.
                ((int)isset($_REQUEST["LgSplitEachChar"])) . ', '.
                ((int)isset($_REQUEST["LgRightToLeft"])) . 
            ')', 
            'Saved'
        );
    } else {
        $message = runsql(
            "UPDATE {$tbpref}languages SET " . 
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
            'LgRemoveSpaces = ' . ((int)isset($_REQUEST["LgRemoveSpaces"])) . ', ' .
            'LgSplitEachChar = ' . ((int)isset($_REQUEST["LgSplitEachChar"])) . ', ' . 
            'LgRightToLeft = ' . ((int)isset($_REQUEST["LgRightToLeft"])) . 
            " WHERE LgID = $val", 
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
    $sql = "SELECT * FROM {$tbpref}languages where LgID = $lid";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    if ($record == false) { 
        my_die("Cannot access language data: $sql"); 
    }

    $needReParse = (
        convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgCharacterSubstitutions"]) 
        != convert_string_to_sqlsyntax_notrim_nonull($record['LgCharacterSubstitutions'])
    ) || (
        convert_string_to_sqlsyntax($_REQUEST["LgRegexpSplitSentences"]) != 
        convert_string_to_sqlsyntax($record['LgRegexpSplitSentences'])
    ) || (
        convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgExceptionsSplitSentences"]) 
        != convert_string_to_sqlsyntax_notrim_nonull($record['LgExceptionsSplitSentences'])
    ) || (
        convert_string_to_sqlsyntax($_REQUEST["LgRegexpWordCharacters"]) != 
        convert_string_to_sqlsyntax($record['LgRegexpWordCharacters'])
    ) || ((isset($_REQUEST["LgRemoveSpaces"]) ? 1 : 0) != $record['LgRemoveSpaces']) ||
    ((isset($_REQUEST["LgSplitEachChar"]) ? 1 : 0) != $record['LgSplitEachChar']);

    mysqli_free_result($res);
    

    $message = runsql(
        "UPDATE {$tbpref}languages SET " . 
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
        'LgRemoveSpaces = ' . ((int)isset($_REQUEST["LgRemoveSpaces"])) . ', ' .
        'LgSplitEachChar = ' . ((int)isset($_REQUEST["LgSplitEachChar"])) . ', ' . 
        'LgRightToLeft = ' . ((int)isset($_REQUEST["LgRightToLeft"])) . 
        " WHERE LgID = $lid", 
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
            "UPDATE {$tbpref}words SET WoWordCount = 0 WHERE WoLgID = $lid", 
            ''
        );
        init_word_count();
        $sql = "SELECT TxID, TxText FROM {$tbpref}texts 
        WHERE TxLgID = $lid ORDER BY TxID";
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

/**
 * Load a language object based in language ID.
 * 
 * @param int $lgid Language ID, if 0 load empty data.
 * 
 * @return Language Created object
 * 
 * @global string $tbpref
 */
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


/**
 * Create the form for a language.
 * 
 * @param Language $language Language object
 */
function edit_language_form($language) 
{
    global $langDefs;
    $sourceLg = '';
    $targetLg = '';
    $currentnativelanguage = getSetting('currentnativelanguage'); 
    if (array_key_exists($currentnativelanguage, $langDefs)) {
        $targetLg = $langDefs[$currentnativelanguage][1];
    }
    if ($language->name) {
        if (array_key_exists($language->name, $langDefs)) {
            $sourceLg = $langDefs[$language->name][1];
        }
        $lgFromDict = langFromDict($language->translator); 
        if ($lgFromDict != '' && $lgFromDict != $sourceLg) {
            $sourceLg = $lgFromDict;
        }


        $targetFromDict = targetLangFromDict($language->translator);
        if ($targetFromDict != '' && $targetFromDict != $targetLg) {
            $targetLg = $targetFromDict;
        }
    }
    ?>
<script type="text/javascript">

    const new_language = <?php echo json_encode($language->name == null); ?>;

    function reloadDictURLs(sourceLg='auto', targetLg='en') {

        let base_url = window.location.href;
        base_url = base_url.substring(0, base_url.lastIndexOf('/'));

        GGTRANSLATE = 'https://translate.google.com/?' + $.param({
                ie: "UTF-8",
                sl: sourceLg,
                tl: targetLg,
                text: 'lwt_term'
        });

        LIBRETRANSLATE = 'http://localhost:5000/?' + $.param({
            lwt_translator: 'libretranslate',
            source: sourceLg,
            target: targetLg,
            q: "lwt_term"
        });

        GGL = base_url + '/ggl.php/?' + $.param({
            sl: sourceLg, tl: targetLg, text: 'lwt_term'
        });
    }

    reloadDictURLs(
        <?php echo json_encode($sourceLg); ?>, 
        <?php echo json_encode($targetLg); ?>
    );

    /**
     * Check for specific language option based on language name 
     */
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
        let result;
        let uses_key = false;
        let base_url = window.location.href;
        base_url = base_url.replace('/edit_languages.php', '/');
        switch (value) {
            case "google_translate":
                result = GGTRANSLATE;
                break;
            case "libretranslate":
                result = LIBRETRANSLATE;
                uses_key = true;
                break;
            case "ggl":
                result = GGL;
                break;
            case "glosbe":
                result = base_url + "glosbe.php";
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
        if (url.startsWith('*')) {
            url = url.substring(1);
        }
        const url_obj = new URL(url);
        const params = url_obj.searchParams;
        if (params.get('lwt_translator') == 'libretranslate') {
            try {
                checkLibreTranslateStatus(url_obj, key=params.key);
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
        const trans_url = new URL(url);
        trans_url.searchParams.append('lwt_key', key);
        getLibreTranslateTranslation(trans_url, 'ping', 'en', 'es')
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

    /**
     * Change the size of demo text.
     */
    function changeLanguageTextSize(value) {
        $('#LgTextSizeExample').css("font-size", value + "%");
    }

    /**
     * Handle changes to the words split method.
     */
    function wordCharChange(value) {
        const regex = LANGDEFS[<?php echo json_encode($language->name); ?>][3];
        const mecab = "mecab";

        let result;
        switch (value) {
            case "regexp":
                result = regex;
                break;
            case "mecab":
                result = mecab;
                break;
        }
        if (result) {
            document.forms.lg_form.LgRegexpWordCharacters.value = result;
        }
    }

    /**
     * Build a dictionary/translator URL with the pop-up option
     */
    function addPopUpOption(url, checked) {
        if (url.startsWith('*')) {
            url = url.substring(1);
        }
        const built_url = new URL(url);
        // Remove trivial cases
        if (checked && built_url.searchParams.has('lwt_popup'))
            return built_url.href;
        if (!checked && !built_url.searchParams.has('lwt_popup'))
            return built_url.href;
        // Now we should change status
        if (checked) {
            built_url.searchParams.append('lwt_popup', 'true');
            return built_url.href;
        }
        built_url.searchParams.delete('lwt_popup');
        return built_url.href;
    }

    /**
     * Change the Pop-Up URL of dictionary.
     */
    function changePopUpState(elem) {
        const l_form = document.forms.lg_form;
        let target;
        switch (elem.name) {
            case "LgDict1PopUp":
                target = l_form.LgDict1URI;
                break;
            case "LgDict2PopUp":
                target = l_form.LgDict2URI;
                break;
            case "LgGoogleTranslatePopUp":
                target = l_form.LgGoogleTranslateURI;
                break;
        }
        target.value = addPopUpOption(target.value, elem.checked);
    }

    /**
     * Change Pop-Up checkboxes based on input box value. 
     */
    function checkDictionaryChanged(input_box) {
        const l_form = document.forms.lg_form;
        switch (input_box.name) {
            case "LgDict1URI":
                target = l_form.LgDict1PopUp;
                break;
            case "LgDict2URI":
                target = l_form.LgDict2PopUp;
                break;
            case "LgGoogleTranslateURI":
                target = l_form.LgGoogleTranslatePopUp;
                break;
        }
        let popup = false;
        if (input_box.value.startsWith('*')) {
            input_box.value = input_box.value.substring(1);
            popup = true;
        }
        popup |= (new URL(input_box.value)).searchParams.has("lwt_popup");
        target.checked = popup;
    }

    /**
     * Modify the value of the translator select box if not coherent with the URL.
     */
    function checkTranslatorType(url, type_select) {
        const parsed_url = new URL(url);
        let final_value;
        switch (parsed_url.searchParams.get("lwt_translator")) {
            case "libretranslate":
                // Using LibreTranslate
                final_value = "libretranslate";
                break;
            default:
                // Defaulting to Google
                final_value = "google_translate";
                break;
        }
        type_select.value = final_value;
    }

    /**
     * Check if all fields are coherent with translator URL.
     */
    function checkTranslatorChanged(translator_input) {
        checkTranslatorStatus(translator_input.value);
        checkDictionaryChanged(translator_input);
        checkTranslatorType(
            translator_input.value, document.forms.lg_form.LgTranslatorName
        );
    }

    /**
     * Check the word splitting method.
     */
    function checkWordChar(method) {
        document.forms.lg_form.LgRegexpAlt.value = (method == "mecab") ? "mecab" : "regex";
    }

    /**
     * Check if the help field are coherent with the input fields.
     * 
     * param {element} l_form Language form.
     */
    function checkLanguageForm(l_form) {
        checkLanguageChanged(l_form.LgName.value);
        checkDictionaryChanged(l_form.LgDict1URI);
        checkDictionaryChanged(l_form.LgDict2URI);
        checkTranslatorChanged(l_form.LgGoogleTranslateURI);
        checkWordChar(l_form.LgRegexpWordCharacters.value);
    }

    $(function () { checkLanguageForm(document.forms.lg_form); });
</script>
<form class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" 
    method="post" onsubmit="return check_dupl_lang(<?php echo $language->id; ?>);" 
    name="lg_form">
    <input type="hidden" name="LgID" value="<?php echo $language->id; ?>" />
    <table class="tab1" cellspacing="0" cellpadding="5">
    <tr>
        <td class="td1 right">Study Language "L2":</td>
        <td class="td1">
            <input type="text" class="notempty setfocus checkoutsidebmp respinput" 
            data_info="Study Language" name="LgName" id="LgName" 
            value="<?php echo tohtml($language->name); ?>" maxlength="40" 
            oninput="checkLanguageChanged(this.value);" /> 
            <img src="icn/status-busy.png" title="Field must not be empty" 
            alt="Field must not be empty" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Dictionary 1 URI:</td>
        <td class="td1">
            <input type="url" class="notempty checkdicturl checkoutsidebmp respinput" 
            name="LgDict1URI" 
            value="<?php echo tohtml($language->dict1uri); ?>"  
            maxlength="200" data_info="Dictionary 1 URI" 
            oninput="checkDictionaryChanged(this);" />
            
            <br />
            <input type="checkbox" name="LgDict1PopUp" id="LgDict1PopUp" 
            onchange="changePopUpState(this);" />
            
            <label for="LgDict1PopUp" 
            title="Open in a new window. Some dictionaries cannot be displayed in iframes">
                Open in Pop-Up
            </label>
            <img src="icn/status-busy.png" title="Field must not be empty" 
            alt="Field must not be empty" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Dictionary 2 URI:</td>
        <td class="td1">
            <input type="url" class="checkdicturl checkoutsidebmp respinput" 
            name="LgDict2URI" 
            value="<?php echo tohtml($language->dict2uri); ?>" maxlength="200"
            data_info="Dictionary 2 URI"
            oninput="checkDictionaryChanged(this);" />
            
            <br />
            <input type="checkbox" name="LgDict2PopUp" id="LgDict2PopUp" 
            onchange="changePopUpState(this);" />
            
            <label for="LgDict2PopUp"
            title="Open in a new window. Some dictionaries cannot be displayed in iframes">
                Open in Pop-Up
            </label>
        </td>
    </tr>
    <tr>
        <td class="td1 right">Sentence Translator URI:</td>
        <td class="td1">
            <select onchange="multiWordsTranslateChange(this.value);" 
            name="LgTranslatorName">
                <option value="google_translate">Google Translate (webpage)</option>
                <option value="libretranslate">LibreTranslate API</option>
                <option value="ggl">
                    GoogleTranslate API
                </option>
                <!-- Glosbe has stopped the API -->
                <option value="glosbe" style="display: none;">
                    Glosbe API
                </option>
            </select>
            <input type="url" class="checkdicturl checkoutsidebmp respinput" 
            name="LgGoogleTranslateURI" 
            value="<?php echo tohtml($language->translator); ?>" 
            maxlength="200" data_info="GoogleTranslate URI" 
            oninput="checkTranslatorChanged(this);" class="respinput"
             />

            <div id="LgTranslatorKeyWrapper" style="display: none;">
                <label for="LgTranslatorKey">Key :</label>
                <input type="text" id="LgTranslatorKey" name="LgTranslatorKey"/>
            </div>
            <br />
            <input type="checkbox" name="LgGoogleTranslatePopUp" 
            id="LgGoogleTranslatePopUp" onchange="changePopUpState(this);"/>
            <label for="LgGoogleTranslatePopUp"
            title="Open in a new window. Some translators cannot be displayed in iframes">
                Open in Pop-Up
            </label>
            <div id="translator_error" class="red" ></div>
        </td>
    </tr>
    <tr>
        <td class="td1 right">Text Size (%):</td>
        <td class="td1">
            <input name="LgTextSize" type="number" min="100" max="250" 
            value="<?php echo $language->textsize; ?>" step="50" 
            onchange="changeLanguageTextSize(this.value);" class="respinput" />
            <input type="text" class="respinput"
            style="font-size: <?php echo $language->textsize ?>%;" 
            id="LgTextSizeExample" 
            value="Text will be this size" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Character Substitutions:</td>
        <td class="td1">
            <input type="text" class="checkoutsidebmp respinput" 
            data_info="Character Substitutions" name="LgCharacterSubstitutions" 
            value="<?php echo tohtml($language->charactersubst); ?>" 
            maxlength="500" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">RegExp Split Sentences:</td>
        <td class="td1">
            <input type="text" class="notempty checkoutsidebmp respinput" 
            name="LgRegexpSplitSentences" 
            value="<?php echo tohtml($language->regexpsplitsent); ?>" 
            maxlength="500"
            data_info="RegExp Split Sentences" /> 
            <img src="icn/status-busy.png" title="Field must not be empty" 
            alt="Field must not be empty" />
        </td>
    </tr>
    <tr>
    <td class="td1 right">Exceptions Split Sentences:</td>
    <td class="td1">
        <input type="text" class="checkoutsidebmp respinput" 
        data_info="Exceptions Split Sentences" 
        name="LgExceptionsSplitSentences"
        value="<?php echo tohtml($language->exceptionsplitsent); ?>" 
        maxlength="500" />
    </td>
    </tr>
    <tr>
        <td class="td1 right">RegExp Word Characters:</td>
        <td class="td1">
            <select onchange="wordCharChange(this.value);" style="display: none;" 
            name="LgRegexpAlt">
                <option value="regexp">Regular Expressions (demo)</option>
                <option value="mecab">MeCab (recommended)</option>
            </select>
            <input type="text" class="notempty checkoutsidebmp respinput" 
            data_info="RegExp Word Characters" name="LgRegexpWordCharacters" 
            value="<?php echo tohtml($language->regexpwordchar); ?>" 
            maxlength="500" /> 
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
            <input type="checkbox" name="LgSplitEachChar" id="LgSplitEachChar" 
            value="1" <?php echo $language->spliteachchar ? "checked" : ""; ?> />
            <label for="LgSplitEachChar">(e.g. for Chinese, Japanese, etc.)</label>
        </td>
    </tr>
    <tr>
        <td class="td1 right">Remove spaces:</td>
        <td class="td1">
            <input type="checkbox" name="LgRemoveSpaces" id="LgRemoveSpaces" 
            value="1" <?php echo $language->removespaces ? "checked" : ""; ?> />
            <label for="LgRemoveSpaces">(e.g. for Chinese, Japanese, etc.)</label>
        </td>
    </tr>
    <tr>
        <td class="td1 right">Right-To-Left Script:</td>
        <td class="td1">
            <input type="checkbox" name="LgRightToLeft" id="LgRightToLeft" 
            value="1" <?php echo $language->rightoleft ? "checked" : ""; ?> />
            <label for="LgRightToLeft">
                (e.g. for Arabic, Hebrew, Farsi, Urdu,  etc.)
            </label>
        </td>
    </tr>
    <tr>
        <td class="td1 right">
            Export Template 
            <img class="click" src="icn/question-frame.png" title="Help" alt="Help" 
            onclick="oewin('export_template.html');" /> :
        </td>
        <td class="td1">
            <input type="text" class="checkoutsidebmp" data_info="Export Template" 
            name="LgExportTemplate" class="respinput"
            value="<?php echo tohtml($language->exporttemplate); ?>" 
            maxlength="1000" />
        </td>
    </tr>
    <tr>
        <td class="td1 right" colspan="2">
            <input type="button" value="Cancel" 
            onclick="{resetDirty(); location.href='edit_languages.php';}" /> 
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
 * Returns a dropdown menu of the different languages.
 * 
 * @param string $currentnativelanguage Default language
 * 
 * @global mixed $langDefs
 */
function get_wizard_selectoptions($currentnativelanguage): string 
{
    global $langDefs;
    $r = "<option value=\"\"" . get_selected($currentnativelanguage, "") . 
    ">[Choose...]</option>";
    $keys = array_keys($langDefs);
    foreach ($keys as $item) {
        $r .= "<option value=\"" . $item . "\"" . 
        get_selected($currentnativelanguage, $item) . ">" . $item . "</option>";
    }
    return $r;
}

/**
 * Display a form to create a new language.
 * 
 * @return void
 */
function edit_languages_new() 
{
    global $langDefs;

    $currentnativelanguage = getSetting('currentnativelanguage');
    ?>
    <h2>
        New Language 
        <a target="_blank" href="docs/info.html#howtolang">
            <img src="icn/question-frame.png" title="Help" alt="Help" />
        </a>
    </h2>

    <script type="text/javascript" charset="utf-8">

        const LANGDEFS = <?php echo json_encode($langDefs); ?>;

        /**
         * Main variable for the language selection wizard. 
         */
        const language_wizard = {

            /**
             * Fetches langauge data and launches wizard. 
             */
            go: function () {
                const l1 = $('#l1').val();
                const l2 = $('#l2').val();
                if (l1 == '') {
                    alert ('Please choose your native language (L1)!');
                    return;
                }
                if (l2 == '') {
                    alert ('Please choose your language you want to read/study (L2)!');
                    return;
                }
                if (l2 == l1) {
                    alert ('L1 L2 Languages must not be equal!');
                    return;
                }
                this.apply(LANGDEFS[l2], LANGDEFS[l1], l2);
            },

            /** 
             * Apply wizard based on entered data.
             */
            apply: function (learning_lg, known_lg, learning_lg_name) {
                reloadDictURLs(learning_lg[1], known_lg[1]);
                const url = new URL(window.location.href);
                const base_url = url.protocol + "//" + url.hostname;
                let path = url.pathname;
                const exploded_path = path.split('/');
                exploded_path.pop();
                //path = exploded_path.join('/') + '/trans.php';
                path = path.substring(0, path.lastIndexOf('edit_languages.php'));
                LIBRETRANSLATE = base_url + ':5000/?' + $.param({
                    lwt_translator: "libretranslate",
                    lwt_translator_ajax: encodeURIComponent(base_url + ":5000/translate/?"),
                    source: learning_lg[1],
                    target: known_lg[1],
                    q: "lwt_term"
                });
                $('input[name="LgName"]').val(learning_lg_name).change();
                // There may be a cleaner way to trigger the event
                checkLanguageChanged(learning_lg_name);
                $('input[name="LgDict1URI"]').val(
                    'https://de.glosbe.com/' + learning_lg[0] + '/' + 
                    known_lg[0] + '/lwt_term?lwt_popup=1'
                );
                $('input[name="LgDict1PopUp"]').attr('checked', true);
                $('input[name="LgGoogleTranslateURI"]').val(GGTRANSLATE);
                $('input[name="LgTextSize"]')
                .val(learning_lg[2] ? 200 : 150)
                .change();
                $('input[name="LgRegexpSplitSentences"]').val(learning_lg[4]);
                $('input[name="LgRegexpWordCharacters"]').val(learning_lg[3]);
                $('input[name="LgSplitEachChar"]').attr("checked", learning_lg[5]);
                $('input[name="LgRemoveSpaces"]').attr("checked", learning_lg[6]);
                $('input[name="LgRightToLeft"]').attr("checked", learning_lg[7]);
            },

            /**
             * Changed the "current native language".
             */
            change_native: function (value) {
                do_ajax_save_setting('currentnativelanguage', value);
            }
        };

        $(document).ready(ask_before_exiting);
    </script>
    <div class="td1 center">
        <div class="center" style="border: 1px solid black;">
            <h3 class="clickedit" onclick="$('#wizard_zone').toggle(400);" >
                Language Settings Wizard
            </h3>
            <div id="wizard_zone">
                <img src="icn/wizard.png" title="Language Settings Wizard" alt="Language Settings Wizard" />

                <div class="flex-spaced">
                    <div>
                        <b>My native language is:</b>
                        <div>
                            <label for="l1">L1</label>
                            <select name="l1" id="l1" onchange="language_wizard.change_native(this.value);">
                                <?php echo get_wizard_selectoptions($currentnativelanguage); ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <b>I want to study:</b>
                        <div>
                        <label for="l2">L2</label>
                            <select name="l2" id="l2">
                                <?php echo get_wizard_selectoptions(''); ?>
                            </select>
                        </div>
                    </div>
                </div>
                <input type="button" style="margin: 5px;" value="Set Language Settings" onclick="language_wizard.go();" />
                <p class="smallgray">
                    Select your native (L1) and study (L2) languages, and let the 
                    wizard set all language settings marked in yellow!<br />
                    (You can adjust the settings afterwards.)
                </p>
            </div>
        </div>
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
 * @param int $lid Language ID
 * 
 * @return void
 * 
 * @global string $tbpref
 * @global array $langDefs 
 */
function edit_languages_change($lid)
{
    global $tbpref, $langDefs;
    $sql = "SELECT * FROM {$tbpref}languages WHERE LgID = $lid";
    $res = do_mysqli_query($sql);
    if (mysqli_fetch_assoc($res)) {
    ?>
    <script type="text/javascript" charset="utf-8">
        const LANGDEFS = <?php echo json_encode($langDefs); ?>;

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
        (e.g. RegExp Word Characters, etc.)<wbr />
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
        <th class="th1 sorttable_nosort">Curr. Lang.</th>
        <th class="th1 sorttable_nosort">Test ↓↓↓</th>
        <th class="th1 sorttable_nosort">Actions</th>
        <th class="th1 clickable">Language</th>
        <th class="th1 sorttable_numeric clickable">Texts, Reparse</th>
        <th class="th1 sorttable_numeric clickable">Arch. Texts</th>
        <th class="th1 sorttable_numeric clickable">News feeds<wbr />(Articles)</th>
        <th class="th1 sorttable_numeric clickable">Terms</th>
        <th class="th1 sorttable_nosort">Export Template?</th>
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
            $tdth = 'td';
            $style = ' style="background-color: #8884; font-weight: bold;"';
            echo '<td ' . $style . ' class="td1 center">
                <img src="icn/exclamation-red.png" title="Current Language" alt="Current Language" />
                </td>';
        } else {
            $tdth = 'td';
            $style = '';
            echo '<td class="td1 center">
                <a href="inc/save_setting_redirect.php?k=currentlanguage&amp;v=' . $lid . '&amp;u=edit_languages.php">
                <img src="icn/tick-button.png" title="Set as Current Language" alt="Set as Current Language" />
                </a>
                </td>';
        }

        echo '<td' . $style . ' class="td1 center"><a href="do_test.php?lang=' . $lid . '">
            <img src="icn/question-balloon.png" title="Test" alt="Test" /></a>
        </' . $tdth . '>
        <td' . $style. ' class="td1 center">
            <a href="' . $_SERVER['PHP_SELF'] . '?chg=' . $lid . '">
                <img src="icn/document--pencil.png" title="Edit" alt="Edit" />
            </a>';
        if ($textcount == 0 && $archtextcount == 0 && $wordcount == 0 && $nfcount == 0) { 
            echo '&nbsp; <span class="click" onclick="if (confirmDelete()) location.href=\'' . $_SERVER['PHP_SELF'] . '?del=' . $lid . '\';">
                <img src="icn/minus-button.png" title="Delete" alt="Delete" /></span>'; 
        } else { 
            echo '&nbsp; <img src="icn/placeholder.png" title="Delete not possible" alt="Delete not possible" />'; 
        }
        echo '</td>
        <td ' . $style . ' class="td1 center">' . tohtml((string)$record['LgName']) . '</td>';
        if ($textcount > 0) { 
            echo '<td ' . $style . ' class="td1 center">
                <a href="edit_texts.php?page=1&amp;query=&amp;filterlang=' . $lid . '">' . 
            $textcount . '</a> <a href="' . $_SERVER['PHP_SELF'] . '?refresh=' . $lid . '">
                <img src="icn/lightning.png" title="Reparse Texts" alt="Reparse Texts" /></a>'; 
        } else {
            echo '<td ' . $style . ' class="td1 center">0 <img src="';
            print_file_path('icn/placeholder.png');
            echo'" title="No texts to reparse" alt="No texts to reparse" />';
        }
        echo '</td>';
        echo '<td ' . $style . ' class="td1 center">' . 
        ($archtextcount > 0 ? '<a href="edit_archivedtexts.php?page=1&amp;query=&amp;filterlang=' . $lid . '">' . 
        $archtextcount . '</a>' : '0' ) . '</td>';
        echo '<td ' . $style . ' class="td1 center">' . 
        ($nfcount > 0 ? '<a href="do_feeds.php?query=&amp;selected_feed=&amp;check_autoupdate=1&amp;filterlang=' . $lid . '">' . 
        $nfcount . ' (' . $fartcount . ')</a>' : '0' ) . '</td>';
        echo '<td ' . $style . ' class="td1 center">' . 
        ($wordcount > 0 ? '<a href="edit_words.php?page=1&amp;query=&amp;text=&amp;status=&amp;filterlang=' . 
        $lid . '&amp;status=&amp;tag12=0&amp;tag2=&amp;tag1=">' . $wordcount . '</a>' : '0' ) . '</td>';
        echo '<td ' . $style . ' class="td1 center" style="border-top-right-radius:0;">' . 
        (isset($record['LgExportTemplate']) ? '<img src="icn/status.png" title="Yes" alt="Yes" />' : 
        '<img src="icn/status-busy.png" title="No" alt="No" />' ) . '</td>';
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
        // Insert new language or change an existing one 
        if ($_REQUEST['op'] == 'Save') {
            // New language
            $message = edit_languages_op_save();
        } elseif ($_REQUEST['op'] == 'Change') {
            // Language edition
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
