<?php

/**
 * \file
 * \brief Useful software data.
 * 
 * Call: server_data.php
 * 
 * @package Lwt
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/index_8php.html
 * @since   2.7.0
 */

require_once 'inc/session_utility.php';

/**
 * Return a lot of different server state variables.
 * 
 * @return array{"db_prefix": string, "db_size": float, "server_soft": string[], "apache": string, "php": string, "mysql": string} 
 * Table prefix, database size, server software, apache version, PHP version, MySQL 
 * version
 * 
 * @global string $tbpref Database table prefix
 * @global string $dbname Database name
 *
 * @psalm-return array{
 *  "db_prefix": string, "db_size": float, "server_soft": non-empty-list<string>, 
 *  "apache": string, "php": false|string, "mysql": string
 * }
 */
function get_server_data_table(): array 
{
    global $tbpref, $dbname;
    $dbaccess_format = convert_string_to_sqlsyntax($dbname);
    $data_table = array();
    $data_table["db_name"] = $dbname;
    $data_table["db_prefix"] = $tbpref;
    $data_table["db_size"] = (float)get_first_value(
        "SELECT ROUND(SUM(data_length+index_length)/1024/1024, 1) AS value 
        FROM information_schema.TABLES 
        WHERE table_schema = $dbaccess_format 
        AND table_name IN (
            '{$tbpref}archivedtexts', '{$tbpref}archtexttags', '{$tbpref}feedlinks', '{$tbpref}languages',
            '{$tbpref}newsfeeds', '{$tbpref}sentences', '{$tbpref}settings', '{$tbpref}tags', '{$tbpref}tags2', 
            '{$tbpref}textitems2', '{$tbpref}texts', '{$tbpref}texttags', '{$tbpref}words', '{$tbpref}wordtags'
        )"
    );
    if (!isset($data_table["db_size"])) { 
        $data_table["db_size"] = 0.0; 
    }

    $data_table["server_soft"] = explode(' ', $_SERVER['SERVER_SOFTWARE']);
    $data_table["apache"] = "Apache/?";
    if (substr($data_table["server_soft"][0], 0, 7) == "Apache/") { 
        $data_table["apache"] = $data_table["server_soft"][0]; 
    }
    $data_table["php"] = phpversion();
    $data_table["mysql"] = (string)get_first_value("SELECT VERSION() as value");
    return $data_table;
}

$data = get_server_data_table();

pagestart("Server Data", true);
?>
<table>
    <thead>
        <tr>
            <th>Data</th>
            <th>Value</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>LWT version</td>
            <td><?php echo get_version_number(); ?></td>
        </tr>
        <tr>
            <td><a href="https://en.wikipedia.org/wiki/Database" target="_blank">Database</a> name</td>
            <td><i><?php echo $data["db_name"]; ?></i></td>
        </tr>
        <tr>
            <td>Database prefix (surrounded by "")</td>
            <td>"<?php echo $data["db_prefix"]; ?>"</td>
        </tr>
        <tr>
            <td>Database Location</td>
            <td><i><?php echo $data["server_soft"][0]; ?></i></td>
        </tr>
        <tr>
            <td>Database Size</td>
            <td><?php echo $data["db_size"]; ?> MB</td>
        </tr>
        <tr>
            <td><a href="https://en.wikipedia.org/wiki/Web_server" target="_blank">Web Server</a></td>
            <td><i><?php echo $_SERVER['HTTP_HOST']; ?></i></td>
        </tr>
        <tr>
            <td>Server Software</td>
            <td>
                <a href="https://en.wikipedia.org/wiki/Apache_HTTP_Server" target="_blank">
                    <?php echo $data["apache"]; ?>
                </a>
            </td>
        </tr>
        <tr>
            <td><a href="https://en.wikipedia.org/wiki/PHP" target="_blank">PHP</a> Version</td>
            <td><?php echo $data["php"];; ?></td>
        </tr>
        <tr>
            <td><a href="https://en.wikipedia.org/wiki/MySQL" target="_blank">MySQL</a> Version</td>
            <td><?php echo $data["mysql"];; ?></td>
        </tr>
    </tbody>
</table>
