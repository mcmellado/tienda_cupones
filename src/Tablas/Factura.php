<?php

namespace App\Tablas;

use PDO;

class Factura extends Modelo
{
    protected static string $tabla = 'facturas';

    public $id;
    public $created_at;
    public $usuario_id;

    public $cupon_utilizado;
    private $total;

    public function __construct(array $campos)
    {
        $this->id = $campos['id'];
        $this->created_at = $campos['created_at'];
        $this->usuario_id = $campos['usuario_id'];
        $this->cupon_utilizado = $campos['cupon_utilizado'];
        $this->total = isset($campos['total']) ? $campos['total'] : null;
    }

    public static function existe(int $id, ?PDO $pdo = null): bool
    {
        return static::obtener($id, $pdo) !== null;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    public function getUsuarioId()
    {
        return $this->usuario_id;
    }

    public function getCupon()
    {
        return $this->cupon_utilizado;
    }

    public function setTotal($total) {
        $this->total = $total;
    }

    public function setCuponUtilizado($cuponUtilizado)
    {
        $this->cupon_utilizado = $cuponUtilizado;
    }

    public function getTotal(?PDO $pdo = null)
    {
        $pdo = $pdo ?? conectar();

        if (!isset($this->total)) {
            $sent = $pdo->prepare('SELECT SUM(cantidad * precio) AS total
                                     FROM articulos_facturas l
                                     JOIN articulos a
                                       ON l.articulo_id = a.id
                                    WHERE factura_id = :id');
            $sent->execute([':id' => $this->id]);
            $this->total = $sent->fetchColumn();
        }

        if(isset($this->cupon_utilizado)) 

        {
            $sent = $pdo->prepare('SELECT SUM(cantidad * precio) AS total
                                     FROM articulos_facturas l
                                     JOIN articulos a
                                       ON l.articulo_id = a.id
                                    WHERE factura_id = :id');
            $sent->execute([':id' => $this->id]);
            $total_antiguo =  $this->total = $sent->fetchColumn();

            $cupon = $this->cupon_utilizado;
            $pdo = conectar();
            $descuento = $pdo->prepare("SELECT descuento from cupones where cupon = :cupon");
            $descuento->execute([':cupon' => $cupon]);
            $descuento = $descuento->fetchColumn();
            
            $descuento = $descuento / 100;
            $descuento_quitar = $total_antiguo * $descuento;
            $total_nuevo = $total_antiguo - $descuento_quitar;

            return $total_nuevo;
        }

        return $this->total;
    }

    public static function todosConTotal(
        array $where = [],
        array $execute = [],
        ?PDO $pdo = null
    ): array
    {
        $pdo = $pdo ?? conectar();

        $where = !empty($where)
            ? 'WHERE ' . implode(' AND ', $where)
            : '';
        $sent = $pdo->prepare("SELECT f.*, SUM(cantidad * precio) AS total
                                 FROM facturas f
                                 JOIN articulos_facturas l
                                   ON l.factura_id = f.id
                                 JOIN articulos a
                                   ON l.articulo_id = a.id
                               $where
                             GROUP BY f.id");
        $sent->execute($execute);
        $filas = $sent->fetchAll(PDO::FETCH_ASSOC);
        $res = [];
        foreach ($filas as $fila) {
            $res[] = new static($fila);
        }
        return $res;
    }

    public function getLineas(?PDO $pdo = null): array
    {
        $pdo = $pdo ?? conectar();

        $sent = $pdo->prepare('SELECT *
                                 FROM articulos_facturas
                                WHERE factura_id = :factura_id');
        $sent->execute([':factura_id' => $this->id]);
        $lineas = $sent->fetchAll(PDO::FETCH_ASSOC);
        $res = [];
        foreach ($lineas as $linea) {
            $res[] = new Linea($linea);
        }
        return $res;
    }
}
