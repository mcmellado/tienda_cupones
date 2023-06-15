<?php

use App\Tablas\Articulo;

session_start();

require '../vendor/autoload.php';

try {
    $id = obtener_get('id');

    if ($id === null) {
        return volver();
    }

    $articulo = Articulo::obtener($id);

    if ($articulo === null) {
        return volver();
    }

    if ($articulo->getStock() <= 0) {
        $_SESSION['error'] = 'No hay existencias suficientes.';
        return volver();
    }

    $carrito = unserialize(carrito());
    $stock = $articulo->getStock();
    $lineas = $carrito->getLineas();
    $cantidad = empty($lineas) || !isset($lineas[$id]) ? 0 : $lineas[$id]->getCantidad();

    if($cantidad < $stock) {
        $carrito->insertar($id);
        $_SESSION['carrito'] = serialize($carrito);
    } else {
        $_SESSION['error'] = "No hay existencias suficientes";
    }

} catch (ValueError $e) {
    // TODO: mostrar mensaje de error en un Alert
}

volver();
