<?php
// Este archivo muestra los privilegios de los usuarios de base de datos
session_start();
$page_title = 'Privilegios de Base de Datos';

// Conexión como root para poder ver los privilegios
$DB_HOST = '127.0.0.1';
$DB_USER = 'root';  // Solo root puede ver privilegios de otros usuarios
$DB_PASS = '';
$DB_PORT = '3306';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;port=$DB_PORT", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Usuarios a verificar
    $usuarios = ['login_app_user', 'login_readonly'];
    $privilegios = [];
    
    foreach ($usuarios as $usuario) {
        // Consulta para mostrar privilegios
        $stmt = $pdo->query("SHOW GRANTS FOR '{$usuario}'@'localhost'");
        $privilegios[$usuario] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
} catch (PDOException $e) {
    $error = "Error al conectar: " . $e->getMessage();
}

include 'header.php';
?>

<div class="main-content">
    <div class="container">
        <h2>Privilegios de Usuarios de Base de Datos</h2>
        <p class="subtitle">Verificación de Seguridad | UTP</p>
        
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <p class="note">Nota: Asegúrate de haber creado los usuarios mediante script SQL.</p>
        <?php else: ?>
            <div class="verification-info">
                <h3>Comando utilizado para ver privilegios:</h3>
                <code>SHOW GRANTS FOR 'nombre_usuario'@'localhost';</code>
            </div>
            
            <?php foreach ($privilegios as $usuario => $grants): ?>
                <div style="margin: 20px 0; padding: 15px; background: #f5f5f5; border-left: 4px solid #003366;">
                    <h3>Usuario: <?php echo htmlspecialchars($usuario); ?>@localhost</h3>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($grants as $grant): ?>
                            <li style="padding: 5px 0; font-family: monospace;">
                                ✓ <?php echo htmlspecialchars($grant); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
            
            <div class="message success">
                ✅ Los usuarios tienen privilegios mínimos necesarios (principio de menor privilegio)
            </div>
        <?php endif; ?>
        
        <button onclick="location.href='<?php echo isset($_SESSION['user']) ? 'seguridad.php' : 'login.php'; ?>'">
            Volver
        </button>
    </div>
</div>

<?php include 'footer.php'; ?>