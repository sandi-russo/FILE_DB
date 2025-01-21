<?php

// Includo le costanti per l'inclusione dei file
require_once 'constants.php';

require_once CONFIG_DIR . '/database.php';
require_once HELPERS_DIR . '/messages.php';

// Crea un'istanza della classe Database
$db = new Database();

// Esegui la configurazione del database
$db->setupDB();

showMessage("Database configurato correttamente", 'success');

