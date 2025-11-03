<?php
session_start();
require_once 'conexion_bd.php';

// Si ya hay sesión activa, redirigir
if (isset($_SESSION['user'])) {
    header('Location: seguridad.php');
    exit;
}

$error = '';
$nombre = '';
$apellido = '';
$correo = '';
$sexo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $sexo = $_POST['sexo'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validaciones
    if (empty($nombre) || empty($apellido) || empty($correo) || empty($sexo) || empty($password) || empty($confirm_password)) {
        $error = 'Por favor completa todos los campos.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        try {
            // Verificar si el correo ya existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
            $stmt->execute([$correo]);
            
            if ($stmt->fetch()) {
                $error = 'El correo electrónico ya está registrado.';
            } else {
                // Hash de la contraseña
                $hashMagic = password_hash($password, PASSWORD_DEFAULT);
                
                // Insertar nuevo usuario (secret_2fa vacío por ahora)
                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, apellido, correo, HashMagic, sexo, secret_2fa) VALUES (?, ?, ?, ?, ?, '')");
                $stmt->execute([$nombre, $apellido, $correo, $hashMagic, $sexo]);
                
                // Redirigir al login con mensaje de éxito
                header('Location: login.php?registered=1');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Error al registrar el usuario. Inténtalo de nuevo.';
        }
    }
}

$page_title = 'Registro de Usuario';
include 'header.php';
?>

<div class="main-content">
    <div class="container">
        <h2>Registro de Usuario</h2>
        <p class="subtitle">Registro de Usuario | UTP</p>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" required 
                    value="<?php echo htmlspecialchars($nombre); ?>">
            </div>
            
            <div class="form-group">
                <label for="apellido">Apellido:</label>
                <input type="text" id="apellido" name="apellido" required 
                    value="<?php echo htmlspecialchars($apellido); ?>">
            </div>
            
            <div class="form-group">
                <label for="correo">Correo:</label>
                <input type="email" id="correo" name="correo" required 
                    value="<?php echo htmlspecialchars($correo); ?>">
            </div>
            
            <div class="form-group">
                <label for="sexo">Sexo:</label>
                <select id="sexo" name="sexo" required>
                    <option value="">Selecciona una opción</option>
                    <option value="Masculino" <?php echo $sexo === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                    <option value="Femenino" <?php echo $sexo === 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                    <option value="Otro" <?php echo $sexo === 'Otro' ? 'selected' : ''; ?>>Otro</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required minlength="6">
                <small>Mínimo 6 caracteres</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña:</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            
            <button type="submit">Registrar</button>
        </form>
        
        <p class="note">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
        </p>
    </div>
</div>

<?php include 'footer.php'; ?>