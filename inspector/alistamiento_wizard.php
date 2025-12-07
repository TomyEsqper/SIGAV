<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/checklist_mapping.php';

verificarSesion(['inspector', 'admin']);

// Utilidades de URL base para despliegues en subcarpetas (Hostinger, etc.)
function getBaseUrl() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $dir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    return ($host ? ($scheme . '://' . $host) : '') . $dir;
}

$BASE_URL = getBaseUrl();
$URL_WIZARD = $BASE_URL . '/alistamiento_wizard.php';
$URL_ALISTAMIENTO = $BASE_URL . '/alistamiento.php';
$URL_INDEX = $BASE_URL . '/index.php';

$vehiculoId = isset($_GET['vehiculo']) ? intval($_GET['vehiculo']) : 0;
if ($vehiculoId <= 0) {
    header('Location: ' . $URL_INDEX);
    exit;
}

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;

// Cargar datos del vehículo
try {
    $db = getDB();
    // Ajustar columnas a las existentes en la tabla vehiculos
    $stmt = $db->prepare('SELECT id, numero_interno, placa, propietario, estado FROM vehiculos WHERE id = ?');
    $stmt->execute([$vehiculoId]);
    $vehiculo = $stmt->fetch();
    if (!$vehiculo) {
        throw new Exception('Vehículo no encontrado');
    }
    // Si estamos en el paso 2, cargar lista de conductores activos
    if ($step === 2) {
        $stmtCond = $db->prepare('SELECT id, nombre, cedula, telefono FROM conductores WHERE activo = 1 ORDER BY nombre ASC');
        $stmtCond->execute();
        $conductores = $stmtCond->fetchAll();
    }
} catch (Exception $e) {
    http_response_code(404);
    echo 'Error: ' . htmlspecialchars($e->getMessage());
    exit;
}

// Helper: guardar foto temporal del wizard y devolver ruta relativa
function saveWizardPhotoTmp($vehiculoId, $postKey, $fileInfo) {
    if (!isset($fileInfo) || !is_array($fileInfo)) { return null; }
    if (($fileInfo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { return null; }
    $tmpName = $fileInfo['tmp_name'] ?? '';
    if (!$tmpName || !is_uploaded_file($tmpName)) { return null; }
    $mime = mime_content_type($tmpName) ?: 'image/jpeg';
    $ext = 'jpg';
    if (preg_match('/png$/i', $mime)) { $ext = 'png'; }
    elseif (preg_match('/gif$/i', $mime)) { $ext = 'gif'; }
    elseif (preg_match('/jpeg|jpg$/i', $mime)) { $ext = 'jpg'; }
    $dirRel = 'uploads/tmp_wizard/' . intval($vehiculoId);
    $dirAbs = dirname(__DIR__) . '/' . $dirRel;
    if (!is_dir($dirAbs)) { @mkdir($dirAbs, 0777, true); }
    $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $postKey);
    $fileRel = $dirRel . '/' . date('Ymd_His') . '_' . mt_rand(1000,9999) . '_' . $safeKey . '.' . $ext;
    $fileAbs = dirname(__DIR__) . '/' . $fileRel;
    if (@move_uploaded_file($tmpName, $fileAbs)) {
        return $fileRel;
    }
    return null;
}

// Manejo de envío del paso actual
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentStep = isset($_POST['current_step']) ? intval($_POST['current_step']) : 1;

    if ($currentStep === 1) {
        $tipo = isset($_POST['tipo_designacion']) ? $_POST['tipo_designacion'] : null;
        if (!$tipo || !in_array($tipo, ['FIJO', 'SUPERNUMERARIO'], true)) {
            $error = 'Seleccione una opción válida';
        } else {
            $_SESSION['alistamiento_wizard'][$vehiculoId]['tipo_designacion'] = $tipo;
            // Ir al siguiente paso (placeholder por ahora)
            $next = $currentStep + 1;
            header('Location: ' . $URL_WIZARD . '?vehiculo=' . $vehiculoId . '&step=' . $next);
            exit;
        }
    } elseif ($currentStep === 2) {
        $conductorId = isset($_POST['conductor_id']) ? intval($_POST['conductor_id']) : 0;
        if ($conductorId <= 0) {
            $error = 'Seleccione un conductor válido';
        } else {
            $_SESSION['alistamiento_wizard'][$vehiculoId]['conductor_id'] = $conductorId;
            $next = $currentStep + 1;
            header('Location: ' . $URL_WIZARD . '?vehiculo=' . $vehiculoId . '&step=' . $next);
            exit;
        }
    } elseif ($currentStep === 3) {
        $tipoMant = isset($_POST['mantenimiento_tipo']) ? $_POST['mantenimiento_tipo'] : '';
        $fechaMant = isset($_POST['mantenimiento_fecha']) ? $_POST['mantenimiento_fecha'] : '';
        $otroMant = isset($_POST['mantenimiento_otro']) ? trim($_POST['mantenimiento_otro']) : '';

        $tiposValidos = ['ACEITE_MOTOR', 'LIQUIDO_FRENOS', 'LLANTAS', 'SINCRONIZACION', 'ALINEACION_BALANCEO', 'TENSION_FRENOS', 'NINGUNA', 'OTRO'];
        $fechaValida = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaMant) === 1;

        if (!in_array($tipoMant, $tiposValidos, true)) {
            $error = 'Seleccione el tipo de mantenimiento';
        } elseif ($tipoMant !== 'NINGUNA' && !$fechaValida) {
            $error = 'Seleccione una fecha válida';
        } elseif ($tipoMant === 'OTRO' && $otroMant === '') {
            $error = 'Describa el mantenimiento en "Otro"';
        } else {
            $_SESSION['alistamiento_wizard'][$vehiculoId]['mantenimiento_tipo'] = $tipoMant;
            $_SESSION['alistamiento_wizard'][$vehiculoId]['mantenimiento_fecha'] = $fechaMant;
            if ($tipoMant === 'OTRO') {
                $_SESSION['alistamiento_wizard'][$vehiculoId]['mantenimiento_otro'] = $otroMant;
            } else {
                unset($_SESSION['alistamiento_wizard'][$vehiculoId]['mantenimiento_otro']);
            }
            $next = $currentStep + 1;
            header('Location: ' . $URL_WIZARD . '?vehiculo=' . $vehiculoId . '&step=' . $next);
            exit;
        }
    } elseif ($currentStep === 4) {
        $validos = ['BUENO', 'MALO', 'N/A'];
        $map = [
            'vg_gps' => 'gps',
            'vg_camara1' => 'camara1',
            'vg_camara2' => 'camara2',
            'vg_caja_mdvr' => 'caja_mdvr',
            'vg_mdvr' => 'mdvr',
            'vg_memoria_mdvr' => 'memoria_mdvr',
        ];
        $data = [];
        foreach ($map as $postKey => $sessionKey) {
            $val = isset($_POST[$postKey]) ? $_POST[$postKey] : '';
            if (!in_array($val, $validos, true)) {
                $error = 'Complete todas las respuestas con BUENO, MALO o N/A';
                break;
            }
            $data[$sessionKey] = $val;
        }
        if (empty($error)) {
            $_SESSION['alistamiento_wizard'][$vehiculoId]['videograbacion'] = $data;
            foreach ($map as $postKey => $sessionKey) {
                if (($data[$sessionKey] ?? '') === 'MALO') {
                    $fileKey = 'photo_' . $postKey;
                    if (isset($_FILES[$fileKey]) && ($_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                        $rel = saveWizardPhotoTmp($vehiculoId, $postKey, $_FILES[$fileKey]);
                        if ($rel) {
                            $_SESSION['alistamiento_wizard_photos'][$vehiculoId]['videograbacion'][$sessionKey] = $rel;
                        }
                    }
                }
            }
            $next = $currentStep + 1;
            header('Location: ' . $URL_WIZARD . '?vehiculo=' . $vehiculoId . '&step=' . $next);
            exit;
        }
    } elseif ($currentStep === 5) {
        $validos = ['BUENO', 'MALO'];
        $map = [
            'pm_pedal_clutch' => 'pedal_clutch',
            'pm_sistema_encendido' => 'sistema_encendido',
            'pm_sistema_transmision' => 'sistema_transmision',
            'pm_sistema_direccion' => 'sistema_direccion',
            'pm_correas_mangueras' => 'correas_mangueras',
            'pm_sistema_arranque' => 'sistema_arranque',
        ];
        $data = [];
        foreach ($map as $postKey => $sessionKey) {
            $val = isset($_POST[$postKey]) ? $_POST[$postKey] : '';
            if (!in_array($val, $validos, true)) {
                $error = 'Complete todas las respuestas con BUENO o MALO';
                break;
            }
            $data[$sessionKey] = $val;
        }
        if (empty($error)) {
            $_SESSION['alistamiento_wizard'][$vehiculoId]['puesta_marcha'] = $data;
            // Guardar fotos temporales para ítems marcados MALO
            foreach ($map as $postKey => $sessionKey) {
                if (($data[$sessionKey] ?? '') === 'MALO') {
                    $fileKey = 'photo_' . $postKey;
                    if (isset($_FILES[$fileKey]) && ($_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                        $rel = saveWizardPhotoTmp($vehiculoId, $postKey, $_FILES[$fileKey]);
                        if ($rel) {
                            $_SESSION['alistamiento_wizard_photos'][$vehiculoId]['puesta_marcha'][$sessionKey] = $rel;
                        }
                    }
                }
            }
            $next = $currentStep + 1;
            header('Location: ' . $URL_WIZARD . '?vehiculo=' . $vehiculoId . '&step=' . $next);
            exit;
        }
    } elseif ($currentStep === 6) {
        $validos = ['BUENO', 'MALO'];
        $map = [
            'ec_senales_carretera' => 'senales_carretera',
            'ec_caja_herramientas' => 'caja_herramientas',
            'ec_botiquin' => 'botiquin',
            'ec_tacos_bloqueo' => 'tacos_bloqueo',
            'ec_cruceta' => 'cruceta',
            'ec_chalecos' => 'chalecos',
            'ec_gato' => 'gato',
            'ec_extintor' => 'extintor',
            'ec_linterna' => 'linterna',
        ];
        $data = [];
        foreach ($map as $postKey => $sessionKey) {
            $val = isset($_POST[$postKey]) ? $_POST[$postKey] : '';
            if (!in_array($val, $validos, true)) {
                $error = 'Complete todas las respuestas con BUENO o MALO';
                break;
            }
            $data[$sessionKey] = $val;
        }
        if (empty($error)) {
            $_SESSION['alistamiento_wizard'][$vehiculoId]['equipo_carretera'] = $data;
            foreach ($map as $postKey => $sessionKey) {
                if (($data[$sessionKey] ?? '') === 'MALO') {
                    $fileKey = 'photo_' . $postKey;
                    if (isset($_FILES[$fileKey]) && ($_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                        $rel = saveWizardPhotoTmp($vehiculoId, $postKey, $_FILES[$fileKey]);
                        if ($rel) {
                            $_SESSION['alistamiento_wizard_photos'][$vehiculoId]['equipo_carretera'][$sessionKey] = $rel;
                        }
                    }
                }
            }
            $next = $currentStep + 1;
            header('Location: ' . $URL_WIZARD . '?vehiculo=' . $vehiculoId . '&step=' . $next);
            exit;
        }
    } elseif ($currentStep === 7) {
        $validos = ['BUENO', 'MALO'];
        $map = [
            'rm_cadena_cardan' => 'cadena_cardan',
            'rm_freno_emergencia' => 'freno_emergencia',
            'rm_freno_principal' => 'freno_principal',
            'rm_suspension_gral' => 'suspension_gral',
        ];
        $data = [];
        foreach ($map as $postKey => $sessionKey) {
            $val = isset($_POST[$postKey]) ? $_POST[$postKey] : '';
            if (!in_array($val, $validos, true)) {
                $error = 'Complete todas las respuestas con BUENO o MALO';
                break;
            }
            $data[$sessionKey] = $val;
        }
        if (empty($error)) {
            $_SESSION['alistamiento_wizard'][$vehiculoId]['revision_mecanica'] = $data;
            foreach ($map as $postKey => $sessionKey) {
                if (($data[$sessionKey] ?? '') === 'MALO') {
                    $fileKey = 'photo_' . $postKey;
                    if (isset($_FILES[$fileKey]) && ($_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                        $rel = saveWizardPhotoTmp($vehiculoId, $postKey, $_FILES[$fileKey]);
                        if ($rel) {
                            $_SESSION['alistamiento_wizard_photos'][$vehiculoId]['revision_mecanica'][$sessionKey] = $rel;
                        }
                    }
                }
            }
            $next = $currentStep + 1;
            header('Location: ' . $URL_WIZARD . '?vehiculo=' . $vehiculoId . '&step=' . $next);
            exit;
        }
    } elseif ($currentStep === 8) {
        $validos = ['BUENO', 'MALO'];
        $map = [
            'cc_tubos_apoyo' => 'tubos_apoyo',
            'cc_estado_piso' => 'estado_piso',
            'cc_estado_claraboya' => 'estado_claraboya',
            'cc_estado_sillas' => 'estado_sillas',
            'cc_cierre_puertas_ventanas' => 'cierre_puertas_ventanas',
            'cc_aseo_vehiculo' => 'aseo_vehiculo',
            'cc_espejos_retrovisores' => 'espejos_retrovisores',
            'cc_vidrios_panoramicos' => 'vidrios_panoramicos',
            'cc_salidas_emergencia' => 'salidas_emergencia',
            'cc_disp_velocidad_aviso' => 'disp_velocidad_aviso',
            'cc_cinturon_conductor' => 'cinturon_conductor',
            'cc_cinturon_auxiliar' => 'cinturon_auxiliar',
            'cc_limpia_parabrisas' => 'limpia_parabrisas',
            'cc_eyector_agua' => 'eyector_agua',
            'cc_estado_placas' => 'estado_placas',
            'cc_manijas_calapies' => 'manijas_calapies',
        ];
        $data = [];
        foreach ($map as $postKey => $sessionKey) {
            $val = isset($_POST[$postKey]) ? $_POST[$postKey] : '';
            if (!in_array($val, $validos, true)) {
                $error = 'Complete todas las respuestas con BUENO o MALO';
                break;
            }
            $data[$sessionKey] = $val;
        }
        if (empty($error)) {
            $_SESSION['alistamiento_wizard'][$vehiculoId]['cabina_carroceria'] = $data;
            foreach ($map as $postKey => $sessionKey) {
                if (($data[$sessionKey] ?? '') === 'MALO') {
                    $fileKey = 'photo_' . $postKey;
                    if (isset($_FILES[$fileKey]) && ($_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                        $rel = saveWizardPhotoTmp($vehiculoId, $postKey, $_FILES[$fileKey]);
                        if ($rel) {
                            $_SESSION['alistamiento_wizard_photos'][$vehiculoId]['cabina_carroceria'][$sessionKey] = $rel;
                        }
                    }
                }
            }
            $next = $currentStep + 1;
            header('Location: ' . $URL_WIZARD . '?vehiculo=' . $vehiculoId . '&step=' . $next);
            exit;
        }
    } elseif ($currentStep === 9) {
        $validos = ['BUENO', 'MALO'];
        $map = [
            'ri_indicador_combustible' => 'indicador_combustible',
            'ri_velocimetro' => 'velocimetro',
            'ri_luces_direccional' => 'luces_direccional',
            'ri_luces_altas' => 'luces_altas',
            'ri_tacometro_motor' => 'tacometro_motor',
        ];
        $data = [];
        foreach ($map as $postKey => $sessionKey) {
            $val = isset($_POST[$postKey]) ? $_POST[$postKey] : '';
            if (!in_array($val, $validos, true)) {
                $error = 'Complete todas las respuestas con BUENO o MALO';
                break;
            }
            $data[$sessionKey] = $val;
        }
        if (empty($error)) {
            $_SESSION['alistamiento_wizard'][$vehiculoId]['revision_instrumentos'] = $data;
            foreach ($map as $postKey => $sessionKey) {
                if (($data[$sessionKey] ?? '') === 'MALO') {
                    $fileKey = 'photo_' . $postKey;
                    if (isset($_FILES[$fileKey]) && ($_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                        $rel = saveWizardPhotoTmp($vehiculoId, $postKey, $_FILES[$fileKey]);
                        if ($rel) {
                            $_SESSION['alistamiento_wizard_photos'][$vehiculoId]['revision_instrumentos'][$sessionKey] = $rel;
                        }
                    }
                }
            }
            $next = $currentStep + 1;
            header('Location: ' . $URL_WIZARD . '?vehiculo=' . $vehiculoId . '&step=' . $next);
            exit;
        }
    } elseif ($currentStep === 10) {
        $validos = ['BUENO', 'MALO'];
        $map = [
            'rf_aceite_motor' => 'aceite_motor',
            'rf_aceite_hidraulico_direccion' => 'aceite_hidraulico_direccion',
            'rf_aceites_caja_transmision' => 'aceites_caja_transmision',
            'rf_combustible_sistema' => 'combustible_sistema',
            'rf_liquido_freno' => 'liquido_freno',
            'rf_refrigerante' => 'refrigerante',
        ];
        $data = [];
        foreach ($map as $postKey => $sessionKey) {
            $val = isset($_POST[$postKey]) ? $_POST[$postKey] : '';
            if (!in_array($val, $validos, true)) {
                $error = 'Complete todas las respuestas con BUENO o MALO';
                break;
            }
            $data[$sessionKey] = $val;
        }
        if (empty($error)) {
            $_SESSION['alistamiento_wizard'][$vehiculoId]['revision_fugas'] = $data;
            foreach ($map as $postKey => $sessionKey) {
                if (($data[$sessionKey] ?? '') === 'MALO') {
                    $fileKey = 'photo_' . $postKey;
                    if (isset($_FILES[$fileKey]) && ($_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                        $rel = saveWizardPhotoTmp($vehiculoId, $postKey, $_FILES[$fileKey]);
                        if ($rel) {
                            $_SESSION['alistamiento_wizard_photos'][$vehiculoId]['revision_fugas'][$sessionKey] = $rel;
                        }
                    }
                }
            }
            $next = $currentStep + 1;
            header('Location: ' . $URL_WIZARD . '?vehiculo=' . $vehiculoId . '&step=' . $next);
            exit;
        }
    } elseif ($currentStep === 11) {
        $validos = ['BUENO', 'MALO'];
        $map = [
            're_luces_altas' => 'luces_altas',
            're_luces_bajas' => 'luces_bajas',
            're_direccionales' => 'direccionales',
            're_stop_frenos' => 'stop_frenos',
            're_posicion_laterales' => 'posicion_laterales',
            're_reversa' => 'reversa',
            're_parqueo' => 'parqueo',
            're_luces_cabina' => 'luces_cabina',
            're_luces_tablero' => 'luces_tablero',
            're_pito' => 'pito',
        ];
        $data = [];
        foreach ($map as $postKey => $sessionKey) {
            $val = isset($_POST[$postKey]) ? $_POST[$postKey] : '';
            if (!in_array($val, $validos, true)) {
                $error = 'Complete todas las respuestas con BUENO o MALO';
                break;
            }
            $data[$sessionKey] = $val;
        }
        if (empty($error)) {
            $_SESSION['alistamiento_wizard'][$vehiculoId]['revision_electrica'] = $data;
            foreach ($map as $postKey => $sessionKey) {
                if (($data[$sessionKey] ?? '') === 'MALO') {
                    $fileKey = 'photo_' . $postKey;
                    if (isset($_FILES[$fileKey]) && ($_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                        $rel = saveWizardPhotoTmp($vehiculoId, $postKey, $_FILES[$fileKey]);
                        if ($rel) {
                            $_SESSION['alistamiento_wizard_photos'][$vehiculoId]['revision_electrica'][$sessionKey] = $rel;
                        }
                    }
                }
            }
            $next = $currentStep + 1;
            header('Location: ' . $URL_WIZARD . '?vehiculo=' . $vehiculoId . '&step=' . $next);
            exit;
        }
    } elseif ($currentStep === 12) {
        $validos = ['BUENO', 'MALO'];
        $map = [
            'rl_salpicaderas' => 'salpicaderas',
            'rl_llanta_delantera_izquierda' => 'llanta_delantera_izquierda',
            'rl_llanta_delantera_derecha' => 'llanta_delantera_derecha',
            'rl_llanta_trasera_interior_izquierda' => 'llanta_trasera_interior_izquierda',
            'rl_llanta_trasera_externa_izquierda' => 'llanta_trasera_externa_izquierda',
            'rl_llanta_trasera_interior_derecha' => 'llanta_trasera_interior_derecha',
            'rl_llanta_trasera_externa_derecha' => 'llanta_trasera_externa_derecha',
            'rl_llanta_repuesto' => 'llanta_repuesto',
            'rl_pernos' => 'pernos',
        ];
        $data = [];
        foreach ($map as $postKey => $sessionKey) {
            $val = isset($_POST[$postKey]) ? $_POST[$postKey] : '';
            if (!in_array($val, $validos, true)) {
                $error = 'Complete todas las respuestas con BUENO o MALO';
                break;
            }
            $data[$sessionKey] = $val;
        }
        if (empty($error)) {
            $_SESSION['alistamiento_wizard'][$vehiculoId]['revision_llantas'] = $data;
            foreach ($map as $postKey => $sessionKey) {
                if (($data[$sessionKey] ?? '') === 'MALO') {
                    $fileKey = 'photo_' . $postKey;
                    if (isset($_FILES[$fileKey]) && ($_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                        $rel = saveWizardPhotoTmp($vehiculoId, $postKey, $_FILES[$fileKey]);
                        if ($rel) {
                            $_SESSION['alistamiento_wizard_photos'][$vehiculoId]['revision_llantas'][$sessionKey] = $rel;
                        }
                    }
                }
            }

            // Calcular estado final (verde/amarillo/rojo)
            $wiz = $_SESSION['alistamiento_wizard'][$vehiculoId] ?? [];
            $vitales = ['puesta_marcha', 'revision_mecanica', 'cabina_carroceria', 'revision_instrumentos', 'revision_fugas', 'revision_electrica', 'revision_llantas'];
            $noVitales = ['videograbacion', 'equipo_carretera'];

            $hayMaloVital = false;
            foreach ($vitales as $cat) {
                if (isset($wiz[$cat]) && is_array($wiz[$cat])) {
                    foreach ($wiz[$cat] as $val) {
                        if ($val === 'MALO') { $hayMaloVital = true; break 2; }
                    }
                }
            }

            $hayMaloNoVital = false;
            // Videograbación acepta N/A y no penaliza; solo MALO cuenta
            if (isset($wiz['videograbacion']) && is_array($wiz['videograbacion'])) {
                foreach ($wiz['videograbacion'] as $val) { if ($val === 'MALO') { $hayMaloNoVital = true; break; } }
            }
            if (!$hayMaloNoVital && isset($wiz['equipo_carretera']) && is_array($wiz['equipo_carretera'])) {
                foreach ($wiz['equipo_carretera'] as $val) { if ($val === 'MALO') { $hayMaloNoVital = true; break; } }
            }

            if ($hayMaloVital) {
                $estadoFinal = 'rojo';
            } elseif ($hayMaloNoVital) {
                $estadoFinal = 'amarillo';
            } else {
                $estadoFinal = 'verde';
            }

            // Construir observaciones generales con las fallas detectadas
            $catNames = [
                'puesta_marcha' => 'Puesta en Marcha',
                'revision_mecanica' => 'Revisión Mecánica',
                'cabina_carroceria' => 'Cabina y Carrocería',
                'revision_instrumentos' => 'Revisión de Instrumentos',
                'revision_fugas' => 'Revisión de Fugas',
                'revision_electrica' => 'Revisión Eléctrica',
                'revision_llantas' => 'Revisión de Llantas',
                'videograbacion' => 'Videograbación y Satelital',
                'equipo_carretera' => 'Equipo de Carretera',
            ];
            $fallasPorCategoria = [];
            foreach ($wiz as $catKey => $items) {
                if (!is_array($items)) { continue; }
                $malos = [];
                foreach ($items as $itemKey => $val) {
                    if ($catKey === 'videograbacion' && $val === 'N/A') { continue; }
                    if ($val === 'MALO') { $malos[] = $itemKey; }
                }
                if (!empty($malos)) {
                    $nombreCat = $catNames[$catKey] ?? ucfirst(str_replace('_', ' ', $catKey));
                    $fallasPorCategoria[$nombreCat] = $malos;
                }
            }
            $observacionesGenerales = null;
            if (!empty($fallasPorCategoria)) {
                $partes = [];
                foreach ($fallasPorCategoria as $nombreCat => $malos) {
                    $partes[] = $nombreCat . ': ' . implode(', ', $malos);
                }
                $observacionesGenerales = 'Fallas detectadas -> ' . implode('; ', $partes);
            }

            // Guardar el alistamiento en BD (solo en tabla `alistamientos`)
            try {
                $usuario = obtenerUsuarioActual();
                $inspectorId = (int)($usuario['id'] ?? 0);
                // Capturar conductor seleccionado en el paso 2 (si existe)
                $conductorId = (int)($_SESSION['alistamiento_wizard'][$vehiculoId]['conductor_id'] ?? 0);
                if ($conductorId <= 0) { $conductorId = null; }
                if ($conductorId) {
                    try {
                        $alistamientoId = $db->insert(
                            "INSERT INTO alistamientos (vehiculo_id, inspector_id, estado_final, es_alistamiento_parcial, observaciones_generales, conductor_id) VALUES (?, ?, ?, ?, ?, ?)",
                            [$vehiculoId, $inspectorId, $estadoFinal, 0, $observacionesGenerales, $conductorId]
                        );
                    } catch (Exception $eIns) {
                        $alistamientoId = $db->insert(
                            "INSERT INTO alistamientos (vehiculo_id, inspector_id, estado_final, es_alistamiento_parcial, observaciones_generales) VALUES (?, ?, ?, ?, ?)",
                            [$vehiculoId, $inspectorId, $estadoFinal, 0, $observacionesGenerales]
                        );
                        try { $db->execute("UPDATE alistamientos SET conductor_id = ? WHERE id = ?", [$conductorId, $alistamientoId]); } catch (Exception $eUpd) {}
                    }
                } else {
                    $alistamientoId = $db->insert(
                        "INSERT INTO alistamientos (vehiculo_id, inspector_id, estado_final, es_alistamiento_parcial, observaciones_generales) VALUES (?, ?, ?, ?, ?)",
                        [$vehiculoId, $inspectorId, $estadoFinal, 0, $observacionesGenerales]
                    );
                }

                // Persistir detalles del checklist en tablas dedicadas (con fotos del wizard si existen)
                $photos = $_SESSION['alistamiento_wizard_photos'][$vehiculoId] ?? [];
                persistWizardDetails($alistamientoId, $wiz, $photos);
                // Limpiar fotos temporales de sesión
                unset($_SESSION['alistamiento_wizard_photos'][$vehiculoId]);

                // Paso final del wizard: marcar finalización
                $_SESSION['alistamiento_wizard'][$vehiculoId]['finalizado'] = true;

                // Redirigir al resumen del alistamiento sin evidencias adicionales
                header('Location: ' . $URL_ALISTAMIENTO . '?vehiculo=' . $vehiculoId);
                exit;
            } catch (Exception $e) {
                // Si falla el guardado, mantener el flujo pero registrar el error en sesión para mostrar luego
                $_SESSION['alistamiento_wizard'][$vehiculoId]['error_guardado'] = 'No se pudo guardar el alistamiento: ' . $e->getMessage();
                // En caso de error, volver al resumen
                header('Location: ' . $URL_ALISTAMIENTO . '?vehiculo=' . $vehiculoId);
                exit;
            }
        }
    }
}

// HTML de la página del wizard
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Alistamiento - Wizard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo $URL_INDEX; ?>">SIGAVV Inspector</a>
            <div class="d-flex text-white">
                <span class="me-3">Vehículo: <?php echo htmlspecialchars($vehiculo['numero_interno'] ?? $vehiculo['placa']); ?></span>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Alistamiento</h1>
            <a class="btn btn-outline-secondary" href="<?php echo $URL_ALISTAMIENTO; ?>?vehiculo=<?php echo $vehiculoId; ?>">Volver</a>
        </div>

        <div class="mb-3">
            <div class="progress" role="progressbar" aria-label="Progreso" aria-valuenow="<?php echo $step; ?>" aria-valuemin="1" aria-valuemax="5">
                <div class="progress-bar" style="width: <?php echo min(100, $step * 20); ?>%"></div>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <div class="card">
                <div class="card-header">Paso 1: Tipo de Conductor (FIJO o SUPERNUMERARIO)</div>
                <div class="card-body">
            <form method="post" action="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=1">
                <input type="hidden" name="current_step" value="1" />
                <div class="mb-3">
                            <label class="form-label">Seleccione el tipo de conductor</label>
                            <small class="text-muted d-block mb-2">Esta categoría define si el conductor es fijo o supernumerario.</small>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_designacion" id="tipo_fijo" value="FIJO" required <?php echo (($_SESSION['alistamiento_wizard'][$vehiculoId]['tipo_designacion'] ?? '') === 'FIJO') ? 'checked' : ''; ?> />
                                <label class="form-check-label" for="tipo_fijo">FIJO</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_designacion" id="tipo_super" value="SUPERNUMERARIO" required <?php echo (($_SESSION['alistamiento_wizard'][$vehiculoId]['tipo_designacion'] ?? '') === 'SUPERNUMERARIO') ? 'checked' : ''; ?> />
                                <label class="form-check-label" for="tipo_super">SUPERNUMERARIO</label>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" disabled>Anterior</button>
                            <button type="submit" class="btn btn-primary">Siguiente</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif ($step === 2): ?>
            <div class="card">
                <div class="card-header">Paso 2: Seleccionar Conductor del día</div>
                <div class="card-body">
                    <?php if (isset($conductores) && is_array($conductores) && count($conductores) > 0): ?>
                        <?php 
                            $selectedConductor = $_SESSION['alistamiento_wizard'][$vehiculoId]['conductor_id'] ?? 0;
                            $selectedConductorText = '';
                            if ($selectedConductor) {
                                foreach ($conductores as $c) {
                                    if ((int)$c['id'] === (int)$selectedConductor) {
                                        $selectedConductorText = $c['nombre'] . ' (CC: ' . $c['cedula'] . ')';
                                        break;
                                    }
                                }
                            }
                        ?>
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
            <form method="post" action="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=2">
                            <input type="hidden" name="current_step" value="2" />
                            <input type="hidden" name="conductor_id" id="conductorId" value="<?php echo (int)$selectedConductor; ?>" />
                            <div class="mb-3 position-relative">
                                <label class="form-label">Conductor asignado</label>
                                <small class="text-muted d-block mb-2">Empiece a escribir nombre o cédula y seleccione de las sugerencias.</small>
                                <input type="text" class="form-control" id="conductorSearch" placeholder="Ej: Carlos Beltrán o 123456" autocomplete="off" value="<?php echo htmlspecialchars($selectedConductorText); ?>" />
                                <div id="conductorSuggestions" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a class="btn btn-secondary" href="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=1">Anterior</a>
                                <button type="submit" class="btn btn-primary">Siguiente</button>
                            </div>
                        </form>
                        <script>
                            (function(){
                                const conductores = <?php echo json_encode($conductores, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                                const input = document.getElementById('conductorSearch');
                                const hiddenId = document.getElementById('conductorId');
                                const box = document.getElementById('conductorSuggestions');
                                
                                function stripAccents(s){
                                    return s.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                                }
                                function render(list){
                                    box.innerHTML = '';
                                    list.slice(0, 8).forEach(c => {
                                        const item = document.createElement('button');
                                        item.type = 'button';
                                        item.className = 'list-group-item list-group-item-action';
                                        item.textContent = `${c.nombre} (CC: ${c.cedula})`;
                                        item.addEventListener('click', () => {
                                            input.value = `${c.nombre} (CC: ${c.cedula})`;
                                            hiddenId.value = c.id;
                                            box.innerHTML = '';
                                        });
                                        box.appendChild(item);
                                    });
                                    if (list.length === 0 && input.value.trim() !== '') {
                                        const empty = document.createElement('div');
                                        empty.className = 'list-group-item';
                                        empty.textContent = 'Sin coincidencias';
                                        box.appendChild(empty);
                                    }
                                }
                                
                                input.addEventListener('input', () => {
                                    const q = stripAccents(input.value.toLowerCase().trim());
                                    hiddenId.value = '';
                                    if (!q) { box.innerHTML = ''; return; }
                                    const results = conductores.filter(c => {
                                        const nombre = stripAccents(String(c.nombre).toLowerCase());
                                        const cedula = String(c.cedula).toLowerCase();
                                        return nombre.includes(q) || cedula.includes(q);
                                    });
                                    render(results);
                                });
                                
                                // Cerrar sugerencias al hacer click fuera
                                document.addEventListener('click', (e) => {
                                    if (!box.contains(e.target) && e.target !== input) {
                                        box.innerHTML = '';
                                    }
                                });
                            })();
                        </script>
                    <?php else: ?>
                        <div class="alert alert-warning">No hay conductores activos disponibles. Registre conductores en el módulo correspondiente.</div>
                        <div class="d-flex justify-content-between">
                            <a class="btn btn-secondary" href="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=1">Anterior</a>
                            <button type="button" class="btn btn-primary" disabled>Siguiente</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($step === 3): ?>
            <div class="card">
                <div class="card-header">Paso 3: Última Fecha de Mantenimiento</div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php 
                        $mantTipo = $_SESSION['alistamiento_wizard'][$vehiculoId]['mantenimiento_tipo'] ?? '';
                        $mantFecha = $_SESSION['alistamiento_wizard'][$vehiculoId]['mantenimiento_fecha'] ?? '';
                        $mantOtro = $_SESSION['alistamiento_wizard'][$vehiculoId]['mantenimiento_otro'] ?? '';
                    ?>
            <form method="post" action="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=3">
                        <input type="hidden" name="current_step" value="3" />
                        <div class="mb-3">
                            <label class="form-label">En el último mantenimiento se realizó:</label>
                            <small class="text-muted d-block mb-2">Seleccione la opción y (si aplica) registre la fecha en el calendario.</small>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mantenimiento_tipo" id="mant_aceite" value="ACEITE_MOTOR" <?php echo ($mantTipo === 'ACEITE_MOTOR') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="mant_aceite">Cambio de Aceite del Motor</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mantenimiento_tipo" id="mant_frenos" value="LIQUIDO_FRENOS" <?php echo ($mantTipo === 'LIQUIDO_FRENOS') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="mant_frenos">Cambio de Líquido de Frenos</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mantenimiento_tipo" id="mant_llantas" value="LLANTAS" <?php echo ($mantTipo === 'LLANTAS') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="mant_llantas">Cambio de Llantas</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mantenimiento_tipo" id="mant_sync" value="SINCRONIZACION" <?php echo ($mantTipo === 'SINCRONIZACION') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="mant_sync">Sincronización</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mantenimiento_tipo" id="mant_alin" value="ALINEACION_BALANCEO" <?php echo ($mantTipo === 'ALINEACION_BALANCEO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="mant_alin">Alineación y Balanceo</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mantenimiento_tipo" id="mant_tension" value="TENSION_FRENOS" <?php echo ($mantTipo === 'TENSION_FRENOS') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="mant_tension">Tensión de Frenos</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mantenimiento_tipo" id="mant_ninguna" value="NINGUNA" <?php echo ($mantTipo === 'NINGUNA') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="mant_ninguna">Ninguna</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mantenimiento_tipo" id="mant_otro" value="OTRO" <?php echo ($mantTipo === 'OTRO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="mant_otro">Otro</label>
                            </div>
                            <div class="mt-2" id="mantOtroBox" style="display: <?php echo ($mantTipo === 'OTRO') ? 'block' : 'none'; ?>;">
                                <input type="text" class="form-control" name="mantenimiento_otro" id="mant_otro_text" placeholder="Describa el mantenimiento" value="<?php echo htmlspecialchars($mantOtro); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha del último mantenimiento</label>
                            <input type="date" class="form-control" name="mantenimiento_fecha" id="mant_fecha" value="<?php echo htmlspecialchars($mantFecha); ?>" required>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a class="btn btn-secondary" href="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=2">Anterior</a>
                            <button type="submit" class="btn btn-primary">Siguiente</button>
                        </div>
                    </form>
                    <script>
                        (function(){
                            const radios = document.querySelectorAll('input[name="mantenimiento_tipo"]');
                            const box = document.getElementById('mantOtroBox');
                            const text = document.getElementById('mant_otro_text');
                            const fecha = document.getElementById('mant_fecha');
                            function update(){
                                const val = Array.from(radios).find(r => r.checked)?.value;
                                if (val === 'OTRO') {
                                    box.style.display = 'block';
                                    text.setAttribute('required', 'required');
                                } else {
                                    box.style.display = 'none';
                                    text.removeAttribute('required');
                                }
                                if (val === 'NINGUNA') {
                                    fecha.removeAttribute('required');
                                } else {
                                    fecha.setAttribute('required', 'required');
                                }
                            }
                            radios.forEach(r => r.addEventListener('change', update));
                            update();
                        })();
                    </script>
                </div>
            </div>
        <?php elseif ($step === 4): ?>
            <div class="card">
                <div class="card-header">Paso 4: Inspección Visual de Videograbación y Seguimiento Satelital</div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php $vg = $_SESSION['alistamiento_wizard'][$vehiculoId]['videograbacion'] ?? []; ?>
                    <p class="mb-3"><strong>Nota:</strong> En las respuestas de este ítem por favor registrar <strong>BUENO</strong>, <strong>MALO</strong> o <strong>N/A</strong> según corresponda.</p>
            <form method="post" action="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=4">
                        <input type="hidden" name="current_step" value="4" />
                        <?php
                            $opciones = ['BUENO', 'MALO', 'N/A'];
                            function radios($name, $current, $opciones) {
                                foreach ($opciones as $i => $opt) {
                                    $id = $name . '_' . strtolower(str_replace(['/', ' '], ['_', ''], $opt));
                                    $checked = ($current === $opt) ? 'checked' : '';
                                    $required = ($i === 0) ? 'required' : '';
                                    echo '<div class="form-check form-check-inline">';
                                    echo '<input class="form-check-input" type="radio" name="' . $name . '" id="' . $id . '" value="' . $opt . '" ' . $checked . ' ' . $required . ' />';
                                    echo '<label class="form-check-label" for="' . $id . '">' . $opt . '</label>';
                                    echo '</div>';
                                }
                            }
                        ?>
                        <div class="mb-3">
                            <label class="form-label">Dispositivo GPS</label><br>
                            <?php radios('vg_gps', $vg['gps'] ?? '', $opciones); ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cámara 1 (Cabina)</label><br>
                            <?php radios('vg_camara1', $vg['camara1'] ?? '', $opciones); ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cámara 2 (Pasillo)</label><br>
                            <?php radios('vg_camara2', $vg['camara2'] ?? '', $opciones); ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Caja de MDVR</label><br>
                            <?php radios('vg_caja_mdvr', $vg['caja_mdvr'] ?? '', $opciones); ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">MDVR</label><br>
                            <?php radios('vg_mdvr', $vg['mdvr'] ?? '', $opciones); ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Memoria MDVR</label><br>
                            <?php radios('vg_memoria_mdvr', $vg['memoria_mdvr'] ?? '', $opciones); ?>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a class="btn btn-secondary" href="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=3">Anterior</a>
                            <button type="submit" class="btn btn-primary">Siguiente</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif ($step === 5): ?>
            <div class="card">
                <div class="card-header">Paso 5: Verificación Puesta en Marcha</div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php $pm = $_SESSION['alistamiento_wizard'][$vehiculoId]['puesta_marcha'] ?? []; ?>
                    <p class="mb-3"><strong>Nota:</strong> Responda <strong>BUENO</strong> o <strong>MALO</strong> según corresponda.</p>
            <form method="post" action="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=5">
                        <input type="hidden" name="current_step" value="5" />

                        <div class="mb-3">
                            <label class="form-label">Pedal Clutch (superficie antideslizante, salida gradual, recorrido)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="pm_pedal_clutch" id="pm_pedal_clutch_bueno" value="BUENO" <?php echo (($pm['pedal_clutch'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="pm_pedal_clutch_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="pm_pedal_clutch" id="pm_pedal_clutch_malo" value="MALO" <?php echo (($pm['pedal_clutch'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="pm_pedal_clutch_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Sistema de Encendido (enciende en el primer start)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="pm_sistema_encendido" id="pm_sistema_encendido_bueno" value="BUENO" <?php echo (($pm['sistema_encendido'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="pm_sistema_encendido_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="pm_sistema_encendido" id="pm_sistema_encendido_malo" value="MALO" <?php echo (($pm['sistema_encendido'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="pm_sistema_encendido_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Sistema de Transmisión (arranque adelante/atrás, caja/palanca cambios)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="pm_sistema_transmision" id="pm_sistema_transmision_bueno" value="BUENO" <?php echo (($pm['sistema_transmision'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="pm_sistema_transmision_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="pm_sistema_transmision" id="pm_sistema_transmision_malo" value="MALO" <?php echo (($pm['sistema_transmision'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="pm_sistema_transmision_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Sistema de Dirección (giros izquierda/derecha sin ruidos extraños)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="pm_sistema_direccion" id="pm_sistema_direccion_bueno" value="BUENO" <?php echo (($pm['sistema_direccion'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="pm_sistema_direccion_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="pm_sistema_direccion" id="pm_sistema_direccion_malo" value="MALO" <?php echo (($pm['sistema_direccion'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="pm_sistema_direccion_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Correas y Mangueras (alineadas, tensionadas, sin desgaste)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="pm_correas_mangueras" id="pm_correas_mangueras_bueno" value="BUENO" <?php echo (($pm['correas_mangueras'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="pm_correas_mangueras_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="pm_correas_mangueras" id="pm_correas_mangueras_malo" value="MALO" <?php echo (($pm['correas_mangueras'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="pm_correas_mangueras_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Sistema de Arranque</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="pm_sistema_arranque" id="pm_sistema_arranque_bueno" value="BUENO" <?php echo (($pm['sistema_arranque'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="pm_sistema_arranque_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="pm_sistema_arranque" id="pm_sistema_arranque_malo" value="MALO" <?php echo (($pm['sistema_arranque'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="pm_sistema_arranque_malo">MALO</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a class="btn btn-secondary" href="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=4">Anterior</a>
                            <button type="submit" class="btn btn-primary">Siguiente</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif ($step === 6): ?>
            <div class="card">
                <div class="card-header">Paso 6: Equipo de Carretera</div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php $ec = $_SESSION['alistamiento_wizard'][$vehiculoId]['equipo_carretera'] ?? []; ?>
                    <p class="mb-3"><strong>Nota:</strong> Responda <strong>BUENO</strong> o <strong>MALO</strong> para cada elemento del equipo requerido.</p>
            <form method="post" action="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=6">
                        <input type="hidden" name="current_step" value="6" />

                        <div class="mb-3">
                            <label class="form-label">Señales de Carretera (mínimo dos (2) triángulos reflectivos con soportes o lámparas de señal amarilla intermitentes)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ec_senales_carretera" id="ec_senales_carretera_bueno" value="BUENO" <?php echo (($ec['senales_carretera'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="ec_senales_carretera_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ec_senales_carretera" id="ec_senales_carretera_malo" value="MALO" <?php echo (($ec['senales_carretera'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ec_senales_carretera_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Caja de Herramientas (mínimo: alicate, destornilladores, llave de expansión y llaves fijas)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ec_caja_herramientas" id="ec_caja_herramientas_bueno" value="BUENO" <?php echo (($ec['caja_herramientas'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="ec_caja_herramientas_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ec_caja_herramientas" id="ec_caja_herramientas_malo" value="MALO" <?php echo (($ec['caja_herramientas'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ec_caja_herramientas_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Botiquín de Primeros Auxilios (mínimo uno (1) en buen estado, disponible y completo)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ec_botiquin" id="ec_botiquin_bueno" value="BUENO" <?php echo (($ec['botiquin'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="ec_botiquin_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ec_botiquin" id="ec_botiquin_malo" value="MALO" <?php echo (($ec['botiquin'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ec_botiquin_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tacos para Bloqueo (mínimo dos (2))</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ec_tacos_bloqueo" id="ec_tacos_bloqueo_bueno" value="BUENO" <?php echo (($ec['tacos_bloqueo'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="ec_tacos_bloqueo_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ec_tacos_bloqueo" id="ec_tacos_bloqueo_malo" value="MALO" <?php echo (($ec['tacos_bloqueo'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ec_tacos_bloqueo_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cruceta (mínimo una (1))</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ec_cruceta" id="ec_cruceta_bueno" value="BUENO" <?php echo (($ec['cruceta'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="ec_cruceta_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ec_cruceta" id="ec_cruceta_malo" value="MALO" <?php echo (($ec['cruceta'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ec_cruceta_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Chalecos Reflectivos (mínimo dos (2))</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ec_chalecos" id="ec_chalecos_bueno" value="BUENO" <?php echo (($ec['chalecos'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="ec_chalecos_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ec_chalecos" id="ec_chalecos_malo" value="MALO" <?php echo (($ec['chalecos'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ec_chalecos_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Gato (mínimo uno (1) acorde al peso y dimensión del vehículo)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ec_gato" id="ec_gato_bueno" value="BUENO" <?php echo (($ec['gato'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="ec_gato_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ec_gato" id="ec_gato_malo" value="MALO" <?php echo (($ec['gato'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ec_gato_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Extintor (mínimo uno (1) recargado, manómetro vigente, etiqueta y anillo en buen estado)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ec_extintor" id="ec_extintor_bueno" value="BUENO" <?php echo (($ec['extintor'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="ec_extintor_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ec_extintor" id="ec_extintor_malo" value="MALO" <?php echo (($ec['extintor'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ec_extintor_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Linterna (mínimo una (1) funcional)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ec_linterna" id="ec_linterna_bueno" value="BUENO" <?php echo (($ec['linterna'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="ec_linterna_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ec_linterna" id="ec_linterna_malo" value="MALO" <?php echo (($ec['linterna'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ec_linterna_malo">MALO</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a class="btn btn-secondary" href="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=5">Anterior</a>
                            <button type="submit" class="btn btn-primary">Siguiente</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif ($step === 7): ?>
            <div class="card">
                <div class="card-header">Paso 7: Revisión Mecánica</div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php $rm = $_SESSION['alistamiento_wizard'][$vehiculoId]['revision_mecanica'] ?? []; ?>
                    <p class="mb-3"><strong>Nota:</strong> Responda <strong>BUENO</strong> o <strong>MALO</strong> para cada ítem.</p>
            <form method="post" action="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=7">
                        <input type="hidden" name="current_step" value="7" />

                        <div class="mb-3">
                            <label class="form-label">Cadena para Cardan (presencia de cadena que sirva de sostén en caso de desprendimiento del cardan)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rm_cadena_cardan" id="rm_cadena_cardan_bueno" value="BUENO" <?php echo (($rm['cadena_cardan'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="rm_cadena_cardan_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rm_cadena_cardan" id="rm_cadena_cardan_malo" value="MALO" <?php echo (($rm['cadena_cardan'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="rm_cadena_cardan_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Freno de Emergencia (prueba con arranque del vehículo)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rm_freno_emergencia" id="rm_freno_emergencia_bueno" value="BUENO" <?php echo (($rm['freno_emergencia'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="rm_freno_emergencia_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rm_freno_emergencia" id="rm_freno_emergencia_malo" value="MALO" <?php echo (($rm['freno_emergencia'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="rm_freno_emergencia_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Freno Principal (tensionado; verificar recorrido del pedal; superficie antideslizante y ergonómica accesible; libre de residuos aceitosos y bordes filosos)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rm_freno_principal" id="rm_freno_principal_bueno" value="BUENO" <?php echo (($rm['freno_principal'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="rm_freno_principal_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rm_freno_principal" id="rm_freno_principal_malo" value="MALO" <?php echo (($rm['freno_principal'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="rm_freno_principal_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Estado Suspensión General (existencia de amortiguadores y hojas de muelles en buen estado)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rm_suspension_gral" id="rm_suspension_gral_bueno" value="BUENO" <?php echo (($rm['suspension_gral'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="rm_suspension_gral_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rm_suspension_gral" id="rm_suspension_gral_malo" value="MALO" <?php echo (($rm['suspension_gral'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="rm_suspension_gral_malo">MALO</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a class="btn btn-secondary" href="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=6">Anterior</a>
                            <button type="submit" class="btn btn-primary">Siguiente</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif ($step === 8): ?>
            <div class="card">
                <div class="card-header">Paso 8: Revisión Cabina y Carrocería</div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php $cc = $_SESSION['alistamiento_wizard'][$vehiculoId]['cabina_carroceria'] ?? []; ?>
                    <p class="mb-3"><strong>Nota:</strong> Responda <strong>BUENO</strong> o <strong>MALO</strong> para cada ítem.</p>
            <form method="post" action="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=8">
                        <input type="hidden" name="current_step" value="8" />

                        <div class="mb-3">
                            <label class="form-label">Tubos de Apoyo (que no estén sueltos, deben estar fijos)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_tubos_apoyo" id="cc_tubos_apoyo_bueno" value="BUENO" <?php echo (($cc['tubos_apoyo'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="cc_tubos_apoyo_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_tubos_apoyo" id="cc_tubos_apoyo_malo" value="MALO" <?php echo (($cc['tubos_apoyo'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cc_tubos_apoyo_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Estado del Piso (sin hendiduras, juntas no despegadas y superficie antideslizante en buen estado)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_estado_piso" id="cc_estado_piso_bueno" value="BUENO" <?php echo (($cc['estado_piso'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="cc_estado_piso_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_estado_piso" id="cc_estado_piso_malo" value="MALO" <?php echo (($cc['estado_piso'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cc_estado_piso_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Estado de Claraboya (apertura y cierre)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_estado_claraboya" id="cc_estado_claraboya_bueno" value="BUENO" <?php echo (($cc['estado_claraboya'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="cc_estado_claraboya_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_estado_claraboya" id="cc_estado_claraboya_malo" value="MALO" <?php echo (($cc['estado_claraboya'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cc_estado_claraboya_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Estado de Sillas (tapicería en buen estado; cantidad coincide con tarjeta de propiedad; aseguradas; graduable en silla y espaldar para conductor y pasajeros; con apoyacabeza para conductor y silla auxiliar)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_estado_sillas" id="cc_estado_sillas_bueno" value="BUENO" <?php echo (($cc['estado_sillas'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="cc_estado_sillas_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_estado_sillas" id="cc_estado_sillas_malo" value="MALO" <?php echo (($cc['estado_sillas'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cc_estado_sillas_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cierre de Puertas y Ventanas (estado de chapas; apertura y cierre de puertas delanteras; cierre y seguros de ventanas)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_cierre_puertas_ventanas" id="cc_cierre_puertas_ventanas_bueno" value="BUENO" <?php echo (($cc['cierre_puertas_ventanas'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="cc_cierre_puertas_ventanas_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_cierre_puertas_ventanas" id="cc_cierre_puertas_ventanas_malo" value="MALO" <?php echo (($cc['cierre_puertas_ventanas'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cc_cierre_puertas_ventanas_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Aseo del Vehículo (recipiente para residuos; organización de guantera y elementos de cabina)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_aseo_vehiculo" id="cc_aseo_vehiculo_bueno" value="BUENO" <?php echo (($cc['aseo_vehiculo'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="cc_aseo_vehiculo_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_aseo_vehiculo" id="cc_aseo_vehiculo_malo" value="MALO" <?php echo (($cc['aseo_vehiculo'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cc_aseo_vehiculo_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Estado del Espejo Retrovisor (mínimo dos, no rayados ni manchados, sin fisuras y fijos)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_espejos_retrovisores" id="cc_espejos_retrovisores_bueno" value="BUENO" <?php echo (($cc['espejos_retrovisores'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="cc_espejos_retrovisores_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_espejos_retrovisores" id="cc_espejos_retrovisores_malo" value="MALO" <?php echo (($cc['espejos_retrovisores'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cc_espejos_retrovisores_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Estado de Vidrios Panorámicos (sin rupturas y polarizados no obstruyen trayectoria de plumillas)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_vidrios_panoramicos" id="cc_vidrios_panoramicos_bueno" value="BUENO" <?php echo (($cc['vidrios_panoramicos'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="cc_vidrios_panoramicos_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_vidrios_panoramicos" id="cc_vidrios_panoramicos_malo" value="MALO" <?php echo (($cc['vidrios_panoramicos'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cc_vidrios_panoramicos_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Salidas de Emergencia (señalización visible, instrucciones de uso y accesorios en buen estado: un martillo por ventana o sistema de expulsión)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_salidas_emergencia" id="cc_salidas_emergencia_bueno" value="BUENO" <?php echo (($cc['salidas_emergencia'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="cc_salidas_emergencia_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_salidas_emergencia" id="cc_salidas_emergencia_malo" value="MALO" <?php echo (($cc['salidas_emergencia'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cc_salidas_emergencia_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Dispositivo de Velocidad y Aviso (funcional, visible y audible con aviso que lo notifica)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_disp_velocidad_aviso" id="cc_disp_velocidad_aviso_bueno" value="BUENO" <?php echo (($cc['disp_velocidad_aviso'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="cc_disp_velocidad_aviso_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_disp_velocidad_aviso" id="cc_disp_velocidad_aviso_malo" value="MALO" <?php echo (($cc['disp_velocidad_aviso'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cc_disp_velocidad_aviso_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cinturón de Seguridad Conductor (hebilla y riata en buen estado; retráctil; bloqueo al halar; buen ajuste)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_cinturon_conductor" id="cc_cinturon_conductor_bueno" value="BUENO" <?php echo (($cc['cinturon_conductor'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="cc_cinturon_conductor_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_cinturon_conductor" id="cc_cinturon_conductor_malo" value="MALO" <?php echo (($cc['cinturon_conductor'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cc_cinturon_conductor_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cinturón de Seguridad Auxiliar (hebilla y riata en buen estado; retráctil; bloqueo al halar; buen ajuste)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_cinturon_auxiliar" id="cc_cinturon_auxiliar_bueno" value="BUENO" <?php echo (($cc['cinturon_auxiliar'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="cc_cinturon_auxiliar_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_cinturon_auxiliar" id="cc_cinturon_auxiliar_malo" value="MALO" <?php echo (($cc['cinturon_auxiliar'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cc_cinturon_auxiliar_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Limpia Parabrisas (dos plumillas existentes y funcionales)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_limpia_parabrisas" id="cc_limpia_parabrisas_bueno" value="BUENO" <?php echo (($cc['limpia_parabrisas'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="cc_limpia_parabrisas_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_limpia_parabrisas" id="cc_limpia_parabrisas_malo" value="MALO" <?php echo (($cc['limpia_parabrisas'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cc_limpia_parabrisas_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Eyector de Agua Limpia para Parabrisas</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_eyector_agua" id="cc_eyector_agua_bueno" value="BUENO" <?php echo (($cc['eyector_agua'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="cc_eyector_agua_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_eyector_agua" id="cc_eyector_agua_malo" value="MALO" <?php echo (($cc['eyector_agua'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cc_eyector_agua_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Estado de Placas (cinco placas, nítidas, correcta colocación)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_estado_placas" id="cc_estado_placas_bueno" value="BUENO" <?php echo (($cc['estado_placas'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="cc_estado_placas_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_estado_placas" id="cc_estado_placas_malo" value="MALO" <?php echo (($cc['estado_placas'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cc_estado_placas_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Manijas y Calapiés (superficie antideslizante, fijas y limpias)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_manijas_calapies" id="cc_manijas_calapies_bueno" value="BUENO" <?php echo (($cc['manijas_calapies'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="cc_manijas_calapies_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cc_manijas_calapies" id="cc_manijas_calapies_malo" value="MALO" <?php echo (($cc['manijas_calapies'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cc_manijas_calapies_malo">MALO</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a class="btn btn-secondary" href="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=7">Anterior</a>
                            <button type="submit" class="btn btn-primary">Siguiente</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif ($step === 9): ?>
            <div class="card">
                <div class="card-header">Paso 9: Revisión de Instrumentos</div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php $ri = $_SESSION['alistamiento_wizard'][$vehiculoId]['revision_instrumentos'] ?? []; ?>
                    <p class="mb-3"><strong>Nota:</strong> Al encender, verifique que no queden encendidos los testigos. Responda <strong>BUENO</strong> o <strong>MALO</strong> para cada instrumento.</p>
            <form method="post" action="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=9">
                        <input type="hidden" name="current_step" value="9" />

                        <div class="mb-3">
                            <label class="form-label">Indicador de Combustible</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ri_indicador_combustible" id="ri_indicador_combustible_bueno" value="BUENO" <?php echo (($ri['indicador_combustible'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="ri_indicador_combustible_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ri_indicador_combustible" id="ri_indicador_combustible_malo" value="MALO" <?php echo (($ri['indicador_combustible'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ri_indicador_combustible_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tacómetro de Velocímetro (funcional)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ri_velocimetro" id="ri_velocimetro_bueno" value="BUENO" <?php echo (($ri['velocimetro'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="ri_velocimetro_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ri_velocimetro" id="ri_velocimetro_malo" value="MALO" <?php echo (($ri['velocimetro'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ri_velocimetro_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Indicador de Luces Direccional</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ri_luces_direccional" id="ri_luces_direccional_bueno" value="BUENO" <?php echo (($ri['luces_direccional'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="ri_luces_direccional_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ri_luces_direccional" id="ri_luces_direccional_malo" value="MALO" <?php echo (($ri['luces_direccional'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ri_luces_direccional_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Indicador de Luces Altas</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ri_luces_altas" id="ri_luces_altas_bueno" value="BUENO" <?php echo (($ri['luces_altas'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="ri_luces_altas_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ri_luces_altas" id="ri_luces_altas_malo" value="MALO" <?php echo (($ri['luces_altas'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ri_luces_altas_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tacómetro de Motor (funcional)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ri_tacometro_motor" id="ri_tacometro_motor_bueno" value="BUENO" <?php echo (($ri['tacometro_motor'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="ri_tacometro_motor_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ri_tacometro_motor" id="ri_tacometro_motor_malo" value="MALO" <?php echo (($ri['tacometro_motor'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ri_tacometro_motor_malo">MALO</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a class="btn btn-secondary" href="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=8">Anterior</a>
                            <button type="submit" class="btn btn-primary">Siguiente</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif ($step === 10): ?>
            <div class="card">
                <div class="card-header">Paso 10: Revisión de Fugas</div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php $rf = $_SESSION['alistamiento_wizard'][$vehiculoId]['revision_fugas'] ?? []; ?>
                    <p class="mb-3"><strong>Nota:</strong> En límites permisibles sin presencia de fugas. Responda <strong>BUENO</strong> o <strong>MALO</strong> para cada ítem.</p>
            <form method="post" action="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=10">
                        <input type="hidden" name="current_step" value="10" />

                        <div class="mb-3">
                            <label class="form-label">Aceite de Motor</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rf_aceite_motor" id="rf_aceite_motor_bueno" value="BUENO" <?php echo (($rf['aceite_motor'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="rf_aceite_motor_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rf_aceite_motor" id="rf_aceite_motor_malo" value="MALO" <?php echo (($rf['aceite_motor'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="rf_aceite_motor_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Aceite Hidráulico Dirección</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rf_aceite_hidraulico_direccion" id="rf_aceite_hidraulico_direccion_bueno" value="BUENO" <?php echo (($rf['aceite_hidraulico_direccion'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="rf_aceite_hidraulico_direccion_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rf_aceite_hidraulico_direccion" id="rf_aceite_hidraulico_direccion_malo" value="MALO" <?php echo (($rf['aceite_hidraulico_direccion'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="rf_aceite_hidraulico_direccion_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Aceites Caja de Transmisiones</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rf_aceites_caja_transmision" id="rf_aceites_caja_transmision_bueno" value="BUENO" <?php echo (($rf['aceites_caja_transmision'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="rf_aceites_caja_transmision_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rf_aceites_caja_transmision" id="rf_aceites_caja_transmision_malo" value="MALO" <?php echo (($rf['aceites_caja_transmision'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="rf_aceites_caja_transmision_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Combustible del Sistema</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rf_combustible_sistema" id="rf_combustible_sistema_bueno" value="BUENO" <?php echo (($rf['combustible_sistema'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="rf_combustible_sistema_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rf_combustible_sistema" id="rf_combustible_sistema_malo" value="MALO" <?php echo (($rf['combustible_sistema'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="rf_combustible_sistema_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Líquido de Freno</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rf_liquido_freno" id="rf_liquido_freno_bueno" value="BUENO" <?php echo (($rf['liquido_freno'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="rf_liquido_freno_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rf_liquido_freno" id="rf_liquido_freno_malo" value="MALO" <?php echo (($rf['liquido_freno'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="rf_liquido_freno_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Refrigerante</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rf_refrigerante" id="rf_refrigerante_bueno" value="BUENO" <?php echo (($rf['refrigerante'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="rf_refrigerante_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rf_refrigerante" id="rf_refrigerante_malo" value="MALO" <?php echo (($rf['refrigerante'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="rf_refrigerante_malo">MALO</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a class="btn btn-secondary" href="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=9">Anterior</a>
                            <button type="submit" class="btn btn-primary">Siguiente</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif ($step === 11): ?>
            <div class="card">
                <div class="card-header">Paso 11: Revisión Eléctrica</div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php $re = $_SESSION['alistamiento_wizard'][$vehiculoId]['revision_electrica'] ?? []; ?>
                    <p class="mb-3"><strong>Nota:</strong> Verifique luces e indicadores. Responda <strong>BUENO</strong> o <strong>MALO</strong> para cada ítem.</p>
            <form method="post" action="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=11">
                        <input type="hidden" name="current_step" value="11" />

                        <div class="mb-3">
                            <label class="form-label">Luces Delanteras Altas (operativas - cambios intensidad)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="re_luces_altas" id="re_luces_altas_bueno" value="BUENO" <?php echo (($re['luces_altas'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="re_luces_altas_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="re_luces_altas" id="re_luces_altas_malo" value="MALO" <?php echo (($re['luces_altas'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="re_luces_altas_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Luces Delanteras Bajas (operativas - cambios intensidad)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="re_luces_bajas" id="re_luces_bajas_bueno" value="BUENO" <?php echo (($re['luces_bajas'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="re_luces_bajas_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="re_luces_bajas" id="re_luces_bajas_malo" value="MALO" <?php echo (($re['luces_bajas'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="re_luces_bajas_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Luces Direccionales (luz amarilla no intermitente)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="re_direccionales" id="re_direccionales_bueno" value="BUENO" <?php echo (($re['direccionales'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="re_direccionales_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="re_direccionales" id="re_direccionales_malo" value="MALO" <?php echo (($re['direccionales'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="re_direccionales_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Luces de Stop y Frenos (funcional; asegurada luz roja solo en parte trasera y no intermitente)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="re_stop_frenos" id="re_stop_frenos_bueno" value="BUENO" <?php echo (($re['stop_frenos'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="re_stop_frenos_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="re_stop_frenos" id="re_stop_frenos_malo" value="MALO" <?php echo (($re['stop_frenos'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="re_stop_frenos_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Luces de Posición Laterales (delimitadoras; no pueden ser blancas; no están permitidas)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="re_posicion_laterales" id="re_posicion_laterales_bueno" value="BUENO" <?php echo (($re['posicion_laterales'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="re_posicion_laterales_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="re_posicion_laterales" id="re_posicion_laterales_malo" value="MALO" <?php echo (($re['posicion_laterales'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="re_posicion_laterales_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Luz de Reversa (iluminación luz blanca y pito reversa)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="re_reversa" id="re_reversa_bueno" value="BUENO" <?php echo (($re['reversa'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="re_reversa_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="re_reversa" id="re_reversa_malo" value="MALO" <?php echo (($re['reversa'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="re_reversa_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Luces de Parqueo (luz amarilla no intermitente)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="re_parqueo" id="re_parqueo_bueno" value="BUENO" <?php echo (($re['parqueo'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="re_parqueo_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="re_parqueo" id="re_parqueo_malo" value="MALO" <?php echo (($re['parqueo'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="re_parqueo_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Luces Cabina (no oscuras; deben ser blancas)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="re_luces_cabina" id="re_luces_cabina_bueno" value="BUENO" <?php echo (($re['luces_cabina'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="re_luces_cabina_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="re_luces_cabina" id="re_luces_cabina_malo" value="MALO" <?php echo (($re['luces_cabina'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="re_luces_cabina_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Luces Tablero (testigos indicadores)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="re_luces_tablero" id="re_luces_tablero_bueno" value="BUENO" <?php echo (($re['luces_tablero'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="re_luces_tablero_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="re_luces_tablero" id="re_luces_tablero_malo" value="MALO" <?php echo (($re['luces_tablero'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="re_luces_tablero_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Pito (funcional en cualquier posición de la cabrilla)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="re_pito" id="re_pito_bueno" value="BUENO" <?php echo (($re['pito'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="re_pito_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="re_pito" id="re_pito_malo" value="MALO" <?php echo (($re['pito'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="re_pito_malo">MALO</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a class="btn btn-secondary" href="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=10">Anterior</a>
                            <button type="submit" class="btn btn-primary">Siguiente</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif ($step === 12): ?>
            <div class="card">
                <div class="card-header">Paso 12: Revisión de Llantas</div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php $rl = $_SESSION['alistamiento_wizard'][$vehiculoId]['revision_llantas'] ?? []; ?>
                    <p class="mb-3"><strong>Nota:</strong> Marca de desgaste ≥ 2 mm, presión adecuada, libre de deformidades y protuberancias; rines sin deformidades y asegurados. Responda <strong>BUENO</strong> o <strong>MALO</strong> para cada ítem.</p>
            <form method="post" action="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=12">
                        <input type="hidden" name="current_step" value="12" />

                        <div class="mb-3">
                            <label class="form-label">Salpicaderas (completas dos (2) en buen estado)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rl_salpicaderas" id="rl_salpicaderas_bueno" value="BUENO" <?php echo (($rl['salpicaderas'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="rl_salpicaderas_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rl_salpicaderas" id="rl_salpicaderas_malo" value="MALO" <?php echo (($rl['salpicaderas'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="rl_salpicaderas_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Llanta Delantera Izquierda*</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rl_llanta_delantera_izquierda" id="rl_llanta_delantera_izquierda_bueno" value="BUENO" <?php echo (($rl['llanta_delantera_izquierda'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="rl_llanta_delantera_izquierda_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rl_llanta_delantera_izquierda" id="rl_llanta_delantera_izquierda_malo" value="MALO" <?php echo (($rl['llanta_delantera_izquierda'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="rl_llanta_delantera_izquierda_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Llanta Delantera Derecha*</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rl_llanta_delantera_derecha" id="rl_llanta_delantera_derecha_bueno" value="BUENO" <?php echo (($rl['llanta_delantera_derecha'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="rl_llanta_delantera_derecha_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rl_llanta_delantera_derecha" id="rl_llanta_delantera_derecha_malo" value="MALO" <?php echo (($rl['llanta_delantera_derecha'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="rl_llanta_delantera_derecha_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Llanta Trasera Interior Izquierda*</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rl_llanta_trasera_interior_izquierda" id="rl_llanta_trasera_interior_izquierda_bueno" value="BUENO" <?php echo (($rl['llanta_trasera_interior_izquierda'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="rl_llanta_trasera_interior_izquierda_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rl_llanta_trasera_interior_izquierda" id="rl_llanta_trasera_interior_izquierda_malo" value="MALO" <?php echo (($rl['llanta_trasera_interior_izquierda'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="rl_llanta_trasera_interior_izquierda_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Llanta Trasera Externa Izquierda*</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rl_llanta_trasera_externa_izquierda" id="rl_llanta_trasera_externa_izquierda_bueno" value="BUENO" <?php echo (($rl['llanta_trasera_externa_izquierda'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="rl_llanta_trasera_externa_izquierda_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rl_llanta_trasera_externa_izquierda" id="rl_llanta_trasera_externa_izquierda_malo" value="MALO" <?php echo (($rl['llanta_trasera_externa_izquierda'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="rl_llanta_trasera_externa_izquierda_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Llanta Trasera Interior Derecha*</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rl_llanta_trasera_interior_derecha" id="rl_llanta_trasera_interior_derecha_bueno" value="BUENO" <?php echo (($rl['llanta_trasera_interior_derecha'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="rl_llanta_trasera_interior_derecha_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rl_llanta_trasera_interior_derecha" id="rl_llanta_trasera_interior_derecha_malo" value="MALO" <?php echo (($rl['llanta_trasera_interior_derecha'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="rl_llanta_trasera_interior_derecha_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Llanta Trasera Externa Derecha*</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rl_llanta_trasera_externa_derecha" id="rl_llanta_trasera_externa_derecha_bueno" value="BUENO" <?php echo (($rl['llanta_trasera_externa_derecha'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="rl_llanta_trasera_externa_derecha_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rl_llanta_trasera_externa_derecha" id="rl_llanta_trasera_externa_derecha_malo" value="MALO" <?php echo (($rl['llanta_trasera_externa_derecha'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="rl_llanta_trasera_externa_derecha_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Llanta de Repuesto</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rl_llanta_repuesto" id="rl_llanta_repuesto_bueno" value="BUENO" <?php echo (($rl['llanta_repuesto'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="rl_llanta_repuesto_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rl_llanta_repuesto" id="rl_llanta_repuesto_malo" value="MALO" <?php echo (($rl['llanta_repuesto'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="rl_llanta_repuesto_malo">MALO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Pernos (asegurados)</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rl_pernos" id="rl_pernos_bueno" value="BUENO" <?php echo (($rl['pernos'] ?? '') === 'BUENO') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="rl_pernos_bueno">BUENO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rl_pernos" id="rl_pernos_malo" value="MALO" <?php echo (($rl['pernos'] ?? '') === 'MALO') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="rl_pernos_malo">MALO</label>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h5 class="mb-2">OBSERVACIONES</h5>
                        <ol class="mb-3">
                            <li>EL INCUMPLIMIENTO DE CUALQUIERA DE LOS ÍTEMS MARCADOS CON ASTERISCO (*) DA ORIGEN A LA SUSPENSIÓN DE LA OPERACIÓN DEL VEHÍCULO HASTA TANTO NO SE PRACTIQUEN LOS CORRECTIVOS NECESARIOS.</li>
                            <li>EL INCUMPLIMIENTO DE ALGUNO (S) DE LOS OTROS ÍTEMS DARÁ PIE A CORREGIR LA FALLA EN EL MENOR TIEMPO ACORDADO ENTRE EL PROPIETARIO Y LA EMPRESA.</li>
                        </ol>

                        <h5 class="mb-2">Política de protección de datos personales</h5>
                        <p class="small text-muted">La información consignada en este formulario es propiedad de COTRAUTOL, es de uso exclusivo de su destinatario intencional y puede contener información de carácter privado o confidencial. Cualquier revisión, retransmisión, divulgación, copia o uso indebido de este documento, está estrictamente prohibida y será sancionada legalmente. Las respuestas a este formulario, en donde se incluya información de tipo personal propia o de terceros, se entiende como su aceptación inequívoca al eventual tratamiento o uso de los mismos, de acuerdo con las normas vigentes sobre tratamiento de datos personales por parte de COTRAUTOL, como responsable o encargado del tratamiento de datos personales; conforme con las finalidades contenidas en la Política de Protección de Datos Personales publicada en <a href="http://www.cotrautol.com" target="_blank" rel="noopener">www.cotrautol.com</a>. Si requiere información relativa al tratamiento de los datos personales podrá dirigirse al siguiente canal de atención: <a href="mailto:protecciondatos@cotrautol.com">protecciondatos@cotrautol.com</a>.</p>

                        <div class="d-flex justify-content-between">
                            <a class="btn btn-secondary" href="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=11">Anterior</a>
                            <button type="submit" class="btn btn-primary">Finalizar</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">Paso <?php echo $step; ?>: Próximamente</div>
                <div class="card-body">
                    <p>Este paso aún no está definido. Envíame la siguiente categoría e ítems.</p>
                    <div class="d-flex justify-content-between">
                        <a class="btn btn-secondary" href="<?php echo $URL_WIZARD; ?>?vehiculo=<?php echo $vehiculoId; ?>&step=<?php echo max(1, $step - 1); ?>">Anterior</a>
                        <button type="button" class="btn btn-primary" disabled>Siguiente</button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
    // Forzar enctype multipart para permitir fotos
    document.querySelectorAll('form[action*="alistamiento_wizard.php"]').forEach(f => {
        f.setAttribute('enctype', 'multipart/form-data');
    });
    // Al seleccionar MALO, abrir cámara automáticamente y adjuntar foto
    document.querySelectorAll('input[type="radio"][value="MALO"]').forEach(r => {
        r.addEventListener('change', () => {
            if (!r.checked) return;
            const name = r.name;
            const form = r.closest('form');
            if (!form) return;
            let file = form.querySelector('input[type="file"][name="photo_' + name + '"]');
            if (!file) {
                file = document.createElement('input');
                file.type = 'file';
                file.name = 'photo_' + name;
                file.accept = 'image/*';
                file.capture = 'environment';
                file.style.display = 'none';
                form.appendChild(file);
                file.addEventListener('change', () => {
                    if (file.files && file.files[0]) {
                        const preview = document.createElement('img');
                        preview.src = URL.createObjectURL(file.files[0]);
                        preview.alt = 'Evidencia';
                        preview.style.maxWidth = '120px';
                        preview.className = 'mt-2 border';
                        const container = r.closest('.mb-3') || r.parentElement;
                        container.appendChild(preview);
                    }
                });
            }
            // Abrir cámara/selector de archivos
            file.click();
        });
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
