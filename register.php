<?php

session_start();
require_once 'conexion_bd.php';
require_once 'clases.php'; // Incluye Sanitizer y RegistroForm

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
    // Instanciar la clase de registro
    $registro = new RegistroForm($pdo);
    
    // Sanitizar datos (Requisito 5)
    $registro->sanitizarDatos($_POST);
    
    // Obtener datos sanitizados para repoblar el formulario
    $datos = $registro->getData();
    $nombre = $datos['nombre'];
    $apellido = $datos['apellido'];
    $correo = $datos['correo'];
    $sexo = $datos['sexo'];
    
    // Validar
    if ($registro->validar()) {
        // Generar hash
        $hash = $registro->generarHash();
        
        // Guardar usuario
        $userId = $registro->guardarUsuario($hash);
        
        if ($userId) {
            // Generar QR después de registrarse
            // Guardar ID temporalmente para configurar 2FA
            $_SESSION['pending_user'] = [
                'id' => $userId,
                'nombre' => $nombre,
                'correo' => $correo
            ];
            
            // Redirigir a configuración de 2FA
            header('Location: config_2fa.php');
            exit;
        } else {
            $error = $registro->getPrimerError();
        }
    } else {
        $error = $registro->getPrimerError();
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
        
        <!-- Requisito 2: Formulario funcional con validaciones -->
        <form method="POST" action="">
            <div class="form-group">
                <label for="nombre">Nombre: <span style="color: red;">*</span></label>
                <input type="text" id="nombre" name="nombre" required 
                    value="<?php echo htmlspecialchars($nombre); ?>">
            </div>
            
            <div class="form-group">
                <label for="apellido">Apellido: <span style="color: red;">*</span></label>
                <input type="text" id="apellido" name="apellido" required 
                    value="<?php echo htmlspecialchars($apellido); ?>">
            </div>
            
            <!-- Requisito 3: Validación de correo único -->
            <div class="form-group">
                <label for="correo">Correo: <span style="color: red;">*</span></label>
                <input type="email" id="correo" name="correo" required 
                    value="<?php echo htmlspecialchars($correo); ?>">
                <small>Debe ser un correo válido y único</small>
            </div>
            
            <div class="form-group">
                <label for="sexo">Sexo: <span style="color: red;">*</span></label>
                <select id="sexo" name="sexo" required>
                    <option value="">Selecciona una opción</option>
                    <option value="Masculino" <?php echo $sexo === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                    <option value="Femenino" <?php echo $sexo === 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                    <option value="Otro" <?php echo $sexo === 'Otro' ? 'selected' : ''; ?>>Otro</option>
                </select>
            </div>
            
            <!-- Requisito 2: Validación de contraseñas -->
            <div class="form-group">
                <label for="password">Contraseña: <span style="color: red;">*</span></label>
                <input type="password" id="password" name="password" required minlength="6">
                <small>Mínimo 6 caracteres</small>
            </div>
            
            <!-- Requisito 2: Contraseñas coincidentes -->
            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña: <span style="color: red;">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                <small>Debe coincidir con la contraseña</small>
            </div>
            
            <button type="submit">Registrar</button>
        </form>
        
        <p class="note">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
        </p>
    </div>
</div>

<?php include 'footer.php'; ?>