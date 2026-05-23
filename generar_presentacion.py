"""
Genera presentacion.pptx — Defensa de proyecto final de grado superior DAW
Proyecto: L&J CRM Web — Panel de control con cPanel integrado
Autor: Jaime
"""
from pptx import Presentation
from pptx.util import Inches, Pt, Emu
from pptx.dml.color import RGBColor
from pptx.enum.shapes import MSO_SHAPE
from pptx.enum.text import PP_ALIGN, MSO_ANCHOR

# ---------- Paleta de colores — idéntica a la aplicación L&J CRM ----------
COLOR_PRIMARY   = RGBColor(0x66, 0x7E, 0xEA)  # #667eea — gradiente inicio (purple-blue)
COLOR_SECONDARY = RGBColor(0x76, 0x4B, 0xA2)  # #764ba2 — gradiente fin (deep purple)
COLOR_ACCENT    = RGBColor(0x25, 0x63, 0xEB)  # #2563eb — azul brillante (links / CTA)
COLOR_DARK      = RGBColor(0x0F, 0x17, 0x2A)  # #0f172a — fondo oscuro máximo
COLOR_CARD_BG   = RGBColor(0x1E, 0x29, 0x3B)  # #1e293b — sidebar / tarjeta oscura
COLOR_HEADING   = RGBColor(0x1E, 0x29, 0x3B)  # mismo — texto encabezado sobre fondo claro
COLOR_LIGHT     = RGBColor(0xF1, 0xF5, 0xF9)  # #f1f5f9 — fondo claro de página
COLOR_WHITE     = RGBColor(0xFF, 0xFF, 0xFF)
COLOR_MUTED     = RGBColor(0x64, 0x74, 0x8B)  # #64748b — slate-500 texto secundario
COLOR_SUCCESS   = RGBColor(0x10, 0xB9, 0x81)  # #10b981
COLOR_WARNING   = RGBColor(0xF5, 0x9E, 0x0B)  # #f59e0b
COLOR_DANGER    = RGBColor(0xEF, 0x44, 0x44)  # #ef4444
COLOR_TINT      = RGBColor(0xDD, 0xD6, 0xFE)  # #ddd6fe — violet-200 (tinte claro texto subtítulos)

# ---------- Setup presentación 16:9 ----------
prs = Presentation()
prs.slide_width  = Inches(13.333)
prs.slide_height = Inches(7.5)

SW = prs.slide_width
SH = prs.slide_height

BLANK = prs.slide_layouts[6]  # blank layout — control total


# ---------- Helpers ----------
def add_rect(slide, x, y, w, h, fill=COLOR_PRIMARY, line=None):
    shape = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, x, y, w, h)
    shape.fill.solid()
    shape.fill.fore_color.rgb = fill
    if line is None:
        shape.line.fill.background()
    else:
        shape.line.color.rgb = line
    shape.shadow.inherit = False
    return shape


def add_text(slide, x, y, w, h, text, size=18, bold=False, color=COLOR_DARK,
             align=PP_ALIGN.LEFT, anchor=MSO_ANCHOR.TOP, font="Calibri"):
    tb = slide.shapes.add_textbox(x, y, w, h)
    tf = tb.text_frame
    tf.word_wrap = True
    tf.margin_left = Emu(0)
    tf.margin_right = Emu(0)
    tf.margin_top = Emu(0)
    tf.margin_bottom = Emu(0)
    tf.vertical_anchor = anchor

    lines = text.split("\n") if isinstance(text, str) else [text]
    for i, line in enumerate(lines):
        p = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        p.alignment = align
        run = p.add_run()
        run.text = line
        run.font.name = font
        run.font.size = Pt(size)
        run.font.bold = bold
        run.font.color.rgb = color
    return tb


def add_bullets(slide, x, y, w, h, items, size=18, color=COLOR_DARK,
                bullet_color=COLOR_ACCENT, spacing=6, font="Calibri"):
    """items: lista de strings o tuplas (bold_part, rest)."""
    tb = slide.shapes.add_textbox(x, y, w, h)
    tf = tb.text_frame
    tf.word_wrap = True
    tf.margin_left = Emu(0); tf.margin_right = Emu(0)
    tf.margin_top = Emu(0); tf.margin_bottom = Emu(0)

    for i, item in enumerate(items):
        p = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        p.alignment = PP_ALIGN.LEFT
        p.space_after = Pt(spacing)

        # bullet dot
        bullet = p.add_run()
        bullet.text = "▸  "
        bullet.font.name = font
        bullet.font.size = Pt(size)
        bullet.font.bold = True
        bullet.font.color.rgb = bullet_color

        if isinstance(item, tuple):
            head, rest = item
            r1 = p.add_run()
            r1.text = head
            r1.font.name = font
            r1.font.size = Pt(size)
            r1.font.bold = True
            r1.font.color.rgb = color
            r2 = p.add_run()
            r2.text = rest
            r2.font.name = font
            r2.font.size = Pt(size)
            r2.font.color.rgb = color
        else:
            r = p.add_run()
            r.text = item
            r.font.name = font
            r.font.size = Pt(size)
            r.font.color.rgb = color
    return tb


def slide_background(slide, color=COLOR_LIGHT):
    bg = add_rect(slide, 0, 0, SW, SH, fill=color)
    bg.shadow.inherit = False
    # send to back
    spTree = bg._element.getparent()
    spTree.remove(bg._element)
    spTree.insert(2, bg._element)


def header_bar(slide, title, subtitle=None, number=None, total=None):
    """Barra de cabecera consistente para slides de contenido."""
    # Barra superior fina
    add_rect(slide, 0, 0, SW, Inches(0.12), fill=COLOR_ACCENT)
    # Bloque de título
    add_rect(slide, 0, Inches(0.12), SW, Inches(0.95), fill=COLOR_PRIMARY)
    # Título
    add_text(slide, Inches(0.6), Inches(0.20), Inches(11), Inches(0.55),
             title, size=28, bold=True, color=COLOR_WHITE,
             anchor=MSO_ANCHOR.MIDDLE)
    if subtitle:
        add_text(slide, Inches(0.6), Inches(0.70), Inches(11), Inches(0.35),
                 subtitle, size=13, color=COLOR_TINT,
                 anchor=MSO_ANCHOR.MIDDLE)
    # Numeración slide
    if number is not None and total is not None:
        add_text(slide, Inches(12.0), Inches(0.20), Inches(1.2), Inches(0.6),
                 f"{number:02d} / {total:02d}", size=12, color=COLOR_WHITE,
                 align=PP_ALIGN.RIGHT, anchor=MSO_ANCHOR.MIDDLE)


def footer(slide, text="L&J CRM Web — Proyecto Final DAW — Jaime"):
    add_text(slide, Inches(0.6), Inches(7.1), Inches(12), Inches(0.3),
             text, size=10, color=COLOR_MUTED, align=PP_ALIGN.LEFT)


def card(slide, x, y, w, h, title, body, accent=COLOR_ACCENT,
         title_size=15, body_size=12):
    """Tarjeta con barra de color a la izquierda."""
    add_rect(slide, x, y, w, h, fill=COLOR_WHITE)
    add_rect(slide, x, y, Inches(0.08), h, fill=accent)
    add_text(slide, x + Inches(0.25), y + Inches(0.12), w - Inches(0.35),
             Inches(0.4), title, size=title_size, bold=True, color=COLOR_HEADING)
    add_text(slide, x + Inches(0.25), y + Inches(0.55), w - Inches(0.35),
             h - Inches(0.6), body, size=body_size, color=COLOR_DARK)


# ── Helpers para diagramas de flujo ──────────────────────────────────────────

def flow_node(slide, x, y, w, h, label, fill=None, text_color=COLOR_WHITE,
              size=11, rounded=False):
    """Nodo de diagrama de flujo (rectángulo con texto centrado)."""
    fill = fill or COLOR_PRIMARY
    if rounded:
        shape = slide.shapes.add_shape(
            MSO_SHAPE.ROUNDED_RECTANGLE, x, y, w, h)
        shape.adjustments[0] = 0.15
        shape.fill.solid()
        shape.fill.fore_color.rgb = fill
        shape.line.fill.background()
        shape.shadow.inherit = False
        tf = shape.text_frame
        tf.word_wrap = True
        tf.margin_left = Emu(0); tf.margin_right = Emu(0)
        tf.margin_top = Emu(0); tf.margin_bottom = Emu(0)
        tf.vertical_anchor = MSO_ANCHOR.MIDDLE
        p = tf.paragraphs[0]; p.alignment = PP_ALIGN.CENTER
        r = p.add_run(); r.text = label
        r.font.name = "Calibri"; r.font.size = Pt(size)
        r.font.bold = True; r.font.color.rgb = text_color
    else:
        add_rect(slide, x, y, w, h, fill=fill)
        add_text(slide, x, y, w, h, label, size=size, bold=True,
                 color=text_color, align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)


def flow_arrow_r(slide, x, y, h, color=None):
    """Flecha derecha (→) entre nodos horizontales."""
    color = color or COLOR_MUTED
    add_text(slide, x, y + (h - Inches(0.28)) / 2, Inches(0.28), Inches(0.28),
             "▶", size=13, color=color,
             align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)


def flow_arrow_d(slide, x, y, w, color=None):
    """Flecha abajo (↓) entre nodos verticales."""
    color = color or COLOR_MUTED
    add_text(slide, x + (w - Inches(0.28)) / 2, y, Inches(0.28), Inches(0.28),
             "▼", size=13, color=color,
             align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)


def flow_label(slide, x, y, w, h, text, color=None, size=9):
    """Etiqueta flotante sobre una flecha de flujo."""
    color = color or COLOR_MUTED
    add_text(slide, x, y, w, h, text, size=size, color=color,
             align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)


# ============================================================
# SLIDE 1 — PORTADA
# ============================================================
TOTAL = 25

def slide_portada():
    s = prs.slides.add_slide(BLANK)
    # Fondo con degradado simulado con rectángulos
    add_rect(s, 0, 0, SW, SH, fill=COLOR_DARK)
    add_rect(s, 0, 0, SW, Inches(4.5), fill=COLOR_PRIMARY)
    # Acento diagonal — barra lateral
    add_rect(s, 0, 0, Inches(0.25), SH, fill=COLOR_ACCENT)

    # Logo simbólico (cuadrado con iniciales)
    logo = s.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE,
                              Inches(0.9), Inches(0.8), Inches(1.1), Inches(1.1))
    logo.fill.solid(); logo.fill.fore_color.rgb = COLOR_ACCENT
    logo.line.fill.background()
    logo.adjustments[0] = 0.18
    tf = logo.text_frame
    tf.margin_left = Emu(0); tf.margin_right = Emu(0)
    tf.margin_top = Emu(0); tf.margin_bottom = Emu(0)
    tf.vertical_anchor = MSO_ANCHOR.MIDDLE
    p = tf.paragraphs[0]; p.alignment = PP_ALIGN.CENTER
    r = p.add_run(); r.text = "L&J"
    r.font.size = Pt(36); r.font.bold = True
    r.font.color.rgb = COLOR_WHITE; r.font.name = "Calibri"

    add_text(s, Inches(2.2), Inches(0.95), Inches(8), Inches(0.5),
             "PROYECTO FINAL DE GRADO SUPERIOR — DAW",
             size=14, bold=True, color=COLOR_TINT)

    add_text(s, Inches(2.2), Inches(1.40), Inches(10), Inches(0.6),
             "L&J CRM Web",
             size=44, bold=True, color=COLOR_WHITE)

    add_text(s, Inches(0.9), Inches(3.4), Inches(11), Inches(0.8),
             "Panel de control con CRM y cPanel integrado",
             size=28, bold=False, color=COLOR_WHITE)

    add_text(s, Inches(0.9), Inches(4.10), Inches(11), Inches(0.6),
             "PHP 8.3  ·  MySQL 8.4  ·  Nginx  ·  Docker  ·  JavaScript vanilla",
             size=16, color=RGBColor(0xC4, 0xB5, 0xFD))

    # Bloque inferior con autor / curso
    add_rect(s, 0, Inches(5.6), SW, Inches(1.9), fill=COLOR_DARK)
    add_text(s, Inches(0.9), Inches(5.85), Inches(6), Inches(0.4),
             "AUTOR", size=11, bold=True, color=COLOR_ACCENT)
    add_text(s, Inches(0.9), Inches(6.10), Inches(6), Inches(0.6),
             "Jaime", size=22, bold=True, color=COLOR_WHITE)

    add_text(s, Inches(0.9), Inches(6.65), Inches(6), Inches(0.4),
             "Desarrollo de Aplicaciones Web",
             size=13, color=COLOR_TINT)

    add_text(s, Inches(7.5), Inches(5.85), Inches(5), Inches(0.4),
             "CURSO", size=11, bold=True, color=COLOR_ACCENT,
             align=PP_ALIGN.RIGHT)
    add_text(s, Inches(7.5), Inches(6.10), Inches(5), Inches(0.5),
             "2025 / 2026", size=22, bold=True, color=COLOR_WHITE,
             align=PP_ALIGN.RIGHT)
    add_text(s, Inches(7.5), Inches(6.65), Inches(5), Inches(0.4),
             "Defensa final — 20 min",
             size=13, color=COLOR_TINT,
             align=PP_ALIGN.RIGHT)


# ============================================================
# SLIDE 2 — ÍNDICE
# ============================================================
def slide_indice():
    s = prs.slides.add_slide(BLANK)
    slide_background(s)
    header_bar(s, "Índice de la presentación",
               "Recorrido por las fases del proyecto",
               number=2, total=TOTAL)

    items_izq = [
        ("01.  ", "Contexto y motivación"),
        ("02.  ", "Objetivo y alcance del MVP"),
        ("03.  ", "Actores, roles y permisos"),
        ("04.  ", "Stack tecnológico"),
        ("05.  ", "Arquitectura general"),
        ("06.  ", "Modelo de datos"),
        ("07.  ", "Migraciones idempotentes"),
        ("08.  ", "Autenticación y RBAC"),
        ("09.  ", "Seguridad anti-CSRF"),
        ("10.  ", "Anti-XSS y CSP"),
    ]
    items_der = [
        ("11.  ", "Auditoría con diff"),
        ("12.  ", "CRM — Contactos y Leads"),
        ("13.  ", "Pipeline kanban + Drag & Drop"),
        ("14.  ", "Actividades — widget compartido"),
        ("15.  ", "cPanel — Hosting"),
        ("16.  ", "Administración del sistema"),
        ("17.  ", "UX/UI — Tema claro/oscuro"),
        ("18.  ", "Monitorización en tiempo real"),
        ("19.  ", "Despliegue Docker"),
        ("20.  ", "Conclusiones y mejoras"),
    ]

    # Cards de índice
    add_rect(s, Inches(0.6), Inches(1.4), Inches(6.0), Inches(5.4), fill=COLOR_WHITE)
    add_rect(s, Inches(6.7), Inches(1.4), Inches(6.0), Inches(5.4), fill=COLOR_WHITE)

    y = Inches(1.6)
    for num, text in items_izq:
        add_text(s, Inches(0.9), y, Inches(0.6), Inches(0.4),
                 num, size=14, bold=True, color=COLOR_ACCENT)
        add_text(s, Inches(1.5), y, Inches(5), Inches(0.4),
                 text, size=14, color=COLOR_DARK)
        y += Inches(0.50)

    y = Inches(1.6)
    for num, text in items_der:
        add_text(s, Inches(7.0), y, Inches(0.6), Inches(0.4),
                 num, size=14, bold=True, color=COLOR_ACCENT)
        add_text(s, Inches(7.6), y, Inches(5), Inches(0.4),
                 text, size=14, color=COLOR_DARK)
        y += Inches(0.50)

    footer(s)


# ============================================================
# SLIDE genérico content
# ============================================================
def content_slide(num, title, subtitle, render_body):
    s = prs.slides.add_slide(BLANK)
    slide_background(s)
    header_bar(s, title, subtitle, number=num, total=TOTAL)
    render_body(s)
    footer(s)
    return s


# ============================================================
# SLIDE 3 — CONTEXTO Y MOTIVACIÓN
# ============================================================
def slide_contexto(s):
    add_text(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.5),
             "¿Por qué este proyecto?",
             size=20, bold=True, color=COLOR_HEADING)

    items = [
        ("Las PYMES necesitan ", "una herramienta única para gestionar clientes, ventas e infraestructura web."),
        ("Los CRMs comerciales ", "(Salesforce, HubSpot) tienen un coste y curva de aprendizaje elevados."),
        ("Los paneles de hosting ", "(cPanel, Plesk) son cerrados, costosos y no se integran con el flujo comercial."),
        ("Mi propuesta: ", "una plataforma open-source, ligera y educativa que combina CRM + cPanel en un único panel."),
    ]
    add_bullets(s, Inches(0.8), Inches(2.0), Inches(12), Inches(2.2),
                items, size=16, spacing=10)

    # Bloque de objetivos académicos
    add_rect(s, Inches(0.6), Inches(4.5), Inches(12.1), Inches(2.2),
             fill=COLOR_WHITE)
    add_rect(s, Inches(0.6), Inches(4.5), Inches(0.12), Inches(2.2),
             fill=COLOR_SUCCESS)
    add_text(s, Inches(0.9), Inches(4.65), Inches(11), Inches(0.4),
             "OBJETIVOS ACADÉMICOS DEMOSTRADOS",
             size=12, bold=True, color=COLOR_SUCCESS)
    add_text(s, Inches(0.9), Inches(5.0), Inches(11), Inches(0.45),
             "Aplicar de forma integrada todos los módulos del ciclo DAW",
             size=18, bold=True, color=COLOR_HEADING)
    objs = [
        "Desarrollo Web en Entorno Servidor (PHP + MySQL + sesiones)",
        "Desarrollo Web en Entorno Cliente (HTML5, CSS3, JS vanilla, fetch API)",
        "Despliegue de aplicaciones (Docker, Nginx, PHP-FPM)",
        "Diseño de interfaces (responsive, accesibilidad, modo oscuro)",
    ]
    add_bullets(s, Inches(0.9), Inches(5.55), Inches(11.5), Inches(1.2),
                objs, size=13, spacing=2, bullet_color=COLOR_SUCCESS)


# ============================================================
# SLIDE 4 — OBJETIVO Y ALCANCE MVP
# ============================================================
def slide_alcance(s):
    add_text(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.5),
             "Producto Mínimo Viable (MVP) — Metodología MoSCoW",
             size=18, bold=True, color=COLOR_HEADING)

    # Tabla visual: dentro / fuera MVP
    add_rect(s, Inches(0.6), Inches(2.0), Inches(6.0), Inches(5.0), fill=COLOR_WHITE)
    add_rect(s, Inches(0.6), Inches(2.0), Inches(6.0), Inches(0.55), fill=COLOR_SUCCESS)
    add_text(s, Inches(0.7), Inches(2.05), Inches(5.8), Inches(0.45),
             "✓  DENTRO DEL MVP",
             size=15, bold=True, color=COLOR_WHITE,
             anchor=MSO_ANCHOR.MIDDLE)
    dentro = [
        "Autenticación con sesión y RBAC",
        "CRUD de contactos, leads, oportunidades",
        "Pipeline kanban con 5 etapas + drag & drop",
        "Actividades vinculadas a entidades",
        "Búsqueda y filtros server-side + paginación",
        "Gestión de dominios y cuentas de correo",
        "Auditoría completa con diff antes/después",
        "Backups SQL, monitorización, estadísticas",
        "Tema claro/oscuro WCAG AA",
    ]
    add_bullets(s, Inches(0.9), Inches(2.7), Inches(5.5), Inches(4.2),
                dentro, size=12, spacing=4, bullet_color=COLOR_SUCCESS)

    add_rect(s, Inches(6.8), Inches(2.0), Inches(6.0), Inches(5.0), fill=COLOR_WHITE)
    add_rect(s, Inches(6.8), Inches(2.0), Inches(6.0), Inches(0.55), fill=COLOR_DANGER)
    add_text(s, Inches(6.9), Inches(2.05), Inches(5.8), Inches(0.45),
             "✗  FUERA DEL MVP (post-entrega)",
             size=15, bold=True, color=COLOR_WHITE,
             anchor=MSO_ANCHOR.MIDDLE)
    fuera = [
        "Single Sign-On (SSO) y autenticación 2FA",
        "Recuperación de contraseña por email",
        "Importación/exportación masiva CSV",
        "Detección avanzada de duplicados",
        "Scoring automático de leads e integraciones",
        "Calendario sync y notificaciones push",
        "Búsqueda global tipo \"command palette\"",
        "Adjuntos (diseño documentado, no implementado)",
        "Alta disponibilidad / Kubernetes",
    ]
    add_bullets(s, Inches(7.1), Inches(2.7), Inches(5.5), Inches(4.2),
                fuera, size=12, spacing=4, bullet_color=COLOR_DANGER)


# ============================================================
# SLIDE 5 — ACTORES Y ROLES
# ============================================================
def slide_roles(s):
    add_text(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.5),
             "Actores, roles y matriz de permisos (RBAC)",
             size=18, bold=True, color=COLOR_HEADING)

    # Tres tarjetas de roles
    card(s, Inches(0.6), Inches(2.0), Inches(4.0), Inches(2.3),
         "🔒  No autenticado",
         "Solo accede a:\n• Landing pública\n• Pantalla de login\n\nNo puede invocar ningún endpoint del API.",
         accent=COLOR_MUTED, title_size=16, body_size=12)
    card(s, Inches(4.8), Inches(2.0), Inches(4.0), Inches(2.3),
         "👤  Usuario",
         "Accede al panel con permisos operativos:\n• CRUD CRM completo\n• Ver actividades propias\n• Configurar su cuenta",
         accent=COLOR_ACCENT, title_size=16, body_size=12)
    card(s, Inches(9.0), Inches(2.0), Inches(3.8), Inches(2.3),
         "🛡️  Administrador",
         "Permisos completos:\n• Todo lo de usuario\n• Gestión de usuarios\n• Auditoría / BBDD / Backups",
         accent=COLOR_DANGER, title_size=16, body_size=12)

    # Matriz simplificada
    add_text(s, Inches(0.6), Inches(4.55), Inches(12), Inches(0.4),
             "Matriz de permisos (alto nivel)",
             size=14, bold=True, color=COLOR_HEADING)

    rows = [
        ("Acción", "No auth", "Usuario", "Admin"),
        ("Landing pública / login", "✓", "—", "—"),
        ("Panel autenticado", "✗", "✓", "✓"),
        ("CRUD contactos / leads / oportunidades", "✗", "✓", "✓"),
        ("Gestión de usuarios", "✗", "✗", "✓"),
        ("Auditoría / Bases de datos / Backups", "✗", "✗", "✓"),
    ]
    y = Inches(5.05)
    col_x = [Inches(0.6), Inches(6.5), Inches(8.5), Inches(10.5)]
    col_w = [Inches(5.9), Inches(2.0), Inches(2.0), Inches(2.2)]
    for i, row in enumerate(rows):
        is_header = (i == 0)
        bg = COLOR_PRIMARY if is_header else (COLOR_WHITE if i % 2 == 1 else COLOR_LIGHT)
        for j, val in enumerate(row):
            add_rect(s, col_x[j], y, col_w[j], Inches(0.36), fill=bg)
            color = COLOR_WHITE if is_header else COLOR_DARK
            bold = is_header
            align = PP_ALIGN.LEFT if j == 0 else PP_ALIGN.CENTER
            add_text(s, col_x[j] + Inches(0.1), y + Inches(0.04),
                     col_w[j] - Inches(0.2), Inches(0.3),
                     val, size=11, bold=bold, color=color,
                     align=align, anchor=MSO_ANCHOR.MIDDLE)
        y += Inches(0.36)


# ============================================================
# SLIDE 6 — STACK TECNOLÓGICO
# ============================================================
def slide_stack(s):
    add_text(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.5),
             "Stack tecnológico — elecciones y justificación",
             size=18, bold=True, color=COLOR_HEADING)

    capas = [
        ("FRONTEND",
         "HTML5 · CSS3 · JavaScript vanilla",
         "Sin frameworks. Demuestra dominio del lenguaje base; módulos JS IIFE con guard de inicialización.",
         COLOR_ACCENT),
        ("BACKEND",
         "PHP 8.3-FPM · PDO con MySQL",
         "PHP moderno con tipado estricto y Argon2id. PDO con consultas preparadas frente a inyección SQL.",
         COLOR_PRIMARY),
        ("BASE DE DATOS",
         "MySQL 8.4",
         "Motor InnoDB con claves foráneas, integridad referencial, ENUMs y JSON para auditoría.",
         COLOR_SUCCESS),
        ("SERVIDOR WEB",
         "Nginx Alpine",
         "Servidor de alto rendimiento, configuración mínima, reverse proxy a PHP-FPM por socket UNIX.",
         COLOR_WARNING),
        ("DESPLIEGUE",
         "Docker Compose (6 servicios)",
         "Entorno reproducible: web · php · db · migrate · phpMyAdmin · cAdvisor (métricas).",
         COLOR_DANGER),
    ]
    y = Inches(1.95)
    for tag, tech, desc, c in capas:
        add_rect(s, Inches(0.6), y, Inches(12.1), Inches(1.0), fill=COLOR_WHITE)
        add_rect(s, Inches(0.6), y, Inches(2.0), Inches(1.0), fill=c)
        add_text(s, Inches(0.6), y, Inches(2.0), Inches(0.5),
                 tag, size=12, bold=True, color=COLOR_WHITE,
                 align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
        add_text(s, Inches(0.6), y + Inches(0.45), Inches(2.0), Inches(0.5),
                 "▎", size=12, color=COLOR_WHITE, align=PP_ALIGN.CENTER)
        add_text(s, Inches(2.8), y + Inches(0.08), Inches(9.8), Inches(0.4),
                 tech, size=15, bold=True, color=COLOR_HEADING)
        add_text(s, Inches(2.8), y + Inches(0.50), Inches(9.8), Inches(0.5),
                 desc, size=11, color=COLOR_MUTED)
        y += Inches(1.05)


# ============================================================
# SLIDE 7 — ARQUITECTURA GENERAL
# ============================================================
def slide_arquitectura(s):
    add_text(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.5),
             "Arquitectura de capas — orquestada con Docker Compose",
             size=18, bold=True, color=COLOR_HEADING)

    # Caja contenedor "Docker Compose"
    add_rect(s, Inches(0.6), Inches(2.0), Inches(12.1), Inches(4.8),
             fill=COLOR_WHITE)
    add_rect(s, Inches(0.6), Inches(2.0), Inches(12.1), Inches(0.4),
             fill=COLOR_DARK)
    add_text(s, Inches(0.6), Inches(2.0), Inches(12), Inches(0.4),
             "🐳   DOCKER COMPOSE — entorno reproducible",
             size=12, bold=True, color=COLOR_WHITE,
             align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)

    # Servicios como cajas
    servicios = [
        ("Cliente\n(navegador)", "fetch + cookies", Inches(0.85), Inches(2.7),
         Inches(2.0), Inches(1.3), COLOR_MUTED),
        ("nginx:alpine\nPuerto :91", "static + proxy", Inches(3.05), Inches(2.7),
         Inches(2.0), Inches(1.3), COLOR_WARNING),
        ("php:8.3-fpm\nFastCGI :9000", "lógica de negocio", Inches(5.25), Inches(2.7),
         Inches(2.0), Inches(1.3), COLOR_PRIMARY),
        ("mysql:8.4\nPuerto :3307", "datos persistentes", Inches(7.45), Inches(2.7),
         Inches(2.0), Inches(1.3), COLOR_SUCCESS),
        ("migrate\n(one-shot)", "esquema + seed", Inches(9.65), Inches(2.7),
         Inches(1.5), Inches(1.3), COLOR_ACCENT),
        ("phpMyAdmin\n:8082", "admin BD", Inches(11.3), Inches(2.7),
         Inches(1.3), Inches(1.3), COLOR_DANGER),
    ]
    for name, sub, x, y, w, h, color in servicios:
        add_rect(s, x, y, w, h, fill=color)
        add_text(s, x, y + Inches(0.15), w, Inches(0.7),
                 name, size=11, bold=True, color=COLOR_WHITE,
                 align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
        add_text(s, x, y + Inches(0.92), w, Inches(0.35),
                 sub, size=9, color=COLOR_WHITE,
                 align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)

    # Flechas (representadas con texto)
    arrows_y = Inches(3.35)
    arrow_positions = [Inches(2.85), Inches(5.05), Inches(7.25), Inches(9.45)]
    for x in arrow_positions:
        add_text(s, x, arrows_y, Inches(0.25), Inches(0.4),
                 "▶", size=18, bold=True, color=COLOR_DARK,
                 align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)

    # Capa inferior: volúmenes + red
    add_rect(s, Inches(0.85), Inches(4.4), Inches(11.6), Inches(0.9),
             fill=COLOR_LIGHT)
    add_text(s, Inches(0.95), Inches(4.5), Inches(11.4), Inches(0.4),
             "Volúmenes Docker (persistencia):",
             size=11, bold=True, color=COLOR_HEADING)
    add_text(s, Inches(0.95), Inches(4.85), Inches(11.4), Inches(0.4),
             "db_data (datos MySQL)   ·   ./backups (dumps SQL)   ·   ./public (hot-reload del código)",
             size=11, color=COLOR_DARK)

    # Capa de aplicación: estructura
    add_rect(s, Inches(0.85), Inches(5.45), Inches(11.6), Inches(1.2),
             fill=COLOR_LIGHT)
    add_text(s, Inches(0.95), Inches(5.55), Inches(11.4), Inches(0.4),
             "Estructura de la aplicación PHP:",
             size=11, bold=True, color=COLOR_HEADING)
    add_text(s, Inches(0.95), Inches(5.90), Inches(11.4), Inches(0.4),
             "public/api/ → 14 endpoints REST     ·     public/modules/dashboard/ → cpanel + partials",
             size=11, color=COLOR_DARK)
    add_text(s, Inches(0.95), Inches(6.25), Inches(11.4), Inches(0.4),
             "public/config/ → conexion.php · seguridad.php · auditoria.php (bloqueado por nginx)",
             size=11, color=COLOR_DARK)


# ============================================================
# SLIDE 8 — MODELO DE DATOS
# ============================================================
def slide_modelo(s):
    add_text(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.5),
             "Modelo de datos — 8 tablas con integridad referencial",
             size=18, bold=True, color=COLOR_HEADING)

    # Diagrama simplificado: tablas centrales + ramas
    # Tabla central: usuarios
    add_rect(s, Inches(5.7), Inches(2.0), Inches(2.0), Inches(0.7),
             fill=COLOR_PRIMARY)
    add_text(s, Inches(5.7), Inches(2.0), Inches(2.0), Inches(0.7),
             "usuarios", size=14, bold=True, color=COLOR_WHITE,
             align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)

    # CRM
    add_rect(s, Inches(2.0), Inches(3.3), Inches(2.0), Inches(0.6),
             fill=COLOR_ACCENT)
    add_text(s, Inches(2.0), Inches(3.3), Inches(2.0), Inches(0.6),
             "contactos", size=12, bold=True, color=COLOR_WHITE,
             align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)

    add_rect(s, Inches(4.3), Inches(3.3), Inches(2.0), Inches(0.6),
             fill=COLOR_ACCENT)
    add_text(s, Inches(4.3), Inches(3.3), Inches(2.0), Inches(0.6),
             "leads", size=12, bold=True, color=COLOR_WHITE,
             align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)

    add_rect(s, Inches(6.6), Inches(3.3), Inches(2.2), Inches(0.6),
             fill=COLOR_ACCENT)
    add_text(s, Inches(6.6), Inches(3.3), Inches(2.2), Inches(0.6),
             "oportunidades", size=12, bold=True, color=COLOR_WHITE,
             align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)

    add_rect(s, Inches(9.1), Inches(3.3), Inches(2.2), Inches(0.6),
             fill=COLOR_ACCENT)
    add_text(s, Inches(9.1), Inches(3.3), Inches(2.2), Inches(0.6),
             "actividades", size=12, bold=True, color=COLOR_WHITE,
             align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)

    # auditoría
    add_rect(s, Inches(0.6), Inches(2.0), Inches(2.0), Inches(0.7),
             fill=COLOR_DANGER)
    add_text(s, Inches(0.6), Inches(2.0), Inches(2.0), Inches(0.7),
             "auditoria", size=14, bold=True, color=COLOR_WHITE,
             align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)

    # Hosting
    add_rect(s, Inches(10.3), Inches(2.0), Inches(2.0), Inches(0.7),
             fill=COLOR_WARNING)
    add_text(s, Inches(10.3), Inches(2.0), Inches(2.0), Inches(0.7),
             "dominios", size=13, bold=True, color=COLOR_WHITE,
             align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
    add_rect(s, Inches(10.3), Inches(2.85), Inches(2.0), Inches(0.7),
             fill=COLOR_WARNING)
    add_text(s, Inches(10.3), Inches(2.85), Inches(2.0), Inches(0.7),
             "cuentas_correo", size=12, bold=True, color=COLOR_WHITE,
             align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)

    # Tabla descriptiva debajo
    add_text(s, Inches(0.6), Inches(4.3), Inches(12), Inches(0.4),
             "Reglas de integridad implementadas",
             size=14, bold=True, color=COLOR_HEADING)

    items = [
        ("Claves foráneas con ON DELETE: ",
         "CASCADE (actividades → contactos/leads/oportunidades), SET NULL (auditoría → usuarios)"),
        ("Restricciones de unicidad: ",
         "email único en usuarios, dominio + tipo único en dominios, email único en cuentas_correo"),
        ("Tipos ENUM controlados: ",
         "rol (usuario/admin), estado_lead, etapa_oportunidad, tipo_actividad, tipo_dominio, etc."),
        ("Auditoría JSON: ",
         "campos antes/después como JSON para diff visual en el panel admin"),
        ("Argon2id en usuarios.password_hash — ",
         "nunca contraseñas en texto plano (PHP password_hash con PASSWORD_ARGON2ID)"),
    ]
    add_bullets(s, Inches(0.7), Inches(4.7), Inches(12), Inches(2.5),
                items, size=12, spacing=5)


# ============================================================
# SLIDE 9 — MIGRACIONES IDEMPOTENTES
# ============================================================
def slide_migraciones(s):
    add_text(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.5),
             "Sistema de migraciones — esquema versionado e idempotente",
             size=18, bold=True, color=COLOR_HEADING)

    # Flow horizontal de migraciones
    pasos = [
        ("001_init.sql", "Esquema base\n6 tablas + FKs", COLOR_ACCENT),
        ("002_leads_FK", "Añade contacto_id\na leads", COLOR_ACCENT),
        ("003_seed_users", "Admin + 3 usuarios\n(Argon2id)", COLOR_PRIMARY),
        ("004_seed_crm", "Contactos, leads,\noportunidades", COLOR_PRIMARY),
        ("005_dominios", "Tabla hosting\ndominios", COLOR_WARNING),
        ("006_cuentas_correo", "Tabla hosting\ncuentas correo", COLOR_WARNING),
    ]
    x = Inches(0.6)
    for label, desc, color in pasos:
        add_rect(s, x, Inches(2.1), Inches(2.0), Inches(1.5), fill=color)
        add_text(s, x, Inches(2.2), Inches(2.0), Inches(0.45),
                 label, size=12, bold=True, color=COLOR_WHITE,
                 align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
        add_text(s, x, Inches(2.75), Inches(2.0), Inches(0.7),
                 desc, size=10, color=COLOR_WHITE,
                 align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
        x += Inches(2.1)

    # Flechas
    fx = Inches(2.55)
    for _ in range(5):
        add_text(s, fx, Inches(2.75), Inches(0.1), Inches(0.4),
                 "▶", size=12, color=COLOR_DARK,
                 align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
        fx += Inches(2.1)

    # Bloque "Cómo funciona"
    card(s, Inches(0.6), Inches(4.0), Inches(6.0), Inches(2.8),
         "🔧  Runner — database/migrate.php",
         "1. Crea tabla _migraciones (si no existe)\n"
         "2. Lee /database/migrations/ ordenado\n"
         "3. Para cada fichero: comprueba si ya se ejecutó\n"
         "4. Si no → BEGIN, ejecuta SQL o invoca PHP, INSERT en _migraciones, COMMIT\n"
         "5. Si error → ROLLBACK y aborta sin corromper estado",
         accent=COLOR_PRIMARY, title_size=14, body_size=12)

    card(s, Inches(6.8), Inches(4.0), Inches(6.0), Inches(2.8),
         "✓  Por qué es importante",
         "• IDEMPOTENTE: ejecutar dos veces no rompe nada\n"
         "• TRANSACCIONAL: cada migración es atómica\n"
         "• MIXTO: soporta .sql nativo y .php (Argon2id seed)\n"
         "• AUTO-EJECUCIÓN: corre al levantar docker compose\n"
         "• PROFESIONAL: misma estrategia que Flyway / Laravel",
         accent=COLOR_SUCCESS, title_size=14, body_size=12)


# ============================================================
# SLIDE 10 — AUTENTICACIÓN Y RBAC
# ============================================================
def slide_auth(s):
    add_text(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.5),
             "Autenticación segura y control de acceso por rol (RBAC)",
             size=18, bold=True, color=COLOR_HEADING)

    # Flujo de login
    add_text(s, Inches(0.6), Inches(2.0), Inches(12), Inches(0.4),
             "Flujo de inicio de sesión",
             size=14, bold=True, color=COLOR_HEADING)

    pasos = [
        ("1. Usuario", "envía\ncredenciales", COLOR_MUTED),
        ("2. login.php", "SELECT por\nusuario", COLOR_ACCENT),
        ("3. PHP", "password_verify\nArgon2id", COLOR_PRIMARY),
        ("4. session_start", "$_SESSION\n['id_usuario']", COLOR_SUCCESS),
        ("5. Redirect", "→ cpanel.php\ncon sesión", COLOR_DANGER),
    ]
    x = Inches(0.6)
    for title, desc, color in pasos:
        add_rect(s, x, Inches(2.5), Inches(2.4), Inches(1.0), fill=color)
        add_text(s, x, Inches(2.55), Inches(2.4), Inches(0.4),
                 title, size=12, bold=True, color=COLOR_WHITE,
                 align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
        add_text(s, x, Inches(3.0), Inches(2.4), Inches(0.5),
                 desc, size=10, color=COLOR_WHITE,
                 align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
        x += Inches(2.5)

    # RBAC
    add_text(s, Inches(0.6), Inches(4.0), Inches(12), Inches(0.4),
             "Control de acceso basado en roles (RBAC)",
             size=14, bold=True, color=COLOR_HEADING)

    card(s, Inches(0.6), Inches(4.5), Inches(6.0), Inches(2.3),
         "🛡️  Guard en cada endpoint",
         "Cada API verifica:\n"
         "1. session_start() activo\n"
         "2. $_SESSION['id_usuario'] presente\n"
         "3. Para admin-only: $_SESSION['rol'] === 'administrador'\n"
         "4. Si falla → 401 Unauthorized o 403 Forbidden",
         accent=COLOR_ACCENT, title_size=14, body_size=11)

    card(s, Inches(6.8), Inches(4.5), Inches(6.0), Inches(2.3),
         "🔒  Protecciones especiales (admin)",
         "Reglas en gestión de usuarios:\n"
         "• No auto-eliminación (un admin no se borra a sí mismo)\n"
         "• No eliminar al último administrador del sistema\n"
         "• No cambiar el propio rol (evita auto-degradación)\n"
         "• Argon2id en cada cambio de contraseña",
         accent=COLOR_DANGER, title_size=14, body_size=11)


# ============================================================
# SLIDE 11 — CSRF
# ============================================================
def slide_csrf(s):
    add_text(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.5),
             "Protección anti-CSRF — Synchronizer Token + Custom Header",
             size=18, bold=True, color=COLOR_HEADING)

    # Flujo en 4 pasos verticales
    add_text(s, Inches(0.6), Inches(2.0), Inches(7), Inches(0.4),
             "Flujo del token CSRF",
             size=14, bold=True, color=COLOR_HEADING)

    pasos = [
        ("1️⃣", "Servidor",
         "PHP genera bin2hex(random_bytes(32))\ny lo guarda en $_SESSION['csrf_token']"),
        ("2️⃣", "HTML",
         "<meta name=\"csrf-token\" content=\"abc123...\">\nincrustado en el <head>"),
        ("3️⃣", "Cliente (JS)",
         "fetchSeguro() lee el meta y añade automáticamente\nX-CSRF-Token en POST/PUT/DELETE/PATCH"),
        ("4️⃣", "Servidor (validación)",
         "csrfValidar() usa hash_equals() para comparar\n→ si no coincide: HTTP 403 Forbidden"),
    ]
    y = Inches(2.5)
    for emoji, lugar, desc in pasos:
        add_rect(s, Inches(0.6), y, Inches(7.0), Inches(0.9),
                 fill=COLOR_WHITE)
        add_rect(s, Inches(0.6), y, Inches(0.7), Inches(0.9),
                 fill=COLOR_PRIMARY)
        add_text(s, Inches(0.6), y, Inches(0.7), Inches(0.9),
                 emoji, size=20, color=COLOR_WHITE,
                 align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
        add_text(s, Inches(1.5), y + Inches(0.10), Inches(6), Inches(0.3),
                 lugar, size=11, bold=True, color=COLOR_ACCENT)
        add_text(s, Inches(1.5), y + Inches(0.40), Inches(6), Inches(0.5),
                 desc, size=11, color=COLOR_DARK)
        y += Inches(1.05)

    # Por qué dos capas
    card(s, Inches(8.0), Inches(2.5), Inches(4.8), Inches(2.2),
         "¿Por qué dos capas?",
         "El token solo no basta: si hay XSS, el atacante\n"
         "puede leerlo. La cabecera custom (X-CSRF-Token)\n"
         "exige CORS preflight, lo que un sitio malicioso\n"
         "no puede sortear sin permiso explícito del API.",
         accent=COLOR_DANGER, title_size=13, body_size=11)

    card(s, Inches(8.0), Inches(4.8), Inches(4.8), Inches(2.0),
         "Endpoints mutantes protegidos",
         "✓ contactos.php · leads.php\n"
         "✓ oportunidades.php · actividades.php\n"
         "✓ usuarios.php · dominios.php\n"
         "✓ cuentas_correo.php · configuracion.php\n"
         "✓ backups.php",
         accent=COLOR_SUCCESS, title_size=13, body_size=11)


# ============================================================
# SLIDE 12 — XSS y CSP
# ============================================================
def slide_xss(s):
    add_text(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.5),
             "Anti-XSS — Content Security Policy y escape de salida",
             size=18, bold=True, color=COLOR_HEADING)

    # CSP visual
    add_text(s, Inches(0.6), Inches(2.0), Inches(12), Inches(0.4),
             "Content-Security-Policy emitida en todas las respuestas",
             size=14, bold=True, color=COLOR_HEADING)

    csp = [
        ("default-src 'self'", "Solo recursos del mismo origen por defecto"),
        ("script-src 'self'", "Bloquea <script> inline y eval (← clave anti-XSS)"),
        ("style-src 'self' 'unsafe-inline' + Google Fonts", "Necesario para Font Awesome"),
        ("img-src 'self' data:", "Permite imágenes locales y data-URIs (iniciales)"),
        ("connect-src 'self'", "Bloquea fetch/XHR a dominios externos (anti-exfiltración)"),
        ("frame-ancestors 'none'", "Anti-clickjacking más fuerte que X-Frame-Options"),
    ]
    y = Inches(2.45)
    for dirv, desc in csp:
        add_rect(s, Inches(0.6), y, Inches(6.5), Inches(0.45),
                 fill=COLOR_WHITE)
        add_text(s, Inches(0.7), y, Inches(3.0), Inches(0.45),
                 dirv, size=11, bold=True, color=COLOR_DANGER,
                 font="Consolas", anchor=MSO_ANCHOR.MIDDLE)
        add_text(s, Inches(3.5), y, Inches(3.5), Inches(0.45),
                 desc, size=10, color=COLOR_DARK,
                 anchor=MSO_ANCHOR.MIDDLE)
        y += Inches(0.50)

    # Cabeceras adicionales
    card(s, Inches(7.3), Inches(2.45), Inches(5.4), Inches(2.0),
         "🛡️  Cabeceras HTTP complementarias",
         "X-Content-Type-Options: nosniff\n"
         "X-Frame-Options: DENY\n"
         "Referrer-Policy: strict-origin-when-cross-origin\n"
         "Permissions-Policy: geolocation=(), microphone=(),\n"
         "                    camera=()",
         accent=COLOR_PRIMARY, title_size=13, body_size=10)

    card(s, Inches(7.3), Inches(4.55), Inches(5.4), Inches(2.3),
         "✓  Escape de salida en 3 capas",
         "PHP → HTML:  htmlspecialchars(ENT_QUOTES)\n"
         "JS → DOM:    element.textContent = valor\n"
         "JS → innerHTML:  solo con función esc() previa\n\n"
         "Event delegation con data-* — sin handlers inline\n"
         "(condición sine qua non para script-src 'self')",
         accent=COLOR_SUCCESS, title_size=13, body_size=11)


# ============================================================
# SLIDE 13 — AUDITORÍA
# ============================================================
def slide_auditoria(s):
    add_text(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.5),
             "Auditoría — quién, qué y cuándo, con diff antes/después",
             size=18, bold=True, color=COLOR_HEADING)

    # Mockup visual de tabla auditoría
    add_text(s, Inches(0.6), Inches(2.0), Inches(12), Inches(0.4),
             "Estructura de un evento de auditoría",
             size=14, bold=True, color=COLOR_HEADING)

    add_rect(s, Inches(0.6), Inches(2.45), Inches(7.5), Inches(3.2),
             fill=COLOR_WHITE)
    add_rect(s, Inches(0.6), Inches(2.45), Inches(7.5), Inches(0.5),
             fill=COLOR_PRIMARY)
    add_text(s, Inches(0.7), Inches(2.45), Inches(7), Inches(0.5),
             "  Tabla auditoria (columnas)",
             size=12, bold=True, color=COLOR_WHITE,
             anchor=MSO_ANCHOR.MIDDLE)

    columnas = [
        ("id_auditoria", "INT AUTO_INCREMENT"),
        ("usuario_id", "FK → usuarios (SET NULL si se borra)"),
        ("tabla", "Entidad afectada (contactos, leads…)"),
        ("registro_id", "ID del registro modificado"),
        ("accion", "INSERT / UPDATE / DELETE / CONVERT"),
        ("datos_antes", "JSON con el estado previo"),
        ("datos_despues", "JSON con el estado nuevo"),
        ("created_at", "TIMESTAMP automático"),
    ]
    y = Inches(3.0)
    for col, desc in columnas:
        add_text(s, Inches(0.85), y, Inches(2.5), Inches(0.3),
                 col, size=10, bold=True, color=COLOR_ACCENT,
                 font="Consolas")
        add_text(s, Inches(3.35), y, Inches(4.5), Inches(0.3),
                 desc, size=10, color=COLOR_DARK)
        y += Inches(0.32)

    # Card derecha: lo que aporta
    card(s, Inches(8.3), Inches(2.45), Inches(4.4), Inches(1.6),
         "🔍  Diff expandible en el panel",
         "Cada fila se puede expandir y muestra el JSON\n"
         "antes/después con líneas resaltadas en verde\n"
         "(añadido) y rojo (eliminado).",
         accent=COLOR_ACCENT, title_size=12, body_size=10)

    card(s, Inches(8.3), Inches(4.20), Inches(4.4), Inches(1.45),
         "🛡️  Tolerancia a fallos",
         "registrarAuditoria() captura su propia\n"
         "PDOException — un fallo de log NUNCA rompe\n"
         "la operación principal del usuario.",
         accent=COLOR_SUCCESS, title_size=12, body_size=10)

    card(s, Inches(8.3), Inches(5.75), Inches(4.4), Inches(1.10),
         "📊  Filtros disponibles",
         "Por tabla (whitelist), acción, usuario,\n"
         "rango de fechas; paginación offset.",
         accent=COLOR_WARNING, title_size=12, body_size=10)

    # Eventos auditados (bullets bajo)
    add_text(s, Inches(0.6), Inches(5.85), Inches(7.5), Inches(0.35),
             "Eventos registrados: CRUD CRM, CRUD usuarios, conversiones lead→contacto, cambios de etapa en pipeline, login/logout (opcional).",
             size=10, color=COLOR_MUTED)


# ============================================================
# SLIDE 14 — CONTACTOS Y LEADS
# ============================================================
def slide_crm_contactos(s):
    add_text(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.5),
             "CRM — Contactos y Leads con conversión atómica",
             size=18, bold=True, color=COLOR_HEADING)

    # Dos columnas: Contactos / Leads
    card(s, Inches(0.6), Inches(2.0), Inches(6.0), Inches(3.0),
         "👥  Módulo Contactos",
         "• CRUD completo con validación cliente + servidor\n"
         "• Búsqueda debounced (400ms) server-side con LIKE\n"
         "• Filtros: empresa, fechas, ordenación asc/desc\n"
         "• Paginación server-side con LIMIT/OFFSET\n"
         "• Detalle lateral con widget de actividades\n"
         "• Validaciones específicas: email, teléfono español",
         accent=COLOR_ACCENT, title_size=15, body_size=12)

    card(s, Inches(6.8), Inches(2.0), Inches(6.0), Inches(3.0),
         "📩  Módulo Leads",
         "• CRUD con estados (nuevo, contactado, calificado…)\n"
         "• Filtros por estado, origen, rango de fechas\n"
         "• Badges semánticos según estado del lead\n"
         "• Panel de detalle lateral con actividades\n"
         "• Auditoría en crear, editar, eliminar y convertir\n"
         "• Conversión a contacto en transacción atómica",
         accent=COLOR_WARNING, title_size=15, body_size=12)

    # Bloque: Conversión atómica
    add_rect(s, Inches(0.6), Inches(5.2), Inches(12.1), Inches(1.7),
             fill=COLOR_WHITE)
    add_rect(s, Inches(0.6), Inches(5.2), Inches(0.12), Inches(1.7),
             fill=COLOR_SUCCESS)
    add_text(s, Inches(0.9), Inches(5.30), Inches(11.5), Inches(0.4),
             "⚛️  CONVERSIÓN LEAD → CONTACTO  —  transacción atómica",
             size=13, bold=True, color=COLOR_SUCCESS)
    add_text(s, Inches(0.9), Inches(5.65), Inches(11.5), Inches(0.4),
             "BEGIN  →  INSERT contacto  →  UPDATE lead (contacto_id, estado='convertido')  →  AUDIT ×2  →  COMMIT",
             size=12, bold=True, color=COLOR_HEADING, font="Consolas")
    add_text(s, Inches(0.9), Inches(6.05), Inches(11.5), Inches(0.4),
             "Si cualquier paso falla → ROLLBACK. Detecta email duplicado antes de empezar.",
             size=11, color=COLOR_DARK)
    add_text(s, Inches(0.9), Inches(6.40), Inches(11.5), Inches(0.4),
             "Botón de conversión se deshabilita en la UI si el lead ya está convertido (evita doble click).",
             size=11, color=COLOR_DARK)


# ============================================================
# SLIDE 15 — PIPELINE KANBAN
# ============================================================
def slide_kanban(s):
    add_text(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.5),
             "Pipeline kanban de oportunidades — Drag & Drop HTML5",
             size=18, bold=True, color=COLOR_HEADING)

    # Mockup visual del kanban: 5 columnas
    etapas = [
        ("Prospecto", COLOR_MUTED, ["Lead frío", "Demo solicitada"]),
        ("Propuesta", COLOR_ACCENT, ["Cliente A — €5K", "Cliente B — €2K"]),
        ("Negociación", COLOR_WARNING, ["Cliente C — €12K"]),
        ("Ganada", COLOR_SUCCESS, ["Cliente D — €8K", "Cliente E — €4K", "Cliente F — €1K"]),
        ("Perdida", COLOR_DANGER, ["Cliente G — €3K"]),
    ]

    col_w = Inches(2.4)
    col_h = Inches(3.0)
    x = Inches(0.6)
    for nombre, color, cards in etapas:
        # cabecera
        add_rect(s, x, Inches(2.0), col_w, Inches(0.45), fill=color)
        add_text(s, x, Inches(2.0), col_w, Inches(0.45),
                 nombre, size=12, bold=True, color=COLOR_WHITE,
                 align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
        # área droppable
        add_rect(s, x, Inches(2.45), col_w, col_h, fill=COLOR_WHITE)
        # cards
        cy = Inches(2.55)
        for c in cards:
            add_rect(s, x + Inches(0.1), cy, col_w - Inches(0.2), Inches(0.5),
                     fill=COLOR_LIGHT)
            add_rect(s, x + Inches(0.1), cy, Inches(0.05), Inches(0.5),
                     fill=color)
            add_text(s, x + Inches(0.2), cy, col_w - Inches(0.3), Inches(0.5),
                     c, size=10, color=COLOR_DARK, anchor=MSO_ANCHOR.MIDDLE)
            cy += Inches(0.6)
        x += Inches(2.5)

    # Features bajo el mockup
    add_text(s, Inches(0.6), Inches(5.6), Inches(12), Inches(0.4),
             "Funcionalidades del pipeline",
             size=14, bold=True, color=COLOR_HEADING)

    items = [
        ("Drag & drop HTML5: ",
         "eventos dragstart/dragend en cards, dragover/drop en columnas; mueve la oportunidad vía API sin modal"),
        ("Cambio de etapa con confirmación: ",
         "desde el panel de detalle lateral aparece modal info (azul) antes de aplicar"),
        ("Filtros avanzados + búsqueda debounced: ",
         "por valor, fecha de cierre estimada, ordenación; sin recargar la vista completa"),
        ("Auditoría detallada: ",
         "cada movimiento de etapa registra antes/después en la tabla auditoría con timestamp"),
    ]
    add_bullets(s, Inches(0.7), Inches(6.05), Inches(12), Inches(1.4),
                items, size=11, spacing=3)


# ============================================================
# SLIDE 16 — ACTIVIDADES
# ============================================================
def slide_actividades(s):
    add_text(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.5),
             "Actividades — widget reutilizable vinculado a 3 entidades",
             size=18, bold=True, color=COLOR_HEADING)

    # Esquema del widget
    add_rect(s, Inches(0.6), Inches(2.0), Inches(12.1), Inches(0.6),
             fill=COLOR_DARK)
    add_text(s, Inches(0.6), Inches(2.0), Inches(12), Inches(0.6),
             "ActividadesWidget.init({ prefix, entityType, entityId })",
             size=14, bold=True, color=COLOR_WHITE, font="Consolas",
             align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)

    # Tres entidades que usan el mismo widget
    add_rect(s, Inches(0.6), Inches(2.6), Inches(0.3), Inches(0.5),
             fill=COLOR_DARK)
    add_rect(s, Inches(4.85), Inches(2.6), Inches(0.3), Inches(0.5),
             fill=COLOR_DARK)
    add_rect(s, Inches(9.1), Inches(2.6), Inches(0.3), Inches(0.5),
             fill=COLOR_DARK)

    entidades = [
        ("Contactos", "contacto_id", COLOR_ACCENT, Inches(0.6)),
        ("Leads", "lead_id", COLOR_WARNING, Inches(4.6)),
        ("Oportunidades", "oportunidad_id", COLOR_SUCCESS, Inches(8.6)),
    ]
    for nombre, campo, color, x in entidades:
        add_rect(s, x, Inches(3.1), Inches(4.0), Inches(0.9), fill=color)
        add_text(s, x, Inches(3.1), Inches(4.0), Inches(0.45),
                 nombre, size=14, bold=True, color=COLOR_WHITE,
                 align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
        add_text(s, x, Inches(3.50), Inches(4.0), Inches(0.45),
                 f"FK actividades.{campo}", size=10, color=COLOR_WHITE,
                 font="Consolas", align=PP_ALIGN.CENTER,
                 anchor=MSO_ANCHOR.MIDDLE)

    # Tipos
    add_text(s, Inches(0.6), Inches(4.3), Inches(12), Inches(0.4),
             "5 tipos de actividad — cada uno con icono Font Awesome",
             size=14, bold=True, color=COLOR_HEADING)

    tipos = [
        ("📝 Nota", "Texto libre asociado a la entidad"),
        ("📞 Llamada", "Registro de contacto telefónico"),
        ("👥 Reunión", "Cita o videoconferencia"),
        ("✅ Tarea", "Acción pendiente con fecha objetivo"),
        ("📧 Email", "Comunicación por correo"),
    ]
    x = Inches(0.6)
    for emoji, desc in tipos:
        add_rect(s, x, Inches(4.8), Inches(2.4), Inches(1.6), fill=COLOR_WHITE)
        add_text(s, x, Inches(4.9), Inches(2.4), Inches(0.5),
                 emoji, size=22, bold=True, color=COLOR_HEADING,
                 align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
        add_text(s, x, Inches(5.55), Inches(2.4), Inches(0.85),
                 desc, size=10, color=COLOR_DARK,
                 align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
        x += Inches(2.5)

    # Detalle de implementación
    add_text(s, Inches(0.6), Inches(6.55), Inches(12), Inches(0.4),
             "Implementación: IIFE con guard de inicialización, formulario inline, listado ordenado por fecha, auditoría completa.",
             size=11, color=COLOR_MUTED)


# ============================================================
# SLIDE 17 — cPanel HOSTING
# ============================================================
def slide_cpanel(s):
    add_text(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.5),
             "Módulos cPanel — Hosting (más allá del CRM tradicional)",
             size=18, bold=True, color=COLOR_HEADING)

    # Dos cards grandes
    card(s, Inches(0.6), Inches(2.0), Inches(6.0), Inches(2.4),
         "🌐  Dominios",
         "CRUD completo con:\n"
         "• Tipo: principal / subdominio / addon / parked\n"
         "• Estado: activo / pendiente / suspendido\n"
         "• IP del servidor + toggle SSL\n"
         "• Validación con regex de dominio y formato IP\n"
         "• Bug fix: backtick-escape de `ssl` (palabra reservada MySQL)",
         accent=COLOR_ACCENT, title_size=15, body_size=11)

    card(s, Inches(6.8), Inches(2.0), Inches(6.0), Inches(2.4),
         "✉️  Cuentas de correo",
         "CRUD completo con:\n"
         "• Cuota en MB (0 = ilimitada)\n"
         "• Formato dinámico: ≥ 1024 MB → muestra en GB\n"
         "• Dominio extraído automáticamente del email\n"
         "• Estado: activo / suspendido\n"
         "• Validación email completa (EMAIL_RE)",
         accent=COLOR_WARNING, title_size=15, body_size=11)

    # Por qué es destacable
    add_rect(s, Inches(0.6), Inches(4.6), Inches(12.1), Inches(2.3),
             fill=COLOR_WHITE)
    add_rect(s, Inches(0.6), Inches(4.6), Inches(0.12), Inches(2.3),
             fill=COLOR_PRIMARY)
    add_text(s, Inches(0.9), Inches(4.7), Inches(11.5), Inches(0.4),
             "💡  ¿POR QUÉ INTEGRAR HOSTING EN UN CRM?",
             size=13, bold=True, color=COLOR_HEADING)
    items = [
        "Las agencias web venden hosting + servicios; sus clientes están en el CRM. Tener ambos en un único panel reduce fricción.",
        "Comparten infraestructura: mismo sistema de auditoría, mismas validaciones, mismo patrón de paginación.",
        "Permite escalar a un proveedor de servicios cloud sin tocar más que las APIs de hosting.",
        "Caso de uso real: cuando se cierra una oportunidad ganada, se puede aprovisionar dominio + email desde la misma interfaz.",
    ]
    add_bullets(s, Inches(0.95), Inches(5.15), Inches(11.5), Inches(1.8),
                items, size=11, spacing=3, bullet_color=COLOR_HEADING)


# ============================================================
# SLIDE 18 — ADMINISTRACIÓN
# ============================================================
def slide_admin(s):
    add_text(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.5),
             "Sección de administración — herramientas solo para admin",
             size=18, bold=True, color=COLOR_HEADING)

    # 4 cards
    card(s, Inches(0.6), Inches(2.0), Inches(6.0), Inches(2.3),
         "👨‍💼  Gestión de usuarios",
         "CRUD de cuentas + asignación de roles\n"
         "• Validación: nombre alfanumérico único, email único\n"
         "• Contraseña ≥ 8 caracteres, letras + números\n"
         "• Protección anti-autoborrado y último admin",
         accent=COLOR_DANGER, title_size=14, body_size=11)

    card(s, Inches(6.8), Inches(2.0), Inches(6.0), Inches(2.3),
         "📊  Bases de datos",
         "Estadísticas MySQL en tiempo real con SHOW TABLE STATUS\n"
         "• Motor, filas, tamaño, colación, última modificación\n"
         "• SET SESSION information_schema_stats_expiry=0\n"
         "• Botón directo a phpMyAdmin",
         accent=COLOR_ACCENT, title_size=14, body_size=11)

    card(s, Inches(0.6), Inches(4.4), Inches(6.0), Inches(2.3),
         "💾  Copias de seguridad",
         "Backups SQL completos de la BD\n"
         "• Creación, descarga, eliminación con confirmación\n"
         "• Acción rápida desde el dashboard\n"
         "• Volumen Docker dedicado: ./backups",
         accent=COLOR_SUCCESS, title_size=14, body_size=11)

    card(s, Inches(6.8), Inches(4.4), Inches(6.0), Inches(2.3),
         "📋  Auditoría",
         "Visualización del log de cambios\n"
         "• Tabla paginada con filtros (entidad, acción)\n"
         "• Diff JSON expandible con resaltado verde/rojo\n"
         "• Whitelist de tablas — evita SQL injection en filtros",
         accent=COLOR_WARNING, title_size=14, body_size=11)


# ============================================================
# SLIDE 19 — UX/UI
# ============================================================
def slide_ux(s):
    add_text(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.5),
             "UX/UI — tema claro/oscuro, accesibilidad y patrones consistentes",
             size=18, bold=True, color=COLOR_HEADING)

    # Demo visual tema light/dark
    # Light
    add_rect(s, Inches(0.6), Inches(2.0), Inches(5.8), Inches(2.5),
             fill=COLOR_WHITE)
    add_rect(s, Inches(0.6), Inches(2.0), Inches(5.8), Inches(0.4),
             fill=COLOR_ACCENT)
    add_text(s, Inches(0.6), Inches(2.0), Inches(5.8), Inches(0.4),
             "☀️  TEMA CLARO", size=12, bold=True, color=COLOR_WHITE,
             align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
    add_text(s, Inches(0.9), Inches(2.55), Inches(5.2), Inches(0.4),
             "Header  ▸  Dashboard  ▸  Acciones",
             size=11, color=COLOR_DARK)
    add_rect(s, Inches(0.9), Inches(3.0), Inches(5.2), Inches(0.4),
             fill=COLOR_LIGHT)
    add_text(s, Inches(1.0), Inches(3.0), Inches(5.0), Inches(0.4),
             "Tarjeta — fondo claro",
             size=11, color=COLOR_DARK, anchor=MSO_ANCHOR.MIDDLE)
    add_rect(s, Inches(0.9), Inches(3.5), Inches(1.5), Inches(0.35),
             fill=COLOR_SUCCESS)
    add_text(s, Inches(0.9), Inches(3.5), Inches(1.5), Inches(0.35),
             "Activo", size=10, bold=True, color=COLOR_WHITE,
             align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
    add_text(s, Inches(0.9), Inches(4.05), Inches(5.2), Inches(0.4),
             "Texto sobre fondo claro — WCAG AA",
             size=11, color=COLOR_DARK)

    # Dark
    add_rect(s, Inches(6.9), Inches(2.0), Inches(5.8), Inches(2.5),
             fill=COLOR_DARK)
    add_rect(s, Inches(6.9), Inches(2.0), Inches(5.8), Inches(0.4),
             fill=COLOR_PRIMARY)
    add_text(s, Inches(6.9), Inches(2.0), Inches(5.8), Inches(0.4),
             "🌙  TEMA OSCURO", size=12, bold=True, color=COLOR_WHITE,
             align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
    add_text(s, Inches(7.2), Inches(2.55), Inches(5.2), Inches(0.4),
             "Header  ▸  Dashboard  ▸  Acciones",
             size=11, color=COLOR_WHITE)
    add_rect(s, Inches(7.2), Inches(3.0), Inches(5.2), Inches(0.4),
             fill=COLOR_CARD_BG)
    add_text(s, Inches(7.3), Inches(3.0), Inches(5.0), Inches(0.4),
             "Tarjeta — fondo oscuro",
             size=11, color=COLOR_WHITE, anchor=MSO_ANCHOR.MIDDLE)
    add_rect(s, Inches(7.2), Inches(3.5), Inches(1.5), Inches(0.35),
             fill=COLOR_SUCCESS)
    add_text(s, Inches(7.2), Inches(3.5), Inches(1.5), Inches(0.35),
             "Activo", size=10, bold=True, color=COLOR_WHITE,
             align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
    add_text(s, Inches(7.2), Inches(4.05), Inches(5.2), Inches(0.4),
             "Texto sobre fondo oscuro — WCAG AA",
             size=11, color=COLOR_WHITE)

    # Patrones UI
    add_text(s, Inches(0.6), Inches(4.7), Inches(12), Inches(0.4),
             "Patrones de UI consistentes en toda la aplicación",
             size=14, bold=True, color=COLOR_HEADING)

    patrones = [
        ("Toggle persistido en localStorage  ·  ", "sincronizado entre cabecera y dropdown de usuario"),
        ("CSS modular en 6 archivos  ·  ", "cpanel-base, crm, pipeline, admin, sistema y dark con cache-busting filemtime"),
        ("Componentes reutilizables  ·  ", "Toast (success/error/info), Modal de confirmación, paginación, widget de actividades"),
        ("Estados vacíos y de carga  ·  ", "crm-empty y crm-loading consistentes en todos los listados"),
        ("Badges semánticos con clases CSS  ·  ", "sin style=\"\" inline (requisito de CSP script-src 'self')"),
    ]
    add_bullets(s, Inches(0.7), Inches(5.15), Inches(12), Inches(2),
                patrones, size=11, spacing=3)


# ============================================================
# SLIDE 20 — MONITORIZACIÓN
# ============================================================
def slide_monitor(s):
    add_text(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.5),
             "Monitorización en tiempo real y estadísticas de negocio",
             size=18, bold=True, color=COLOR_HEADING)

    # Mockup del dashboard
    # Tres tarjetas de métricas
    metricas = [
        ("CPU", "32%", COLOR_ACCENT, "del servidor host"),
        ("RAM", "1.4 GB", COLOR_WARNING, "de 8 GB disponibles"),
        ("Disco", "12%", COLOR_SUCCESS, "uso total"),
    ]
    x = Inches(0.6)
    for label, value, color, sub in metricas:
        add_rect(s, x, Inches(2.0), Inches(4.0), Inches(1.6),
                 fill=COLOR_WHITE)
        add_rect(s, x, Inches(2.0), Inches(4.0), Inches(0.08),
                 fill=color)
        add_text(s, x + Inches(0.2), Inches(2.15), Inches(3.6),
                 Inches(0.4), label, size=12, bold=True, color=COLOR_MUTED)
        add_text(s, x + Inches(0.2), Inches(2.50), Inches(3.6),
                 Inches(0.7), value, size=32, bold=True, color=color)
        add_text(s, x + Inches(0.2), Inches(3.25), Inches(3.6),
                 Inches(0.3), sub, size=10, color=COLOR_MUTED)
        x += Inches(4.2)

    # Bullets
    add_text(s, Inches(0.6), Inches(3.85), Inches(12), Inches(0.4),
             "Información del dashboard principal",
             size=14, bold=True, color=COLOR_HEADING)

    items = [
        ("Métricas reales del host: ",
         "lee /proc/stat, /proc/meminfo y df desde PHP; refresca cada 3 segundos vía fetch + setInterval"),
        ("Actividad reciente: ",
         "los 10 últimos eventos de auditoría con tiempo relativo (\"hace 2 min\"); admins ven todos, usuarios solo los propios"),
        ("Estadísticas CRM: ",
         "distribución de leads por estado, oportunidades por etapa, valor potencial total del pipeline"),
        ("Acciones rápidas: ",
         "botones para crear backup, nueva cuenta de correo, FTP, SSL — navegación programática a sus secciones"),
        ("cAdvisor disponible en :8080: ",
         "métricas profundas de contenedores Docker para diagnóstico de rendimiento (no expuesto en producción)"),
    ]
    add_bullets(s, Inches(0.7), Inches(4.30), Inches(12), Inches(2.6),
                items, size=11, spacing=4)


# ============================================================
# SLIDE 21 — DESPLIEGUE
# ============================================================
def slide_despliegue(s):
    add_text(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.5),
             "Despliegue — un único comando, entorno reproducible",
             size=18, bold=True, color=COLOR_HEADING)

    # Bloque de comando
    add_rect(s, Inches(0.6), Inches(2.0), Inches(12.1), Inches(1.2),
             fill=COLOR_DARK)
    add_text(s, Inches(0.9), Inches(2.10), Inches(11), Inches(0.4),
             "▶  Arranque completo del proyecto:",
             size=12, color=COLOR_ACCENT)
    add_text(s, Inches(0.9), Inches(2.50), Inches(11), Inches(0.6),
             "$  docker compose up -d --build",
             size=22, bold=True, color=COLOR_WHITE, font="Consolas")

    # Resultado
    add_text(s, Inches(0.6), Inches(3.4), Inches(12), Inches(0.4),
             "Lo que sucede automáticamente",
             size=14, bold=True, color=COLOR_HEADING)

    items = [
        "Construye la imagen PHP con extensiones necesarias (PDO, mbstring, finfo)",
        "Levanta MySQL 8.4 con healthcheck (espera a que esté listo)",
        "Levanta Nginx Alpine con la configuración del proyecto",
        "Ejecuta el servicio migrate (one-shot) que crea el esquema y siembra datos de prueba",
        "Expone phpMyAdmin en :8082 y cAdvisor en :8080 para administración y métricas",
        "Aplicación disponible en http://localhost:91 — login con admin / lolito412/ (solo desarrollo)",
    ]
    add_bullets(s, Inches(0.7), Inches(3.85), Inches(12), Inches(2.5),
                items, size=12, spacing=3)

    # Bloque ventajas
    add_rect(s, Inches(0.6), Inches(6.30), Inches(12.1), Inches(0.7),
             fill=COLOR_WHITE)
    add_rect(s, Inches(0.6), Inches(6.30), Inches(0.12), Inches(0.7),
             fill=COLOR_SUCCESS)
    add_text(s, Inches(0.9), Inches(6.30), Inches(11), Inches(0.7),
             "✓  ZERO-CONFIG: clonar repo → docker compose up → todo funciona en cualquier máquina con Docker",
             size=12, bold=True, color=COLOR_HEADING,
             anchor=MSO_ANCHOR.MIDDLE)


# ============================================================
# SLIDE 22 — CONCLUSIONES
# ============================================================
def slide_conclusiones(s):
    add_text(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.5),
             "Conclusiones, lecciones aprendidas y posibles mejoras",
             size=18, bold=True, color=COLOR_HEADING)

    # Logros conseguidos
    add_rect(s, Inches(0.6), Inches(2.0), Inches(6.0), Inches(4.8),
             fill=COLOR_WHITE)
    add_rect(s, Inches(0.6), Inches(2.0), Inches(6.0), Inches(0.55),
             fill=COLOR_SUCCESS)
    add_text(s, Inches(0.6), Inches(2.0), Inches(6.0), Inches(0.55),
             "✓  LOGROS DEL PROYECTO",
             size=15, bold=True, color=COLOR_WHITE,
             align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
    logros = [
        ("MVP funcional end-to-end ", "con 14 endpoints REST y 13 módulos JS"),
        ("8 tablas con integridad referencial ", "completa y migraciones idempotentes"),
        ("Seguridad de nivel profesional: ", "Argon2id, CSRF, CSP, RBAC, auditoría"),
        ("Pipeline kanban ", "con drag & drop HTML5 nativo"),
        ("Tema claro/oscuro ", "con contraste WCAG AA verificado"),
        ("Despliegue reproducible ", "con un solo comando Docker"),
        ("Documentación exhaustiva ", "por fases en /implementaciones"),
    ]
    add_bullets(s, Inches(0.85), Inches(2.7), Inches(5.7), Inches(4.0),
                logros, size=11, spacing=5, bullet_color=COLOR_SUCCESS)

    # Mejoras futuras
    add_rect(s, Inches(6.8), Inches(2.0), Inches(6.0), Inches(4.8),
             fill=COLOR_WHITE)
    add_rect(s, Inches(6.8), Inches(2.0), Inches(6.0), Inches(0.55),
             fill=COLOR_WARNING)
    add_text(s, Inches(6.8), Inches(2.0), Inches(6.0), Inches(0.55),
             "▸  MEJORAS POST-MVP (roadmap)",
             size=15, bold=True, color=COLOR_WHITE,
             align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
    mejoras = [
        ("Fase 04 — Pruebas: ", "smoke tests, e2e con Cypress, pruebas de seguridad"),
        ("Importación/exportación CSV ", "para contactos, leads y oportunidades"),
        ("Hardening de login: ", "rate limit, bloqueo por intentos fallidos"),
        ("API REST versionada: ", "/api/v1/ para integraciones futuras"),
        ("Importación/exportación CSV ", "para contactos, leads y oportunidades"),
        ("Plantillas de email: ", "envío directo desde la entidad asociada"),
        ("Adjuntos: ", "diseño ya documentado (Fase 02), pendiente de implementar"),
    ]
    add_bullets(s, Inches(7.05), Inches(2.7), Inches(5.7), Inches(4.0),
                mejoras, size=11, spacing=5, bullet_color=COLOR_WARNING)


# ============================================================
# SLIDE 23 — GRACIAS (extra)
# ============================================================
def slide_gracias():
    s = prs.slides.add_slide(BLANK)
    add_rect(s, 0, 0, SW, SH, fill=COLOR_DARK)
    add_rect(s, 0, 0, SW, Inches(0.25), fill=COLOR_ACCENT)
    add_rect(s, 0, SH - Inches(0.25), SW, Inches(0.25), fill=COLOR_ACCENT)

    add_text(s, Inches(0.6), Inches(2.5), Inches(12), Inches(1.5),
             "¡Gracias por su atención!",
             size=60, bold=True, color=COLOR_WHITE,
             align=PP_ALIGN.CENTER)

    add_text(s, Inches(0.6), Inches(4.2), Inches(12), Inches(0.6),
             "Preguntas, comentarios y demo en vivo del proyecto",
             size=20, color=COLOR_TINT,
             align=PP_ALIGN.CENTER)

    # Repo info
    add_rect(s, Inches(3.5), Inches(5.5), Inches(6.3), Inches(1.2),
             fill=COLOR_PRIMARY)
    add_text(s, Inches(3.5), Inches(5.6), Inches(6.3), Inches(0.4),
             "PROYECTO L&J CRM Web",
             size=12, bold=True, color=COLOR_ACCENT,
             align=PP_ALIGN.CENTER)
    add_text(s, Inches(3.5), Inches(5.95), Inches(6.3), Inches(0.4),
             "github.com/Jimmy31101  ·  DAW 2025/2026",
             size=14, color=COLOR_WHITE,
             align=PP_ALIGN.CENTER, font="Consolas")
    add_text(s, Inches(3.5), Inches(6.30), Inches(6.3), Inches(0.4),
             "$ docker compose up -d --build",
             size=12, color=COLOR_TINT,
             align=PP_ALIGN.CENTER, font="Consolas")


# ============================================================
# DIAGRAMA 1 — Flujo de autenticación y RBAC
# ============================================================
def slide_diagrama_auth(s):
    add_text(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.5),
             "Diagrama de flujo — Autenticación + RBAC",
             size=18, bold=True, color=COLOR_HEADING)

    # ── Fila superior: petición de login ──────────────────────────────
    add_text(s, Inches(0.6), Inches(2.0), Inches(12), Inches(0.3),
             "1.  Petición de login desde el navegador",
             size=11, bold=True, color=COLOR_MUTED)

    NW, NH = Inches(2.05), Inches(0.65)
    GAP = Inches(0.3)
    y0 = Inches(2.35)

    nodes_row1 = [
        ("Formulario\nlogin.php",    COLOR_CARD_BG),
        ("JS valida\ninputs",         COLOR_SECONDARY),
        ("POST /auth/\nlogin.php",    COLOR_ACCENT),
        ("SELECT\nusuario BD",        COLOR_ACCENT),
        ("Argon2id\nverify()",        COLOR_PRIMARY),
    ]
    xs = []
    x = Inches(0.6)
    for label, color in nodes_row1:
        flow_node(s, x, y0, NW, NH, label, fill=color)
        xs.append(x)
        x += NW + GAP
    for i in range(len(xs) - 1):
        flow_arrow_r(s, xs[i] + NW, y0, NH)

    # ── Bifurcación KO / OK ───────────────────────────────────────────
    # Fallo
    add_text(s, Inches(0.6), Inches(3.2), Inches(12), Inches(0.25),
             "2.  Resultado — dos caminos posibles",
             size=11, bold=True, color=COLOR_MUTED)

    flow_node(s, Inches(0.6),  Inches(3.5), NW, NH, "❌  401\nCredenciales\ninválidas",
              fill=COLOR_DANGER, size=10)
    flow_arrow_d(s, Inches(0.6) + NW * 4 + GAP * 4, y0, NW, color=COLOR_DANGER)
    add_text(s, Inches(0.6) + NW * 4 + GAP * 4 + Inches(0.05),
             Inches(3.0), Inches(1.0), Inches(0.28),
             "KO", size=9, bold=True, color=COLOR_DANGER, align=PP_ALIGN.CENTER)

    # Éxito (verde)
    flow_node(s, Inches(3.0),  Inches(3.5), NW, NH, "✓  session_start()\n$_SESSION\n[id_usuario, rol]",
              fill=COLOR_SUCCESS, size=10)
    flow_arrow_r(s, Inches(3.0) + NW, Inches(3.5), NH, color=COLOR_SUCCESS)
    flow_node(s, Inches(5.35), Inches(3.5), NW, NH, "Redirect →\ncpanel.php",
              fill=COLOR_SUCCESS, size=10)
    add_text(s, Inches(3.0) - Inches(0.05), Inches(3.0), Inches(1.0), Inches(0.28),
             "OK", size=9, bold=True, color=COLOR_SUCCESS, align=PP_ALIGN.CENTER)
    flow_arrow_d(s, Inches(3.0) - Inches(0.5), y0, NW, color=COLOR_SUCCESS)

    # ── Fila RBAC ──────────────────────────────────────────────────────
    add_text(s, Inches(0.6), Inches(4.4), Inches(12), Inches(0.3),
             "3.  Guard RBAC en cada endpoint API",
             size=11, bold=True, color=COLOR_MUTED)

    rbac_nodes = [
        ("Llamada\nAPI endpoint", COLOR_CARD_BG),
        ("¿Sesión\nactiva?",      COLOR_SECONDARY),
        ("¿Rol\ncorrecto?",       COLOR_SECONDARY),
        ("Procesar\npetición",    COLOR_SUCCESS),
    ]
    x = Inches(0.6)
    rbac_xs = []
    for label, color in rbac_nodes:
        flow_node(s, x, Inches(4.75), NW, NH, label, fill=color)
        rbac_xs.append(x)
        x += NW + GAP
    for i in range(len(rbac_xs) - 1):
        flow_arrow_r(s, rbac_xs[i] + NW, Inches(4.75), NH)

    flow_node(s, Inches(0.6),  Inches(5.6), NW, NH, "401\nNo autenticado", fill=COLOR_DANGER, size=10)
    flow_node(s, Inches(3.0),  Inches(5.6), NW, NH, "403\nForbidden",      fill=COLOR_DANGER, size=10)
    flow_arrow_d(s, rbac_xs[1], Inches(4.75), NW, color=COLOR_DANGER)
    flow_arrow_d(s, rbac_xs[2], Inches(4.75), NW, color=COLOR_DANGER)

    # Leyenda lateral
    add_rect(s, Inches(8.7), Inches(3.4), Inches(4.0), Inches(3.3), fill=COLOR_WHITE)
    add_rect(s, Inches(8.7), Inches(3.4), Inches(0.08), Inches(3.3), fill=COLOR_PRIMARY)
    add_text(s, Inches(8.95), Inches(3.5), Inches(3.7), Inches(0.35),
             "PUNTOS CLAVE", size=11, bold=True, color=COLOR_HEADING)
    puntos = [
        "Argon2id: función costosa, resistente a fuerza bruta",
        "session_start() en cada endpoint, sin excepción",
        "RBAC inline: $_SESSION['rol'] en cada guard",
        "Anti-autoborrado y protección del último admin",
        "Timeout de sesión configurable vía php.ini",
    ]
    add_bullets(s, Inches(8.95), Inches(3.9), Inches(3.7), Inches(2.6),
                puntos, size=10, spacing=4,
                color=COLOR_CARD_BG, bullet_color=COLOR_PRIMARY)


# ============================================================
# DIAGRAMA 2 — Flujo del token CSRF
# ============================================================
def slide_diagrama_csrf(s):
    add_text(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.5),
             "Diagrama de flujo — Ciclo completo del token CSRF",
             size=18, bold=True, color=COLOR_HEADING)

    NW, NH = Inches(2.2), Inches(0.7)
    GAP = Inches(0.25)

    # ── Fase 1: servidor genera token ─────────────────────────────────
    add_text(s, Inches(0.6), Inches(2.0), Inches(12), Inches(0.3),
             "Fase A — Servidor genera y distribuye el token al cargar la página",
             size=11, bold=True, color=COLOR_MUTED)

    row1 = [
        ("Petición GET\npágina cpanel",     COLOR_CARD_BG),
        ("PHP genera\nbin2hex(random_bytes)", COLOR_SECONDARY),
        ("$_SESSION\n['csrf_token']",        COLOR_SECONDARY),
        ("<meta name=\n\"csrf-token\">",     COLOR_PRIMARY),
        ("Página cargada\nen navegador",     COLOR_CARD_BG),
    ]
    x = Inches(0.6)
    row1_xs = []
    for label, color in row1:
        flow_node(s, x, Inches(2.35), NW, NH, label, fill=color, size=10)
        row1_xs.append(x)
        x += NW + GAP
    for i in range(len(row1_xs) - 1):
        flow_arrow_r(s, row1_xs[i] + NW, Inches(2.35), NH)

    # ── Fase 2: cliente usa el token ──────────────────────────────────
    add_text(s, Inches(0.6), Inches(3.25), Inches(12), Inches(0.3),
             "Fase B — El cliente adjunta el token en cada mutación (POST / PUT / DELETE)",
             size=11, bold=True, color=COLOR_MUTED)

    row2 = [
        ("JS lee\ndocument.querySelector\n(meta csrf-token)",  COLOR_CARD_BG),
        ("fetchSeguro()\ninyecta cabecera\nX-CSRF-Token",       COLOR_ACCENT),
        ("Petición\nHTTP con\ncabecera custom",                 COLOR_ACCENT),
    ]
    x = Inches(0.6)
    row2_xs = []
    for label, color in row2:
        flow_node(s, x, Inches(3.6), NW, Inches(0.85), label, fill=color, size=10)
        row2_xs.append(x)
        x += NW + GAP
    for i in range(len(row2_xs) - 1):
        flow_arrow_r(s, row2_xs[i] + NW, Inches(3.6), Inches(0.85))

    # ── Fase 3: servidor valida ───────────────────────────────────────
    add_text(s, Inches(0.6), Inches(4.65), Inches(12), Inches(0.3),
             "Fase C — El servidor valida el token antes de procesar",
             size=11, bold=True, color=COLOR_MUTED)

    row3 = [
        ("csrfValidar()\nlee cabecera\nX-CSRF-Token",    COLOR_SECONDARY),
        ("hash_equals()\n$_SESSION token\nvs cabecera",   COLOR_SECONDARY),
    ]
    x = Inches(0.6)
    row3_xs = []
    for label, color in row3:
        flow_node(s, x, Inches(5.0), NW, Inches(0.85), label, fill=color, size=10)
        row3_xs.append(x)
        x += NW + GAP
    flow_arrow_r(s, row3_xs[0] + NW, Inches(5.0), Inches(0.85))

    flow_node(s, Inches(0.6) + NW*2 + GAP*2, Inches(5.0), NW, Inches(0.85),
              "✓  Procesar\npetición API",
              fill=COLOR_SUCCESS, size=10)
    flow_node(s, Inches(0.6) + NW*3 + GAP*3, Inches(5.0), NW, Inches(0.85),
              "❌  403\nForbidden",
              fill=COLOR_DANGER, size=10)
    flow_arrow_r(s, Inches(0.6) + NW*1 + GAP*1 + NW, Inches(5.0), Inches(0.85), color=COLOR_SUCCESS)
    flow_arrow_r(s, Inches(0.6) + NW*2 + GAP*2 + NW, Inches(5.0), Inches(0.85), color=COLOR_DANGER)
    flow_label(s, Inches(0.6) + NW*2 + GAP*2 + NW - Inches(0.05),
               Inches(4.85), NW, Inches(0.2), "OK", color=COLOR_SUCCESS, size=9)
    flow_label(s, Inches(0.6) + NW*3 + GAP*3 + NW - Inches(0.05),
               Inches(4.85), NW, Inches(0.2), "token inválido", color=COLOR_DANGER, size=9)

    # Leyenda
    add_rect(s, Inches(9.3), Inches(3.5), Inches(3.7), Inches(3.3), fill=COLOR_WHITE)
    add_rect(s, Inches(9.3), Inches(3.5), Inches(0.08), Inches(3.3), fill=COLOR_SECONDARY)
    add_text(s, Inches(9.55), Inches(3.6), Inches(3.4), Inches(0.35),
             "¿POR QUÉ DOS CAPAS?", size=11, bold=True, color=COLOR_HEADING)
    notas = [
        "Token solo: XSS puede leerlo del DOM",
        "Cabecera custom: exige CORS preflight",
        "CORS bloquea peticiones cross-origin",
        "hash_equals: previene timing attacks",
        "GET/HEAD no validan (solo lectura)",
    ]
    add_bullets(s, Inches(9.55), Inches(4.05), Inches(3.4), Inches(2.6),
                notas, size=10, spacing=4,
                color=COLOR_CARD_BG, bullet_color=COLOR_SECONDARY)


# ============================================================
# DIAGRAMA 3 — Ciclo de notificaciones y recordatorios
# ============================================================
def slide_diagrama_notificaciones(s):
    add_text(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.5),
             "Diagrama de flujo — Sistema de notificaciones y recordatorios",
             size=18, bold=True, color=COLOR_HEADING)

    NW, NH = Inches(2.05), Inches(0.65)
    GAP = Inches(0.28)

    # ── Fila 1: inicialización ────────────────────────────────────────
    add_text(s, Inches(0.6), Inches(2.0), Inches(12), Inches(0.3),
             "1.  Inicio — inicialización del módulo al autenticarse",
             size=11, bold=True, color=COLOR_MUTED)

    init_nodes = [
        ("checkAuth()\ncpanel-core.js",       COLOR_CARD_BG),
        ("Notificaciones\n.init()",            COLOR_PRIMARY),
        ("GET /api/\npreferencias.php",        COLOR_ACCENT),
        ("_aplicar\nPreferencias()",           COLOR_SECONDARY),
        ("_reprogramar\nPolling()",            COLOR_SECONDARY),
    ]
    x = Inches(0.6)
    init_xs = []
    for label, color in init_nodes:
        flow_node(s, x, Inches(2.35), NW, NH, label, fill=color, size=10)
        init_xs.append(x)
        x += NW + GAP
    for i in range(len(init_xs) - 1):
        flow_arrow_r(s, init_xs[i] + NW, Inches(2.35), NH)

    # ── Fila 2: ciclo de polling ───────────────────────────────────────
    add_text(s, Inches(0.6), Inches(3.2), Inches(12), Inches(0.3),
             "2.  Ciclo de polling — se repite según frecuencia configurada (30 s – 5 min)",
             size=11, bold=True, color=COLOR_MUTED)

    poll_nodes = [
        ("setInterval()\nfrecuencia\nconfigurable",   COLOR_CARD_BG),
        ("GET /api/\nnotificaciones\n.php",            COLOR_ACCENT),
        ("BD: actividades\nrecordatorio_at\n<= NOW()", COLOR_ACCENT),
        ("_detectar\nNuevas()\ncomparar IDs",          COLOR_SECONDARY),
    ]
    x = Inches(0.6)
    poll_xs = []
    for label, color in poll_nodes:
        flow_node(s, x, Inches(3.55), NW, Inches(0.85), label, fill=color, size=10)
        poll_xs.append(x)
        x += NW + GAP
    for i in range(len(poll_xs) - 1):
        flow_arrow_r(s, poll_xs[i] + NW, Inches(3.55), Inches(0.85))

    # ── Fila 3: presentación ───────────────────────────────────────────
    add_text(s, Inches(0.6), Inches(4.6), Inches(12), Inches(0.3),
             "3.  Presentación — aviso al usuario si hay notificaciones nuevas",
             size=11, bold=True, color=COLOR_MUTED)

    pres_nodes = [
        ("_actualizar\nBadge(n)",          COLOR_SUCCESS),
        ("_reproducir\nSonido()\nWebAudio", COLOR_WARNING),
        ("_pushBrowser()\nNotification\nAPI", COLOR_WARNING),
        ("_renderizar()\ndropdown\nlistado",  COLOR_PRIMARY),
        ("Reprogramar\nsetTimeout\nrecursivo", COLOR_CARD_BG),
    ]
    x = Inches(0.6)
    pres_xs = []
    for label, color in pres_nodes:
        flow_node(s, x, Inches(4.95), NW, Inches(0.85), label, fill=color, size=10)
        pres_xs.append(x)
        x += NW + GAP
    for i in range(len(pres_xs) - 1):
        flow_arrow_r(s, pres_xs[i] + NW, Inches(4.95), Inches(0.85))

    # Flecha loop (último nodo fila 3 → primer nodo fila 2)
    add_text(s, pres_xs[-1] + NW + Inches(0.02), Inches(5.0), Inches(0.25), Inches(0.3),
             "↺", size=14, color=COLOR_MUTED,
             align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)

    # ── Acciones del usuario ───────────────────────────────────────────
    add_text(s, Inches(0.6), Inches(6.05), Inches(12), Inches(0.3),
             "4.  Acciones sobre una notificación (POST /api/notificaciones.php)",
             size=11, bold=True, color=COLOR_MUTED)

    acc_nodes = [
        ("Descartar\n(descartado=1)", COLOR_DANGER),
        ("Completar\n(completada=1)", COLOR_SUCCESS),
        ("Posponer\n(+N minutos)",    COLOR_WARNING),
    ]
    x = Inches(0.6)
    for label, color in acc_nodes:
        flow_node(s, x, Inches(6.35), NW, NH, label, fill=color, size=10)
        x += NW + GAP + Inches(0.5)

    add_text(s, Inches(7.7), Inches(6.35), Inches(5.0), NH,
             "→ CSRF validado  →  UPDATE BD  →  Auditoría  →  Respuesta JSON",
             size=10, bold=True, color=COLOR_CARD_BG, anchor=MSO_ANCHOR.MIDDLE)


# ============================================================
# Generar todas las slides
# ============================================================
slide_portada()
slide_indice()
content_slide(3,  "01. Contexto y motivación",
              "Por qué construir un panel que une CRM y cPanel", slide_contexto)
content_slide(4,  "02. Objetivo y alcance del MVP",
              "Qué entra y qué queda fuera — priorización MoSCoW", slide_alcance)
content_slide(5,  "03. Actores, roles y permisos",
              "Modelo RBAC: no autenticado · usuario · administrador", slide_roles)
content_slide(6,  "04. Stack tecnológico",
              "Tecnologías elegidas y justificación de cada capa", slide_stack)
content_slide(7,  "05. Arquitectura general",
              "6 servicios orquestados con Docker Compose", slide_arquitectura)
content_slide(8,  "06. Modelo de datos",
              "8 tablas con FKs, ENUMs, auditoría JSON y Argon2id", slide_modelo)
content_slide(9,  "07. Migraciones idempotentes",
              "Esquema versionado al estilo Flyway / Laravel", slide_migraciones)
content_slide(10, "08. Autenticación y RBAC",
              "Argon2id, sesiones PHP y guards por rol en cada endpoint", slide_auth)
content_slide(11, "Diagrama — Flujo de autenticación y RBAC",
              "Login · session_start · guard · 401 / 403", slide_diagrama_auth)
content_slide(12, "09. Seguridad anti-CSRF",
              "Synchronizer Token + cabecera custom X-CSRF-Token", slide_csrf)
content_slide(13, "Diagrama — Ciclo completo del token CSRF",
              "Generar · incrustar · inyectar · validar con hash_equals", slide_diagrama_csrf)
content_slide(14, "10. Anti-XSS y Content Security Policy",
              "CSP estricta, escape de salida en 3 capas, sin handlers inline", slide_xss)
content_slide(15, "11. Auditoría con diff antes/después",
              "Trazabilidad completa de cada cambio en el sistema", slide_auditoria)
content_slide(16, "12. CRM — Contactos y Leads",
              "CRUD, búsqueda, filtros, paginación y conversión atómica", slide_crm_contactos)
content_slide(17, "13. Pipeline kanban + Drag & Drop",
              "5 etapas, HTML5 drag&drop nativo, modal de confirmación", slide_kanban)
content_slide(18, "14. Actividades y Notificaciones",
              "Widget compartido · recordatorios · polling · WebAudio · browser push", slide_actividades)
content_slide(19, "Diagrama — Sistema de notificaciones y recordatorios",
              "init · polling configurable · badge · sonido · browser push · acciones", slide_diagrama_notificaciones)
content_slide(20, "15. cPanel — Hosting integrado",
              "Dominios y cuentas de correo: más allá del CRM clásico", slide_cpanel)
content_slide(21, "16. Administración del sistema",
              "Usuarios, bases de datos, backups y auditoría (admin-only)", slide_admin)
content_slide(22, "17. UX/UI — Tema claro/oscuro WCAG AA",
              "Accesibilidad, consistencia y CSS modular con cache-busting", slide_ux)
content_slide(23, "18. Monitorización en tiempo real",
              "CPU/RAM/Disco cada 3s + estadísticas de negocio del CRM", slide_monitor)
content_slide(24, "19. Despliegue Docker",
              "Un comando, entorno idéntico en cualquier máquina", slide_despliegue)
content_slide(25, "20. Conclusiones y mejoras",
              "Logros conseguidos y roadmap post-MVP", slide_conclusiones)
slide_gracias()


# ============================================================
# Guardar
# ============================================================
OUTPUT = r"c:\Users\Jaime\Desktop\TRABAJOS DAW\_Proyecto\LANDJ\presentacion_LandJ_CRM.pptx"
prs.save(OUTPUT)
print(f"OK -> {OUTPUT}")
print(f"Total de slides generadas: {len(prs.slides)}")
