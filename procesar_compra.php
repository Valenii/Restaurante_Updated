<?php
session_start();
require_once "conexion.php"; // conexión en $conexion

// Mostrar errores (solo en desarrollo)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
header('Content-Type: application/json; charset=utf-8');

// ✅ Validar sesión
if (!isset($_SESSION['id'])) {
    echo json_encode(['error' => 'No estás logueado.']);
    exit;
}

$usuario_id = intval($_SESSION['id']);

// ✅ Validar carrito recibido
if (!isset($_POST['carrito'])) {
    echo json_encode(['error' => 'Carrito no enviado.']);
    exit;
}

$carrito = json_decode($_POST['carrito'], true);
if (!$carrito || !is_array($carrito) || count($carrito) === 0) {
    echo json_encode(['error' => 'Carrito vacío o inválido.']);
    exit;
}

$totalCompra = 0;

// ✅ Iniciar transacción
$conexion->begin_transaction();

try {
    foreach ($carrito as $item) {

        // 🔹 Validar datos recibidos
        if (!isset($item['id']) || !isset($item['cantidad']) || !isset($item['precio'])) {
            throw new Exception("Datos incompletos del producto en el carrito.");
        }

        $producto_id = intval($item['id']);
        $cantidad    = intval($item['cantidad']);
        $precio      = floatval($item['precio']);

        if ($producto_id <= 0 || $cantidad <= 0 || $precio <= 0) {
            throw new Exception("Datos inválidos en el producto del carrito.");
        }

        $subtotal = $cantidad * $precio;
        $totalCompra += $subtotal;

        // 🔹 Verificar stock del producto
        $q = $conexion->prepare("SELECT stock FROM productos WHERE id = ?");
        $q->bind_param("i", $producto_id);
        $q->execute();
        $r = $q->get_result();
        $producto = $r->fetch_assoc();

        if (!$producto) {
            throw new Exception("Producto no encontrado (ID: $producto_id)");
        }

        if ($producto['stock'] < $cantidad) {
            throw new Exception("Stock insuficiente para el producto ID $producto_id");
        }

        // 🔹 Actualizar stock
        $nuevoStock = $producto['stock'] - $cantidad;
        $update = $conexion->prepare("UPDATE productos SET stock = ? WHERE id = ?");
        $update->bind_param("ii", $nuevoStock, $producto_id);
        $update->execute();

        // 🔹 Registrar compra
        $insert = $conexion->prepare("
            INSERT INTO compras (usuario_id, producto_id, cantidad, total, fecha)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $insert->bind_param("iiid", $usuario_id, $producto_id, $cantidad, $subtotal);
        $insert->execute();
    }

    // ✅ Confirmar transacción
    $conexion->commit();

    echo json_encode([
        'success' => 'Compra registrada correctamente 🎉',
        'total' => number_format($totalCompra, 2, ',', '.')
    ]);

} catch (Exception $e) {
    // ❌ Revertir cambios en caso de error
    $conexion->rollback();
    echo json_encode(['error' => $e->getMessage()]);
}
?>
