-- Elimina las restricciones de clave foránea en la tabla "facturas" antes de eliminarla
ALTER TABLE facturas DROP CONSTRAINT IF EXISTS facturas_usuario_id_fkey;

-- Elimina las restricciones de clave foránea en la tabla "articulos_facturas" antes de eliminarla
ALTER TABLE articulos_facturas DROP CONSTRAINT IF EXISTS articulos_facturas_articulo_id_fkey;
ALTER TABLE articulos_facturas DROP CONSTRAINT IF EXISTS articulos_facturas_factura_id_fkey;

-- Elimina las tablas si existen
DROP TABLE IF EXISTS articulos_facturas;
DROP TABLE IF EXISTS facturas;
DROP TABLE IF EXISTS articulos;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS cupones;

-- Crea las tablas
CREATE TABLE cupones (
    id     bigserial PRIMARY KEY,
    fecha  date         NOT NULL,
    cupon  varchar(255) NOT NULL
);

CREATE TABLE articulos (
    id          bigserial     PRIMARY KEY,
    codigo      varchar(13)   NOT NULL UNIQUE,
    descripcion varchar(255)  NOT NULL,
    precio      numeric(7, 2) NOT NULL,
    stock       int           NOT NULL
);

CREATE TABLE usuarios (
    id       bigserial    PRIMARY KEY,
    usuario  varchar(255) NOT NULL UNIQUE,
    password varchar(255) NOT NULL,
    validado bool         NOT NULL
);

CREATE TABLE facturas (
    id         bigserial  PRIMARY KEY,
    created_at timestamp  NOT NULL DEFAULT localtimestamp(0),
    usuario_id bigint NOT NULL REFERENCES usuarios (id),
    cupon_utilizado varchar(255)
);

CREATE TABLE articulos_facturas (
    articulo_id bigint NOT NULL REFERENCES articulos (id),
    factura_id  bigint NOT NULL REFERENCES facturas (id),
    cantidad    int    NOT NULL,
    PRIMARY KEY (articulo_id, factura_id)
);

-- Carga inicial de datos de prueba

INSERT INTO cupones (cupon, fecha)
    VALUES ('50 %', '2023-06-14'),
           ('60 %', '2023-06-16');

INSERT INTO articulos (codigo, descripcion, precio, stock)
    VALUES ('18273892389', 'Yogur piña', 200.50, 4),
           ('83745828273', 'Tigretón', 50.10, 2),
           ('51736128495', 'Disco duro SSD 500 GB', 150.30, 0),
           ('83746828273', 'Tigretón', 50.10, 3),
           ('51786128435', 'Disco duro SSD 500 GB', 150.30, 5),
           ('83745228673', 'Tigretón', 50.10, 8),
           ('51786198495', 'Disco duro SSD 500 GB', 150.30, 1);

INSERT INTO usuarios (usuario, password, validado)
    VALUES ('admin', crypt('admin', gen_salt('bf', 10)), true),
           ('pepe', crypt('pepe', gen_salt('bf', 10)), false);
