<?php

require __DIR__ . "/../connect.inc.php";
$dbname = "test_lwt_db";
require_once __DIR__ . '/../inc/database_connect.php';

use PHPUnit\Framework\TestCase;

class DBTest extends TestCase
{
    public function testDatabaseInstallation()
    {
        include __DIR__ . "/../connect.inc.php";
        $db_schema = "db/schema/baseline.sql";
        $command = "mysql -u $userid -p$passwd -h $server -e 'USE $dbname'";
        exec($command, $output, $returnValue);
        if ($returnValue == 1049) {
            // Execute the SQL file to install the database
            $command = "mysql -u $userid -p$passwd -h $server $dbname < $db_schema";
            exec($command, $output, $returnValue);
    
            // Check if installation worked
            $this->assertEquals(0, $returnValue, 'Database installation failed');
        }

        // Connect to the database and check if necessary tables are created
        $conn = connect_to_database(
            $server, $userid, $passwd, $dbname, $socket ?? ""
        );
        $this->assertTrue(
            mysqli_connect_errno() === 0, 
            'Could not connect to the database: ' . mysqli_connect_error()
        );
    }
}
?>
