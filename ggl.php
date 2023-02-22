<?php

/*
 * \file
 * \brief Google Translate interface
 * 
 * Call: ggl.php?text=[text]&sl=[source language]&tl=[target language] ... translate text
 *     ... sent=[int] ... single sentence mode.
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/ggl_8php.html
 * @since   1.6.0
 * @since   2.7.0 Refactored with functional paradigm
 */
namespace Lwt\Ggl;

require_once 'inc/session_utility.php';
require_once 'inc/googleTimeToken.php' ;
require_once 'inc/classes/googleTranslateClass.php';

use GoogleTranslate;

/*
 * Translate a single sentence using Google Translate.
 * 
 * @param string $text   Text to translate
 * @param string $gglink Google Translate link
 * @param array  $file   Array of translated terms, should contain 1 element
 * 
 * @return void
 */
function translate_sentence($text, $gglink, $file)
{
    ?>
    <h2>Sentence:</h2>
    <span class="red2"><?php echo tohtml($text); ?></span>
    <br><br>
    <h2>Google Translate:</h2>
    <?php echo $gglink ?> 
    <br>
    <table class="tab2" cellspacing="0" cellpadding="0"> 
        <tr>
            <td class="td1bot center" colspan="1">
                <?php echo $file[0]; ?>
            </td>
        </tr>
    </table>
    <?php
}

/*
 * Translate input text using Google Translate.
 * 
 * @param string $text   Text to translate
 * @param string $gglink Google Translate link
 * @param array  $file   Array of translated terms 
 * @param string $sl     Source language (e. g. "es")
 * @param string $tl     Target language (e. g. "en")
 * 
 * @return void
 */
function translate_term($text, $gglink, $file, $sl, $tl)
{
    ?>
<h2>Google Translate:  &nbsp; 
    <span class="red2" id="textToSpeak" style="cursor:pointer" 
    title="Click on expression for pronunciation">
        <?php echo tohtml($text) ?>
    </span> 
    <img id="del_translation" src="icn/broom.png" style="cursor:pointer" 
    title="Empty Translation Field" onclick="deleteTranslation ();"></img>
</h2>
<p>
    (Click on <img src="icn/tick-button.png" title="Choose" alt="Choose" /> 
    to copy word(s) into above term)<br />&nbsp;
</p>
    
<script type="text/javascript">
    $(document).ready( function() {
    let w = window.parent.frames['ro'];
    if (w === undefined) 
        w = window.opener;
    if (w === undefined) 
        $('#del_translation').remove();

    $('#textToSpeak').on('click', function () {
        const txt = $('#textToSpeak').text();
        const audio = new Audio();
        audio.src = 'tts.php?' + $.param({
            tl: <?php echo json_encode($sl); ?>, 
            q: txt
        });
        audio.play();
    });
    });
</script>
    <?php
    foreach ($file as $word){
        echo '<span class="click" onclick="addTranslation(' . 
        prepare_textdata_js($word) . ');">' . 
        '<img src="icn/tick-button.png" title="Copy" alt="Copy" /> &nbsp; ' . 
        tohtml($word) . '</span><br />';
    }
    if (!empty($file)) {
        echo '<br />' . $gglink . "\n";
    }

    ?>
    &nbsp;<hr />&nbsp;
    <form action="ggl.php" method="get">
        Unhappy?<br/>Change term: 
        <input type="text" name="text" maxlength="250" size="15" 
        value="<?php echo tohtml($text); ?>">
        <input type="hidden" name="sl" value="<?php echo tohtml($sl); ?>">
        <input type="hidden" name="tl" value="<?php echo tohtml($tl); ?>">
        <input type="submit" value="Translate via Google Translate">
    </form>
    <?php
}

/*
 * Translate input text using Google Translate.
 * 
 * @param string $text Text to translate
 * @param string $sl Source language (e. g. "es")
 * @param string $tl Target language (e. g. "en")
 * @param bool $sentence_mode Set to true for full sentence translation
 * 
 * @return void
 */
function translate_text($text, $sl, $tl, $sentence_mode)
{
    $file = GoogleTranslate::staticTranslate($text, $sl, $tl, getGoogleTimeToken());

    $gglink = makeOpenDictStr(
        createTheDictLink(
            'http://translate.google.com/#' . $sl . '/' . $tl . '/?lwt_popup=true', 
            $text
        ), 
        " more..."
    );

    if ($sentence_mode) {
        translate_sentence($text, $gglink, $file);
    } else {
        translate_term($text, $gglink, $file, $sl, $tl);
    }
}

/*
 * Translate input text using Google Translate.
 * 
 * @param string $text Text to translate
 * 
 * @return void
 */
function do_content($text)
{
    header('Pragma: no-cache');
    header('Expires: 0');
    pagestart_nobody('Google Translate');
    if (trim($text) != '') {
        translate_text($text, $_GET["sl"], $_GET["tl"], isset($_GET['sent']));
    } else {
        echo "<p class=\"msgblue\">Term is not set!</p>";
    }
    pageend();
}

if (isset($_GET['text'])) {
    do_content($_GET["text"]);
}
?>
