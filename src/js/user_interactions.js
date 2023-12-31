/**
 * General file to control dynamic interactions with the user.
 * 
 * @author  HugoFara <Hugo.Farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   2.0.3-fork
 */

/**
 * Redirect the user to a specific page depending on the value
 */
function quickMenuRedirection(value) {
    const qm = document.getElementById('quickmenu');
    qm.selectedIndex = 0;
    if (value == '')
        return; 
    if (value == 'INFO') {
        top.location.href = 'docs/info.html';
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
        {annotation: LWT_DATA.settings.annotations_mode}, 
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
 * 
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
 * 
 * @param {int} text_id Text id
 * @param {int} position Position to save
 * 
 * @since 2.9.0-fork
 */
function saveReadingPosition(text_id, position) {
    $.post(
        'api.php/v1/texts/' + text_id + '/reading-position',
        { position: position }
    );
}


/**
 * Save audio position
 */
function saveAudioPosition(text_id, pos) {
    $.post(
        'api.php/v1/texts/' + text_id + '/audio-position',
        { position: pos }
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
        'api.php/v1/phonetic-reading',
        {
            async: false,
            data: {
                text: text,
                lang: lang 
            },
            dataType: "json",
            type: "GET",
        }
    )
    .done(
        function (data) {
            phoneticText = data["phonetic_reading"];
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
    return $.getJSON(
        'api.php/v1/phonetic-reading',
        {
            text: text, 
            lang: lang 
        }
    );
}
  

/**
 * Replace any searchValue on object value by replaceValue with deepth.
 * 
 * @param {dict} obj Object to search in
 * @param {string} searchValue Value to find
 * @param {string} replaceValue Value to replace with
 * */
function deepReplace(obj, searchValue, replaceValue) {
    for (let key in obj) {
        if (typeof obj[key] === 'object') {
            // Recursively search nested objects
            deepReplace(obj[key], searchValue, replaceValue);
        } else if (typeof obj[key] === 'string' && obj[key].includes(searchValue)) {
            // If the property is a string and contains the searchValue, replace it
            obj[key] = obj[key].replace(searchValue, replaceValue);
        }
    }
  }
  
/**
 * Find the first string starting with searchValue in object.
 * 
 * @param {dict}   obj         Object to search in
 * @param {string} searchValue Value to search
 */
function deepFindValue(obj, searchValue) {
    for (const key in obj) {
        if (obj.hasOwnProperty(key)) {
            if (typeof obj[key] === 'string' && obj[key].startsWith(searchValue)) {
                return obj[key];
            } else if (typeof obj[key] === 'object') {
                const result = deepFindValue(obj[key], searchValue);
                if (result) {
                    return result;
                }
            }
        }
    }
    return null; // Return null if no matching string is found
}

function readTextWithExternalApp(text, lang) {
    let fetchRequest = JSON.parse(LWT_DATA.language.ttsVoiceApi);

    // TODO: can expose more vars to Request
    deepReplace(fetchRequest, 'lwt_term', text)
    deepReplace(fetchRequest, 'lwt_lang', lang)


    fetchRequest.options.body = JSON.stringify(fetchRequest.options.body)

    fetch(fetchRequest.input, fetchRequest.options)
    .then(response => response.json())
    .then(data => {
        const encodeString = deepFindValue(data, 'data:')
        const utter = new Audio(encodeString)
        utter.play()
    })
    .catch(error => {
        console.error(error)
    });
}

/**
 * Read a text aloud, works with a phonetic version only.
 * 
 * @param {string} text  Text to read, won't be parsed further.
 * @param {string} lang  Language code with BCP 47 convention  
 *                       (e. g. "en-US" for English with an American accent) 
 * @param {number} rate  Reading rate 
 * @param {number} pitch Pitch value 
 * 
 * @return {SpeechSynthesisUtterance} The spoken message object
 * 
 * @since 2.9.0 Accepts "voice" as a new optional argument
 * @since 2.10.0 Can use third-party applications to read text
 */
 function readRawTextAloud(text, lang, rate, pitch, voice) {
    let msg = new SpeechSynthesisUtterance();
    const trimmed = lang.substring(0, 2);
    const prefix = 'tts[' + trimmed;
    msg.text = text;
    if (lang) {
        msg.lang = lang;
    }
    // Voice is a string but we have to assign a SpeechSynthesysVoice
    const useVoice = voice || getCookie(prefix + 'Voice]');
    if (useVoice) {
        const voices = window.speechSynthesis.getVoices();
        for (let i = 0; i < voices.length; i++) {
            if (voices[i].name === useVoice) {
                msg.voice = voices[i];
            }
        }
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
    if (LWT_DATA.language.ttsVoiceApi) {
        readTextWithExternalApp(text, lang);
    } else {
        window.speechSynthesis.speak(msg);
    }
    return msg;
}

/**
 * Read a text aloud, may parse the text to get a phonetic version.
 * 
 * @param {string} text   Text to read, do not need to be phonetic
 * @param {string} lang   Language code with BCP 47 convention  
 *                        (e. g. "en-US" for English with an American accent) 
 * @param {number} rate   Reading rate 
 * @param {number} pitch  Pitch value
 * @param {string} voice Optional voice, the result will depend on the browser used
 * 
 * @since 2.9.0 Accepts "voice" as a new optional argument
 */
function readTextAloud(text, lang, rate, pitch, voice) {
    if (lang.startsWith('ja')) {
        getPhoneticTextAsync(text, lang)
            .then(
                function (data) {
                    readRawTextAloud(
                        data.phonetic_reading, lang, rate, pitch, voice
                    );
                }
            );
    } else {
        readRawTextAloud(text, lang, rate, pitch, voice);
    }
}
