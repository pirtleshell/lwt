<?php

/**
 * \file
 * \brief Define the Term class
 * 
 * @package Lwt
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/Term_8php.html
 * @since   2.7.0
 */

/**
 * A term (word or mutli-word) represented as an object.
 */
class Term
{
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

}
