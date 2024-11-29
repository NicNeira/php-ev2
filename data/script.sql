-- Tabla services
CREATE TABLE services (
  id SERIAL PRIMARY KEY,
  titulo_esp TEXT,
  titulo_eng TEXT,
  descripcion_esp TEXT,
  descripcion_eng TEXT,
  activo BOOLEAN
);

-- Tabla about_us
CREATE TABLE about_us (
  id SERIAL PRIMARY KEY,
  titulo_esp TEXT,
  titulo_eng TEXT,
  descripcion_esp TEXT,
  descripcion_eng TEXT
);

-- Tabla basic_info
CREATE TABLE basic_info (
  id SERIAL PRIMARY KEY,
  tipo TEXT,
  activo BOOLEAN
);

-- Tabla menu_items
CREATE TABLE menu_items (
  id SERIAL PRIMARY KEY,
  basic_info_id INTEGER REFERENCES basic_info(id),
  language TEXT,
  link TEXT,
  texto TEXT,
  activo BOOLEAN
);

-- Tabla hero_info
CREATE TABLE hero_info (
  id SERIAL PRIMARY KEY,
  basic_info_id INTEGER REFERENCES basic_info(id),
  titulo_esp TEXT,
  titulo_eng TEXT,
  parrafo_esp TEXT,
  parrafo_eng TEXT,
  activo BOOLEAN
);

-- Tabla contact_items
CREATE TABLE contact_items (
  id SERIAL PRIMARY KEY,
  basic_info_id INTEGER REFERENCES basic_info(id),
  tipo TEXT,
  valor TEXT,
  activo BOOLEAN
);

-- Tabla social_media_items
CREATE TABLE social_media_items (
  id SERIAL PRIMARY KEY,
  basic_info_id INTEGER REFERENCES basic_info(id),
  rrss TEXT,
  icono TEXT,
  link TEXT,
  activo BOOLEAN
);

-- Ver si la database tiene utf-8
SELECT
  datname,
  encoding,
  datcollate,
  datctype
FROM
  pg_database
WHERE
  datname = 'backend_ev2';

-- Hacer para cada tabla lo siguiente:
-- Query para obtener el maximo id registrado
SELECT
  MAX(id)
FROM
  social_media_items;

-- Query para cambiar el numero de la secuencia del id y no tener error al ingresar un nuevo elemento. Ingresar un digito mas del maximo id anterior para la tabla
ALTER SEQUENCE public.social_media_items_id_seq RESTART WITH 3;