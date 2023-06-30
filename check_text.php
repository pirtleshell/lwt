<?php

/**
 * \file
 * \brief Check (parse & split) a Text (into sentences/words)
 * 
 * Call: check_text.php?...
 *      op=Check ... do the check
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/check__text_8php.html
 * @since   1.0.3
 */

namespace Lwt\Interface\Check_Text;

require_once 'inc/session_utility.php';

/**
 * Do the check text operation.
 * 
 * @param string $text Text to check.
 * @param int    $lgid Language ID.
 * 
 * @return void
 */
function do_operation($text, $lgid)
{
    echo '<p><input type="button" value="&lt;&lt; Back" onclick="history.back();" /></p>';
    if (strlen(prepare_textdata($text)) > 65000) {
        echo "<p>Error: Text too long, must be below 65000 Bytes.</p>"; 
    } else {
        splitCheckText($text, $lgid, -1);
    }
    echo '<p><input type="button" value="&lt;&lt; Back" onclick="history.back();" /></p>';
}

/**
 * Display the main form for the check text page.
 * 
 * @global string $tbpref
 * 
 * @return void
 */
function display_form()
{
    global $tbpref;
    $sql = "SELECT LgID, LgGoogleTranslateURI FROM {$tbpref}languages 
    WHERE LgGoogleTranslateURI<>''";
    $res = do_mysqli_query($sql);
    $return = array();
    while ($lg_record = mysqli_fetch_assoc($res)) {
        $url = $lg_record["LgGoogleTranslateURI"];
        $return[$lg_record["LgID"]] = langFromDict($url);
    }
    ?>

    <script type="text/javascript" charset="utf-8">
        /**
         * Change the language of inputs for text and title based on selected 
         * language.
         * 
         * @returns undefined
         */
        function change_textboxes_language() {
            const lid = document.getElementById("TxLgID").value;
            const language_data = <?php echo json_encode($return); ?>;
            $('#TxTitle').attr('lang', language_data[lid]);
            $('#TxText').attr('lang', language_data[lid]);
        }

        $(document).ready(ask_before_exiting);
        $(document).ready(change_textboxes_language);
     </script>
<form class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<table class="tab3" cellspacing="0" cellpadding="5">
    <tr>
    <td class="td1 right">Language:</td>
    <td class="td1">
        <select name="TxLgID" id="TxLgID" class="notempty setfocus" onchange="change_textboxes_language();">
            <?php
            echo get_languages_selectoptions(getSetting('currentlanguage'), '[Choose...]');
            ?>
        </select> 
        <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
    </td>
    </tr>
    <tr>
        <td class="td1 right">Text:<br /><br />(max.<br />65,000<br />bytes)</td>
        <td class="td1">
            <textarea name="TxText" id="TxText" class="notempty checkbytes checkoutsidebmp" data_maxlength="65000" data_info="Text" cols="60" rows="20"></textarea> 
            <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <tr>
        <td class="td1 right" colspan="2">
            <input type="button" value="&lt;&lt; Back" onclick="location.href='index.php';" /> 
            <input type="submit" name="op" value="Check" />
        </td>
</tr>
</table>
</form>
    <?php
}

pagestart('Check a Text', true);

if (isset($_REQUEST['op'])) {
    do_operation((string)$_REQUEST['TxText'], (int)$_REQUEST['TxLgID']);
} else {
    display_form();
}
pageend();

?>
