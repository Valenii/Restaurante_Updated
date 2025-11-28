<?php
session_start();

$conexion = new mysqli("localhost", "root", "", "restaurante_log_reg");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$resultado = $conexion->query("SELECT ID, Nombre, Precio, Stock, Imagen FROM productos");
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
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Menú - MendoFood</title>
<link rel="stylesheet" href="normalize.css" />
<link rel="stylesheet" href="index.css" />
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
<style>
.modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background-color: rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:1000; }
.modal-contenido { background:white; padding:20px; border-radius:10px; width:320px; max-height:80vh; overflow-y:auto; }
.modal-contenido ul { list-style:none; padding:0; margin:0 0 10px 0; }
.modal-contenido li { border-bottom:1px solid #ccc; padding:5px 0; display:flex; justify-content:space-between; align-items:center; }
.modal-contenido li span.nombre-producto { flex:1; }
.modal-contenido li button.eliminar { background:none; color:red; border:none; cursor:pointer; font-size:20px; }
.carrito { position:relative; display:inline-block; margin-left:20px; cursor:pointer; }
.carrito-icono { width:30px; }
#contador-carrito { position:absolute; top:-8px; right:-10px; background-color:orange; color:white; font-size:12px; font-weight:bold; padding:3px 6px; border-radius:50%; }
.cerrar { background:#444; color:white; padding:8px 15px; border:none; cursor:pointer; border-radius:5px; margin-top:10px; width:100%; font-weight:700; }
</style>
</head>
<body>
<div class="contenedor">
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
        <a href="logout.php">Cerrar sesión</a>
      <?php else: ?>
        <a href="login-register.php">Iniciar Sesión</a>
        <a href="login-register.php">Registrarse</a>
      <?php endif; ?>
    </li>
  </ul>

  <div class="carrito" id="abrir-carrito">
    <img src="Imagenes/icons8-carrito-de-compras-30.png" alt="Carrito" class="carrito-icono" />
    <span id="contador-carrito">0</span>
  </div>
</nav>
</header>

<main class="comida">
  <h2 class="comida--titulo">Nuestro Menú</h2>
  <div class="platos">
    <article class="plato"><a href="menu/hamburguesas.php"><img src="Imagenes/Hamburguesas.png" alt="Hamburguesas" /></a><h1>Hamburguesas</h1></article>
    <article class="plato"><a href="menu/papas.php"><img src="Imagenes/papas-fritas.png" alt="Papas Fritas" /></a><h1>Papas Fritas</h1></article>
    <article class="plato"><a href="menu/bebidas.php"><img src="Imagenes/coca_vidrio.png" alt="Bebidas" /></a><h1>Bebidas</h1></article>
    <article class="plato"><a href="menu/pollo.php"><img src="Imagenes/pollo_frito_clasico.png" alt="Pollo Frito" /></a><h1>Pollo Frito</h1></article>
  </div>
</main>

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
const abrirCarrito = document.getElementById("abrir-carrito");
const cerrarCarrito = document.getElementById("cerrar-carrito");
const modal = document.getElementById("modal-carrito");
const listaCarrito = document.getElementById("lista-carrito");
const totalCarrito = document.getElementById("total-carrito");
const contadorCarrito = document.getElementById("contador-carrito");
const btnComprar = document.getElementById("btn-comprar");
const usuarioLogueado = <?php echo isset($_SESSION['usuario']) ? 'true' : 'false'; ?>;

// Recuperar carrito completo de localStorage
let carrito = JSON.parse(localStorage.getItem("carrito")) || [];

// Funciones
function actualizarContador(){
    contadorCarrito.textContent = carrito.reduce((acc,item)=>acc+item.cantidad,0);
}

function mostrarCarrito(){
    listaCarrito.innerHTML = "";
    let total=0;
    carrito.forEach((item,index)=>{
        total += item.precio * item.cantidad;
        const li = document.createElement("li");
        li.innerHTML = `<span class="nombre-producto">${item.nombre} x${item.cantidad}</span>
                        <span>$${(item.precio*item.cantidad).toFixed(2)}</span>
                        <button class="eliminar"><ion-icon name="close-outline"></ion-icon></button>`;
        li.querySelector(".eliminar").addEventListener("click",()=>{
            carrito.splice(index,1);
            localStorage.setItem("carrito", JSON.stringify(carrito));
            actualizarContador();
            mostrarCarrito();
        });
        listaCarrito.appendChild(li);
    });
    totalCarrito.textContent = total.toFixed(2);
}

// Abrir y cerrar modal
abrirCarrito.addEventListener("click", ()=>{ mostrarCarrito(); modal.style.display="flex"; });
cerrarCarrito.addEventListener("click", ()=>{ modal.style.display="none"; });
window.addEventListener("click", e=>{ if(e.target===modal) modal.style.display="none"; });

// Comprar
btnComprar.addEventListener("click", ()=>{
    if(!usuarioLogueado){
        alert("⚠️ Primero tienes que iniciar sesión para comprar.");
        window.location.href="login-register.php";
    } else {
        fetch("procesar_stock.php", {
            method:"POST",
            headers:{"Content-Type":"application/json"},
            body:JSON.stringify({productos: carrito.map(item=>({id:item.producto_id,cantidad:item.cantidad}))})
        })
        .then(res=>res.json())
        .then(data=>{
            if(data.mensaje){
                alert("Compra realizada con éxito.");
                carrito=[];
                localStorage.setItem("carrito", JSON.stringify(carrito));
                actualizarContador();
                mostrarCarrito();
            } else {
                alert("Error al actualizar stock: "+data.error);
            }
        }).catch(err=>alert("Error al procesar la compra: "+err));
    }
});

document.addEventListener("DOMContentLoaded", actualizarContador);
</script>
</body>
</html>
