/**
 * \file
 * \brief General file to control dynamic interactions with the user.
 * 
 * @package Lwt
 * @author  HugoFara <Hugo.Farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   2.0.3-fork
 */

/**
 * Redirect the user to a specific page depending on the value
 */
function quickMenuRedirection(value) {
    var qm = document.getElementById('quickmenu');
    qm.selectedIndex=0;
    if (value == '')
        return; 
    if (value == 'INFO') {
        top.location.href = 'info.php';
    } else if (value == 'rss_import') {
        top.location.href = 'do_feeds.php?check_autoupdate=1';
    } else {
        top.location.href = value + '.php';
    }
}

/**
 * Create an interactable to add a new expression.
 * 
 * WARNING! This function was not properly tested!
 * 
 * @param {string[]} text         An array of words forming the expression 
 * @param {string}   attrs        A group of attributes to add 
 * @param {int}      length       Number of words, should correspond to WoWordCount
 * @param {string}   hex          Lowercase formatted version of the text.
 * @param {bool}     showallwords Set to false if a group if multi-words should be 
 *                                displayed as index
 * @returns {undefined}
 * 
 * @since 2.5.2-fork Don't hide multi-word index when inserting new multi-word. 
 */
function newExpressionInteractable(text, attrs, length, hex, showallwords) {

    const context = window.parent.document;
    for (key in text) {
        const words = $('span[id^="ID-'+ key +'-"]', context).not(".hide"); 
        const text_refresh = (
            words.attr('data_code') !== undefined 
            && words.attr('data_code') <= length);
        $('#ID-' + key + '-' + length, context).remove();
        let i = '';
        for (let j = length - 1; j > 0; j--) {
            if (j==1)
                i = '#ID-' + key + '-1';
            if ($('#ID-' + key + '-' + j, context).length) {
                i = '#ID-' + key + '-' + j;
                break;
            }
        }
        $(i, context)
        .before('<span id="ID-' + key + '-' + length + '"' + attrs + '>' 
        + text[key] + '</span>');
        const el = $('#ID-' + key + '-' + length, context);
        el.addClass('order' + key).attr('data_order', key);
        const txt = el
            .nextUntil(
                $('#ID-' + (parseInt(key) + length * 2 - 1) + '-1', context),
                '[id$="-1"]'
            )
            .map(function() {
                return $(this).text();
            })
            .get().join("");
        const pos = $('#ID-' + key + '-1', context).attr('data_pos');
        el.attr('data_text', txt).attr('data_pos', pos);
        if (!showallwords) {
            if (true || text_refresh) {
                //refresh_text(el);
            } else {
                el.addClass('hide');
            }
        }
    }
}


/** 
 * Prepare the interaction events with the text.
 * 
 * @since 2.0.3-fork
 */
function prepareTextInteractions() {
    $('.word').each(word_each_do_text_text);
    $('.mword').each(mword_each_do_text_text);
    $('.word').on('click', word_click_event_do_text_text);
    $('#thetext').on('selectstart','span',false).on(
        'mousedown','.wsty',
        {annotation: ANNOTATIONS_MODE}, 
        mword_drag_n_drop_select);
    $('#thetext').on('click', '.mword', mword_click_event_do_text_text);
    $('.word').on('dblclick', word_dblclick_event_do_text_text);
    $('#thetext').on('dblclick', '.mword', word_dblclick_event_do_text_text);
    $(document).on('keydown', keydown_event_do_text_text);
    $('#thetext').hoverIntent(
        {
            over: word_hover_over, 
            out: word_hover_out, 
            interval: 150, 
            selector:".wsty,.mwsty"
        }
    );
}


/** 
 * Scroll to a specific reading position
 * @global {int} POS Position to go to
 * @since 2.0.3-fork
 */
function goToLastPosition() {
    // Last registered position to go to
    const lookPos = POS;
    // Position to scroll to
    let pos = 0;
    if (lookPos > 0) {
        let posObj = $(".wsty[data_pos=" + lookPos + "]").not(".hide").eq(0);
        if (posObj.attr("data_pos") === undefined) {
            pos = $(".wsty").not(".hide").filter(function() {
                return $(this).attr("data_pos") <= lookPos;
            }).eq(-1);
        }
    }
    $(document).scrollTo(pos);
    focus();
    setTimeout(overlib, 10);
    setTimeout(cClick, 100);
}


/**
 * Save the current reading position.
 * @global {string} TID Text ID
 * 
 * @since 2.0.3-fork
 */
function saveCurrentPosition() {
    var pos = 0;
    var top = $(window).scrollTop()-$('.wsty').not('.hide').eq(0).height();
    $('.wsty').not('.hide').each(function() {
        if ($(this).offset().top >= top){
            pos = $(this).attr('data_pos');
            return false;
        }
    });
    $.ajax(
        {
            type: "POST",
            url:'inc/ajax_save_text_position.php', 
            data: { 
                id: TID, 
                position: pos 
            }, 
            async: false
        }
    );
}

/**
 * Get the phonetic version of a text.
 * 
 * @param {string} text Text to convert to phonetics.
 * @param {string} lang Language, either two letters code or four letters (BCP 47)
 */
function getPhoneticText(text, lang) {
    let phoneticText;
    $.ajax(
        {
            async: false,
            data: {
                text: text, 
                lang: lang 
            }, 
            type: "GET",
            url:'inc/ajax_get_phonetic.php', 
        }
    )
    .done(
        function (data) {
            phoneticText = data;
        }
    );
    return phoneticText;
}

/**
 * Get the phonetic version of a text, asynchronous.
 * 
 * @param {string} text Text to convert to phonetics.
 * @param {string} lang Language, either two letters code or four letters (BCP 47)
 */
async function getPhoneticTextAsync(text, lang) {
    return $.get(
        'inc/ajax_get_phonetic.php',
        data={
            text: text, 
            lang: lang 
        },
    );
}

/**
 * Read a text aloud, only work with a phonetic version.
 * 
 * @param {string} text  Text to read, won't be parsed further.
 * @param {string} lang  Language code with BCP 47 convention  
 *                       (e. g. "en-US" for English with an American accent) 
 * @param {number} rate  Reading rate 
 * @param {number} pitch Pitch value 
 */
 function readRawTextAloud(text, lang, rate, pitch) {
    let msg = new SpeechSynthesisUtterance();
    const trimmed = lang.substring(0, 2);
    const prefix = 'tts[' + trimmed;
    msg.text = text;
    if (lang) {
        msg.lang = lang;
    } else if (getCookie(prefix + 'RegName]')) {
        msg.lang = trimmed + '-' + getCookie(prefix + 'RegName]');
    }
    if (rate) {
        msg.rate = rate;
    } else if (getCookie(prefix + 'Rate]')) {
        msg.rate = parseInt(getCookie(prefix + 'Rate]'), 10);
    }
    if (pitch) {
        msg.pitch = pitch;
    } else if (getCookie(prefix + 'Pitch]')) {
        msg.pitch = parseInt(getCookie(prefix + 'Pitch]'), 10);
    }
    window.speechSynthesis.speak(msg);
}

/**
 * Read a text aloud, may parse the text to get a phonetic version.
 * 
 * @param {string} text  Text to read, do not need to be phonetic
 * @param {string} lang  Language code with BCP 47 convention  
 *                       (e. g. "en-US" for English with an American accent) 
 * @param {number} rate  Reading rate 
 * @param {number} pitch Pitch value 
 */
function readTextAloud(text, lang, rate, pitch) {
    let parsed_text;
    if (lang.substring(0, 2) == 'ja') {
        parsed_text = getPhoneticText(text, lang);
    } else {
        parsed_text = text;
    }
    readRawTextAloud(parsed_text, lang, rate, pitch);
}