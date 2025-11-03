<?php
session_start();
require_once 'conexion_bd.php';

// Verificar que hay un usuario pendiente de verificar 2FA
if (!isset($_SESSION['pending_user']) || !isset($_SESSION['pending_user']['secret_2fa'])) {
    header('Location: login.php');
    exit;
}

$usuario = $_SESSION['pending_user'];
$secret = $usuario['secret_2fa'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? '');
    
    if (empty($codigo)) {
        $error = 'Por favor ingresa el código de 6 dígitos.';
    } elseif (!preg_match('/^\d{6}$/', $codigo)) {
        $error = 'El código debe ser de 6 dígitos numéricos.';
    } else {
        // Verificar el código TOTP
        $timestamp = floor(time() / 30);
        $validCode = false;
        
        // Verificar código actual y ±1 período (30 seg antes/después)
        for ($i = -1; $i <= 1; $i++) {
            $timeSlice = $timestamp + $i;
            $generatedCode = generateTOTP($secret, $timeSlice);
            
            if ($codigo === $generatedCode) {
                $validCode = true;
                break;
            }
        }
        
        if ($validCode) {
            // Código correcto, establecer sesión completa
            $_SESSION['user'] = $usuario['nombre'];
            $_SESSION['user_id'] = $usuario['id'];
            
            // Limpiar datos temporales
            unset($_SESSION['pending_user']);
            
            // Redirigir a página segura
            header('Location: seguridad.php');
            exit;
        } else {
            $error = 'Código incorrecto. Verifica tu aplicación de autenticación.';
        }
    }
}

// Función para generar código TOTP
function generateTOTP($secret, $timeSlice) {
    $key = base32Decode($secret);
    $time = pack('N*', 0) . pack('N*', $timeSlice);
    $hash = hash_hmac('sha1', $time, $key, true);
    $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
    $truncatedHash = substr($hash, $offset, 4);
    $value = unpack('N', $truncatedHash)[1];
    $value = $value & 0x7FFFFFFF;
    $modulo = pow(10, 6);
    return str_pad($value % $modulo, 6, '0', STR_PAD_LEFT);
}

// Función para decodificar Base32
function base32Decode($secret) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = strtoupper($secret);
    $paddedSecret = str_pad($secret, strlen($secret) + (8 - strlen($secret) % 8) % 8, '=');
    
    $binary = '';
    for ($i = 0; $i < strlen($paddedSecret); $i++) {
        if ($paddedSecret[$i] !== '=') {
            $binary .= str_pad(decbin(strpos($chars, $paddedSecret[$i])), 5, '0', STR_PAD_LEFT);
        }
    }
    
    $decoded = '';
    for ($i = 0; $i < strlen($binary); $i += 8) {
        $byte = substr($binary, $i, 8);
        if (strlen($byte) === 8) {
            $decoded .= chr(bindec($byte));
        }
    }
    
    return $decoded;
}

$page_title = 'Verificación 2FA';
include 'header.php';
?>

<div class="main-content">
    <div class="container">
        <h2>Verificación de Dos Factores</h2>
        <p class="subtitle">Verificación 2FA | UTP</p>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="verification-info">
            <p>🔒 <strong>Bienvenido/a, <?php echo htmlspecialchars($usuario['nombre']); ?></strong></p>
            <p>Para completar el inicio de sesión, ingresa el código de 6 dígitos que aparece en tu aplicación Google Authenticator.</p>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="codigo">Código de verificación:</label>
                <input type="text" id="codigo" name="codigo" required 
                       pattern="\d{6}" maxlength="6" 
                       placeholder="000000" autocomplete="off" autofocus>
            </div>
            
            <button type="submit">Verificar</button>
        </form>
        
        <p class="note">
            <a href="logout.php">Cancelar y volver al inicio</a>
        </p>
    </div>
</div>

<?php include 'footer.php'; ?>