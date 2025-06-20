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

// Ajustar nivel de errores para producción
version_compare(PHP_VERSION, '5.5.0') <= 0
    ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED & E_ERROR)
    : error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED & E_ERROR & ~E_STRICT);

// Incluir versión de vtiger
include('vtigerversion.php');

// Aumentar límite de memoria para gráficos
ini_set('memory_limit','6000M');

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

$site_URL = rtrim(getenv('VTIGER_URL') ?: '', '/') . '/';
if (empty($site_URL)) {
    die('VTIGER_URL no definido');
}

// 3) RUTA RAÍZ DINÁMICA
$root_directory = __DIR__ . '/';

// 4) CONFIGURACIÓN ADICIONAL (sin cambiar)
$CALENDAR_DISPLAY = 'true';
$USE_RTE         = 'true';

$HELPDESK_SUPPORT_EMAIL_ID      = 'support@vtiger.com.co';
$HELPDESK_SUPPORT_NAME          = 'your-support name';
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

// Tamaño máximo de subida (bytes)
$upload_maxsize = 50145728; // 50MB

// Funcionalidad de export
$allow_exports = 'all';

// Extensiones bloqueadas (se les añade .txt)
$upload_badext = array(
    'php','php3','php4','php5','pl','cgi','py','asp','cfm','js','vbs',
    'html','htm','exe','bin','bat','sh','dll','phps','phtml','xhtml',
    'rb','msi','jsp','shtml','sth','shtm'
);

// Paginación y vistas
$list_max_entries_per_page   = '20';
$history_max_viewed          = '5';
$default_action              = 'index';
$default_theme               = 'softed';
$default_user_name           = '';
$default_password            = '';
$create_default_user         = false;

// Moneda
$currency_name = 'Colombia, Pesos';

// Charset y lenguaje
$default_charset   = 'UTF-8';
$default_language  = 'en_us';

// Otros ajustes de vista y tracking
$display_empty_home_blocks = false;
$disable_stats_tracking    = false;

// Clave única de la aplicación
$application_unique_key = 'ad821a331ef9616362a07d02e6655f6d';

// Longitud máxima de texto en listviews
$listview_max_textlength = 40;

// Tiempo máximo de ejecución de scripts PHP
$php_max_execution_time = 0;

// Zona horaria
$default_timezone = 'UTC';
if (isset($default_timezone) && function_exists('date_default_timezone_set')) {
    @date_default_timezone_set($default_timezone);
}

// Layout por defecto y tamaño de selección de campos
$default_layout             = 'v7';
$maxListFieldsSelectionSize = 15;

// Incluir la configuración de seguridad al final
include_once 'config.security.php';
?>
