<?php
session_start();
require_once 'conexion_bd.php';
require_once 'vendor/autoload.php'; // Cargar las dependencias de Composer

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

// Verificar que hay un usuario pendiente de configurar 2FA
if (!isset($_SESSION['pending_user'])) {
    header('Location: login.php');
    exit;
}

$usuario = $_SESSION['pending_user'];
$error = '';
$success = '';

// Generar secret si no existe en la sesión
if (!isset($_SESSION['2fa_temp_secret'])) {
    // Generar un secret aleatorio de 16 caracteres (Base32)
    $secret = '';
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Caracteres válidos en Base32
    for ($i = 0; $i < 16; $i++) {
        $secret .= $chars[random_int(0, strlen($chars) - 1)];
    }
    $_SESSION['2fa_temp_secret'] = $secret;
} else {
    $secret = $_SESSION['2fa_temp_secret'];
}

// Crear la URL para Google Authenticator
$issuer = 'LoginLab'; // Nombre de tu aplicación
$accountName = $usuario['correo'];
$otpauthUrl = "otpauth://totp/{$issuer}:{$accountName}?secret={$secret}&issuer={$issuer}";

// Generar el código QR
$qrcode = new QRCode();
$qrCodeImage = $qrcode->render($otpauthUrl);

// Procesar verificación del código 2FA
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
        
        // Verificar código actual y ±1 período (30 seg antes/después) para dar margen
        for ($i = -1; $i <= 1; $i++) {
            $timeSlice = $timestamp + $i;
            $generatedCode = generateTOTP($secret, $timeSlice);
            
            if ($codigo === $generatedCode) {
                $validCode = true;
                break;
            }
        }
        
        if ($validCode) {
            try {
                // Guardar el secret en la base de datos
                $stmt = $pdo->prepare("UPDATE usuarios SET secret_2fa = ? WHERE id = ?");
                $stmt->execute([$secret, $usuario['id']]);
                
                // Establecer la sesión completa
                $_SESSION['user'] = $usuario['nombre'];
                $_SESSION['user_id'] = $usuario['id'];
                
                // Limpiar datos temporales
                unset($_SESSION['pending_user']);
                unset($_SESSION['2fa_temp_secret']);
                
                // Redirigir a seguridad.php
                header('Location: seguridad.php?2fa_enabled=1');
                exit;
            } catch (PDOException $e) {
                $error = 'Error al guardar la configuración. Inténtalo de nuevo.';
            }
        } else {
            $error = 'Código incorrecto. Verifica e intenta nuevamente.';
        }
    }
}

// Función para generar código TOTP
function generateTOTP($secret, $timeSlice) {
    // Decodificar Base32
    $key = base32Decode($secret);
    
    // Convertir timestamp a bytes (64-bit big-endian)
    $time = pack('N*', 0) . pack('N*', $timeSlice);
    
    // Generar HMAC-SHA1
    $hash = hash_hmac('sha1', $time, $key, true);
    
    // Obtener offset del último byte
    $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
    
    // Extraer 4 bytes desde el offset
    $truncatedHash = substr($hash, $offset, 4);
    
    // Convertir a entero de 32 bits
    $value = unpack('N', $truncatedHash)[1];
    
    // Aplicar máscara y obtener 6 dígitos
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

$page_title = 'Configurar 2FA';
include 'header.php';
?>

<div class="main-content">
    <div class="container">
        <h2>Configurar Autenticación de Dos Factores</h2>
        <p class="subtitle">Configuración de 2FA | UTP</p>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="instructions">
            <p><strong>Sigue estos pasos para configurar tu 2FA:</strong></p>
            <ol>
                <li>Abre Google Authenticator en tu dispositivo móvil</li>
                <li>Escanea el código QR a continuación</li>
                <li>O ingresa manualmente la clave secreta</li>
                <li>Ingresa el código de 6 dígitos que aparece en la app</li>
            </ol>
        </div>
        
        <div class="qr-container">
            <p><strong>Escanea este código QR:</strong></p>
            <img src="<?php echo $qrCodeImage; ?>" alt="Código QR 2FA">
        </div>
        
        <div class="secret-code">
            <strong>Clave secreta (manual):</strong><br>
            <?php echo chunk_split($secret, 4, ' '); ?>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="codigo">Código de verificación (6 dígitos):</label>
                <input type="text" id="codigo" name="codigo" required 
                       pattern="\d{6}" maxlength="6" 
                       placeholder="000000" autocomplete="off">
            </div>
            
            <button type="submit">Verificar y Activar 2FA</button>
        </form>
        
        <p class="note">
            <a href="logout.php">Cancelar y cerrar sesión</a>
        </p>
    </div>
</div>

<?php include 'footer.php'; ?>