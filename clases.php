<?php
/**
 * Archivo unificado que contiene todas las clases del sistema
 * Incluye: Sanitizer y RegistroForm
 */
class Sanitizer {
    
    /**
     * Sanitiza una cadena de texto eliminando etiquetas HTML y caracteres especiales
     * @param string $data - Dato a sanitizar
     * @return string - Dato sanitizado
     */
    public static function sanitizeString($data) {
        // Eliminar espacios al inicio y final
        $data = trim($data);
        // Eliminar barras invertidas
        $data = stripslashes($data);
        // Convertir caracteres especiales a entidades HTML
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
    
    /**
     * Sanitiza y valida un correo electrónico
     * @param string $email - Correo a sanitizar
     * @return string|false - Correo sanitizado o false si no es válido
     */
    public static function sanitizeEmail($email) {
        // Eliminar espacios
        $email = trim($email);
        // Convertir a minúsculas
        $email = strtolower($email);
        // Filtrar y validar el email
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        // Validar formato
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }
        return false;
    }
    
    /**
     * Sanitiza un número entero
     * @param mixed $number - Número a sanitizar
     * @return int - Número entero sanitizado
     */
    public static function sanitizeInt($number) {
        // Filtrar y convertir a entero
        return filter_var($number, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Sanitiza un array de datos aplicando sanitización a cada elemento
     * @param array $data - Array a sanitizar
     * @return array - Array sanitizado
     */
    public static function sanitizeArray($data) {
        $sanitized = [];
        foreach ($data as $key => $value) {
            // Sanitizar la clave
            $cleanKey = self::sanitizeString($key);
            
            // Sanitizar el valor según su tipo
            if (is_array($value)) {
                $sanitized[$cleanKey] = self::sanitizeArray($value);
            } else {
                $sanitized[$cleanKey] = self::sanitizeString($value);
            }
        }
        return $sanitized;
    }
    
    /**
     * Sanitiza texto para SQL (prevención adicional, aunque PDO prepared statements ya protegen)
     * @param string $data - Dato a sanitizar
     * @return string - Dato sanitizado
     */
    public static function sanitizeForSQL($data) {
        // Eliminar caracteres peligrosos para SQL
        $data = trim($data);
        $data = strip_tags($data);
        // Escapar comillas
        $data = addslashes($data);
        return $data;
    }
}

class RegistroForm {
    
    private $pdo;
    private $errors = [];
    private $data = [];
    
    /**
     * Constructor: recibe la conexión PDO
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Sanitiza todos los datos del formulario
     * @param array $postData - Datos del formulario POST
     */
    public function sanitizarDatos($postData) {
        $this->data['nombre'] = Sanitizer::sanitizeString($postData['nombre'] ?? '');
        $this->data['apellido'] = Sanitizer::sanitizeString($postData['apellido'] ?? '');
        $this->data['correo'] = Sanitizer::sanitizeEmail($postData['correo'] ?? '');
        $this->data['sexo'] = Sanitizer::sanitizeString($postData['sexo'] ?? '');
        $this->data['password'] = $postData['password'] ?? '';
        $this->data['confirm_password'] = $postData['confirm_password'] ?? '';
    }
    
    /**
     * Valida que todos los campos estén completos
     * @return bool
     */
    public function validarCamposRequeridos() {
        if (empty($this->data['nombre']) || empty($this->data['apellido']) || 
            empty($this->data['correo']) || empty($this->data['sexo']) || 
            empty($this->data['password']) || empty($this->data['confirm_password'])) {
            $this->errors[] = 'Por favor completa todos los campos.';
            return false;
        }
        return true;
    }
    
    /**
     * Valida el formato del correo electrónico
     * @return bool
     */
    public function validarEmail() {
        if ($this->data['correo'] === false || !filter_var($this->data['correo'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'El correo electrónico no es válido.';
            return false;
        }
        return true;
    }
    
    /**
     * Valida que el correo no esté duplicado en la BD
     * @return bool
     */
    public function validarCorreoUnico() {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
            $stmt->execute([$this->data['correo']]);
            
            if ($stmt->fetch()) {
                $this->errors[] = 'El correo electrónico ya está registrado.';
                return false;
            }
            return true;
        } catch (PDOException $e) {
            $this->errors[] = 'Error al verificar el correo.';
            return false;
        }
    }
    
    /**
     * Valida la longitud mínima de la contraseña
     * @return bool
     */
    public function validarPassword() {
        if (strlen($this->data['password']) < 6) {
            $this->errors[] = 'La contraseña debe tener al menos 6 caracteres.';
            return false;
        }
        return true;
    }
    
    /**
     * Valida que las contraseñas coincidan
     * @return bool
     */
    public function validarPasswordsCoinciden() {
        if ($this->data['password'] !== $this->data['confirm_password']) {
            $this->errors[] = 'Las contraseñas no coinciden.';
            return false;
        }
        return true;
    }
    
    /**
     * Genera el hash de la contraseña
     * @return string - Hash de la contraseña
     */
    public function generarHash() {
        return password_hash($this->data['password'], PASSWORD_DEFAULT);
    }
    
    /**
     * Guarda el hash en la base de datos
     * @param string $hash - Hash de la contraseña
     * @return int|false - ID del usuario insertado o false si falla
     */
    public function guardarUsuario($hash) {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO usuarios (nombre, apellido, correo, HashMagic, sexo, secret_2fa) 
                VALUES (?, ?, ?, ?, ?, '')"
            );
            $stmt->execute([
                $this->data['nombre'],
                $this->data['apellido'],
                $this->data['correo'],
                $hash,
                $this->data['sexo']
            ]);
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            $this->errors[] = 'Error al registrar el usuario.';
            return false;
        }
    }
    
    /**
     * Ejecuta todas las validaciones
     * @return bool - true si todas las validaciones pasan
     */
    public function validar() {
        $this->errors = []; // Limpiar errores previos
        
        if (!$this->validarCamposRequeridos()) return false;
        if (!$this->validarEmail()) return false;
        if (!$this->validarCorreoUnico()) return false;
        if (!$this->validarPassword()) return false;
        if (!$this->validarPasswordsCoinciden()) return false;
        
        return true;
    }
    
    /**
     * Obtiene los errores de validación
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Obtiene el primer error
     * @return string|null
     */
    public function getPrimerError() {
        return !empty($this->errors) ? $this->errors[0] : null;
    }
    
    /**
     * Obtiene los datos sanitizados
     * @return array
     */
    public function getData() {
        return $this->data;
    }
}