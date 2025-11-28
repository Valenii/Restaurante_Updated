<?php
session_start();

// 🟠 Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: login-register.php");
    exit;
}

// 🟠 Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "restaurante_log_reg");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

/* ============================================================
   🟢 ELIMINAR PRODUCTO
============================================================ */
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);

    // Obtener nombre de imagen y eliminar archivo
    $resultado = $conexion->query("SELECT imagen FROM productos WHERE id = $id");
    if ($resultado && $fila = $resultado->fetch_assoc()) {
        $imagenRuta = "Imagenes/" . $fila['imagen'];
        if (file_exists($imagenRuta)) unlink($imagenRuta);
    }

    // Eliminar producto de la base
    $conexion->query("DELETE FROM productos WHERE id = $id");
    header("Location: admin.php");
    exit;
}

/* ============================================================
   🟢 EDITAR PRODUCTO
============================================================ */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar_id'])) {
    $id = intval($_POST['editar_id']);
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $stock = $_POST['stock'];
    $categoria_id = $_POST['categoria_id'];

    // Obtener nombre de la categoría
    $res = $conexion->query("SELECT nombre FROM categorias WHERE id = '$categoria_id'");
    $categoriaNombre = '';
    if ($res && $row = $res->fetch_assoc()) {
        $categoriaNombre = $row['nombre'];
    }

    // Si subió nueva imagen
    if (isset($_FILES["imagen"]) && $_FILES["imagen"]["error"] == 0) {
        $nombreArchivo = time() . "_" . basename($_FILES["imagen"]["name"]);
        $rutaDestino = "Imagenes/" . $nombreArchivo;

        if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $rutaDestino)) {
            // Eliminar la anterior
            $res = $conexion->query("SELECT imagen FROM productos WHERE id = $id");
            if ($res && $fila = $res->fetch_assoc()) {
                $imgAnterior = "Imagenes/" . $fila['imagen'];
                if (file_exists($imgAnterior)) unlink($imgAnterior);
            }
            $conexion->query("UPDATE productos SET imagen='$nombreArchivo' WHERE id=$id");
        }
    }

    // Actualizar resto de los campos
    $conexion->query("UPDATE productos 
                      SET nombre='$nombre', descripcion='$descripcion', precio='$precio', stock='$stock',
                          categoria='$categoriaNombre', categoria_id='$categoria_id'
                      WHERE id=$id");

    header("Location: admin.php");
    exit;
}

/* ============================================================
   🟢 INSERTAR NUEVO PRODUCTO
============================================================ */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nuevo'])) {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $stock = $_POST['stock'];
    $categoria_id = $_POST['categoria_id'];

    // Obtener nombre de la categoría
    $res = $conexion->query("SELECT nombre FROM categorias WHERE id = '$categoria_id'");
    $categoriaNombre = '';
    if ($res && $row = $res->fetch_assoc()) {
        $categoriaNombre = $row['nombre'];
    }

    // Subir imagen
    $imagen = "";
    if (isset($_FILES["imagen"]) && $_FILES["imagen"]["error"] == 0) {
        $nombreArchivo = time() . "_" . basename($_FILES["imagen"]["name"]);
        $rutaDestino = "Imagenes/" . $nombreArchivo;
        if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $rutaDestino)) {
            $imagen = $nombreArchivo;
        }
    }

    // Insertar producto
    $conexion->query(
        "INSERT INTO productos (nombre, precio, stock, descripcion, imagen, categoria_id)
         VALUES ('$nombre', '$precio', '$stock', '$descripcion', '$imagen', '$categoria_id')"
    );

    header("Location: admin.php");
    exit;
}

/* ============================================================
   🟢 OBTENER PRODUCTOS Y CATEGORÍAS
============================================================ */
$productos = $conexion->query("
    SELECT p.id, 
           p.nombre, 
           p.precio, 
           p.stock, 
           p.descripcion, 
           p.imagen, 
           c.nombre AS categoria, 
           p.categoria_id
    FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
");

$categorias = $conexion->query("SELECT * FROM categorias");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel Admin - MendoFood</title>
<link rel="stylesheet" href="normalize.css">
<link rel="stylesheet" href="index.css">

<style>
body { background-color: rgb(245,245,245); }
.admin-container { width: 90%; margin: 30px auto; }
h1 { text-align: center; margin-bottom: 30px; }
.productos-tabla { width: 100%; border-collapse: collapse; background-color: white; border-radius: 10px; overflow: hidden; }
.productos-tabla th, .productos-tabla td { padding: 12px; text-align: center; border-bottom: 1px solid #ddd; }
.productos-tabla th { background-color: orange; color: white; }
.productos-tabla img { width: 80px; border-radius: 8px; }
.btn-editar, .btn-eliminar { padding: 6px 10px; border: none; border-radius: 6px; cursor: pointer; color: white; font-weight: 700; }
.btn-editar { background-color: #ff9800; }
.btn-eliminar { background-color: #e74c3c; }
.btn-editar:hover { background-color: #e68900; }
.btn-eliminar:hover { background-color: #c0392b; }
.formulario { background-color: white; padding: 20px; border-radius: 10px; margin-top: 40px; }
.formulario h2 { text-align: center; margin-bottom: 20px; }
.formulario form { display: flex; flex-direction: column; gap: 15px; }
.formulario input, .formulario select, .formulario textarea {
  padding: 10px; border: 1px solid #ccc; border-radius: 8px; font-size: 15px;
}
.formulario button {
  background-color: orange; color: white; font-weight: 900; padding: 10px;
  border: none; border-radius: 8px; cursor: pointer;
}
.formulario button:hover { background-color: #cc7a00; }
.header-admin {
  padding: 15px 5%; background-color: white; display: flex;
  align-items: center; justify-content: space-between; border-bottom: 1px solid #ddd;
}
.header-admin .logo p { font-weight: 900; font-size: 22px; }
.header-admin .logo span { color: orange; }
.header-admin a {
  color: white; background-color: orange; padding: 8px 14px;
  border-radius: 8px; font-weight: 700; text-decoration: none;
}
.header-admin a:hover { background-color: #cc7a00; }
.modal {
  display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(0,0,0,0.5); justify-content: center; align-items: center;
}
.modal-content {
  background: white; padding: 20px; border-radius: 10px; width: 400px;
}
</style>
</head>
<body>

<header class="header-admin">
  <div class="logo"><p>MENDO<span>FOOD</span></p></div>
  <a href="logout.php">Cerrar Sesión</a>
</header>

<div class="admin-container">
  <h1>Panel de Administración</h1>

  <table class="productos-tabla">
    <thead>
      <tr>
        <th>ID</th><th>Imagen</th><th>Nombre</th><th>Precio</th><th>Stock</th><th>Categoría</th><th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($p = $productos->fetch_assoc()): ?>
      <tr>
        <td><?= $p['id']; ?></td>
        <td><img src="Imagenes/<?= htmlspecialchars($p['imagen']); ?>"></td>
        <td><?= htmlspecialchars($p['nombre']); ?></td>
        <td>$<?= number_format($p['precio'], 2); ?></td>
        <td><?= $p['stock']; ?></td>
        <td><?= $p['categoria'] ?: 'Sin categoría'; ?></td>
        <td>
          <button class="btn-editar" 
            onclick="abrirModal('<?= $p['id']; ?>','<?= htmlspecialchars($p['nombre']); ?>','<?= htmlspecialchars($p['descripcion']); ?>','<?= $p['precio']; ?>','<?= $p['stock']; ?>','<?= $p['categoria_id']; ?>')">
            Editar
          </button>
          <a href="?eliminar=<?= $p['id']; ?>" onclick="return confirm('¿Seguro que quieres eliminar este producto?');">
            <button class="btn-eliminar">Eliminar</button>
          </a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <div class="formulario">
    <h2>Agregar Nuevo Producto</h2>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="nuevo" value="1">
      <input type="text" name="nombre" placeholder="Nombre del producto" required>
      <textarea name="descripcion" placeholder="Descripción" rows="3"></textarea>
      <input type="number" name="precio" step="0.01" placeholder="Precio" required>
      <input type="number" name="stock" placeholder="Stock disponible" required>
      <label>Seleccionar imagen:</label>
      <input type="file" name="imagen" accept="image/*" required>

      <select name="categoria_id" required>
        <option value="">Seleccione categoría</option>
        <?php mysqli_data_seek($categorias, 0);
        while ($c = $categorias->fetch_assoc()): ?>
          <option value="<?= $c['id']; ?>"><?= htmlspecialchars($c['nombre']); ?></option>
        <?php endwhile; ?>
      </select>
      <button type="submit">Agregar Producto</button>
    </form>
  </div>
</div>

<!-- 🟠 Modal de edición -->
<div class="modal" id="modalEditar">
  <div class="modal-content">
    <h2>Editar Producto</h2>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="editar_id" id="editar_id">
      <input type="text" name="nombre" id="editar_nombre" required>
      <textarea name="descripcion" id="editar_descripcion" rows="3"></textarea>
      <input type="number" name="precio" step="0.01" id="editar_precio" required>
      <input type="number" name="stock" id="editar_stock" required>
      <label>Imagen nueva (opcional):</label>
      <input type="file" name="imagen" accept="image/*">

      <select name="categoria_id" id="editar_categoria" required>
        <option value="">Seleccione categoría</option>
        <?php mysqli_data_seek($categorias, 0);
        while ($c = $categorias->fetch_assoc()): ?>
          <option value="<?= $c['id']; ?>"><?= htmlspecialchars($c['nombre']); ?></option>
        <?php endwhile; ?>
      </select>

      <button type="submit">Guardar Cambios</button>
    </form>
  </div>
</div>

<script>
function abrirModal(id,nombre,descripcion,precio,stock,categoria){
  document.getElementById('modalEditar').style.display='flex';
  document.getElementById('editar_id').value=id;
  document.getElementById('editar_nombre').value=nombre;
  document.getElementById('editar_descripcion').value=descripcion;
  document.getElementById('editar_precio').value=precio;
  document.getElementById('editar_stock').value=stock;
  document.getElementById('editar_categoria').value=categoria;
}
window.onclick=function(e){
  const modal=document.getElementById('modalEditar');
  if(e.target==modal){ modal.style.display='none'; }
}
</script>

</body>
</html>

