<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione</title>
</head>

<body>

    <!-- Richiamo il file routes.php e gli passo il 'register' nella variabile action che poi sfrutto nello switch case-->
    <form method="POST" action="/routes.php?action=register">
        <label for="nome">Nome:</label>
        <!-- Salvo l'input con la chiave "nome" -->
        <input type="text" id="nome" name="nome" required>
        <!-- Salvo l'input con la chiave "cognome" -->
        <label for="cognome">Cognome:</label>
        <input type="text" id="cognome" name="cognome" required>
        <!-- Salvo l'input con la chiave "email" -->
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <!-- Salvo l'input con la chiave "password" -->
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Registrati</button>
    </form>

</body>

</html>