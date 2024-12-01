<?php
// import_basic_info.php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$jsonData = file_get_contents('basicInfoData.json');
$data = json_decode($jsonData, true)['data'];

foreach ($data as $item) {
    $tipo = $item['tipo'];
    $activo = $item['activo'] ?? true;

    // Insertar en la tabla basic_info
    $query = "INSERT INTO basic_info (tipo, activo) VALUES (:tipo, :activo) RETURNING id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':tipo', $tipo);
    $stmt->bindParam(':activo', $activo, PDO::PARAM_BOOL);

    if ($stmt->execute()) {
        $basic_info_id = $stmt->fetchColumn();
        echo "Basic info de tipo '{$tipo}' insertado con ID {$basic_info_id}.\n";
    } else {
        $errorInfo = $stmt->errorInfo();
        echo "Error al insertar basic info de tipo '{$tipo}': {$errorInfo[2]}\n";
        continue;
    }

    // Insertar en tablas relacionadas según el tipo
    switch ($tipo) {
        case 'menu-principal':
            // Insertar elementos del menú
            $items = $item['items'];
            foreach (['esp', 'eng'] as $lang) {
                foreach ($items[$lang] as $menuItem) {
                    $query = "INSERT INTO menu_items (basic_info_id, language, link, texto, activo)
                              VALUES (:basic_info_id, :language, :link, :texto, :activo)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':basic_info_id', $basic_info_id, PDO::PARAM_INT);
                    $stmt->bindParam(':language', $lang);
                    $stmt->bindParam(':link', $menuItem['link']);
                    $stmt->bindParam(':texto', $menuItem['texto']);
                    $stmt->bindParam(':activo', $menuItem['activo'], PDO::PARAM_BOOL);

                    if ($stmt->execute()) {
                        echo "Elemento de menú '{$menuItem['texto']}' ({$lang}) insertado.\n";
                    } else {
                        $errorInfo = $stmt->errorInfo();
                        echo "Error al insertar elemento de menú '{$menuItem['texto']}' ({$lang}): {$errorInfo[2]}\n";
                    }
                }
            }
            break;

        case 'hero':
            // Insertar información del hero
            $query = "INSERT INTO hero_info (basic_info_id, titulo_esp, titulo_eng, parrafo_esp, parrafo_eng, activo)
                      VALUES (:basic_info_id, :titulo_esp, :titulo_eng, :parrafo_esp, :parrafo_eng, :activo)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':basic_info_id', $basic_info_id, PDO::PARAM_INT);
            $stmt->bindParam(':titulo_esp', $item['titulo']['esp']);
            $stmt->bindParam(':titulo_eng', $item['titulo']['eng']);
            $stmt->bindParam(':parrafo_esp', $item['parrafo']['esp']);
            $stmt->bindParam(':parrafo_eng', $item['parrafo']['eng']);
            $stmt->bindParam(':activo', $activo, PDO::PARAM_BOOL);

            if ($stmt->execute()) {
                echo "Información del hero insertada.\n";
            } else {
                $errorInfo = $stmt->errorInfo();
                echo "Error al insertar información del hero: {$errorInfo[2]}\n";
            }
            break;

        case 'contacto':
            // Insertar elementos de contacto
            foreach ($item['items'] as $contactItem) {
                $query = "INSERT INTO contact_items (basic_info_id, tipo, valor, activo)
                          VALUES (:basic_info_id, :tipo, :valor, :activo)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':basic_info_id', $basic_info_id, PDO::PARAM_INT);
                $stmt->bindParam(':tipo', $contactItem['tipo']);
                $stmt->bindParam(':valor', $contactItem['valor']);
                $stmt->bindParam(':activo', $contactItem['activo'], PDO::PARAM_BOOL);

                if ($stmt->execute()) {
                    echo "Elemento de contacto '{$contactItem['tipo']}' insertado.\n";
                } else {
                    $errorInfo = $stmt->errorInfo();
                    echo "Error al insertar elemento de contacto '{$contactItem['tipo']}': {$errorInfo[2]}\n";
                }
            }
            break;

        case 'rrss':
            // Insertar elementos de redes sociales
            foreach ($item['items'] as $socialItem) {
                $query = "INSERT INTO social_media_items (basic_info_id, rrss, icono, link, activo)
                          VALUES (:basic_info_id, :rrss, :icono, :link, :activo)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':basic_info_id', $basic_info_id, PDO::PARAM_INT);
                $stmt->bindParam(':rrss', $socialItem['rrss']);
                $stmt->bindParam(':icono', $socialItem['icono']);
                $stmt->bindParam(':link', $socialItem['link']);
                $stmt->bindParam(':activo', $socialItem['activo'], PDO::PARAM_BOOL);

                if ($stmt->execute()) {
                    echo "Elemento de red social '{$socialItem['rrss']}' insertado.\n";
                } else {
                    $errorInfo = $stmt->errorInfo();
                    echo "Error al insertar elemento de red social '{$socialItem['rrss']}': {$errorInfo[2]}\n";
                }
            }
            break;

        default:
            echo "Tipo desconocido: {$tipo}\n";
            break;
    }
}
