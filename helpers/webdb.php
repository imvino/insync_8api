<?php
    function openWebDB()
    {
            $db = pg_connect('host=127.0.0.1 dbname=insync user=web password=qey8xUf9 connect_timeout=30')
                    or false;
            
            return $db;
    }
?>
