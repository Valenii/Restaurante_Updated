<?php
session_start();

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "restaurante_log_reg");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
// Traer todos los productos que pertenezcan a la categoría 'Hamburguesas'
$sql = "SELECT p.id, p.nombre, p.precio, p.stock, p.imagen
        FROM productos p
        INNER JOIN categorias c ON p.categoria_id = c.id
        WHERE c.nombre = 'Hamburguesas'";
 
// Ejecuta la consulta SQL y devuelve los productos
$resultado = $conexion->query($sql);
$productos = [];  // Array donde se guardarán los productos

if ($resultado) {
  // Recorre cada fila (producto) y lo agrega al array
    while ($row = $resultado->fetch_assoc()) {
        $productos[] = $row;
    }
} else {
  // Si no hay productos, se detiene la ejecución mostrando un mensaje
    die("Error en la consulta: " . $conexion->error);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Hamburguesas - MendoFood</title>
  <link rel="stylesheet" href="../normalize.css" />
  <link rel="stylesheet" href="../index.css" />
  <style>
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      background-color: rgba(0,0,0,0.6); justify-content: center; align-items: center; z-index: 1000; }
    .modal-contenido { background: white; padding: 20px; border-radius: 10px;
      width: 320px; max-height: 80vh; overflow-y: auto; }
    .modal-contenido ul { list-style: none; padding: 0; margin: 0 0 10px 0; }
    .modal-contenido li { border-bottom: 1px solid #ccc; padding: 5px 0;
      display: flex; justify-content: space-between; align-items: center; }
    .modal-contenido li span.nombre-producto { flex: 1; }
    .modal-contenido li button.eliminar {
      background: none; color: red; border: none; cursor: pointer; font-size: 20px;
      display: flex; align-items: center; justify-content: center;
    }
    .carrito { position: relative; display: inline-block; margin-left: 20px; cursor: pointer; }
    .carrito-icono { width: 30px; }
    #contador-carrito {
      position: absolute; top: -8px; right: -10px; background-color: orange;
      color: white; font-size: 12px; font-weight: bold; padding: 3px 6px;
      border-radius: 50%; min-width: 18px; text-align: center;
    }
    .cerrar {
      background: #444; color: white; padding: 8px 15px; border: none; cursor: pointer;
      border-radius: 5px; margin-top: 10px; width: 100%; font-weight: 700;
    }
    .btn-agregar {
      background-color: orange;
      color: white;
      border: none;
      border-radius: 50%;
      width: 35px;
      height: 35px;
      font-size: 20px;
      cursor: pointer;
      transition: 0.3s;
    }
    .btn-agregar:hover {
      background-color: darkorange;
      transform: scale(1.1);
    }
  </style>
</head>
<body>
<div class="contenedor">
  <header class="header">
    <div class="logo"><p>MENDO<span>FOOD</span></p></div>
    <nav class="menu">
      <ul class="navegacion">
        <li><a href="../index.php">Inicio</a></li>
        <li><a href="../Menu.php">Menú</a></li>
        <li><a href="../servicios.php">Servicios</a></li>
        <li><a href="../We.php">Nosotros</a></li>
        <li><a href="#">Galería</a></li>
        <li>
          <?php if(isset($_SESSION['usuario'])): ?>
            <span><?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
            <a href="../logout.php">Cerrar sesión</a>
          <?php else: ?>
            <a href="../login-register.php">Iniciar Sesión</a>
            <a href="../login-register.php">Registrarse</a>
          <?php endif; ?>
        </li>
      </ul>
      <div class="carrito" id="abrir-carrito">
        <img src="../Imagenes/icons8-carrito-de-compras-30.png" alt="Carrito" class="carrito-icono" />
        <span id="contador-carrito">0</span>
      </div>
    </nav>
  </header>

  <main class="comida">
    <h2 class="comida--titulo">Hamburguesas</h2>
    <div class="platos">
      <?php foreach($productos as $p): ?>
        <article class="plato">
          <img src="../Imagenes/<?php echo htmlspecialchars($p['imagen']); ?>" 
               alt="<?php echo htmlspecialchars($p['nombre']); ?>">
          <p>Deliciosa hamburguesa preparada con ingredientes frescos.</p>
          <div class="plato--info">
            <p>$<?php echo $p['precio']; ?></p>
            <p>Stock: <?php echo $p['stock']; ?></p>
            <button type="button" class="btn-agregar"
                    data-id="<?php echo $p['id']; ?>"
                    data-nombre="<?php echo htmlspecialchars($p['nombre']); ?>"
                    data-precio="<?php echo $p['precio']; ?>">+</button>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </main>
</div>

<div class="modal" id="modal-carrito">
  <div class="modal-contenido">
    <h2>Tu carrito</h2>
    <ul id="lista-carrito"></ul>
    <p><strong>Total:</strong> $<span id="total-carrito">0.00</span></p>
    <button class="cerrar" id="cerrar-carrito">Cerrar</button>
    <button class="cerrar" id="btn-comprar">Comprar</button>
  </div>
</div>

<script>
const abrirCarrito = document.getElementById("abrir-carrito");
const cerrarCarrito = document.getElementById("cerrar-carrito");
const modal = document.getElementById("modal-carrito");
const listaCarrito = document.getElementById("lista-carrito");
const totalCarrito = document.getElementById("total-carrito");
const contadorCarrito = document.getElementById("contador-carrito");
const btnComprar = document.getElementById("btn-comprar");
const botonesAgregar = document.querySelectorAll(".btn-agregar");

let carrito = JSON.parse(localStorage.getItem("carrito")) || [];

// Limpia carritos antiguos con datos incorrectos
if (carrito.some(item => !item.id)) {
  localStorage.removeItem("carrito");
  carrito = [];
}

const usuarioLogueado = <?php echo isset($_SESSION['usuario']) ? 'true' : 'false'; ?>;

function guardarCarrito() {
  localStorage.setItem("carrito", JSON.stringify(carrito));
}

function actualizarContador() {
  const totalCantidad = carrito.reduce((acc, item) => acc + item.cantidad, 0);
  contadorCarrito.textContent = totalCantidad;
}

function mostrarCarrito() {
  listaCarrito.innerHTML = "";
  let total = 0;
  carrito.forEach((item, index) => {
    total += item.precio * item.cantidad;
    const li = document.createElement("li");
    li.innerHTML = `<span class="nombre-producto">${item.nombre} x${item.cantidad}</span>
                    <span>$${(item.precio * item.cantidad).toFixed(2)}</span>
                    <button class="eliminar">×</button>`;
    li.querySelector(".eliminar").addEventListener("click", () => {
      carrito.splice(index, 1);
      guardarCarrito();
      mostrarCarrito();
      actualizarContador();
    });
    listaCarrito.appendChild(li);
  });
  totalCarrito.textContent = total.toFixed(2);
}

botonesAgregar.forEach(boton => {
  boton.addEventListener("click", () => {
    const id = parseInt(boton.dataset.id);
    const nombre = boton.dataset.nombre;
    const precio = parseFloat(boton.dataset.precio);

    const existente = carrito.find(item => item.id === id);
    if (existente) {
      existente.cantidad++;
    } else {
      carrito.push({ id, nombre, precio, cantidad: 1 });
    }
    guardarCarrito();
    actualizarContador();
    mostrarCarrito();
  });
});

abrirCarrito.addEventListener("click", () => { mostrarCarrito(); modal.style.display = "flex"; });
cerrarCarrito.addEventListener("click", () => { modal.style.display = "none"; });
window.addEventListener("click", e => { if (e.target === modal) modal.style.display = "none"; });

btnComprar.addEventListener("click", () => {
  if (!usuarioLogueado) {
    alert("⚠️ Primero tienes que iniciar sesión para comprar.");
    window.location.href = "../login-register.php";
  } else {
    fetch("../procesar_compra.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "carrito=" + encodeURIComponent(JSON.stringify(carrito))
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert(data.success);
        carrito = [];
        guardarCarrito();
        mostrarCarrito();
        actualizarContador();
      } else {
        alert("Error: " + data.error);
      }
    })
    .catch(err => alert("Error al procesar la compra: " + err));
  }
});

document.addEventListener("DOMContentLoaded", actualizarContador);
</script>
</body>
</html>

