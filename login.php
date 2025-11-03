<?php
session_start();
require_once 'conexion_bd.php';

// Si ya hay sesión activa, redirigir a seguridad.php
if (isset($_SESSION['user'])) {
    header('Location: seguridad.php');
    exit;
}

$error = '';
$success = '';

// Mostrar mensaje de éxito si viene del registro
if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success = '¡Registro exitoso! Ahora puedes iniciar sesión.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Obtener IP y User Agent para el registro
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
    $agente = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';
    
    if (empty($correo) || empty($password)) {
        $error = 'Por favor completa todos los campos.';
    } else {
        try {
            // Buscar usuario por correo
            $stmt = $pdo->prepare("SELECT id, nombre, apellido, correo, HashMagic, secret_2fa, activo FROM usuarios WHERE correo = ?");
            $stmt->execute([$correo]);
            $usuario = $stmt->fetch();
            
            if ($usuario && password_verify($password, $usuario['HashMagic'])) {
                // Verificar que la cuenta esté activa
                if ($usuario['activo'] != 1) {
                    $error = 'Tu cuenta está desactivada. Contacta al administrador.';
                    
                    // Registrar intento fallido
                    $stmt = $pdo->prepare("INSERT INTO intentos_login (correo, estado, ip, agente) VALUES (?, 'fallido', ?, ?)");
                    $stmt->execute([$correo, $ip, $agente]);
                } else {
                    // Registrar intento exitoso
                    $stmt = $pdo->prepare("INSERT INTO intentos_login (correo, estado, ip, agente) VALUES (?, 'exitoso', ?, ?)");
                    $stmt->execute([$correo, $ip, $agente]);
                    
                    // Verificar si el usuario ya tiene 2FA configurado
                    if (!empty($usuario['secret_2fa'])) {
                        // Ya tiene 2FA, ir a verificación
                        $_SESSION['pending_user'] = [
                            'id' => $usuario['id'],
                            'nombre' => $usuario['nombre'],
                            'correo' => $usuario['correo'],
                            'secret_2fa' => $usuario['secret_2fa']
                        ];
                        header('Location: verificar_2fa.php');
                        exit;
                    } else {
                        // Primera vez, configurar 2FA
                        $_SESSION['pending_user'] = [
                            'id' => $usuario['id'],
                            'nombre' => $usuario['nombre'],
                            'correo' => $usuario['correo']
                        ];
                        header('Location: config_2fa.php');
                        exit;
                    }
                }
            } else {
                $error = 'Correo o contraseña incorrectos.';
                
                // Registrar intento fallido
                $stmt = $pdo->prepare("INSERT INTO intentos_login (correo, estado, ip, agente) VALUES (?, 'fallido', ?, ?)");
                $stmt->execute([$correo, $ip, $agente]);
            }
        } catch (PDOException $e) {
            $error = 'Error al procesar la solicitud.';
        }
    }
}

$page_title = 'Iniciar Sesión';
include 'header.php';
?>

<div class="main-content">
    <div class="container">
        <h2>Iniciar Sesión</h2>
        <p class="subtitle">Registro de Usuario | UTP</p>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="correo">Correo:</label>
                <input type="email" id="correo" name="correo" required 
                       value="<?php echo htmlspecialchars($correo ?? ''); ?>"
                       placeholder="correo@ejemplo.com">
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required
                       placeholder="••••••">
            </div>
            
            <button type="submit">Iniciar Sesión</button>
        </form>
        
        <p class="note">
            ¿No tienes cuenta? <a href="register.php">Regístrate aquí</a>
        </p>
    </div>
</div>

<?php include 'footer.php'; ?>