# JewelFAQ — Plan de trabajo completo

Fecha: Marzo 2026
Rama de trabajo: `claude/add-payment-contact-restrictions-U0oMA`
Estado actual: web funcional con Stripe, 7 páginas HTML + PHP backend, diseño bento/Apple, URLs en inglés, email eliminado, sin banner.

---

## Contexto para retomar el chat

Si continúas en un chat nuevo, di esto al inicio:

> "Estoy trabajando en el repositorio jewelfaq en la rama claude/add-payment-contact-restrictions-U0oMA. Tenemos un plan detallado en PLAN.md en la raíz del repo. Lee ese archivo y continúa desde donde se paró."

---

## Estado del repositorio tras Fase 1 completa

### Archivos HTML (páginas públicas)
| Archivo | Estado |
|---|---|
| index.html | ✅ Traducido, sin banner, con form, placeholders, modales |
| individuals.html | ✅ Traducido (antes particulares.html) |
| jewellers-3d.html | ✅ Traducido (antes joyeros-3d.html) |
| students.html | ✅ Traducido (antes estudiantes.html) |
| about.html | ✅ Traducido (antes sobre-mi.html) |
| contact.html | ✅ Traducido (antes contacto.html) |
| pricing.html | ✅ Traducido (antes tarifas.html) |
| thank-you.html | ✅ Creado en Fase 2 |

### Archivos PHP (backend)
| Archivo | Función |
|---|---|
| checkout.php | Genera Stripe link — ahora llamado desde admin.php |
| submit.php | Recibe form gratuito → guarda DB → redirige a thank-you |
| config.php | Claves Stripe, tier £50 |
| db.php | SQLite + columna status (pending_review, awaiting_payment, paid, answered, rejected) |
| admin.php | Panel admin con tabs por status + Accept/Reject + WhatsApp link |
| stripe_helper.php | generate_payment_link() + funciones Stripe |
| webhook.php | Recibe Stripe event → actualiza status a 'paid' |
| success.php | Post-pago (antes pago-exito.php) |
| cancel.php | Cancelación (antes pago-cancel.php) |
| view-response.php | Cliente ve respuesta con token (antes ver-respuesta.php) |
| contact.php | Legacy — no se usa |

---

## Flujo de pago NUEVO (implementado en Fase 2)

```
Usuario rellena form en cualquier página — GRATIS, sin pago
    ↓
submit.php → guarda caso en DB con status: 'pending_review'
    ↓
thank-you.html → "Colin will review and contact you via WhatsApp"
    ↓
Colin ve caso en admin.php → sección "Pending Review"
    ↓
Colin decide:
    ├── [Accept] → sistema crea Stripe payment link
    │       → admin muestra URL + botón WhatsApp prellenado
    │       → caso pasa a status: 'awaiting_payment'
    │
    └── [Reject] → caso marcado 'rejected'
                 → Colin avisa manualmente por WhatsApp
    ↓
Usuario recibe link de pago por WhatsApp de Colin
    ↓
Usuario paga £50 → Stripe → webhook.php → status: 'paid'
    ↓
Colin ve caso en admin → sección "Paid — Awaiting Response"
    ↓
Colin escribe respuesta → status: 'answered'
    ↓
view-response.php — usuario accede con token único
```

---

## Preguntas pendientes / decisiones del propietario

- [ ] **Foto del hero**: ¿qué foto concreta va en el hero principal? (soldando, microscopio, manos en el taller...)
- [ ] **Nombre del Ltd**: ¿"JewelFAQ Ltd" o "The Jewellery Crafters Ltd"? Añadir a T&Cs cuando esté registrado
- [ ] **Email de notificación a Colin**: cuando llega caso nuevo, ¿quieres email o solo admin.php?
- [ ] **Botón Refund en admin**: ¿añadir botón para reembolsar casos ya pagados que Colin rechaza tarde?

---

## FASE 3 — Rediseño visual (sesión futura)

### Paleta nueva
```
--bg-dark:     #1A1A1A
--bg-cream:    #FAFAF5
--gold:        #C9A253
--gold-light:  rgba(201,162,83,0.12)
```

### Tipografía nueva
```
--font-display: 'Cormorant Garant', Georgia, serif  (titulares)
--font-body:    'Inter', sans-serif                 (sin cambio)
```

### Estructura homepage nueva
1. HERO oscuro — foto Colin a la derecha, titular impactante, 1 review visible
2. TRUST STRIP dorado — 30 years · Birmingham JQ · Diamond Setting School · YouTube
3. WHO IT'S FOR — 3 columnas con foto
4. HOW IT WORKS — 3 pasos enfatizando "FREE" en paso 1
5. ABOUT COLIN — 2 columnas foto+texto
6. TESTIMONIOS — fondo oscuro, 3 reviews Google
7. PRICING — £50 grande, packs debajo
8. FORMULARIO DIRECTO — submit.php
9. FAQ — acordeón
10. CTA FINAL — WhatsApp + formulario

*Documento actualizado: Marzo 2026*
