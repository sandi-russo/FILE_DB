<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /views/login.php');
    exit;
}

require_once __DIR__ . '/../controllers/auth_controller.php';
$authController = new AuthController();
$pendingUsers = $authController->getPendingUsers();
$ruoli = $authController->getRuoli();
$ssds = $authController->getSSD();
$corsiDiStudio = $authController->getCorsiDiStudio();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Amministratore</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
      
    </style>
</head>
<body>
    <div class="container">
        <div class="user-info">
            <h2>Dashboard Amministratore</h2>
            <p>Benvenuto, <?php echo htmlspecialchars($_SESSION['nome'] . ' ' . $_SESSION['cognome']); ?></p>
            <p>Email: <?php echo htmlspecialchars($_SESSION['email']); ?></p>
        </div>

        <div id="alertMessage" class="alert"></div>

        <div class="pending-users">
            <h3>Utenti in attesa di approvazione</h3>
            <table id="pendingUsersTable">
                <thead>
                    <tr>
                        <th>Codice Fiscale</th>
                        <th>Nome</th>
                        <th>Cognome</th>
                        <th>Email</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingUsers as $user): ?>
                    <tr id="user-<?php echo htmlspecialchars($user['CodiceFiscale']); ?>">
                        <td><?php echo htmlspecialchars($user['CodiceFiscale']); ?></td>
                        <td><?php echo htmlspecialchars($user['Nome']); ?></td>
                        <td><?php echo htmlspecialchars($user['Cognome']); ?></td>
                        <td><?php echo htmlspecialchars($user['Email']); ?></td>
                        <td class="action-buttons">
                            <button class="btn btn-student" onclick="showStudentApprovalModal('<?php echo $user['CodiceFiscale']; ?>', '<?php echo $user['Email']; ?>')">
                                Approva come Studente
                            </button>
                            <button class="btn btn-teacher" onclick="showTeacherApprovalModal('<?php echo $user['CodiceFiscale']; ?>', '<?php echo $user['Email']; ?>')">
                                Approva come Docente
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <form id="logoutForm" action="/routes.php?action=logout" method="POST" style="margin-top: 20px;">
            <button class="btn" type="submit">Logout</button>
        </form>
    </div>

    <!-- Modal per l'approvazione docente -->
    <div id="teacherModal" class="modal">
        <div class="modal-content">
            <h4>Approva come Docente</h4>
            <form id="teacherApprovalForm">
                <input type="hidden" id="teacherCf">
                <input type="hidden" id="teacherEmail">
                
                <div class="form-group">
                    <label for="ruoloSelect">Ruolo:</label>
                    <select id="ruoloSelect" required>
                        <option value="">Seleziona ruolo</option>
                        <?php foreach ($ruoli as $ruolo): ?>
                        <option value="<?php echo $ruolo['Id']; ?>">
                            <?php echo htmlspecialchars($ruolo['Nome']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="ssdSelect">SSD:</label>
                    <select id="ssdSelect" required>
                        <option value="">Seleziona SSD</option>
                        <?php foreach ($ssds as $ssd): ?>
                        <option value="<?php echo $ssd['Codice']; ?>">
                            <?php echo htmlspecialchars($ssd['Codice'] . ' - ' . $ssd['Descrizione']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="btn" onclick="closeModal('teacherModal')">Annulla</button>
                    <button type="submit" class="btn btn-teacher">Conferma</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal per l'approvazione studente -->
    <div id="studentModal" class="modal">
        <div class="modal-content">
            <h4>Approva come Studente</h4>
            <form id="studentApprovalForm">
                <input type="hidden" id="studentCf">
                <input type="hidden" id="studentEmail">
                
                <div class="form-group">
                    <label for="corsoSelect">Corso di Studio:</label>
                    <select id="corsoSelect" required>
                        <option value="">Seleziona corso</option>
                        <?php foreach ($corsiDiStudio as $corso): ?>
                        <option value="<?php echo $corso['Nome']; ?>">
                            <?php echo htmlspecialchars($corso['Nome']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="btn" onclick="closeModal('studentModal')">Annulla</button>
                    <button type="submit" class="btn btn-student">Conferma</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function showAlert(message, type) {
        const alert = document.getElementById('alertMessage');
        alert.textContent = message;
        alert.className = `alert alert-${type}`;
        alert.style.display = 'block';
        setTimeout(() => {
            alert.style.display = 'none';
        }, 3000);
    }

    function showTeacherApprovalModal(cf, email) {
        document.getElementById('teacherCf').value = cf;
        document.getElementById('teacherEmail').value = email;
        document.getElementById('teacherModal').style.display = 'block';
    }

    function showStudentApprovalModal(cf, email) {
        document.getElementById('studentCf').value = cf;
        document.getElementById('studentEmail').value = email;
        document.getElementById('studentModal').style.display = 'block';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    document.getElementById('teacherApprovalForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const cf = document.getElementById('teacherCf').value;
        const email = document.getElementById('teacherEmail').value;
        const ruoloId = document.getElementById('ruoloSelect').value;
        const ssdCodice = document.getElementById('ssdSelect').value;

        approveUser(cf, email, 'docente', {
            ruoloId: ruoloId,
            ssdCodice: ssdCodice
        });
        
        closeModal('teacherModal');
    });

    document.getElementById('studentApprovalForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const cf = document.getElementById('studentCf').value;
        const email = document.getElementById('studentEmail').value;
        const corsoDiStudio = document.getElementById('corsoSelect').value;

        approveUser(cf, email, 'studente', {
            corsoDiStudio: corsoDiStudio
        });
        
        closeModal('studentModal');
    });

    function approveUser(cf, email, type, additionalData) {
        fetch('/routes.php?action=approve', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                cf: cf,
                email: email,
                type: type,
                ...additionalData
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const row = document.getElementById(`user-${cf}`);
                if (row) {
                    row.remove();
                }
                showAlert(`Utente approvato con successo come ${type}`, 'success');
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Errore durante l\'approvazione', 'error');
        });
    }
    </script>
</body>
</html>