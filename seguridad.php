<?php
session_start(); // Inicia la sesión para acceder a las variables de sesión.

// Verificación de seguridad: Si no hay un usuario en la sesión,
// Significa que el usuario no ha iniciado sesión.
// En este caso, se le redirige a la página de login.
if (!isset($_SESSION['user'])) {
    header('Location: login.php'); // Redirige al usuario a login.php.
    exit; // Termina la ejecución del script para evitar que se muestre contenido no autorizado.
}

$page_title = 'Zona Protegida';
include 'header.php';
?>

<div class="main-content">
    <div class="container">
        <h2>¡Bienvenid@ <?php echo htmlspecialchars($_SESSION['user']); ?>! 👋</h2>
        <p class="subtitle">Usted ha iniciado sesión exitosamente.</p>
        
        <?php 
        // Comprueba si la URL contiene el parámetro '2fa_enabled' y si su valor es '1'.
        // Esto se usa para mostrar un mensaje de éxito después de que el usuario habilita 2FA.
        if (isset($_GET['2fa_enabled']) && $_GET['2fa_enabled'] == 1): 
        ?>
            <div class="message success">¡Autenticación de Dos Factores habilitada con éxito!</div>
        <?php endif; ?>

        <div class="verification-info">
            <p>✅ Has iniciado sesión correctamente en el sistema.</p>
            <p>Tu sesión está protegida con autenticación de dos factores (2FA).</p>
        </div>

        <button onclick="location.href='logout.php'">Cerrar sesión</button>
    </div>
</div>

<?php include 'footer.php'; ?>
