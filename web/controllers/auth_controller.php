<?php

// Includo le costanti per l'inclusione dei file
require_once __DIR__ . '/../config/constants.php';

require_once CONFIG_DIR . '/database.php';
require_once HELPERS_DIR . '/messages.php';

class AuthController
{

    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    // Funzione per la registrazione
    public function register($nome, $cognome, $email, $password)
    {
        try {
            // Pulizia input
            htmlspecialchars(strip_tags($nome));
            htmlspecialchars(strip_tags($cognome));
            htmlspecialchars(strip_tags($email));

            // Controllo la validità dell'email tramite filter_var
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo showMessage("Email non valida.", 'danger');
                return;
            }

            // Hash della password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Query per la registrazione dell'utente
            $stmt = $this->db->prepare("INSERT INTO AMMINISTRATORE (Nome, Cognome, Email, Password) VALUES (?,?,?,?)");
            $stmt->bind_param("ssss", $nome, $cognome, $email, $hashedPassword);

            if ($stmt->execute()) {
                echo showMessage("Registrazione avvenuta con successo!", 'success');
            } else {
                echo showMessage("Errore durante la registrazione:" . $stmt->error, 'danger');
            }

            $stmt->close();
        } catch (Exception $e) {
            echo showMessage("Errore: " . $e->getMessage(), 'danger');
        }
    }

    public function login($email, $password)
    {
        try {
            // Pulizia input
            $email = filter_var($email, FILTER_SANITIZE_EMAIL);

            // Controllo la validità dell'email tramite filter_var
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo showMessage("Email non valida.", 'danger');
                return;
            }

            $stmt = $this->db->prepare("SELECT ID_Amministratore, Password FROM AMMINISTRATORE WHERE Email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();

                // Verifica della password
                if (password_verify($password, $row['Password'])) {
                    session_start();
                    $_SESSION['user_id'] = $row['ID_Amministratore'];
                    echo showMessage("Login effettuato con successo!", 'success');
                } else {
                    echo showMessage("Password errata.", 'danger');
                }
            } else {
                echo showMessage("Utente non trovato.", 'danger');
            }

            $stmt->close();
        } catch (Exception $e) {
            echo showMessage("Errore: " . $e->getMessage(), 'danger');
        }
    }

    public function logout () {
        session_start();
        session_unset();
        session_destroy();
        echo showMessage("Logout effettuato con successo!", 'success');
    }
}