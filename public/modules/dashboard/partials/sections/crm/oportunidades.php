<section id="oportunidades" class="content-section">
    <div class="section-header">
        <div>
            <h2 class="section-title">Oportunidades</h2>
            <p class="section-subtitle">Gestiona tus oportunidades de negocio por etapa</p>
        </div>
        <button class="btn-primary" id="opor-nuevo-btn">
            <i class="fas fa-plus"></i> Nueva Oportunidad
        </button>
    </div>

    <!-- Panel crear / editar -->
    <div class="form-panel" id="opor-form-panel">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title" id="opor-form-titulo">Nueva Oportunidad</h3>
            </div>
            <form id="opor-form" class="form-grid" novalidate>
                <div class="form-group full-width">
                    <label class="form-label">Título <span class="form-required">*</span></label>
                    <input type="text" id="of-titulo" class="form-input" placeholder="Nombre de la oportunidad" maxlength="150" autocomplete="off">
                    <span class="form-error" id="oerr-titulo"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Valor estimado (€)</label>
                    <input type="number" id="of-valor" class="form-input" placeholder="0.00" min="0" step="0.01" autocomplete="off">
                    <span class="form-error" id="oerr-valor"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Etapa</label>
                    <select id="of-etapa" class="form-input">
                        <option value="prospecto">Prospecto</option>
                        <option value="propuesta">Propuesta</option>
                        <option value="negociacion">Negociación</option>
                        <option value="cerrada_ganada">Cerrada — Ganada</option>
                        <option value="cerrada_perdida">Cerrada — Perdida</option>
                    </select>
                    <span class="form-error" id="oerr-etapa"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha de cierre esperada</label>
                    <input type="date" id="of-fecha-cierre" class="form-input" autocomplete="off">
                    <span class="form-error" id="oerr-fecha"></span>
                </div>
                <div class="form-group full-width">
                    <label class="form-label">Descripción</label>
                    <textarea id="of-descripcion" class="form-textarea" rows="3" placeholder="Detalles de la oportunidad..." maxlength="500"></textarea>
                    <div class="form-counter"><span id="odesc-count">0</span>/500</div>
                    <span class="form-error" id="oerr-descripcion"></span>
                </div>
                <div class="form-actions">
                    <button type="submit" id="opor-guardar-btn" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button type="button" id="opor-cancelar-btn" class="btn-secondary">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="crm-toolbar">
        <div class="crm-search">
            <i class="fas fa-search"></i>
            <input type="text" id="opor-buscar" class="crm-search-input"
                   placeholder="Buscar por título, descripción o contacto...">
        </div>
        <div class="crm-filtros">
            <select id="opor-filtro-etapa" class="crm-select">
                <option value="">Todas las etapas</option>
                <option value="prospecto">Prospecto</option>
                <option value="propuesta">Propuesta</option>
                <option value="negociacion">Negociación</option>
                <option value="cerrada_ganada">Ganada</option>
                <option value="cerrada_perdida">Perdida</option>
            </select>
            <button class="btn-filtros-toggle" id="op-filtros-toggle" title="Filtros avanzados">
                <i class="fas fa-sliders-h"></i> Filtros
                <span class="filtros-badge" id="op-filtros-badge" style="display:none">0</span>
            </button>
        </div>
    </div>
    <div class="crm-filtros-avanzados" id="op-filtros-avanzados">
        <div class="crm-filtro-grupo">
            <label class="crm-filtro-label">Valor mín. (€)</label>
            <input type="number" id="op-filtro-valor-min" class="form-input form-input-sm" placeholder="0" min="0" step="1">
        </div>
        <div class="crm-filtro-grupo">
            <label class="crm-filtro-label">Valor máx. (€)</label>
            <input type="number" id="op-filtro-valor-max" class="form-input form-input-sm" placeholder="Sin límite" min="0" step="1">
        </div>
        <div class="crm-filtro-grupo">
            <label class="crm-filtro-label">Cierre desde</label>
            <input type="date" id="op-filtro-cierre-desde" class="form-input form-input-sm">
        </div>
        <div class="crm-filtro-grupo">
            <label class="crm-filtro-label">Cierre hasta</label>
            <input type="date" id="op-filtro-cierre-hasta" class="form-input form-input-sm">
        </div>
        <div class="crm-filtro-grupo">
            <label class="crm-filtro-label">Ordenar por</label>
            <div class="crm-orden-wrap">
                <select id="op-filtro-orden" class="crm-select">
                    <option value="created_at">Fecha creación</option>
                    <option value="titulo">Título</option>
                    <option value="valor">Valor</option>
                    <option value="etapa">Etapa</option>
                    <option value="fecha_cierre_esperada">Fecha cierre</option>
                </select>
                <button class="btn-dir" id="op-filtro-dir" data-dir="desc" title="Dirección">
                    <i class="fas fa-sort-amount-down"></i>
                </button>
            </div>
        </div>
        <button class="btn-filtros-clear" id="op-filtros-clear"><i class="fas fa-times"></i> Limpiar</button>
    </div>

    <!-- Pipeline board -->
    <div class="pipeline-board" id="opor-board">
        <div class="pipeline-col pipeline-col--prospecto">
            <div class="pipeline-col-header">
                <span class="pipeline-col-title"><i class="fas fa-seedling"></i> Prospecto</span>
                <span class="pipeline-col-count" id="cnt-prospecto">0</span>
            </div>
            <div class="pipeline-cards" id="cards-prospecto">
                <p class="pipeline-loading"><i class="fas fa-spinner fa-spin"></i></p>
            </div>
        </div>
        <div class="pipeline-col pipeline-col--propuesta">
            <div class="pipeline-col-header">
                <span class="pipeline-col-title"><i class="fas fa-file-alt"></i> Propuesta</span>
                <span class="pipeline-col-count" id="cnt-propuesta">0</span>
            </div>
            <div class="pipeline-cards" id="cards-propuesta"></div>
        </div>
        <div class="pipeline-col pipeline-col--negociacion">
            <div class="pipeline-col-header">
                <span class="pipeline-col-title"><i class="fas fa-handshake"></i> Negociación</span>
                <span class="pipeline-col-count" id="cnt-negociacion">0</span>
            </div>
            <div class="pipeline-cards" id="cards-negociacion"></div>
        </div>
        <div class="pipeline-col pipeline-col--ganada">
            <div class="pipeline-col-header">
                <span class="pipeline-col-title"><i class="fas fa-trophy"></i> Ganada</span>
                <span class="pipeline-col-count" id="cnt-cerrada_ganada">0</span>
            </div>
            <div class="pipeline-cards" id="cards-cerrada_ganada"></div>
        </div>
        <div class="pipeline-col pipeline-col--perdida">
            <div class="pipeline-col-header">
                <span class="pipeline-col-title"><i class="fas fa-times-circle"></i> Perdida</span>
                <span class="pipeline-col-count" id="cnt-cerrada_perdida">0</span>
            </div>
            <div class="pipeline-cards" id="cards-cerrada_perdida"></div>
        </div>
    </div>

    <!-- Panel detalle -->
    <div class="detalle-overlay" id="opor-detalle-overlay" style="display:none">
        <div class="detalle-panel" id="opor-detalle-panel">
            <div class="detalle-header">
                <div class="detalle-avatar opor-avatar" id="odet-avatar">
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="detalle-header-info">
                    <h3 id="odet-titulo"></h3>
                    <span class="detalle-empresa" id="odet-etapa-badge"></span>
                </div>
                <div class="detalle-header-actions">
                    <button class="btn-icon" id="odet-editar-btn" title="Editar"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon danger" id="odet-eliminar-btn" title="Eliminar"><i class="fas fa-trash"></i></button>
                    <button class="btn-icon" id="odet-cerrar-btn" title="Cerrar"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="detalle-body">

                <!-- Cambiar etapa -->
                <div class="opor-etapa-cambio">
                    <span class="detalle-label"><i class="fas fa-arrows-alt-h"></i> Mover a etapa</span>
                    <div class="opor-etapa-btns" id="odet-etapa-btns"></div>
                </div>

                <!-- Campos -->
                <div class="detalle-campos">
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-euro-sign"></i> Valor estimado</span>
                        <span class="detalle-valor" id="odet-valor"></span>
                    </div>
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-calendar-alt"></i> Cierre esperado</span>
                        <span class="detalle-valor" id="odet-fecha-cierre"></span>
                    </div>
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-user"></i> Contacto</span>
                        <span class="detalle-valor" id="odet-contacto"></span>
                    </div>
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-funnel-dollar"></i> Lead</span>
                        <span class="detalle-valor" id="odet-lead"></span>
                    </div>
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-calendar-plus"></i> Creada</span>
                        <span class="detalle-valor" id="odet-fecha-creacion"></span>
                    </div>
                </div>

                <!-- Descripción -->
                <div class="detalle-notas" id="odet-desc-bloque">
                    <span class="detalle-label"><i class="fas fa-align-left"></i> Descripción</span>
                    <p id="odet-descripcion"></p>
                </div>

                <!-- Actividades -->
                <div class="detalle-actividades">
                    <div class="detalle-seccion-titulo">
                        <span><i class="fas fa-history"></i> Actividades</span>
                        <button class="btn-icon-xs" id="odet-act-nuevo-btn" title="Nueva actividad"><i class="fas fa-plus"></i></button>
                    </div>
                    <div class="act-form-inline" id="odet-act-form" style="display:none">
                        <select id="odet-act-tipo" class="form-input form-input-sm">
                            <option value="nota">Nota</option>
                            <option value="llamada">Llamada</option>
                            <option value="reunion">Reunión</option>
                            <option value="tarea">Tarea</option>
                            <option value="email">Email</option>
                        </select>
                        <textarea id="odet-act-desc" class="form-textarea" rows="2" placeholder="Descripción de la actividad..." maxlength="500"></textarea>
                        <input type="date" id="odet-act-fecha" class="form-input form-input-sm">
                        <div class="act-form-actions">
                            <button id="odet-act-guardar" class="btn-primary btn-sm">Guardar</button>
                            <button id="odet-act-cancelar" class="btn-secondary btn-sm">Cancelar</button>
                        </div>
                    </div>
                    <ul class="det-actividades-lista" id="odet-act-lista">
                        <li class="det-act-vacio"><i class="fas fa-spinner fa-spin"></i> Cargando...</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
