<section id="contactos" class="content-section">
    <div class="section-header">
        <div>
            <h2 class="section-title">Contactos</h2>
            <p class="section-subtitle">Gestiona tu directorio de clientes y contactos</p>
        </div>
        <button class="btn-primary" id="contactos-nuevo-btn">
            <i class="fas fa-plus"></i> Nuevo Contacto
        </button>
    </div>

    <!-- Panel crear / editar -->
    <div class="form-panel" id="contactos-form-panel">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title" id="contactos-form-titulo">Nuevo Contacto</h3>
            </div>
            <form id="contactos-form" class="form-grid" novalidate>
                <div class="form-group">
                    <label class="form-label">Nombre <span class="form-required">*</span></label>
                    <input type="text" id="f-nombre" class="form-input" placeholder="Nombre" maxlength="100" autocomplete="off">
                    <span class="form-error" id="err-nombre"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Apellidos</label>
                    <input type="text" id="f-apellidos" class="form-input" placeholder="Apellidos" maxlength="100" autocomplete="off">
                    <span class="form-error" id="err-apellidos"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="text" id="f-email" class="form-input" placeholder="correo@ejemplo.com" maxlength="255" autocomplete="off">
                    <span class="form-error" id="err-email"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="text" id="f-telefono" class="form-input" placeholder="600 000 000" maxlength="9" autocomplete="off">
                    <span class="form-error" id="err-telefono"></span>
                </div>
                <div class="form-group full-width">
                    <label class="form-label">Empresa</label>
                    <input type="text" id="f-empresa" class="form-input" placeholder="Nombre de la empresa" maxlength="150" autocomplete="off">
                    <span class="form-error" id="err-empresa"></span>
                </div>
                <div class="form-group full-width">
                    <label class="form-label">Notas</label>
                    <textarea id="f-notas" class="form-textarea" rows="3" placeholder="Observaciones sobre este contacto..." maxlength="255"></textarea>
                    <div class="form-counter"><span id="notas-count">0</span>/500</div>
                    <span class="form-error" id="err-notas"></span>
                </div>
                <div class="form-actions">
                    <button type="submit" id="contactos-guardar-btn" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button type="button" id="contactos-cancelar-btn" class="btn-secondary">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Panel detalle -->
    <div class="detalle-overlay" id="contacto-detalle-overlay" style="display:none">
        <div class="detalle-panel" id="contacto-detalle-panel">
            <div class="detalle-header">
                <div class="detalle-avatar" id="det-avatar"></div>
                <div class="detalle-header-info">
                    <h3 id="det-nombre"></h3>
                    <span id="det-empresa" class="detalle-empresa"></span>
                </div>
                <div class="detalle-header-actions">
                    <button class="btn-icon" id="det-editar-btn" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon danger" id="det-eliminar-btn" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button class="btn-icon" id="det-cerrar-btn" title="Cerrar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="detalle-body">
                <div class="detalle-campos">
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-envelope"></i> Email</span>
                        <span class="detalle-valor" id="det-email"></span>
                    </div>
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-phone"></i> Teléfono</span>
                        <span class="detalle-valor" id="det-telefono"></span>
                    </div>
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-building"></i> Empresa</span>
                        <span class="detalle-valor" id="det-empresa-campo"></span>
                    </div>
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-calendar-alt"></i> Registrado</span>
                        <span class="detalle-valor" id="det-fecha"></span>
                    </div>
                </div>

                <div class="detalle-notas" id="det-notas-bloque">
                    <span class="detalle-label"><i class="fas fa-sticky-note"></i> Notas</span>
                    <p id="det-notas"></p>
                </div>

                <div class="detalle-actividades">
                    <div class="detalle-seccion-titulo">
                        <span><i class="fas fa-history"></i> Actividades</span>
                        <button class="btn-icon-xs" id="det-act-nuevo-btn" title="Nueva actividad"><i class="fas fa-plus"></i></button>
                    </div>
                    <div class="act-form-inline" id="det-act-form" style="display:none">
                        <select id="det-act-tipo" class="form-input form-input-sm">
                            <option value="nota">Nota</option>
                            <option value="llamada">Llamada</option>
                            <option value="reunion">Reunión</option>
                            <option value="tarea">Tarea</option>
                            <option value="email">Email</option>
                        </select>
                        <textarea id="det-act-desc" class="form-textarea" rows="2" placeholder="Descripción de la actividad..." maxlength="500"></textarea>
                        <div class="act-form-grid">
                            <label class="act-form-mini-label">
                                <span>Fecha</span>
                                <input type="date" id="det-act-fecha" class="form-input form-input-sm">
                            </label>
                            <label class="act-form-mini-label">
                                <span><i class="fas fa-bell"></i> Recordatorio</span>
                                <input type="datetime-local" id="det-act-recordatorio" class="form-input form-input-sm">
                            </label>
                        </div>
                        <div class="act-form-actions">
                            <button id="det-act-guardar" class="btn-primary btn-sm">Guardar</button>
                            <button id="det-act-cancelar" class="btn-secondary btn-sm">Cancelar</button>
                        </div>
                    </div>
                    <ul class="det-actividades-lista" id="det-act-lista">
                        <li class="det-act-vacio"><i class="fas fa-spinner fa-spin"></i> Cargando...</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Buscador + filtros -->
    <div class="crm-toolbar">
        <div class="crm-search">
            <i class="fas fa-search"></i>
            <input type="text" id="contactos-buscar" class="crm-search-input"
                   placeholder="Buscar por nombre, email o empresa...">
        </div>
        <div class="crm-filtros">
            <button class="btn-filtros-toggle" id="ct-filtros-toggle" title="Filtros avanzados">
                <i class="fas fa-sliders-h"></i> Filtros
                <span class="filtros-badge" id="ct-filtros-badge" style="display:none">0</span>
            </button>
        </div>
    </div>
    <div class="crm-filtros-avanzados" id="ct-filtros-avanzados">
        <div class="crm-filtro-grupo">
            <label class="crm-filtro-label">Empresa</label>
            <input type="text" id="ct-filtro-empresa" class="form-input form-input-sm" placeholder="Filtrar por empresa...">
        </div>
        <div class="crm-filtro-grupo">
            <label class="crm-filtro-label">Registrado desde</label>
            <input type="date" id="ct-filtro-desde" class="form-input form-input-sm">
        </div>
        <div class="crm-filtro-grupo">
            <label class="crm-filtro-label">Hasta</label>
            <input type="date" id="ct-filtro-hasta" class="form-input form-input-sm">
        </div>
        <div class="crm-filtro-grupo">
            <label class="crm-filtro-label">Ordenar por</label>
            <div class="crm-orden-wrap">
                <select id="ct-filtro-orden" class="crm-select">
                    <option value="created_at">Fecha registro</option>
                    <option value="nombre">Nombre</option>
                    <option value="empresa">Empresa</option>
                </select>
                <button class="btn-dir" id="ct-filtro-dir" data-dir="desc" title="Dirección">
                    <i class="fas fa-sort-amount-down"></i>
                </button>
            </div>
        </div>
        <button class="btn-filtros-clear" id="ct-filtros-clear"><i class="fas fa-times"></i> Limpiar</button>
    </div>

    <!-- Tabla -->
    <div class="content-card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Contacto</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Empresa</th>
                        <th>Registrado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="contactos-tbody">
                    <tr>
                        <td colspan="6" class="crm-loading">
                            <i class="fas fa-spinner fa-spin"></i> Cargando...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="crm-paginacion" id="ct-paginacion"></div>
</section>
