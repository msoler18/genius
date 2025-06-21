<?php
/**
 * Script de importación de Contactos y Cuentas desde Excel para vtiger 8
 * Uso: php import_contacts.php archivo.xlsx
 */

// Configuración inicial
chdir(__DIR__);
require_once 'includes/main/WebUI.php';
require_once 'modules/Users/Users.php';
require_once 'include/utils/utils.php';

// Login como admin
global $current_user;
$current_user = new Users();
$current_user->id = 1;
$current_user->retrieve_entity_info(1, 'Users');

// Verificar argumentos
if ($argc < 2) {
    die("Uso: php import_contacts.php archivo.xlsx\n");
}

$filename = $argv[1];
if (!file_exists($filename)) {
    die("Error: El archivo $filename no existe\n");
}

// Cargar librería para leer Excel
require_once 'include/PHPExcel/PHPExcel.php';

echo "=== INICIANDO IMPORTACIÓN DE CONTACTOS ===\n";
echo "Archivo: $filename\n\n";

try {
    // Leer archivo Excel
    $objPHPExcel = PHPExcel_IOFactory::load($filename);
    $sheet = $objPHPExcel->getActiveSheet();
    $highestRow = $sheet->getHighestRow();
    $highestColumn = $sheet->getHighestColumn();
    
    echo "Total de filas encontradas: " . ($highestRow - 1) . "\n\n";
    
    // Arrays para caché de empresas
    $empresasCache = array();
    $contactosImportados = 0;
    $empresasCreadas = 0;
    $errores = array();
    
    // Procesar cada fila (asumiendo que la fila 1 tiene encabezados)
    for ($row = 2; $row <= $highestRow; $row++) {
        try {
            // Leer datos de la fila
            $empresa = trim($sheet->getCell('B' . $row)->getValue());
            $contactoCompleto = trim($sheet->getCell('C' . $row)->getValue());
            $cargo = trim($sheet->getCell('D' . $row)->getValue());
            $division = trim($sheet->getCell('E' . $row)->getValue());
            $telefono = trim($sheet->getCell('F' . $row)->getValue());
            $celular = trim($sheet->getCell('G' . $row)->getValue());
            $email = trim($sheet->getCell('H' . $row)->getValue());
            $direccion = trim($sheet->getCell('I' . $row)->getValue());
            $ciudad = trim($sheet->getCell('J' . $row)->getValue());
            $atiende = trim($sheet->getCell('K' . $row)->getValue());
            $emailCartera = trim($sheet->getCell('M' . $row)->getValue());
            
            // Validar datos mínimos
            if (empty($empresa) && empty($contactoCompleto)) {
                continue; // Saltar filas vacías
            }
            
            echo "Procesando fila $row: $contactoCompleto ($empresa)\n";
            
            // 1. CREAR O BUSCAR CUENTA (EMPRESA)
            $accountId = null;
            if (!empty($empresa)) {
                // Verificar caché primero
                if (isset($empresasCache[$empresa])) {
                    $accountId = $empresasCache[$empresa];
                } else {
                    // Buscar en base de datos
                    $accountId = buscarCuenta($empresa);
                    
                    if (!$accountId) {
                        // Crear nueva cuenta
                        $accountId = crearCuenta($empresa, $ciudad, $atiende);
                        if ($accountId) {
                            $empresasCreadas++;
                            echo "  - Cuenta creada: $empresa (ID: $accountId)\n";
                        }
                    }
                    
                    // Guardar en caché
                    $empresasCache[$empresa] = $accountId;
                }
            }
            
            // 2. PROCESAR NOMBRE DEL CONTACTO
            $nombres = separarNombreApellido($contactoCompleto);
            
            // 3. CREAR CONTACTO
            if (!empty($contactoCompleto)) {
                $contactId = crearContacto(
                    $nombres['nombre'],
                    $nombres['apellido'],
                    $email,
                    $telefono,
                    $celular,
                    $cargo,
                    $accountId,
                    $direccion,
                    $ciudad,
                    $division,
                    $emailCartera
                );
                
                if ($contactId) {
                    $contactosImportados++;
                    echo "  - Contacto creado: $contactoCompleto (ID: $contactId)\n";
                } else {
                    $errores[] = "Fila $row: Error al crear contacto $contactoCompleto";
                }
            }
            
        } catch (Exception $e) {
            $errores[] = "Fila $row: " . $e->getMessage();
            echo "  ERROR en fila $row: " . $e->getMessage() . "\n";
        }
    }
    
    // Resumen final
    echo "\n=== RESUMEN DE IMPORTACIÓN ===\n";
    echo "Contactos importados: $contactosImportados\n";
    echo "Cuentas creadas: $empresasCreadas\n";
    echo "Errores encontrados: " . count($errores) . "\n";
    
    if (count($errores) > 0) {
        echo "\nERRORES:\n";
        foreach ($errores as $error) {
            echo "- $error\n";
        }
    }
    
} catch (Exception $e) {
    die("Error general: " . $e->getMessage() . "\n");
}

/**
 * Buscar una cuenta por nombre
 */
function buscarCuenta($nombreCuenta) {
    global $adb;
    
    $query = "SELECT accountid FROM vtiger_account 
              INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_account.accountid 
              WHERE vtiger_account.accountname = ? AND vtiger_crmentity.deleted = 0";
    
    $result = $adb->pquery($query, array($nombreCuenta));
    
    if ($adb->num_rows($result) > 0) {
        return $adb->query_result($result, 0, 'accountid');
    }
    
    return false;
}

/**
 * Crear una nueva cuenta
 */
function crearCuenta($nombreCuenta, $ciudad = '', $asignadoA = '') {
    try {
        $account = CRMEntity::getInstance('Accounts');
        $account->column_fields['accountname'] = $nombreCuenta;
        $account->column_fields['assigned_user_id'] = 1; // Admin por defecto
        
        if (!empty($ciudad)) {
            $account->column_fields['bill_city'] = $ciudad;
            $account->column_fields['ship_city'] = $ciudad;
        }
        
        // Mapear el campo "Atiende" a un campo personalizado o descripción
        if (!empty($asignadoA)) {
            $account->column_fields['description'] = "Atendido por: $asignadoA";
        }
        
        $account->save('Accounts');
        
        return $account->id;
    } catch (Exception $e) {
        echo "Error creando cuenta: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Crear un nuevo contacto
 */
function crearContacto($nombre, $apellido, $email, $telefono, $celular, $cargo, $accountId, $direccion, $ciudad, $division, $emailSecundario) {
    try {
        $contact = CRMEntity::getInstance('Contacts');
        
        // Campos básicos
        $contact->column_fields['firstname'] = $nombre;
        $contact->column_fields['lastname'] = $apellido ?: $nombre; // Si no hay apellido, usar nombre
        $contact->column_fields['email'] = $email;
        $contact->column_fields['phone'] = $telefono;
        $contact->column_fields['mobile'] = $celular;
        $contact->column_fields['title'] = $cargo;
        $contact->column_fields['assigned_user_id'] = 1; // Admin por defecto
        
        // Asociar con cuenta si existe
        if ($accountId) {
            $contact->column_fields['account_id'] = $accountId;
        }
        
        // Dirección
        if (!empty($direccion)) {
            $contact->column_fields['mailingstreet'] = $direccion;
        }
        if (!empty($ciudad)) {
            $contact->column_fields['mailingcity'] = $ciudad;
        }
        
        // Campos adicionales
        if (!empty($division)) {
            $contact->column_fields['department'] = $division;
        }
        if (!empty($emailSecundario)) {
            $contact->column_fields['secondaryemail'] = $emailSecundario;
        }
        
        $contact->save('Contacts');
        
        return $contact->id;
    } catch (Exception $e) {
        echo "Error creando contacto: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Separar nombre completo en nombre y apellido
 */
function separarNombreApellido($nombreCompleto) {
    $partes = explode(' ', trim($nombreCompleto));
    $cantidadPartes = count($partes);
    
    if ($cantidadPartes == 0) {
        return array('nombre' => '', 'apellido' => '');
    } elseif ($cantidadPartes == 1) {
        return array('nombre' => $partes[0], 'apellido' => '');
    } elseif ($cantidadPartes == 2) {
        return array('nombre' => $partes[0], 'apellido' => $partes[1]);
    } else {
        // Asumir que las últimas 2 palabras son apellidos
        $apellido = $partes[$cantidadPartes - 2] . ' ' . $partes[$cantidadPartes - 1];
        $nombre = implode(' ', array_slice($partes, 0, $cantidadPartes - 2));
        return array('nombre' => $nombre, 'apellido' => $apellido);
    }
}

echo "\n=== IMPORTACIÓN COMPLETADA ===\n";
?>