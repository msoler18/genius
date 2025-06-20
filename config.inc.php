<?php
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version 1.1.2
 * ("License"); You may not use this file except in compliance with the 
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an  "AS IS"  basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for
 * the specific language governing rights and limitations under the License.
 * The Original Code is:  SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
********************************************************************************/

// IMPORTANTE: Detectar HTTPS en Heroku
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
    $_SERVER['HTTPS'] = 'on';
}

// Ajustar nivel de errores para PHP 8+ (sin E_STRICT)
error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED);

// Incluir versión de vtiger
include('vtigerversion.php');

// Aumentar límite de memoria para gráficos
ini_set('memory_limit','256M');

// 1) CONFIGURACIÓN DINÁMICA DE BASE DE DATOS DESDE JAWSDB_URL
$dbUrl = getenv('JAWSDB_URL');
if (!$dbUrl) {
    die('JAWSDB_URL no definido');
}
$url = parse_url($dbUrl);
$dbconfig['db_server']   = $url['host'];
$dbconfig['db_port']     = ':' . ($url['port'] ?? '3306');
$dbconfig['db_username'] = $url['user'];
$dbconfig['db_password'] = $url['pass'];
$dbconfig['db_name']     = ltrim($url['path'], '/');
$dbconfig['db_type']     = 'mysqli';
$dbconfig['db_status']   = 'true';
$dbconfig['db_hostname'] = $dbconfig['db_server'] . $dbconfig['db_port'];

// CONFIGURACIÓN DE URL - CRÍTICO PARA EVITAR REDIRECCIONES
$heroku_app = getenv('VTIGER_URL');
if (empty($heroku_app)) {
    // Si no está definida la variable, usar el host actual
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $site_URL = $protocol . $_SERVER['HTTP_HOST'] . '/';
} else {
    $site_URL = rtrim($heroku_app, '/') . '/';
}

// 3) RUTA RAÍZ DINÁMICA
$root_directory = __DIR__ . '/';

// 4) CONFIGURACIÓN ADICIONAL
$CALENDAR_DISPLAY = 'true';
$USE_RTE         = 'true';

// Configuración de correo - ACTUALIZAR CON TUS DATOS
$HELPDESK_SUPPORT_EMAIL_ID      = 'genius@en-stock.com';
$HELPDESK_SUPPORT_NAME          = 'Genius en Stock';
$HELPDESK_SUPPORT_EMAIL_REPLY_ID = $HELPDESK_SUPPORT_EMAIL_ID;

// Log SQL
$dbconfig['log_sql'] = false;

// Opciones de conexión
$dbconfigoption['persistent']      = true;
$dbconfigoption['autofree']        = false;
$dbconfigoption['debug']           = 0;
$dbconfigoption['seqname_format']  = '%s_seq';
$dbconfigoption['portability']     = 0;
$dbconfigoption['ssl']             = false;

// Nombre del host para logs u otros usos
$host_name = $dbconfig['db_hostname'];

// URL para el portal de clientes
$PORTAL_URL = $site_URL . 'customerportal';

// Directorios de caché e importación
$cache_dir   = 'cache/';
$tmp_dir     = 'cache/images/';
$import_dir  = 'cache/import/';
$upload_dir  = 'cache/upload/';
$upload_badext = array(
    'php','php3','php4','php5','pl','cgi','py','asp','cfm','js','vbs',
    'html','htm','exe','bin','bat','sh','dll','phps','phtml','xhtml',
    'rb','msi','jsp','shtml','sth','shtm'
);

// Tamaño máximo de subida
$upload_maxsize = 52428800; // 50MB

// Funcionalidad de export
$allow_exports = 'all';

// Paginación y vistas
$list_max_entries_per_page   = '20';
$history_max_viewed          = '5';
$default_action              = 'index';
$default_module              = 'Users';
$default_theme               = 'softed';
$default_user_name           = 'admin';
$default_password            = '';
$create_default_user         = false;

// Configuración de interfaz
$display_empty_home_blocks = false;
$disable_stats_tracking    = false;

// Moneda
$currency_name = 'Colombia, Pesos';

// Charset y lenguaje
$default_charset   = 'UTF-8';
$default_language  = 'es_co';

// Clave única de la aplicación
$application_unique_key = 'ad821a331ef9616362a07d02e6655f6d';

// Longitud máxima de texto en listviews
$listview_max_textlength = 40;

// Tiempo máximo de ejecución
$php_max_execution_time = 0;

// Zona horaria Colombia
$default_timezone = 'America/Bogota';
if (function_exists('date_default_timezone_set')) {
    @date_default_timezone_set($default_timezone);
}

// Layout por defecto
$default_layout             = 'v7';
$maxListFieldsSelectionSize = 15;

// LOGGING PARA DEBUG
$LOG4PHP_DEBUG = true;
$LOG_LEVEL = 'INFO'; //

// Incluir la configuración de seguridad al final
include_once 'config.security.php';
?>
