# JewelFAQ — Guía de uso para Colin

Esta guía explica cómo funciona el sistema completo desde tu perspectiva: qué tienes que configurar, cómo llegan los casos, cómo los gestionas y cómo cobras.

---

## Visión general del flujo

```
Cliente llena formulario
        ↓
Recibes email de aviso
        ↓
Entras al admin panel → Aceptas o Rechazas
        ↓ (si aceptas)
El sistema genera un link de pago de Stripe (£50)
        ↓
Envías el link al cliente por WhatsApp (1 click)
        ↓
Cliente paga con tarjeta
        ↓
El caso aparece en "Paid — Needs Response"
        ↓
Escribes tu respuesta técnica en el admin panel
        ↓
Envías link de respuesta al cliente por WhatsApp (1 click)
        ↓
Cliente ve tu respuesta en su página privada
```

---

## La base de datos — no tienes que hacer nada

La base de datos es **SQLite** — un fichero local que se crea automáticamente la primera vez que alguien visita la web. No necesitas instalar MySQL, PostgreSQL ni nada por el estilo.

El fichero se guarda en:
```
/jewelfaq/data/jewelfaq.db
```

Este fichero contiene toda la información de los casos:

| Campo | Qué guarda |
|---|---|
| `name` | Nombre del cliente |
| `phone` | WhatsApp del cliente |
| `email` | Email (si lo da) |
| `message` | El caso / pregunta |
| `form_type` | De qué página vino (individuals, jewellers-3d, students…) |
| `status` | Estado actual del caso (ver abajo) |
| `stripe_session_id` | ID del pago en Stripe |
| `payment_link` | URL del link de pago generado |
| `response` | Tu respuesta escrita |
| `refund_id` | ID del reembolso (si lo haces) |

### Estados de un caso

```
pending_review   → recién llegado, lo tienes que revisar
awaiting_payment → aceptado, esperando que el cliente pague
paid             → pagado, tienes que escribir la respuesta
answered         → respondido, completado
rejected         → rechazado por ti
refunded         → reembolsado
```

---

## Configuración inicial (una sola vez)

Antes de lanzar la web, tienes que editar el fichero `config.php` con tus datos reales.

### 1. Claves de Stripe

Entra en [dashboard.stripe.com/apikeys](https://dashboard.stripe.com/apikeys) y copia:
- **Publishable key** → empieza por `pk_live_...`
- **Secret key** → empieza por `sk_live_...`

> Para probar sin cobrar dinero real, usa las claves `pk_test_...` y `sk_test_...`

```php
define('STRIPE_SECRET_KEY', 'sk_live_TU_CLAVE_AQUI');
define('STRIPE_PUBLIC_KEY', 'pk_live_TU_CLAVE_AQUI');
```

### 2. Webhook de Stripe (para que los pagos queden registrados)

El webhook es lo que avisa a tu web cuando alguien paga. Sin esto, los pagos no se registran automáticamente.

**Pasos:**
1. Ve a [dashboard.stripe.com/webhooks](https://dashboard.stripe.com/webhooks)
2. Clic en **"Add endpoint"**
3. URL del endpoint: `https://jewelfaq.com/webhook.php`
4. Evento a escuchar: `checkout.session.completed`
5. Copia el **"Signing secret"** (empieza por `whsec_...`) y pégalo en config.php:

```php
define('STRIPE_WEBHOOK_SECRET', 'whsec_TU_WEBHOOK_SECRET');
```

### 3. URL de tu sitio

```php
define('SITE_URL', 'https://jewelfaq.com');
```

### 4. Tu email para notificaciones

```php
define('CONTACT_EMAIL', 'tu@email.com');
```

Cuando llegue un caso nuevo, recibirás un email automático con los detalles.

### 5. Contraseña del panel de administración

Elige una contraseña y genera el hash con este comando (en el servidor o en local con PHP instalado):

```bash
php -r "echo password_hash('TU_CONTRASEÑA', PASSWORD_DEFAULT);"
```

Copia el resultado (un texto largo que empieza por `$2y$`) y ponlo en config.php:

```php
define('ADMIN_PASSWORD_HASH', '$2y$10$el_hash_que_copiaste_aqui');
```

---

## El panel de administración

Accede en: `https://jewelfaq.com/admin.php`

Introduce tu contraseña. Verás 5 pestañas:

---

### Pestaña 1 — Pending Review (casos nuevos)

Aquí aparecen los casos que acaban de llegar. Para cada uno ves:
- Nombre del cliente
- Su número de WhatsApp
- De qué tipo de servicio es (individuals, jewellers, students…)
- El texto del caso

**Dos botones:**

| Botón | Qué hace |
|---|---|
| ✓ Accept — generate payment link | Acepta el caso y genera un link de pago Stripe de £50 |
| ✗ Reject | Rechaza el caso (pasa a la pestaña Rejected) |

---

### Pestaña 2 — Awaiting Payment

Aquí están los casos que aceptaste y están esperando pago. Verás:
- El link de pago de Stripe (£50)
- Un botón verde **"Send payment link via WhatsApp"** — al hacer clic, se abre WhatsApp con un mensaje pre-escrito con el link. Solo tienes que pulsar enviar.

---

### Pestaña 3 — Paid — Needs Response

Aquí aparecen los casos que ya han pagado. **Esto es automático** — Stripe avisa a tu web en cuanto se completa el pago.

Para cada caso:
1. Lees la pregunta del cliente
2. Escribes tu respuesta técnica en el cuadro de texto
3. Pulsas **"Save response"**

También tienes un botón **"Send view link via WhatsApp"** — se abre WhatsApp con un mensaje con el link privado donde el cliente verá tu respuesta.

Y si necesitas reembolsar: botón rojo **"Issue Refund"** — hace el reembolso directo en Stripe.

---

### Pestaña 4 — Answered

Historial de todos los casos respondidos. Están colapsados, haz clic para expandir y ver el caso y tu respuesta.

---

### Pestaña 5 — Rejected

Historial de casos que rechazaste.

---

## Qué ve el cliente

1. **Envía el formulario** → ve una página de agradecimiento
2. **Recibe tu link de pago** por WhatsApp → paga con tarjeta (Stripe)
3. **Recibe tu link de respuesta** por WhatsApp → accede a una página privada con tu respuesta

El link de respuesta es único y seguro — nadie más puede verlo.

---

## Estructura de ficheros (para referencia)

```
jewelfaq/
├── config.php          ← CONFIGURACIÓN (claves Stripe, email, contraseña)
├── db.php              ← Base de datos (se crea sola, no tocar)
├── admin.php           ← Tu panel de control
├── submit.php          ← Recibe los formularios de los clientes
├── webhook.php         ← Recibe confirmaciones de pago de Stripe
├── stripe_helper.php   ← Funciones para comunicarse con Stripe
├── view-response.php   ← Página donde el cliente ve tu respuesta
├── success.php         ← Página post-pago del cliente
├── cancel.php          ← Página si el cliente cancela el pago
├── data/
│   └── jewelfaq.db     ← La base de datos (se crea automáticamente)
├── index.html          ← Página principal
├── individuals.html    ← Página para particulares
├── jewellers-3d.html   ← Página para joyeros/3D
├── students.html       ← Página para estudiantes
├── pricing.html        ← Precios
├── about.html          ← Sobre ti
└── contact.html        ← Contacto
```

---

## Checklist de lanzamiento

- [ ] Subir todos los ficheros al servidor (hosting con PHP 8.1+)
- [ ] Editar `config.php` con tus claves reales de Stripe
- [ ] Generar el hash de tu contraseña y ponerlo en `config.php`
- [ ] Confirmar que la carpeta `data/` tiene permisos de escritura (`chmod 700 data/`)
- [ ] Configurar el webhook en el dashboard de Stripe
- [ ] Hacer una prueba con tarjeta de test de Stripe
- [ ] Cambiar a claves `live` de Stripe cuando estés listo

---

## Preguntas frecuentes

**¿Dónde se guarda la base de datos?**
En `data/jewelfaq.db`. Es un fichero SQLite normal. Puedes descargarlo para hacer copias de seguridad.

**¿Qué pasa si el cliente paga pero no aparece en "Paid"?**
Revisa que el webhook esté bien configurado en Stripe. El evento debe ser `checkout.session.completed` y la URL debe ser exactamente `https://jewelfaq.com/webhook.php`.

**¿Puedo cambiar el precio de £50?**
Sí, en `config.php`, cambia el valor `'amount' => 5000` (en peniques — 5000 = £50).

**¿Puedo ver la base de datos directamente?**
Puedes descargar `data/jewelfaq.db` y abrirlo con [DB Browser for SQLite](https://sqlitebrowser.org/) — es gratuito y visual.

**¿El cliente recibe emails?**
Actualmente no. La comunicación con el cliente es exclusivamente por WhatsApp (con los botones del admin panel). Tú sí recibes un email cuando llega un caso nuevo.
