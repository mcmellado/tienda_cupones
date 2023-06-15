<?php session_start() ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/css/output.css" rel="stylesheet">
    <title>Comprar</title>
</head>

<body>
    <?php require '../vendor/autoload.php';

    if (!\App\Tablas\Usuario::esta_logueado()) {
        return redirigir_login();
    }

    $carrito = unserialize(carrito());

    $ids = implode(', ', $carrito->getIds());
    $where = "WHERE id IN ($ids)";

    if (obtener_post('_testigo') !== null) {
        $pdo = conectar();
        $sent = $pdo->query("SELECT *
                                FROM articulos
                                $where");

        foreach ($sent->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            if ($fila['stock'] < $carrito->getLinea($fila['id'])->getCantidad()) {
                $_SESSION['error'] = 'No hay existencias suficientes para crear la factura.';
                return volver();
            }        
        }
        // Crear factura
        $usuario = \App\Tablas\Usuario::logueado();
        $usuario_id = $usuario->id;
        $pdo->beginTransaction();
        $cupon = obtener_get('cupon');
        $sent = $pdo->prepare('INSERT INTO facturas (usuario_id, cupon_utilizado)
                               VALUES (:usuario_id, :cupon_utilizado)
                               RETURNING id');
        $sent->execute([':usuario_id' => $usuario_id, ':cupon_utilizado' => $cupon]);
        $factura_id = $sent->fetchColumn();
        $lineas = $carrito->getLineas();
        $values = [];
        $execute = [':f' => $factura_id];
        $i = 1;

        foreach ($lineas as $id => $linea) {
            $values[] = "(:a$i, :f, :c$i)";
            $execute[":a$i"] = $id;
            $execute[":c$i"] = $linea->getCantidad();
            $i++;
        }

        $values = implode(', ', $values);
        $sent = $pdo->prepare("INSERT INTO articulos_facturas (articulo_id, factura_id, cantidad)
                               VALUES $values");
        $sent->execute($execute);
        foreach ($lineas as $id => $linea) {
            $cantidad = $linea->getCantidad();
            $sent = $pdo->prepare('UPDATE articulos
                                      SET stock = stock - :cantidad
                                    WHERE id = :id');
            $sent->execute([':id' => $id, ':cantidad' => $cantidad]);
        }
        $pdo->commit();
        $_SESSION['exito'] = 'La factura se ha creado correctamente.';
        unset($_SESSION['carrito']);
        return volver();
    }

    $cupon = obtener_get('cupon');



    if(isset($cupon)) {

        $pdo = conectar();

        $buscar_cupon = $pdo->prepare("SELECT * FROM cupones WHERE cupon = :cupon");
        $buscar_cupon->execute([":cupon" => $cupon]);
        $buscar_cupon = $buscar_cupon->fetchColumn();

        if($buscar_cupon == null) {
            $_SESSION['error'] = 'El cupón que quieres introducir no existe';
            unset($cupon);

        } else {
            $_SESSION['exito'] = "Se ha aplicado el cupón correspondiente: $cupon";
        }

    } 

    ?>

<div class="container mx-auto">
    <?php require '../src/_menu.php' ?>
    <?php require '../src/_alerts.php' ?>
        <div class="overflow-y-auto py-4 px-3 bg-gray-50 rounded dark:bg-gray-800">

        <form method="GET">
            <fieldset>
                <label>
                    ¿Tienes algún cupón de descuento?:
                    <br>
                    <input type="text" name="cupon" id="cupon">
                    <button type="submit"> Aplicar cupón </button>
                </label>
            </fieldset>
        </form>

            <table class="mx-auto text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <th scope="col" class="py-3 px-6">Código</th>
                    <th scope="col" class="py-3 px-6">Descripción</th>
                    <th scope="col" class="py-3 px-6">Cantidad</th>
                    <?php if(isset($cupon)): ?>
                        <th scope="col" class="py-3 px-6">Antiguo precio</th>
                        <th scope="col" class="py-3 px-6">Nuevo precio</th>
                    <?php else: ?>
                        <th scope="col" class="py-3 px-6">Precio</th>
                    <?php endif ?>
                    <th scope="col" class="py-3 px-6">Importe</th>
                </thead>
                <tbody>
                    <?php $total = 0 ?>
                    <?php foreach ($carrito->getLineas() as $id => $linea) : ?>
                        <?php
                        $articulo = $linea->getArticulo();
                        $precio_antiguo = $articulo->getPrecio();
                        $codigo = $articulo->getCodigo();
                        $cantidad = $linea->getCantidad();
                        if(isset($cupon)) {
                            $precio_antiguo = $articulo->getPrecio();
                            $precio = $articulo->aplicarCupon($cupon);
                        } else {
                            $precio = $articulo->getPrecio();
                        }
                        $importe = $cantidad * $precio;
                        $total += $importe;
                        if(isset($cupon)) {
                           $cupon;
                        }
                        ?>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="py-4 px-6"><?= $articulo->getCodigo() ?></td>
                            <td class="py-4 px-6"><?= $articulo->getDescripcion() ?></td>
                            <td class="py-4 px-6 text-center"><?= $cantidad ?></td>
                            <?php if(isset($cupon)): ?>
                                <td class="py-4 px-6 text-center">
                                    <?= dinero($precio_antiguo) ?>
                                </td>

                                <td class="py-4 px-6 text-center">
                                    <?= dinero($precio) ?>
                                </td>
                            <?php else: ?>
                                <td class="py-4 px-6 text-center">
                                    <?= dinero($precio_antiguo) ?>
                                </td>
                            <?php endif ?>
                            <td class="py-4 px-6 text-center">
                                <?= dinero($importe) ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
                <tfoot>
                <?php if(isset($cupon)): ?>
                                <td class="py-4 px-6">Cupon utilizado: <?= $cupon ?></td>       
                            <?php endif ?>
                    <td colspan="3"></td>
                    <td class="text-center font-semibold">TOTAL:</td>
                    <td class="text-center font-semibold"><?= dinero($total) ?></td>
                </tfoot>
            </table>
            <form action="" method="POST" class="mx-auto flex mt-4">
                <input type="hidden" name="_testigo" value="1">
                <button type="submit" href="" class="mx-auto focus:outline-none text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-900">Realizar pedido</button>
            </form>
        </div>
    </div>
    <script src="/js/flowbite/flowbite.js"></script>
</body>

</html>
