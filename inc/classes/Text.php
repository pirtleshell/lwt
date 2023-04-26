<?php

/**
 * \file
 * \brief Define the Text class
 * 
 * @package Lwt
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/Text_8php.html
 * @since   2.9.0
 */

require_once __DIR__ . "/Language.php";

/**
 * A text represented as an object.
 */
class Text
{
    /**
     * @var int $id Text ID.
     */
    public $id;
    /**
     * @var Language $lgid Language object.
     */
    public $Language;
    /**
     * @var string $title Text title.
     */
    public $title;
    /**
     * @var string $text Associated text.
     */
    public $text;
    /**
     * @var string $annotated Annotated version of the text.
     */
    public $annotated;
    /**
     * @var string $media_uri Media address (local path or URL).
     */
    public $media_uri;
    /**
     * @var string $source Source of text (usually URL).
     */
    public $source;
    /**
     * @var string $position Position in text.
     */
    public $position;
    /**
     * @var float $audio_pos Position of the associated media.
     */
    public $audio_pos;

}
