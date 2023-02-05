<?php

/**
 * \file
 * \brief Define the Language class
 * 
 * @package Lwt
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/Language_8php.html
 * @since   2.7.0
 */

/**
 * A language represented as an object.
 * 
 * This structure is experimental and subject to change.
 */
class Language
{
    /**
     * @var int Language ID.
     */
    public $id;
    /**
     * @var int Language name.
     */
    public $name;
    /**
     * @var int Language ID.
     */
    public $dict1uri;
    /**
     * @var string Associated text.
     */
    public $dict2uri;
    /**
     * @var string Associated text in lower case.
     */
    public $translator;
    /**
     * @var int Term status.
     */
    public $exporttemplate;
    /**
     * @var string Term translation.
     */
    public $textsize;
    /**
     * @var string Sentence containing the term. 
     */
    public $charactersubst;
    /**
     * @var string Romanization.
     */
    public $regexpsplitsent;
    /**
     * @var int Number of words in the term.
     */
    public $exceptionsplitsent;
    /**
     * @var int Last status change date.
     */
    public $regexpwordchar;

    public $removespaces;

    public $spliteachchar;

    public $rightoleft;

    /**
     * Export word data as a JSON dictionnary.
     * 
     * @return string JSON disctionnary. 
     */
    public function export_js_dict()
    {
        return json_encode(
            array(
                "lgid"               => $this->id,
                "dict1uri"           => $this->dict1uri,
                "dict2uri"           => $this->dict2uri,
                "translator"         => $this->translator,
                "exporttemplate"     => $this->exporttemplate,
                "textsize"           => $this->textsize,
                "charactersubst"     => $this->charactersubst,
                "regexpsplitsent"    => $this->regexpsplitsent,
                "exceptionsplitsent" => $this->exceptionsplitsent,
                "regexpwordchar"     => $this->regexpwordchar,
                "removespaces"       => $this->removespaces,
                "spliteachchar"      => $this->spliteachchar,
                "rightoleft"         => $this->rightoleft
            )
        );
    }
}
