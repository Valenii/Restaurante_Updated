<?php
session_start();
include 'conexion.php';

$correo = $_POST['correo'];
$contrasena = $_POST['contrasena'];

// Sentencia preparada para mayor seguridad
$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE Correo = ?");
$stmt->bind_param("s", $correo);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $usuario = $resultado->fetch_assoc();

    // Verificamos la contraseña encriptada
    if (password_verify($contrasena, $usuario['Contraseña'])) {

        // ✅ Guardamos todos los datos necesarios en la sesión
        $_SESSION['id'] = $usuario['ID'];            // 👈 ID real del usuario
        $_SESSION['usuario'] = $usuario['Nombre'];   // Nombre
        $_SESSION['rol'] = $usuario['rol'];          // Rol (si tu tabla tiene ese campo)

        // Redirigimos según rol
        if ($usuario['rol'] == 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: Menu.php");
        }
        exit;

    } else {
        echo "<script>alert('❌ Contraseña incorrecta'); window.location='login.php';</script>";
    }
} else {
    echo "<script>alert('❌ El correo no está registrado'); window.location='login.php';</script>";
}

$conexion->close();
?>
