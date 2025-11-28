<?php
session_start();

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "restaurante_log_reg");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Traer todos los productos (aunque no los mostremos aquí)
$resultado = $conexion->query("SELECT ID, Nombre, Precio, Stock FROM productos");
$productos = [];
if ($resultado) {
    while ($row = $resultado->fetch_assoc()) {
        $productos[$row['ID']] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<title>Servicios - MendoFood</title>

<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700;900&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="normalize.css" />
<link rel="stylesheet" href="index.css" />
<link rel="stylesheet" href="estilo_servicio.css">

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

<style>
/* Modal carrito */
.modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); justify-content: center; align-items: center; z-index: 1000; }
.modal-contenido { background: white; padding: 20px; border-radius: 10px; width: 320px; max-height: 80vh; overflow-y: auto; }
.modal-contenido ul { list-style: none; padding: 0; margin: 0 0 10px 0; }
.modal-contenido li { border-bottom: 1px solid #ccc; padding: 5px 0; display: flex; justify-content: space-between; align-items: center; }
.modal-contenido li span.nombre-producto { flex: 1; }
.modal-contenido li button.eliminar { background: none; color: red; border: none; cursor: pointer; font-size: 20px; display: flex; align-items: center; justify-content: center; }
.modal-contenido li button.eliminar:hover { color: darkred; }

/* Icono del carrito */
.carrito { position: relative; display: inline-block; margin-left: 20px; cursor: pointer; }
.carrito-icono { width: 30px; }
#contador-carrito { position: absolute; top: -8px; right: -10px; background-color: orange; color: white; font-size: 12px; font-weight: bold; padding: 3px 6px; border-radius: 50%; min-width: 18px; text-align: center; }

.cerrar { background: #444; color: white; padding: 8px 15px; border: none; cursor: pointer; border-radius: 5px; margin-top: 10px; width: 100%; font-weight: 700; }
</style>
</head>
<body>
<div class="contenedor">
  <!-- HEADER con carrito -->
  <header class="header">
    <div class="logo"><p>MENDO<span>FOOD</span></p></div>
    <div class="hamburguesa"><img src="Imagenes/menu.png" alt="Menu hamburguesa"></div>
    <nav class="menu">
      <ul class="navegacion">
        <li><a href="index.php">Inicio</a></li>
        <li><a href="Menu.php">Menú</a></li>
        <li><a href="servicios.php">Servicios</a></li>
        <li><a href="We.php">Nosotros</a></li>
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

      <!-- Carrito -->
      <div class="carrito" id="abrir-carrito">
        <img src="Imagenes/icons8-carrito-de-compras-30.png" alt="Carrito" class="carrito-icono" />
        <span id="contador-carrito">0</span>
      </div>
    </nav>
  </header>

  <!-- Contenido principal -->
  <main class="servicios">
    <h1 class="servicios--titulo">Servicios que ofrecemos</h1>
    <div class="container">
      <div class="service"><h2>Pedido en Línea</h2><p>Ordená tu comida favorita desde nuestra web con un proceso simple y rápido.</p></div>
      <div class="service"><h2>Delivery Rápido</h2><p>Recibí tu pedido en minutos gracias a nuestro servicio de reparto eficiente.</p></div>
      <div class="service"><h2>Catering para Eventos</h2><p>Organizá tu fiesta con nuestro catering personalizado para grupos grandes.</p></div>
      <div class="service"><h2>App Móvil Próximamente</h2><p>Próximamente tendrás nuestra app para pedir desde tu celular fácilmente.</p></div>
      <div class="service"><h2>Medios de Pago</h2><p>Aceptamos tarjetas, transferencias y pagos digitales para tu comodidad.</p></div>
      <div class="service"><h2>Programa de Puntos</h2><p>Acumulá puntos con cada compra y canjealos por productos y descuentos.</p></div>
    </div>
  </main>
</div>

<!-- Modal carrito -->
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
// Elementos
const abrirCarrito = document.getElementById("abrir-carrito");
const cerrarCarrito = document.getElementById("cerrar-carrito");
const modal = document.getElementById("modal-carrito");
const listaCarrito = document.getElementById("lista-carrito");
const totalCarrito = document.getElementById("total-carrito");
const contadorCarrito = document.getElementById("contador-carrito");
const btnComprar = document.getElementById("btn-comprar");

// Usuario logueado
const usuarioLogueado = <?php echo isset($_SESSION['usuario']) ? 'true' : 'false'; ?>;

// Carrito persistente (solo cargar, no filtrar ni sobrescribir)
let carrito = JSON.parse(localStorage.getItem("carrito")) || [];

// Funciones
function guardarCarrito(){ localStorage.setItem("carrito", JSON.stringify(carrito)); }
function actualizarContador(){ contadorCarrito.textContent = carrito.reduce((acc,i)=>acc+i.cantidad,0); }

function mostrarCarrito(){
    listaCarrito.innerHTML = "";
    let total = 0;
    carrito.forEach((item,index)=>{
        total += item.precio * item.cantidad;
        const li = document.createElement("li");
        const nombreSpan = document.createElement("span");
        nombreSpan.textContent = `${item.nombre} x${item.cantidad}`;
        nombreSpan.classList.add("nombre-producto");
        const precioSpan = document.createElement("span");
        precioSpan.textContent = `$${(item.precio*item.cantidad).toFixed(2)}`;
        const botonEliminar = document.createElement("button");
        botonEliminar.innerHTML = '<ion-icon name="close-outline"></ion-icon>';
        botonEliminar.classList.add("eliminar");
        botonEliminar.addEventListener("click",()=>{ eliminarProducto(index); });
        li.appendChild(nombreSpan); li.appendChild(precioSpan); li.appendChild(botonEliminar);
        listaCarrito.appendChild(li);
    });
    totalCarrito.textContent = total.toFixed(2);
}

function eliminarProducto(index){ carrito.splice(index,1); guardarCarrito(); mostrarCarrito(); actualizarContador(); }

// Abrir/cerrar modal
abrirCarrito.addEventListener("click",()=>{ mostrarCarrito(); modal.style.display="flex"; });
cerrarCarrito.addEventListener("click",()=>{ modal.style.display="none"; });
window.addEventListener("click", e=>{ if(e.target===modal) modal.style.display="none"; });

// Comprar productos
btnComprar.addEventListener("click",()=>{
    if(!usuarioLogueado){
        alert("⚠️ Primero tienes que iniciar sesión para comprar.");
        window.location.href="login-register.php";
    } else {
        fetch("procesar_compra.php",{
            method:"POST",
            headers:{"Content-Type":"application/x-www-form-urlencoded"},
            body:"carrito="+encodeURIComponent(JSON.stringify(carrito))
        }).then(res=>res.json()).then(data=>{
            if(data.success){
                alert(data.success);
                carrito=[]; guardarCarrito(); mostrarCarrito(); actualizarContador();
            } else { alert("Error: "+data.error); }
        }).catch(err=>alert("Error al procesar la compra: "+err));
    }
});

// Inicializar contador y carrito al cargar
document.addEventListener("DOMContentLoaded",()=>{
    actualizarContador();
    mostrarCarrito();
});
</script>
</body>
</html>
