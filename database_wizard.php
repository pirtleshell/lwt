<?php

/**
 * \file
 * \brief Create / Edit database connection
 * 
 * Call: database_wizard.php
 * 
 * @package Lwt
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/database__wizard_8php.html
 * @since   2.5.0-fork
 */

require_once 'inc/kernel_utility.php';

class Database_Connection {
    public string $server;
    public string $userid;
    public string $passwd;
    public string $dbname;
    public string $socket;

    function __construct($server = "", $userid = "", $passwd = "", $dbname = "", $socket = "")
    {
        $this->server = $server;
        $this->userid = $userid;
        $this->passwd = $passwd;
        $this->dbname = $dbname;
        $this->socket = $socket;
    }

    public function load_file(string $file_name) {
        include $file_name;
        $this->server = $server;
        $this->userid = $userid;
        $this->passwd = $passwd;
        $this->dbname = $dbname;
        $this->socket = $socket;
    }

    public function get_as_text() {
        return '<?php

        /**
         * \file
         * \brief DB variables for MAMP
         */
        
        $server = "' . $this->server . '";
        $userid = "' . $this->userid . '";
        $passwd = "' . $this->passwd . '";
        $dbname = "' . $this->dbname . '";
        $socket = "' . $this->socket . '";
        
        ?>
        ';
    }

}

function database_wizard_change($conn) {
    $handle = fopen(__DIR__ . "/connect.inc.php", 'w');
    fwrite($handle, $conn->get_as_text());
    fclose($handle);
}

function database_wizard_do_operation($op) {
    $message = null;
    if ($op == "Autocomplete") {
        $server = $_SERVER['SERVER_ADDR'];
        $userid = $_SERVER['SERVER_NAME']; 
        $passwd = "";
        $dbname = "";
        $socket = "";
    } else if ($op == "Check") {
        //require_once 'inc/database_connect.php';
        $server = getreq("server"); 
        $userid = getreq("userid");
        $passwd = getreq("passwd");
        $dbname = getreq("dbname");
        $socket = getreq("socket");
        $conn = mysqli_init();
        if ($conn === false) {
            $message = "MySQL is not accessible!";
        } else {
            try {
                if (strlen($socket) > 0) {
                    $success = mysqli_real_connect($conn, $server, $userid, $passwd, $dbname, 0, $socket);
                } else {
                    $success = mysqli_real_connect($conn, $server, $userid, $passwd, $dbname);
                }
                if (!$success) {
                    $message = "Can't connect!";
                } else if (mysqli_errno($conn) != 0) {
                    $message = "ERROR: " . mysqli_error($conn) . " error number: " . mysqli_errno($conn);
                } else {
                    $message = "Connection established with success!";
                }
            } catch (Exception $exept) {
                $message = (string)$exept;
            }
        }
        //$message = "RRRRRRR";
    } else if ($op == "Change") {
        $server = getreq("server"); 
        $userid = getreq("userid");
        $passwd = getreq("passwd");
        $dbname = getreq("dbname");
        $socket = getreq("socket");
    }
    $conn = new Database_Connection($server, $userid, $passwd, $dbname, $socket);
    if ($op == "Change") {
        database_wizard_change($conn);
    }
    database_wizard_form($conn, $message);
}

/**
 * Generate a form to edit the connection.
 * 
 * @param \Database_Connection conn Database connection object 
 */
function database_wizard_form($conn, $error_message=null) {
    pagestart_kernel_nobody("Database Connection Wizard", true);
    if ($error_message != null) {
        //error_message_with_hide($error_message, true);
        echo $error_message;
    }
    ?>
    <form name="database_connect" action="<?= $_SERVER['PHP_SELF']; ?>" method="post">
        <p>
            <label for="server">Server address:</label>
            <input type="text" name="server" id="server" value="<?= htmlspecialchars($conn->server) ?>" required placeholder="localhost">
        </p> 
        <p>
            <label for="userid">Database User Name:</label>
            <input type="text" name="userid" id="userid" value="<?= htmlspecialchars($conn->userid); ?>" required placeholder="root">
        </p>
        <p>
            <label for="passwd">Password:</label>
            <input type="password" name="passwd" id="passwd" value="<?= htmlspecialchars($conn->passwd); ?>" placeholder="abcxyz">
        </p>
        <p>
            <label for="dbname">Database Name:</label>
            <input type="text" name="dbname" id="dbname" value="<?= htmlspecialchars($conn->dbname); ?>" required placeholder="lwt">
        </p>
        <p>
            <label for="socket">Socket Name:</label>
            <input type="text" name="socket" id="socket" value="<?= htmlspecialchars($conn->socket); ?>" required placeholder="/var/run/mysql.sock">
        </p>
        <input type="submit" name="op" value="Autocomplete" />
        <input type="submit" name="op" value="Check" />
        <input type="submit" name="op" value="Change" />
    </form>
    <?php
    pageend();
}

// May be dangerous to expose passwords in clear
function edit_database_connection() {
    $conn = new Database_Connection();
    $conn->load_file('connect.inc.php');
    database_wizard_form($conn);
}

function new_database_connection() {
    database_wizard_form(new Database_Connection());
}

if (getreq('op') != '') {
    database_wizard_do_operation(getreq('op'));
} else {
    if (file_exists('connect.inc.php')) {
        edit_database_connection();
    } else {
        new_database_connection();
    }
}

?>