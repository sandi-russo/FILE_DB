<?php

require_once __DIR__ . '/../config/constants.php';
require_once HELPERS_DIR . '/messages.php';
class Database
{
    private $servername = 'db';
    private $username = 'root';
    private $password = 'password';
    private $dbname = 'progetto_db';
    private $conn;

    public function connect()
    {
        // Verifico se NON sono connesso al db
        if (!$this->conn) {
            $this->conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);

            // Se la connessione fallisce (check con connect_error)
            if ($this->conn->connect_error) {
                die("Connessione fallita: " . $this->conn->connect_error . "<br>");
            }

            // Aggiungo un controllo per verificare che $this->conn sia effettivamente un'istanza di \mysqli
            if (!$this->conn instanceof \mysqli) {
                die("Errore: la connessione non è un'istanza valida di \mysqli. <br>");
            }
        }

        return $this->conn;
    }


    public function close()
    {
        // Verifico se sono connesso al db
        if ($this->conn) {
            // Chiudo la connessione al db
            $this->conn->close();
            $this->conn = null;
        }
    }

    // Funzione generica per eseguire query
    // Funzione generica per eseguire query
    private function eseguiQuery($query)
    {
        // Verifico se la query è stata correttamente eseguita
        if ($this->conn->query($query) === TRUE) {
            echo showMessage("Query eseguita correttamente! <br>", 'success');
        } else {
            throw new Exception("Errore nell'esecuzione della query: " . $this->conn->error . "<br>");
        }
    }

    // Funzione per creare le tabelle nel db
    public function creaTabelle()
    {
        // Definizione delle query per la creazione delle tabelle
        $queries = [
            // Tabella AMMINISTRATORE
            "CREATE TABLE IF NOT EXISTS AMMINISTRATORE (
                Id INT AUTO_INCREMENT PRIMARY KEY,
                Nome VARCHAR(35) NOT NULL,
                Cognome VARCHAR(35) NOT NULL,
                Email VARCHAR(100) NOT NULL,
                Password VARCHAR(100) NOT NULL,
                Precaricato BOOLEAN NOT NULL
            )",

            // Tabella UTENTE
            "CREATE TABLE IF NOT EXISTS UTENTE (
                CodiceFiscale CHAR(16) NOT NULL,
                Email VARCHAR(100) NOT NULL,
                Nome VARCHAR(35) NOT NULL,
                Cognome VARCHAR(35) NOT NULL,
                Password VARCHAR(100) NOT NULL,
                IdAmministratore INT,
                PRIMARY KEY (CodiceFiscale, Email),
                FOREIGN KEY (IdAmministratore) REFERENCES AMMINISTRATORE(Id)
            )",

            // Tabella STUDENTE
            "CREATE TABLE IF NOT EXISTS STUDENTE (
                Id INT AUTO_INCREMENT PRIMARY KEY,
                CodiceFiscale CHAR(16) NOT NULL,
                Email VARCHAR(100) NOT NULL,
                FOREIGN KEY (CodiceFiscale, Email) REFERENCES UTENTE(CodiceFiscale, Email),
                UNIQUE (CodiceFiscale, Email)
            )",

            // Tabella RUOLO
            "CREATE TABLE IF NOT EXISTS RUOLO (
                Id INT AUTO_INCREMENT PRIMARY KEY,
                Nome ENUM('Ricercatore','Associato','Ordinario') NOT NULL,
                Descrizione TEXT NOT NULL
            )",

            // Tabella SSD
            "CREATE TABLE IF NOT EXISTS SSD (
                Codice VARCHAR(20) PRIMARY KEY,
                Descrizione TEXT NOT NULL
            )",

            // Tabella DOCENTE
            "CREATE TABLE IF NOT EXISTS DOCENTE (
                Matricola INT AUTO_INCREMENT PRIMARY KEY,
                CodiceFiscale CHAR(16) NOT NULL,
                Email VARCHAR(100) NOT NULL,
                IdRuolo INT NOT NULL,
                CodiceSSD VARCHAR(20) NOT NULL,
                MatricolaFormattata VARCHAR(6),
                FOREIGN KEY (CodiceFiscale, Email) REFERENCES UTENTE(CodiceFiscale, Email),
                FOREIGN KEY (IdRuolo) REFERENCES RUOLO(Id),
                FOREIGN KEY (CodiceSSD) REFERENCES SSD(Codice),
                UNIQUE (CodiceFiscale, Email)
            )",

            // Tabella PREFERENZA_ORARIA
            "CREATE TABLE IF NOT EXISTS PREFERENZA_ORARIA (
                Id INT AUTO_INCREMENT PRIMARY KEY,
                GiornoSettimana ENUM('Lunedì', 'Martedì','Mercoledì','Giovedì','Venerdì') NOT NULL,
                OraInizio TIME NOT NULL,
                OraFine TIME NOT NULL,
                CHECK (OraInizio >= '09:00:00'),
                CHECK (OraFine <= '18:00:00')
            )",

            // Tabella SELEZIONE
            "CREATE TABLE IF NOT EXISTS SELEZIONE (
                MatricolaDocente INT NOT NULL,
                IdPreferenzaOraria INT NOT NULL,
                PRIMARY KEY (MatricolaDocente, IdPreferenzaOraria),
                FOREIGN KEY (MatricolaDocente) REFERENCES DOCENTE(Matricola),
                FOREIGN KEY (IdPreferenzaOraria) REFERENCES PREFERENZA_ORARIA(Id)
            )",

            // Tabella MATERIALE_DIDATTICO
            "CREATE TABLE IF NOT EXISTS MATERIALE_DIDATTICO (
                Id INT AUTO_INCREMENT PRIMARY KEY,
                Descrizione TEXT NOT NULL,
                Nome VARCHAR(100) NOT NULL,
                DataCaricamento DATE NOT NULL,
                MatricolaDocente INT NOT NULL,
                FOREIGN KEY (MatricolaDocente) REFERENCES DOCENTE(Matricola)
            )",

            // Tabella SEDE
            "CREATE TABLE IF NOT EXISTS SEDE (
                Id INT AUTO_INCREMENT PRIMARY KEY,
                Nome VARCHAR(50) NOT NULL,
                Indirizzo VARCHAR(100) NOT NULL
            )",

            // Tabella EDIFICIO
            "CREATE TABLE IF NOT EXISTS EDIFICIO (
                Id INT AUTO_INCREMENT PRIMARY KEY,
                Descrizione TEXT NOT NULL,
                IdSede INT NOT NULL,
                FOREIGN KEY (IdSede) REFERENCES SEDE(Id)
            )",

            // Tabella AULE
            "CREATE TABLE IF NOT EXISTS AULE (
                Id INT AUTO_INCREMENT PRIMARY KEY,
                Nome VARCHAR(50) NOT NULL,
                Capienza INT NOT NULL,
                Tipologia ENUM('Aula','Laboratorio'),
                IdEdificio INT NOT NULL,
                FOREIGN KEY (IdEdificio) REFERENCES EDIFICIO(Id)
            )",

            // Tabella DOTAZIONE
            "CREATE TABLE IF NOT EXISTS DOTAZIONE (
                Codice VARCHAR(20) PRIMARY KEY,
                Nome VARCHAR(50) NOT NULL,
                Descrizione TEXT NOT NULL
            )",

            // Tabella INCLUSIONE
            "CREATE TABLE IF NOT EXISTS INCLUSIONE (
                IdAula INT NOT NULL,
                CodiceDotazione VARCHAR(20) NOT NULL,
                PRIMARY KEY (IdAula, CodiceDotazione),
                FOREIGN KEY (IdAula) REFERENCES AULE(Id),
                FOREIGN KEY (CodiceDotazione) REFERENCES DOTAZIONE(Codice)
            )",

            // Tabella SCHERMO
            "CREATE TABLE IF NOT EXISTS SCHERMO (
                Id INT AUTO_INCREMENT PRIMARY KEY,
                Descrizione TEXT NOT NULL,
                IdAula INT NOT NULL,
                FOREIGN KEY (IdAula) REFERENCES AULE(Id)
            )",

            // Tabella CHIAVE
            "CREATE TABLE IF NOT EXISTS CHIAVE (
                Id INT AUTO_INCREMENT PRIMARY KEY,
                Descrizione TEXT NOT NULL,
                DataOraUtilizzo DATETIME NOT NULL,
                IdAula INT NOT NULL,
                MatricolaDocente INT,
                FOREIGN KEY (IdAula) REFERENCES AULE(Id),
                FOREIGN KEY (MatricolaDocente) REFERENCES DOCENTE(Matricola)
            )",

            // Tabella PRENOTAZIONE
            "CREATE TABLE IF NOT EXISTS PRENOTAZIONE (
                MatricolaDocente INT NOT NULL,
                IdAula INT NOT NULL,
                GiornoSettimana ENUM('Lunedì', 'Martedì','Mercoledì','Giovedì','Venerdì') NOT NULL,
                OraInizio TIME NOT NULL,
                OraFine TIME NOT NULL,
                CHECK (OraInizio >= '09:00:00'),
                CHECK (OraFine <= '18:00:00'),
                FOREIGN KEY (IdAula) REFERENCES AULE(Id),
                FOREIGN KEY (MatricolaDocente) REFERENCES DOCENTE(Matricola),
                PRIMARY KEY (MatricolaDocente, IdAula, GiornoSettimana, OraInizio, OraFine)
            )",

            // Tabella DIPARTIMENTO
            "CREATE TABLE IF NOT EXISTS DIPARTIMENTO (
                Codice VARCHAR(20) PRIMARY KEY,
                Nome VARCHAR(50) NOT NULL,
                Descrizione TEXT NOT NULL
            )",

            // Tabella CORSO_DI_STUDIO
            "CREATE TABLE IF NOT EXISTS CORSO_DI_STUDIO (
                Nome VARCHAR(50) NOT NULL PRIMARY KEY,
                Durata INT NOT NULL,
                CHECK (Durata BETWEEN 1 AND 6),
                Descrizione TEXT NOT NULL,
                CodiceDipartimento VARCHAR(20) NOT NULL,
                FOREIGN KEY (CodiceDipartimento) REFERENCES DIPARTIMENTO(Codice)
            )",

            // Tabella APPARTENENZA
            "CREATE TABLE IF NOT EXISTS APPARTENENZA (
                IdStudente INT NOT NULL,
                NomeCorsoDiStudio VARCHAR(50) NOT NULL,
                FOREIGN KEY (IdStudente) REFERENCES STUDENTE(Id),
                FOREIGN KEY (NomeCorsoDiStudio) REFERENCES CORSO_DI_STUDIO(Nome),
                PRIMARY KEY (IdStudente, NomeCorsoDiStudio)
            )",

            // Tabella INSEGNAMENTO
            "CREATE TABLE IF NOT EXISTS INSEGNAMENTO (
                Codice VARCHAR(20) PRIMARY KEY,
                Nome VARCHAR(50) NOT NULL,
                CFU INT NOT NULL,
                Lingua VARCHAR(20) NOT NULL,
                Tipologia ENUM('Lezione','Esercitazione','Laboratorio') NOT NULL,
                IndTempo ENUM('I Semestre','II Semestre','Annuale') NOT NULL,
                OreSettimanali INT NOT NULL,
                NumTotaliOre INT NOT NULL,
                NomeCorsoDiStudio VARCHAR(50) NOT NULL,
                FOREIGN KEY (NomeCorsoDiStudio) REFERENCES CORSO_DI_STUDIO(Nome)
            )",

            // Tabella INERENZA
            "CREATE TABLE IF NOT EXISTS INERENZA (
                CodiceInsegnamento VARCHAR(20) NOT NULL,
                CodiceSSD VARCHAR(20) NOT NULL,
                FOREIGN KEY (CodiceInsegnamento) REFERENCES INSEGNAMENTO(Codice),
                FOREIGN KEY (CodiceSSD) REFERENCES SSD(Codice),
                PRIMARY KEY (CodiceInsegnamento, CodiceSSD)
            )",

            // Tabella SVOLGIMENTO
            "CREATE TABLE IF NOT EXISTS SVOLGIMENTO (
                CodiceInsegnamento VARCHAR(20) NOT NULL,
                IdAula INT NOT NULL,
                GiornoSettimana ENUM('Lunedì', 'Martedì','Mercoledì','Giovedì','Venerdì') NOT NULL,
                OraInizio TIME NOT NULL,
                OraFine TIME NOT NULL,
                CHECK (OraInizio >= '09:00:00'),
                CHECK (OraFine <= '18:00:00'),
                FOREIGN KEY (IdAula) REFERENCES AULE(Id),
                FOREIGN KEY (CodiceInsegnamento) REFERENCES INSEGNAMENTO(Codice),
                PRIMARY KEY (CodiceInsegnamento, IdAula, GiornoSettimana, OraInizio, OraFine)
            )",

            // Tabella EVENTO
            "CREATE TABLE IF NOT EXISTS EVENTO (
                Id INT AUTO_INCREMENT PRIMARY KEY,
                Nome VARCHAR(50) NOT NULL,
                Descrizione TEXT NOT NULL,
                DataEvento DATE NOT NULL,
                OraInizio TIME NOT NULL,
                OraFine TIME NOT NULL,
                CHECK (OraInizio >= '09:00:00'),
                CHECK (OraFine <= '18:00:00'),
                IdAula INT NOT NULL,
                FOREIGN KEY (IdAula) REFERENCES AULE(Id)
            )",

            // Tabella ISCRIZIONE
            "CREATE TABLE IF NOT EXISTS ISCRIZIONE (
                IdEvento INT NOT NULL,
                IdStudente INT NOT NULL,
                FOREIGN KEY (IdEvento) REFERENCES EVENTO(Id),
                FOREIGN KEY (IdStudente) REFERENCES STUDENTE(Id),
                PRIMARY KEY (IdEvento, IdStudente)
            )"
        ];

        // Esegui tutte le query per la creazione delle tabelle
        foreach ($queries as $query) {
            $this->eseguiQuery($query);
        }
    }

    // Funzione per creare i trigger nelle tabelle del db
    public function creaTriggers()
    {
        // Definizione delle query per la creazione dei trigger
        $triggers = [
            // Trigger per aggiornare il campo MatricolaFormattata in DOCENTE
            "CREATE TRIGGER before_insert_docente
        BEFORE INSERT ON DOCENTE
        FOR EACH ROW
        BEGIN
            SET NEW.MatricolaFormattata = LPAD(NEW.Matricola, 6, '0');
        END",

            // Trigger per verificare l'orario di inizio e fine in PREFERENZA_ORARIA
            "CREATE TRIGGER before_insert_preferenza_oraria
        BEFORE INSERT ON PREFERENZA_ORARIA
        FOR EACH ROW
        BEGIN
            IF NEW.OraInizio < '09:00:00' THEN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'OraInizio deve essere maggiore o uguale alle 09:00';
            END IF;
            IF NEW.OraFine > '18:00:00' THEN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'OraFine deve essere minore o uguale alle 18:00';
            END IF;
        END",

            // Trigger per verificare l'orario di inizio e fine in PRENOTAZIONE
            "CREATE TRIGGER before_insert_prenotazione
        BEFORE INSERT ON PRENOTAZIONE
        FOR EACH ROW
        BEGIN
            IF NEW.OraInizio < '09:00:00' THEN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'OraInizio deve essere maggiore o uguale alle 09:00';
            END IF;
            IF NEW.OraFine > '18:00:00' THEN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'OraFine deve essere minore o uguale alle 18:00';
            END IF;
        END",

            // Trigger per verificare l'orario di inizio e fine in SVOLGIMENTO
            "CREATE TRIGGER before_insert_svolgimento
        BEFORE INSERT ON SVOLGIMENTO
        FOR EACH ROW
        BEGIN
            IF NEW.OraInizio < '09:00:00' THEN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'OraInizio deve essere maggiore o uguale alle 09:00';
            END IF;
            IF NEW.OraFine > '18:00:00' THEN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'OraFine deve essere minore o uguale alle 18:00';
            END IF;
        END",

            // Trigger per verificare l'orario di inizio e fine in EVENTO
            "CREATE TRIGGER before_insert_evento
        BEFORE INSERT ON EVENTO
        FOR EACH ROW
        BEGIN
            IF NEW.OraInizio < '09:00:00' THEN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'OraInizio deve essere maggiore o uguale alle 09:00';
            END IF;
            IF NEW.OraFine > '18:00:00' THEN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'OraFine deve essere minore o uguale alle 18:00';
            END IF;
        END",

            // Trigger per verificare la Durata nel CORSO_DI_STUDIO
            "CREATE TRIGGER before_insert_corso_di_studio
        BEFORE INSERT ON CORSO_DI_STUDIO
        FOR EACH ROW
        BEGIN
            IF NEW.Durata < 1 OR NEW.Durata > 6 THEN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Durata deve essere compresa tra 1 e 6';
            END IF;
        END",

            // Trigger per verificare la Durata nell'update di CORSO_DI_STUDIO
            "CREATE TRIGGER before_update_corso_di_studio
        BEFORE UPDATE ON CORSO_DI_STUDIO
        FOR EACH ROW
        BEGIN
            IF NEW.Durata < 1 OR NEW.Durata > 6 THEN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Durata deve essere compresa tra 1 e 6';
            END IF;
        END"
        ];

        // Prima elimina i trigger esistenti se ci sono
        $dropTriggers = [
            "DROP TRIGGER IF EXISTS before_insert_docente",
            "DROP TRIGGER IF EXISTS before_insert_preferenza_oraria",
            "DROP TRIGGER IF EXISTS before_insert_prenotazione",
            "DROP TRIGGER IF EXISTS before_insert_svolgimento",
            "DROP TRIGGER IF EXISTS before_insert_evento",
            "DROP TRIGGER IF EXISTS before_insert_corso_di_studio",
            "DROP TRIGGER IF EXISTS before_update_corso_di_studio"
        ];

        // Elimina i trigger esistenti
        foreach ($dropTriggers as $drop) {
            $this->eseguiQuery($drop);
        }

        // Crea i nuovi trigger
        foreach ($triggers as $trigger) {
            $this->eseguiQuery($trigger);
        }
    }

    // Funzione per inizializzare il db
    public function setupDB()
    {
        try {
            // Connessione al db
            $this->connect();

            // Creazione delle tabelle
            $this->creaTabelle();

            // Creazione dei trigger
            $this->creaTriggers();

            // Restituisco un array se tutto è andato a buon fine
            return ['status' => 'success', 'message' => 'Configurazione completata con successo. <br>'];
        } catch (Exception $e) {
            // Log dell'errore
            error_log("Errore durante la configurazione del db: " . $e->getMessage() . "<br>");

            // Mostro un messaggio generico all'utente
            echo "Si è verificato un errore durante la configurazione del db. <br>";

            // Restituisco un array in caso di errore
            return ['status' => 'error', 'message' => 'Errore durante la configurazione del db: ' . $e->getMessage() . "<br>"];
        } finally {
            // Chiudo la connessione sempre, anche in caso di errore
            $this->close();
        }
    }
}