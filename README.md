# Sistema de Login con 2FA (Google Authenticator)

### Sistema de autenticación web con registro de usuarios y verificación de dos factores usando Google Authenticator.

## 🚀 Características


- **✅** Registro de usuarios con validación
- **✅** Inicio de sesión seguro
- **✅** Autenticación de dos factores (2FA) con Google Authenticator
- **✅** Generación de códigos QR
- **✅** Registro de intentos de login
- **✅** Diseño responsive

## 🛠️ Tecnologías

- **PHP** 8.10+
- **MySQL/MariaDB**
- **Google** Authenticator (TOTP)
- **QR** Code (chillerlan/php-qrcode)
- **HTML5/CSS3**

## 📋 Requisitos

- **WAMP/XAMPP/LAMP** Server
- **PHP** 8.10 o superior
- **MySQL** 5.7 o superior
- **Composer**

## 🔧 Instalación

### 1. Clonar el repositorio

```bash

git clone https://github.com/AnieLuo/LAB_4_2FA.git

cd LAB_4_2FA

```

### 2. Instalar dependencias

```bash

composer install
composer init
composer require sonata-project/google-authenticator
composer require chillerlan/php-qrcode

```

### 3. Configurar base de datos

Crea la base de datos ejecutando el script SQL:

```sql

CREATE DATABASE IF NOT EXISTS login_lab CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE login_lab;

CREATE TABLE usuarios (
  id INT AUTO\_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) NOT NULL,
  correo VARCHAR(191) NOT NULL UNIQUE,
  HashMagic VARCHAR(255) NOT NULL,
  sexo VARCHAR(20) NOT NULL,
  secret\_2fa VARCHAR(255) NOT NULL,
  fecha\_registro TIMESTAMP DEFAULT CURRENT\_TIMESTAMP,
  activo TINYINT(1) DEFAULT 1
);

CREATE TABLE intentos\_login (
  id INT AUTO\_INCREMENT PRIMARY KEY,
  correo VARCHAR(100),
  estado ENUM('exitoso','fallido') NOT NULL,
  ip VARCHAR(45),
  agente VARCHAR(255),
  fecha TIMESTAMP DEFAULT CURRENT\_TIMESTAMP
);

```

### 4. Configurar conexión

Copia el archivo de ejemplo y configura tus credenciales:

```bash

cp conexion\_bd.example.php conexion\_bd.php

```

Edita `conexion\_bd.php` con tus credenciales de MySQL.

### 5. Configurar servidor web

- **Coloca** el proyecto en la carpeta `www` de WAMP o `htdocs` de XAMPP
- **Accede** a: `http://localhost/nombre_de_carpeta_de_proyecto`

## 📱 Uso

1\. Registrarse\: Crea una cuenta nueva

2\. Iniciar sesión\: Usa tu correo y contraseña

3\. Configurar 2FA\: En el primer login, escanea el código QR con Google Authenticator

4\. Verificar\: Ingresa el código de 6 dígitos

5\. Acceder\: ¡Listo! Has iniciado sesión de forma segura



## 📁 Estructura del proyecto

```

proyecto/

├── conexion\_bd.php          # Configuración BD (no se sube a Git)

├── conexion\_bd.example.php  # Ejemplo de configuración

├── estilo.css               # Estilos

├── header.php               # Header común

├── footer.php               # Footer común

├── login.php                # Página de login

├── register.php             # Página de registro

├── config\_2fa.php           # Configuración 2FA

├── verificar\_2fa.php        # Verificación 2FA

├── seguridad.php            # Página protegida

├── logout.php               # Cerrar sesión

├── composer.json            # Dependencias

├── vendor/                  # Librerías (no se sube)

└── README.md                # Este archivo

```



## 🔒 Seguridad

- **Contraseñas** hasheadas con `password\_hash()`
- **Consultas** preparadas (PDO) para prevenir SQL Injection
- **Validación** de datos en servidor
- **Protección** XSS con `htmlspecialchars()`
- **Sesiones** seguras
- **2FA** con algoritmo TOTP estándar

## 👨‍💻 Autor

#### Anie Luo
#### Universidad Tecnológica de Panamá
#### Curso: Ingeniería Web
#### Profesora: Ing. Irina Fong

## 📄 Licencia
Este proyecto fue desarrollado con fines educativos.



---

