<?php
// ============================================================
// CONFIGURACIÓN JEWELFAQ
// ============================================================
// Edita este archivo con tus claves reales antes de subir.
// NUNCA subas este archivo a un repositorio público.
// ============================================================

// 🔑 Stripe — copia tus claves desde https://dashboard.stripe.com/apikeys
// Usa las claves "test" para probar y "live" en producción.
define('STRIPE_SECRET_KEY', 'sk_test_PON_TU_CLAVE_SECRETA_AQUI');
define('STRIPE_PUBLIC_KEY', 'pk_test_PON_TU_CLAVE_PUBLICA_AQUI');

// 🔑 Stripe Webhook Secret
// Ve a Dashboard > Developers > Webhooks > Add endpoint
// URL del endpoint: https://TUDOMINIO.com/webhook.php
// Evento a escuchar: checkout.session.completed
// Copia el "Signing secret" aquí:
define('STRIPE_WEBHOOK_SECRET', 'whsec_PON_TU_WEBHOOK_SECRET_AQUI');

// 🌐 URL base de tu sitio (sin / al final)
define('SITE_URL', 'https://jewelfaq.com');

// 📧 Tu email para recibir notificaciones de pagos
define('CONTACT_EMAIL', 'ciufuliciboy2@gmail.com');

// 🔐 Contraseña del panel de administración (/admin.php)
// Cambia 'jewelfaq2026' por tu contraseña real y regenera el hash:
// php -r "echo password_hash('TU_CONTRASEÑA', PASSWORD_DEFAULT);"
define('ADMIN_PASSWORD_HASH', '$2y$10$placeholder_change_this_run_php_command_above');

// 🔒 Salt secreto para generar tokens de acceso (texto aleatorio largo)
define('SECRET_SALT', 'CambiaEstoConCualquierTextoAleatorio_JewelFAQ_2026_xK9mP');

// 💷 Niveles de consulta y precios (en peniques — £1.00 = 100)
define('CONSULTATION_TIERS', [
    'basica' => [
        'name'        => 'Consulta Básica',
        'amount'      => 2000, // £20.00
        'description' => '1 pieza, pregunta directa, análisis rápido. Respuesta en 24h.',
    ],
    'estandar' => [
        'name'        => 'Consulta Estándar',
        'amount'      => 4500, // £45.00
        'description' => 'Hasta 3 piezas o consulta con fotos y detalles técnicos. Respuesta en 24h.',
    ],
    'completa' => [
        'name'        => 'Consulta Completa',
        'amount'      => 8000, // £80.00
        'description' => 'Análisis técnico profundo, archivos 3D o múltiples piezas. Respuesta en 24h.',
    ],
]);
