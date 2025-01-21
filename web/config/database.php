<?php

require_once 'constants.php';
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
        // Definizione delle query per la crezione delle tabelle
        $queries = [
            // Tabella UNITA_ORGANIZZATIVA
            "CREATE TABLE IF NOT EXISTS UNITA_ORGANIZZATIVA (
                    Codice VARCHAR(20) PRIMARY KEY,
                    Nome VARCHAR(255) NOT NULL
                )",

            // Tabella SETTORE_SCIENTIFICO
            "CREATE TABLE IF NOT EXISTS SETTORE_SCIENTIFICO (
                      SSD VARCHAR(20) PRIMARY KEY,
                      NomeSettore VARCHAR(100) NOT NULL
                  )",

            // Tabella DOCENTE
            "CREATE TABLE IF NOT EXISTS DOCENTE (
                        ID_Docente INT AUTO_INCREMENT PRIMARY KEY,
                        Nome VARCHAR(50) NOT NULL,
                        Cognome VARCHAR(50) NOT NULL,
                        SSD VARCHAR(20) NOT NULL,
                        Email VARCHAR(100) NOT NULL UNIQUE,
                        Telefono VARCHAR(20),
                        Ruolo ENUM('Contratto (50 ore)', 'Ricercatore (80 ore)', 'Associato (100 ore)', 'Ordinario (120 ore)') NOT NULL,
                        FOREIGN KEY (SSD) REFERENCES SETTORE_SCIENTIFICO(SSD)
                    )",

            // Tabella EDIFICIO
            "CREATE TABLE IF NOT EXISTS EDIFICIO (
                      ID_Edificio VARCHAR(40) PRIMARY KEY,
                      Nome VARCHAR(100) NOT NULL,
                      Indirizzo VARCHAR(200) NOT NULL,
                      CapacitaTotale INT NOT NULL CHECK (CapacitaTotale > 0)
                  )",

            // Tabella AULA
            "CREATE TABLE IF NOT EXISTS AULA (
                      ID_Aula INT AUTO_INCREMENT PRIMARY KEY,
                      Nome VARCHAR(50) NOT NULL,
                      Capacita INT NOT NULL CHECK (Capacita > 0),
                      Tipologia ENUM('teorica', 'laboratorio') NOT NULL,
                      Edificio VARCHAR(40),
                      Attrezzature TEXT,
                      FOREIGN KEY (Edificio) REFERENCES EDIFICIO(ID_Edificio)
                  )",

            // Tabella CORSO_DI_STUDIO
            "CREATE TABLE IF NOT EXISTS CORSO_DI_STUDIO (
                    Codice VARCHAR(25) PRIMARY KEY,
                    Nome VARCHAR(100) NOT NULL,
                    Percorso VARCHAR(100),
                    AnnoCorso INT NOT NULL
                )",

            // Tabella LINGUE
            "CREATE TABLE IF NOT EXISTS LINGUE (
                      CodiceLingua VARCHAR(5) PRIMARY KEY,
                      NomeLingua VARCHAR(50) NOT NULL
                  )",

            // Tabella PERIODO
            "CREATE TABLE IF NOT EXISTS PERIODO (
                      Periodo VARCHAR(50) PRIMARY KEY,
                      DataInizio DATE NOT NULL,
                      DataFine DATE NOT NULL
                  )",

            // Tabella INSEGNAMENTO
            "CREATE TABLE IF NOT EXISTS INSEGNAMENTO (
                      Codice VARCHAR(10) PRIMARY KEY,
                      Nome VARCHAR(100) NOT NULL,
                      AnnoOfferta INT NOT NULL,
                      CFU INT NOT NULL CHECK (CFU > 0),
                      Lingua VARCHAR(5) NOT NULL,
                      SSD VARCHAR(10) NOT NULL,
                      Descrizione TEXT,
                      MetodoEsame TEXT,
                      DocenteTitolare INT,
                      CorsoDiStudio VARCHAR(10),
                      Periodo VARCHAR(50) NOT NULL,
                      FOREIGN KEY (Lingua) REFERENCES LINGUE(CodiceLingua),
                      FOREIGN KEY (SSD) REFERENCES SETTORE_SCIENTIFICO(SSD),
                      FOREIGN KEY (DocenteTitolare) REFERENCES DOCENTE(ID_Docente),
                      FOREIGN KEY (CorsoDiStudio) REFERENCES CORSO_DI_STUDIO(Codice),
                      FOREIGN KEY (Periodo) REFERENCES PERIODO(Periodo)
                  )",

            // Tabella DISPONIBILITA_DOCENTE
            "CREATE TABLE IF NOT EXISTS DISPONIBILITA_DOCENTE (
                    ID_Disponibilita INT AUTO_INCREMENT PRIMARY KEY,
                    ID_Docente INT NOT NULL,
                    Giorno INT NOT NULL CHECK (Giorno BETWEEN 1 AND 7),
                    OraInizio INT NOT NULL CHECK (OraInizio BETWEEN 540 AND 960),
                    OraFine INT NOT NULL CHECK (OraFine BETWEEN 660 AND 1080),
                    FOREIGN KEY (ID_Docente) REFERENCES DOCENTE(ID_Docente)
                )",

            // Tabella DISPONIBILITA_AULA
            "CREATE TABLE IF NOT EXISTS DISPONIBILITA_AULA (
                      ID_Aula INT NOT NULL,
                      Giorno ENUM('lunedi', 'martedi', 'mercoledi', 'giovedi', 'venerdi') NOT NULL,
                      OraInizio TIME NOT NULL CHECK (OraInizio >= '09:00:00' AND OraInizio <= '16:00:00'),
                      OraFine TIME NOT NULL CHECK (OraFine >= '11:00:00' AND OraFine <= '18:00:00'),
                      TipologiaUtilizzo ENUM('lezione', 'laboratorio', 'esame') NOT NULL,
                      PRIMARY KEY (ID_Aula, Giorno, OraInizio),
                      FOREIGN KEY (ID_Aula) REFERENCES AULA(ID_Aula)
                  )",

            // Tabella ORARIO
            "CREATE TABLE IF NOT EXISTS ORARIO (
                    ID_Orario INT AUTO_INCREMENT PRIMARY KEY,
                    Giorno INT NOT NULL CHECK (Giorno BETWEEN 1 AND 7), -- 1=Lunedi, 7=Domenica
                    OraInizio INT NOT NULL CHECK (OraInizio BETWEEN 540 AND 960), -- Minuti dalla mezzanotte (9:00-16:00)
                    OraFine INT NOT NULL CHECK (OraFine BETWEEN 660 AND 1080), -- Minuti dalla mezzanotte (11:00-18:00)
                    Aula INT NOT NULL,
                    Insegnamento VARCHAR(10) NOT NULL,
                    Docente INT NOT NULL,
                    Periodo VARCHAR(50) NOT NULL,
                    FOREIGN KEY (Aula) REFERENCES AULA(ID_Aula),
                    FOREIGN KEY (Insegnamento) REFERENCES INSEGNAMENTO(Codice),
                    FOREIGN KEY (Docente) REFERENCES DOCENTE(ID_Docente),
                    FOREIGN KEY (Periodo) REFERENCES PERIODO(Periodo)
                )",

            // Tabella GRUPPO_STUDENTI
            "CREATE TABLE IF NOT EXISTS GRUPPO_STUDENTI (
                    ID_Gruppo INT AUTO_INCREMENT PRIMARY KEY,
                    CodiceCorsoDiStudio VARCHAR(25) NOT NULL,
                    NumeroStudenti INT NOT NULL CHECK (NumeroStudenti > 0),
                    FOREIGN KEY (CodiceCorsoDiStudio) REFERENCES CORSO_DI_STUDIO(Codice)
                )",

            // Tabella CARICO_LAVORO_DOCENTE
            "CREATE TABLE IF NOT EXISTS CARICO_LAVORO_DOCENTE (
                      ID_Docente INT NOT NULL,
                      Periodo VARCHAR(50) NOT NULL,
                      OreTotali INT NOT NULL CHECK (OreTotali >= 0),
                      MaxOreConsentite INT NOT NULL CHECK (MaxOreConsentite > 0),
                      PRIMARY KEY (ID_Docente, Periodo),
                      FOREIGN KEY (ID_Docente) REFERENCES DOCENTE(ID_Docente),
                      FOREIGN KEY (Periodo) REFERENCES PERIODO(Periodo)
                  )",

            // Tabella AMMINISTRATORE
            "CREATE TABLE IF NOT EXISTS AMMINISTRATORE (
                ID_Amministratore INT AUTO_INCREMENT PRIMARY KEY,
                Nome VARCHAR(50) NOT NULL,
                Cognome VARCHAR(50) NOT NULL,
                Email VARCHAR(100) NOT NULL UNIQUE,
                Password VARCHAR(255) NOT NULL
            )",

            // Tabella ORARIO_STORICO
            "CREATE TABLE IF NOT EXISTS ORARIO_STORICO (
                      ID_Storico INT AUTO_INCREMENT PRIMARY KEY,
                      ID_Orario INT NOT NULL,
                      Giorno DATE NOT NULL,
                      OraInizio TIME NOT NULL,
                      OraFine TIME NOT NULL,
                      Aula INT NOT NULL,
                      Insegnamento VARCHAR(10) NOT NULL,
                      Docente INT NOT NULL,
                      DataModifica DATETIME NOT NULL,
                      ModificatoDa INT NOT NULL,
                      FOREIGN KEY (ID_Orario) REFERENCES ORARIO(ID_Orario),
                      FOREIGN KEY (ModificatoDa) REFERENCES AMMINISTRATORE(ID_Amministratore)
                  )",

            // Tabella MODIFICA
            "CREATE TABLE IF NOT EXISTS MODIFICA (
                      ID_Modifica INT AUTO_INCREMENT PRIMARY KEY,
                      AmministratoreID INT NOT NULL,
                      Oggetto VARCHAR(200),
                      DataOra DATETIME NOT NULL,
                      Dettaglio TEXT,
                      FOREIGN KEY (AmministratoreID) REFERENCES AMMINISTRATORE(ID_Amministratore)
                  )"
        ];

        // Esegui tutte le query per la crezione delle tabelle
        foreach ($queries as $query) {
            $this->eseguiQuery($query);
        }
    }

    // Funzione per creare i trigger nelle tabelle del db
    public function creaTriggers()
    {
        // Definizione delle query per la creazione dei trigger
        $triggers = [
            "CREATE TRIGGER trg_chk_orario_docente
            BEFORE INSERT ON DISPONIBILITA_DOCENTE
            FOR EACH ROW
            BEGIN
                IF NEW.OraFine <= NEW.OraInizio THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'L\'ora finale non può essere minore o uguale all\'ora di inizio.';
                END IF;
            END",

            "CREATE TRIGGER trg_chk_orario_aula
            BEFORE INSERT ON DISPONIBILITA_AULA
            FOR EACH ROW
            BEGIN
                IF NEW.OraFine <= NEW.OraInizio THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'L\'ora finale non può essere minore o uguale all\'ora di inizio.';
                END IF;
            END",

            "CREATE TRIGGER trg_chk_capacita_aula
            BEFORE INSERT ON AULA
            FOR EACH ROW
            BEGIN
                DECLARE capacita_edificio INT;

                -- Ottieni la capacità totale dell'edificio associato
                SELECT CapacitaTotale INTO capacita_edificio
                FROM EDIFICIO
                WHERE ID_Edificio = NEW.Edificio;

                -- Verifica che la Capacita dell'aula non superi la CapacitaTotale dell'edificio
                IF NEW.Capacita > capacita_edificio THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'La capacità dell\'aula non può superare la capacità totale dell\'edificio.';
                END IF;
            END",

            "CREATE TRIGGER trg_chk_date_periodo
            BEFORE INSERT ON PERIODO
            FOR EACH ROW
            BEGIN
                IF NEW.DataInizio >= NEW.DataFine THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'La data di inizio non può essere uguale o successiva alla data di fine.';
                END IF;
            END",

            "CREATE TRIGGER trg_chk_anno_corso
            BEFORE INSERT ON CORSO_DI_STUDIO
            FOR EACH ROW
            BEGIN
                IF NEW.AnnoCorso <= 0 THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'L\'anno del corso deve essere maggiore di zero.';
                END IF;
            END"
        ];

        // Esegui la query per la creazione dei trigger
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