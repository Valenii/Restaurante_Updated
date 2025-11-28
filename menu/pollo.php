<?php 
session_start(); 

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "restaurante_log_reg");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Traemos automáticamente todos los productos de la categoría 'Pollo Frito'
$sql = "SELECT p.id, p.nombre, p.precio, p.stock, p.imagen 
        FROM productos p
        INNER JOIN categorias c ON p.categoria_id = c.id
        WHERE c.nombre = 'Pollo Frito'";
// Ejecuta la consulta SQL y devuelve los productos
$resultado = $conexion->query($sql);

$productos = [];// Array donde se guardarán los productos
if ($resultado && $resultado->num_rows > 0) {
     // Recorre cada fila (producto) y lo agrega al array
    while ($row = $resultado->fetch_assoc()) {
        $productos[] = $row;
    }
} else {
    // Si no hay productos, se detiene la ejecución mostrando un mensaje
    echo "<p style='color:red; text-align:center;'>No hay productos de Pollo Frito disponibles.</p>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Pollo Frito - MendoFood</title>
    <link rel="stylesheet" href="../normalize.css" />
    <link rel="stylesheet" href="../index.css" />
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        /* --- Modal del carrito --- */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%;
                 background-color:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:1000; }
        .modal-contenido { background:white; padding:20px; border-radius:10px; width:320px;
                           max-height:80vh; overflow-y:auto; }
        .modal-contenido ul { list-style:none; padding:0; margin:0 0 10px 0; }
        .modal-contenido li { border-bottom:1px solid #ccc; padding:5px 0; display:flex;
                              justify-content:space-between; align-items:center; }
        .modal-contenido li button.eliminar { background:none; color:red; border:none; cursor:pointer;
                                              font-size:20px; display:flex; align-items:center; justify-content:center; }
        .carrito { position:relative; display:inline-block; margin-left:20px; cursor:pointer; }
        .carrito-icono { width:30px; }
        #contador-carrito { position:absolute; top:-8px; right:-10px; background-color:orange; color:white;
                            font-size:12px; font-weight:bold; padding:3px 6px; border-radius:50%; }
        .cerrar { background:#444; color:white; padding:8px 15px; border:none; cursor:pointer;
                  border-radius:5px; margin-top:10px; width:100%; font-weight:700; }
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
        <h2 class="comida--titulo">Pollo Frito</h2>
        <div class="platos">
            <?php foreach ($productos as $producto): ?>
            <article class="plato">
                <img src="../Imagenes/<?php echo htmlspecialchars($producto['imagen']); ?>" 
                     alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                <h1><?php echo htmlspecialchars($producto['nombre']); ?></h1>
                <p>Delicioso pollo frito con el sabor clásico y crujiente que tanto te gusta.</p>
                <div class="plato--info">
                    <p>$<?php echo number_format($producto['precio'], 2); ?></p>
                    <p>Stock: <span class="stock" data-id="<?php echo $producto['id']; ?>">
                        <?php echo $producto['stock']; ?></span></p>
                    <button type="button" class="btn-agregar"
                            data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>";
                            data-precio="<?php echo $producto['precio']; ?>";
                            data-categoria="Pollo Frito";
                            data-id="<?php echo $producto['id']; ?>">+</button>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- MODAL DEL CARRITO -->
    <div class="modal" id="modal-carrito">
        <div class="modal-contenido">
            <h2>Tu carrito</h2>
            <ul id="lista-carrito"></ul>
            <p><strong>Total:</strong> $<span id="total-carrito">0.00</span></p>
            <button class="cerrar" id="cerrar-carrito">Cerrar</button>
            <button class="cerrar" id="btn-comprar">Comprar</button>
        </div>
    </div>
</div>

<script>
// ===============================
// CARRITO COMPARTIDO ENTRE PÁGINAS
// ===============================
const abrirCarrito = document.getElementById("abrir-carrito");
const cerrarCarrito = document.getElementById("cerrar-carrito");
const modal = document.getElementById("modal-carrito");
const listaCarrito = document.getElementById("lista-carrito");
const totalCarrito = document.getElementById("total-carrito");
const contadorCarrito = document.getElementById("contador-carrito");
const botonesAgregar = document.querySelectorAll(".btn-agregar");
const btnComprar = document.getElementById("btn-comprar");
const usuarioLogueado = <?php echo isset($_SESSION['usuario']) ? 'true' : 'false'; ?>;

// Recuperar carrito global
let carrito = JSON.parse(localStorage.getItem("carrito")) || [];

// ====== FUNCIONES ======
function guardarCarrito() {
    localStorage.setItem("carrito", JSON.stringify(carrito));
}

function actualizarContador() {
    contadorCarrito.textContent = carrito.reduce((acc, item) => acc + item.cantidad, 0);
}

function mostrarCarrito() {
    listaCarrito.innerHTML = "";
    let total = 0;
    carrito.forEach((item, index) => {
        total += item.precio * item.cantidad;
        const li = document.createElement("li");
        li.innerHTML = `
            <span class="nombre-producto">${item.nombre} x${item.cantidad}</span>
            <span>$${(item.precio * item.cantidad).toFixed(2)}</span>
            <button class="eliminar"><ion-icon name="close-outline"></ion-icon></button>
        `;
        li.querySelector(".eliminar").addEventListener("click", () => eliminarProducto(index));
        listaCarrito.appendChild(li);
    });
    totalCarrito.textContent = total.toFixed(2);
}

function eliminarProducto(index) {
    carrito.splice(index, 1);
    guardarCarrito();
    actualizarContador();
    mostrarCarrito();
}

function agregarAlCarrito(nombre, precio, categoria, producto_id) {
    producto_id = parseInt(producto_id);
    const index = carrito.findIndex(item => item.nombre === nombre && item.categoria === categoria);
    if (index !== -1) {
        carrito[index].cantidad++;
    } else {
        carrito.push({ nombre, precio: parseFloat(precio), cantidad: 1, categoria, producto_id });
    }
    guardarCarrito();
    actualizarContador();
}

// ====== EVENTOS ======
botonesAgregar.forEach(boton => {
    boton.addEventListener("click", () => {
        const nombre = boton.dataset.nombre;
        const precio = boton.dataset.precio;
        const categoria = boton.dataset.categoria;
        const producto_id = boton.dataset.id;
        agregarAlCarrito(nombre, precio, categoria, producto_id);
        mostrarCarrito();
    });
});

abrirCarrito.addEventListener("click", () => { mostrarCarrito(); modal.style.display = "flex"; });
cerrarCarrito.addEventListener("click", () => { modal.style.display = "none"; });
window.addEventListener("click", e => { if (e.target === modal) modal.style.display = "none"; });

// Al cargar, mantener el contador sincronizado
actualizarContador();

// ====== COMPRAR ======
btnComprar.addEventListener("click", () => {
    if (!usuarioLogueado) {
        alert("⚠️ Primero tienes que iniciar sesión para comprar.");
        window.location.href = "../login-register.php";
    } else {
        const payload = { productos: carrito.map(item => ({ id: item.producto_id, cantidad: item.cantidad })) };
        fetch("../procesar_stock.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.mensaje) {
                alert("Compra realizada con éxito.");
                carrito = [];
                guardarCarrito();
                mostrarCarrito();
                actualizarContador();
            } else {
                alert("Error al actualizar stock: " + data.error);
            }
        }).catch(err => alert("Error al procesar la compra: " + err));
    }
});
</script>
</body>
</html>
