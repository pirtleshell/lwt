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
     * @var int $id Language ID.
     */
    public $id;
    /**
     * @var int $name Language name.
     */
    public $name;
    /**
     * @var int $dict1uri Language ID.
     */
    public $dict1uri;
    /**
     * @var string $dict2uri Associated text.
     */
    public $dict2uri;
    /**
     * @var string $translator Associated text in lower case.
     */
    public $translator;
    /**
     * @var int $exporttemplate Term status.
     */
    public $exporttemplate;
    /**
     * @var string $textsize Term translation.
     */
    public $textsize;
    /**
     * @var string $charactersubst Character to substitue. 
     */
    public $charactersubst;
    /**
     * @var string $regexpsplitsent Characters that should split a sentence in part.
     */
    public $regexpsplitsent;
    /**
     * @var string $exceptionsplitsent Characters taht should not split sentence.
     */
    public $exceptionsplitsent;

    /**
     * @var string $regexpwordchar Word characters.
     */
    public $regexpwordchar;

    /**
     * @var bool $removespaces If spaces should be removed.
     */
    public $removespaces;

    /**
     * @var bool $spliteachchar If each character should be separated.
     */
    public $spliteachchar;

    /**
     * @var bool $rightoleft If the language is right-to-left.
     */
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
