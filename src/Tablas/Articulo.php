<?php

namespace App\Tablas;

use PDO;

class Articulo extends Modelo
{
    protected static string $tabla = 'articulos';

    public $id;
    private $codigo;
    private $descripcion;
    private $precio;
    private $stock;

    private $id_cupon;

    public function __construct(array $campos)
    {
        $this->id = $campos['id'];
        $this->codigo = $campos['codigo'];
        $this->descripcion = $campos['descripcion'];
        $this->precio = $campos['precio'];
        $this->stock = $campos['stock'];
        $this->id_cupon = $campos['id_cupon'];

    }

    public static function existe(int $id, ?PDO $pdo = null): bool
    {
        return static::obtener($id, $pdo) !== null;
    }

    public function getCodigo()
    {
        return $this->codigo;
    }

    public function getDescripcion()
    {
        return $this->descripcion;
    }

    public function getPrecio()
    {
        return $this->precio;
    }

    public function setIdCUpon($id_cupon) {
        $this->id_cupon = $id_cupon;
    }

    public function getStock()
    {
        return $this->stock;
    }

    public function getIdCupon() {
        return $this->id_cupon;
    }

    public function getCupon(?PDO $pdo = null)
    {
        $pdo = $pdo ?? conectar();
        $sent = $pdo->prepare("SELECT cupones.cupon 
                               FROM articulos 
                               JOIN cupones ON articulos.id_cupon = cupones.id
                               WHERE articulos.id = :id");
        $sent->execute([":id" => $this->id]);
        return $sent->fetchColumn();
    }


    public function aplicarCupon($cupon)
    {
        $precio_articulo = $this->precio;
        $nuevo_precio = $precio_articulo;
    
        switch ($cupon) {
            case '50 %':
                $descuento = $precio_articulo * 0.5;
                $nuevo_precio = $precio_articulo - $descuento; 
                break;
            default:
                $nuevo_precio = $precio_articulo;
                break;
        }
    
        return $nuevo_precio;
    }

    public function quitarCupon() {
        $pdo = conectar();
        $quitar_descuento = $pdo->prepare("UPDATE articulos SET id_cupon = NULL WHERE id = :id");
        $quitar_descuento->execute([":id" => $this->id]);
    }
    
}
