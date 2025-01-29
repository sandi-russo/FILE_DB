<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/messages.php';

class AuthController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function register($data)
    {
        try {
            // Sanitizzazione input
            $cf = strtoupper(trim($data['cf']));
            $nome = htmlspecialchars(strip_tags($data['nome']));
            $cognome = htmlspecialchars(strip_tags($data['cognome']));
            $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
            $password = $data['password'];

            // Validazioni
            if (!preg_match('/^[A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z]$/', $cf)) {
                return ["message" => "Codice fiscale non valido.", "status" => "error"];
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ["message" => "Email non valida.", "status" => "error"];
            }

            // Verifica se utente già esistente
            $stmt = $this->db->prepare("SELECT * FROM UTENTE WHERE CodiceFiscale = ? OR Email = ?");
            $stmt->bind_param("ss", $cf, $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                return ["message" => "Utente già registrato.", "status" => "error"];
            }
            $stmt->close();

            // Hash della password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Inserimento nuovo utente
            $stmt = $this->db->prepare("INSERT INTO UTENTE (CodiceFiscale, Email, Nome, Cognome, Password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $cf, $email, $nome, $cognome, $hashedPassword);

            if ($stmt->execute()) {
                return [
                    "message" => "Registrazione completata con successo! Attendi l'approvazione di un amministratore.",
                    "status" => "success"
                ];
            } else {
                throw new Exception("Errore durante la registrazione");
            }

        } catch (Exception $e) {
            return ["message" => "Errore: " . $e->getMessage(), "status" => "error"];
        }
    }

    public function getPendingUsers()
    {
        try {
            $query = "SELECT * FROM UTENTE WHERE IdAmministratore IS NULL";
            $result = $this->db->query($query);
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getRuoli()
    {
        try {
            $query = "SELECT DISTINCT Id, Nome FROM RUOLO ORDER BY Nome";
            $result = $this->db->query($query);
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getSSD()
    {
        try {
            $query = "SELECT Codice, Descrizione FROM SSD ORDER BY Codice";
            $result = $this->db->query($query);
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getCorsiDiStudio()
    {
        try {
            $query = "SELECT Nome, Descrizione FROM CORSO_DI_STUDIO ORDER BY Nome";
            $result = $this->db->query($query);
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }



    public function approveUser($cf, $email, $type, $adminId, $data)
    {
        try {
            $this->db->begin_transaction();

            // Aggiorna l'ID amministratore nell'utente
            $stmt = $this->db->prepare("UPDATE UTENTE SET IdAmministratore = ? WHERE CodiceFiscale = ? AND Email = ?");
            $stmt->bind_param("iss", $adminId, $cf, $email);
            if (!$stmt->execute()) {
                throw new Exception("Errore nell'aggiornamento dell'utente");
            }

            if ($type === 'docente') {
                // Valida i dati del docente
                if (!isset($data['ruoloId']) || !isset($data['ssdCodice'])) {
                    throw new Exception("Ruolo e SSD sono richiesti per i docenti");
                }

                $stmt = $this->db->prepare("INSERT INTO DOCENTE (CodiceFiscale, Email, IdRuolo, CodiceSSD) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssis", $cf, $email, $data['ruoloId'], $data['ssdCodice']);
                if (!$stmt->execute()) {
                    throw new Exception("Errore nell'inserimento del docente");
                }
            } else {
                // Valida i dati dello studente
                if (!isset($data['corsoDiStudio'])) {
                    throw new Exception("Corso di studio richiesto per gli studenti");
                }

                // Inserisci come studente
                $stmt = $this->db->prepare("INSERT INTO STUDENTE (CodiceFiscale, Email) VALUES (?, ?)");
                $stmt->bind_param("ss", $cf, $email);
                if (!$stmt->execute()) {
                    throw new Exception("Errore nell'inserimento dello studente");
                }

                $studentId = $this->db->insert_id;

                // Inserisci l'appartenenza
                $stmt = $this->db->prepare("INSERT INTO APPARTENENZA (IdStudente, NomeCorsoDiStudio) VALUES (?, ?)");
                $stmt->bind_param("is", $studentId, $data['corsoDiStudio']);
                if (!$stmt->execute()) {
                    throw new Exception("Errore nell'inserimento dell'appartenenza");
                }
            }

            $this->db->commit();
            return ["message" => "Utente approvato con successo", "status" => "success"];

        } catch (Exception $e) {
            $this->db->rollback();
            return ["message" => "Errore nell'approvazione: " . $e->getMessage(), "status" => "error"];
        }
    }
    public function login($data)
    {
        try {
            // Sanitizzazione input
            $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
            $password = $data['password'] ?? '';

            // Query per ottenere tutti i dati dell'amministratore
            $stmt = $this->db->prepare("SELECT Id, Nome, Cognome, Email, Password FROM AMMINISTRATORE WHERE Email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['Password'])) {
                    // Inizia sessione
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }

                    // Salva i dati dell'utente nella sessione
                    $_SESSION['user_id'] = $user['Id'];
                    $_SESSION['nome'] = $user['Nome'];
                    $_SESSION['cognome'] = $user['Cognome'];
                    $_SESSION['email'] = $user['Email'];

                    return [
                        "message" => "Login effettuato con successo!",
                        "status" => "success"
                    ];
                } else {
                    return ["message" => "Password errata.", "status" => "error"];
                }
            } else {
                return ["message" => "Utente non trovato.", "status" => "error"];
            }

        } catch (Exception $e) {
            return ["message" => "Errore durante il login: " . $e->getMessage(), "status" => "error"];
        }
    }
    public function logout()
    {
        session_start();
        session_unset();
        session_destroy();
        http_response_code(200);
        return ["message" => "Logout effettuato con successo!", "status" => "success"];
    }
}