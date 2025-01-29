<?php
function renderPage($content) {
    return "<!DOCTYPE html>
            <html>
            <head>
                <link rel='stylesheet' href='/assets/css/style.css'>
            </head>
            <body>
                $content
            </body>
            </html>";
}

// Funzione per la stampa di un messaggio
function showMessage ($message, $type = 'info') {

    /**
     * Restituisco una stringa HTML che crea un elemento <div> con una classe CSS dinamica
     * classe CSS dell'alert è costruita utilizzando il parametro $type, che avrà un valore di default 'info'
     * In questo modo, la funzione può essere utilizzata per mostrare messaggi di diversi tipi, come 'info', 'success', 'warning', 'danger'
     */
    return "<div class='alert alert-$type'>$message</div>";
}
