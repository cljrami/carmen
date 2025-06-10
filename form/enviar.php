<?php
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Configuración de correo
        $to = "cljrami@gmail.com";
        $from = "noreply@jrami.cl";
        $subject = "Nuevo Formulario de Evaluación Personal";

        // Validación de campos requeridos
        $camposRequeridos = [
            'nombre' => '/^[A-Za-záéíóúñÁÉÍÓÚÑ\s]{2,50}$/u',
            'apellido' => '/^[A-Za-záéíóúñÁÉÍÓÚÑ\s]{2,50}$/u',
            'telefono' => '/^[0-9+]{8,15}$/',
            'email' => FILTER_VALIDATE_EMAIL,
            'fechaNacimiento' => '/^\d{4}-\d{2}-\d{2}$/'
        ];

        $errores = [];
        $datosValidados = [];

        foreach ($camposRequeridos as $campo => $validacion) {
            if (!isset($_POST[$campo]) || empty(trim($_POST[$campo]))) {
                $errores[] = "El campo $campo es obligatorio";
                continue;
            }
            $valor = trim($_POST[$campo]);
            if ($validacion === FILTER_VALIDATE_EMAIL) {
                if (!filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                    $errores[] = "El email no es válido";
                } else {
                    $datosValidados[$campo] = filter_var($valor, FILTER_SANITIZE_EMAIL);
                }
            } else {
                if (!preg_match($validacion, $valor)) {
                    $errores[] = "El formato del campo $campo no es válido";
                } else {
                    $datosValidados[$campo] = htmlspecialchars($valor);
                }
            }
        }

        // Procesar el resto de los campos (no requeridos estrictos)
        foreach ($_POST as $campo => $valor) {
            if (!isset($datosValidados[$campo])) {
                $datosValidados[$campo] = htmlspecialchars(trim($valor));
            }
        }

        if (!empty($errores)) {
            throw new Exception(implode(", ", $errores));
        }

        // Construir el mensaje HTML
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 800px; margin: 0 auto; padding: 20px;}
                .section { margin: 20px 0; padding: 20px; background: #f9f9f9; border-radius: 5px; border-left: 4px solid #2575fc;}
                h2 { color: #2575fc; margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 10px;}
                .field { margin: 10px 0; padding: 10px; background: white; border-radius: 3px;}
                .label { font-weight: bold; color: #666; display: inline-block; width: 200px;}
                .value { margin-left: 10px; color: #333;}
            </style>
        </head>
        <body>
            <h1 style='color: #6a11cb; text-align: center;'>Nuevo Formulario de Evaluación Personal</h1>";

        // Organizar las respuestas por secciones
        $sections = [
            'Información Personal' => [
                'nombre',
                'apellido',
                'telefono',
                'email',
                'fechaNacimiento',
                'pareja',
                'hijos'
            ],
            'Sistema Familiar' => [
                'relacion_madre',
                'relacion_padre',
                'separacion_familiar',
                'secretos_familiares',
                'ambiente_hogar',
                'rechazos_familia',
                'virtudes_familia'
            ],
            'Dinámicas y Patrones Familiares' => [
                'patrones_repetitivos',
                'responsabilidad_familiar',
                'rol_familiar',
                'tendencia_relacional',
                'limites_personales'
            ],
            'Momento Actual y Emociones' => [
                'area_estancamiento',
                'dificultad_avance',
                'emocion_frecuente',
                'cargas_emocionales'
            ],
            'Relación con la Prosperidad y el Éxito' => [
                'relacion_dinero',
                'culpa_exito',
                'comparacion_exito',
                'autoboicot'
            ]
        ];

        foreach ($sections as $section => $fields) {
            $message .= "<div class='section'>";
            $message .= "<h2>$section</h2>";
            foreach ($fields as $field) {
                if (isset($datosValidados[$field]) && $datosValidados[$field] !== "") {
                    $value = nl2br($datosValidados[$field]);
                    $label = ucwords(str_replace('_', ' ', $field));
                    $message .= "
                    <div class='field'>
                        <span class='label'>$label:</span>
                        <span class='value'>$value</span>
                    </div>";
                }
            }
            $message .= "</div>";
        }

        $message .= "
            <div style='text-align: center; margin-top: 20px; color: #666; font-size: 12px;'>
                Este correo fue generado automáticamente. Por favor no responder.
            </div>
        </body>
        </html>";

        // Cabeceras del correo
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            "From: Formulario de Evaluación <$from>",
            "Reply-To: {$datosValidados['email']}",
            'X-Mailer: PHP/' . phpversion()
        ];

        // Enviar el correo
        if (mail($to, $subject, $message, implode("\r\n", $headers))) {
            echo json_encode([
                'success' => true,
                'message' => '¡Formulario enviado con éxito!'
            ]);
        } else {
            throw new Exception('Error al enviar el correo');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al procesar el formulario: ' . $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
