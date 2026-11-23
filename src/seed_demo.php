<?php
/**
 * Carga datos de demostración para poder probar el panel sin conectar Gmail ni Gemini.
 *
 * Uso (desde la raíz del proyecto):
 *   docker compose exec app php src/seed_demo.php
 *
 * Crea un agente de demo y varios tickets con su hilo de mensajes y el análisis IA
 * (categoría, sentimiento y respuesta sugerida) ya generado. Es idempotente: si los
 * datos de demo ya existen, no hace nada. Solo para entornos locales / de demostración.
 *
 * Nota: responder a un ticket de demo intentará enviar el correo por Gmail, por lo que
 * fallará si Gmail no está conectado. El resto del panel funciona sin configuración extra.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\Database;

Dotenv::createImmutable(__DIR__ . '/../')->safeLoad();

$demoEmail    = $_ENV['DEMO_EMAIL'] ?? 'demo@smartticketing.local';
$demoPassword = $_ENV['DEMO_PASSWORD'] ?? 'demo1234';

$tickets = [
    [
        'subject' => 'Cobro duplicado en la factura de octubre',
        'status'  => 'No leído',
        'hours'   => 2,
        'messages' => [
            [
                'from' => 'laura.martin@cliente-demo.com',
                'body' => "Hola,\n\nRevisando mi extracto he visto que la factura FAC-2026-1043 de octubre me la habéis cobrado dos veces (89,90 € cada cargo). ¿Podéis revisarlo y devolverme uno de los cargos?\n\nGracias,\nLaura Martín",
                'ai'   => ['Facturación', 'negativo', "Hola Laura,\n\nSentimos mucho las molestias. Hemos revisado tu cuenta y confirmamos el cargo duplicado de la factura FAC-2026-1043. Ya hemos solicitado la devolución de 89,90 €, que verás reflejada en tu cuenta en un plazo de 3 a 5 días hábiles.\n\nSi tienes cualquier otra duda, estamos a tu disposición.\n\nUn saludo,\nEquipo de Soporte"],
            ],
        ],
    ],
    [
        'subject' => 'El portátil no enciende después de la actualización',
        'status'  => 'En Proceso',
        'hours'   => 20,
        'messages' => [
            [
                'from' => 'carlos.ruiz@cliente-demo.com',
                'body' => "Buenas,\n\nAyer instalé la actualización de firmware que me recomendó el asistente y desde entonces el portátil (modelo ET-Book 14) no enciende. Solo parpadea el LED de carga.\n\n¿Qué puedo hacer?",
                'ai'   => ['Error Técnico', 'negativo', "Hola Carlos,\n\nGracias por avisarnos. Prueba por favor lo siguiente:\n\n1. Desconecta el cargador y mantén pulsado el botón de encendido durante 30 segundos.\n2. Vuelve a conectar el cargador y espera 10 minutos antes de encenderlo.\n\nSi sigue sin arrancar, te enviaremos una etiqueta de recogida gratuita para revisarlo en nuestro servicio técnico, ya que el equipo está en garantía.\n\nUn saludo,\nEquipo de Soporte"],
            ],
            [
                'from'      => $demoEmail,
                'direction' => 'outgoing',
                'body'      => "Hola Carlos,\n\nGracias por avisarnos. Prueba a mantener pulsado el botón de encendido 30 segundos sin el cargador y vuelve a intentarlo. Si no funciona, te enviamos una etiqueta de recogida.\n\nUn saludo,\nEquipo de Soporte",
            ],
            [
                'from' => 'carlos.ruiz@cliente-demo.com',
                'body' => "Lo he probado y sigue igual. ¿Me enviáis la etiqueta de recogida, por favor?",
                'ai'   => ['Error Técnico', 'neutral', "Hola Carlos,\n\nLamentamos que no se haya solucionado. Te acabamos de enviar por correo la etiqueta de recogida gratuita. El mensajero pasará en las próximas 48 horas y, una vez recibamos el equipo, el plazo de reparación habitual es de 5 a 7 días hábiles.\n\nUn saludo,\nEquipo de Soporte"],
            ],
        ],
    ],
    [
        'subject' => '¿Hacéis envíos a Canarias?',
        'status'  => 'No leído',
        'hours'   => 5,
        'messages' => [
            [
                'from' => 'marta.gil@cliente-demo.com',
                'body' => "Hola, quería comprar unos auriculares de vuestra web pero vivo en Tenerife. ¿Enviáis a Canarias y cuánto tarda?",
                'ai'   => ['Consulta General', 'neutral', "Hola Marta,\n\n¡Sí, enviamos a Canarias! El plazo de entrega habitual es de 4 a 6 días hábiles. Ten en cuenta que los envíos a Canarias pueden estar sujetos a aranceles (IGIC), que se gestionan en el momento de la entrega.\n\nUn saludo,\nEquipo de Soporte"],
            ],
        ],
    ],
    [
        'subject' => 'Sugerencia: modo oscuro en la app',
        'status'  => 'Resuelto',
        'hours'   => 72,
        'messages' => [
            [
                'from' => 'javier.lopez@cliente-demo.com',
                'body' => "Me encanta la app de gestión de dispositivos, la uso a diario. Solo echo en falta un modo oscuro para usarla por la noche. ¡Gracias por el buen trabajo!",
                'ai'   => ['Solicitud de Funcionalidad', 'positivo', "Hola Javier,\n\n¡Muchas gracias por tus palabras! Nos alegra mucho que la app te resulte útil. Hemos trasladado tu sugerencia del modo oscuro al equipo de producto para tenerla en cuenta en próximas versiones.\n\nUn saludo,\nEquipo de Soporte"],
            ],
        ],
    ],
    [
        'subject' => 'Pedido #58213 lleva 3 semanas sin llegar',
        'status'  => 'En Proceso',
        'hours'   => 30,
        'messages' => [
            [
                'from' => 'ana.torres@cliente-demo.com',
                'body' => "Es la tercera vez que escribo. Hice el pedido #58213 hace tres semanas y sigue sin llegar. Nadie me da una solución. Si no lo recibo esta semana quiero la devolución completa.",
                'ai'   => ['Queja', 'negativo', "Hola Ana,\n\nTe pedimos disculpas por la espera y por no haberte dado una respuesta antes; entendemos tu malestar. Hemos abierto una incidencia urgente con la empresa de transporte para el pedido #58213. Si no está entregado antes del viernes, procederemos a la devolución completa del importe sin que tengas que hacer nada más.\n\nTe mantendremos informada.\n\nUn saludo,\nEquipo de Soporte"],
            ],
        ],
    ],
];

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT COUNT(*) FROM tickets WHERE gmail_thread_id LIKE 'demo-%'");
    $stmt->execute();
    if ((int) $stmt->fetchColumn() > 0) {
        echo "Los datos de demo ya están cargados. No se ha modificado nada.\n";
        exit(0);
    }

    $db->beginTransaction();

    // Agente de demo
    $stmt = $db->prepare('SELECT id FROM users WHERE email = :email');
    $stmt->execute(['email' => $demoEmail]);
    $agentId = $stmt->fetchColumn();
    if (!$agentId) {
        $stmt = $db->prepare(
            "INSERT INTO users (name, email, password_hash, role) VALUES ('Agente Demo', :email, :hash, 'agent')"
        );
        $stmt->execute(['email' => $demoEmail, 'hash' => password_hash($demoPassword, PASSWORD_DEFAULT)]);
        $agentId = (int) $db->lastInsertId();
    }

    $insertTicket = $db->prepare(
        "INSERT INTO tickets (gmail_thread_id, subject, status, has_new_message, created_at, updated_at)
         VALUES (:thread, :subject, :status, :new, :created, :updated)"
    );
    $insertMessage = $db->prepare(
        "INSERT INTO messages (ticket_id, sender_id, sender_email, body_text, message_id, in_reply_to, direction, created_at)
         VALUES (:ticket, :sender_id, :sender, :body, :mid, :reply_to, :direction, :created)"
    );
    $insertAi = $db->prepare(
        "INSERT INTO ai_suggestions (message_id, suggested_category, suggested_sentiment, suggested_response, created_at)
         VALUES (:message, :category, :sentiment, :response, :created)"
    );

    foreach ($tickets as $i => $t) {
        $start   = time() - $t['hours'] * 3600;
        $count   = count($t['messages']);
        $updated = $start + ($count - 1) * 3 * 3600;

        $insertTicket->execute([
            'thread'  => 'demo-' . ($i + 1),
            'subject' => $t['subject'],
            'status'  => $t['status'],
            'new'     => $t['status'] === 'No leído' ? 1 : 0,
            'created' => date('Y-m-d H:i:s', $start),
            'updated' => date('Y-m-d H:i:s', $updated),
        ]);
        $ticketId = (int) $db->lastInsertId();

        $previousMid = null;
        foreach ($t['messages'] as $j => $m) {
            $outgoing = ($m['direction'] ?? 'incoming') === 'outgoing';
            $mid      = '<demo-' . ($i + 1) . '-' . ($j + 1) . '@smartticketing.local>';
            $created  = date('Y-m-d H:i:s', $start + $j * 3 * 3600);

            $insertMessage->execute([
                'ticket'    => $ticketId,
                'sender_id' => $outgoing ? $agentId : null,
                'sender'    => $m['from'],
                'body'      => $m['body'],
                'mid'       => $mid,
                'reply_to'  => $previousMid,
                'direction' => $outgoing ? 'outgoing' : 'incoming',
                'created'   => $created,
            ]);
            $messageId   = (int) $db->lastInsertId();
            $previousMid = $mid;

            if (!empty($m['ai'])) {
                [$category, $sentiment, $response] = $m['ai'];
                $insertAi->execute([
                    'message'   => $messageId,
                    'category'  => $category,
                    'sentiment' => $sentiment,
                    'response'  => $response,
                    'created'   => $created,
                ]);
            }
        }
    }

    $db->commit();

    echo "Datos de demo cargados: " . count($tickets) . " tickets.\n";
    echo "Accede en http://localhost:" . ($_ENV['APP_PORT'] ?? '8080') . " con {$demoEmail} / {$demoPassword}\n";
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, 'Error cargando los datos de demo: ' . $e->getMessage() . "\n");
    exit(1);
}
