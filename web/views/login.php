<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>login</title>
</head>

<body>

    <!-- Richiamo il file routes.php e gli passo il 'register' nella variabile action che poi sfrutto nello switch case-->
    <form method="POST" action="/routes.php?action=login">
        <label for="email">Email:</label>
        <!-- Salvo l'input con la chiave "email" -->
        <input type="email" id="email" name="email" required>

        <label for="password">Password:</label>
        <!-- Salvo l'input con la chiave "password" -->
        <input type="password" id="password" name="password" required>

        <button type="submit">Login</button>
    </form>

</body>

</html>