<section id="recurrentes" class="content-section">
    <div class="section-header">
        <div>
            <h2 class="section-title">Facturas Recurrentes</h2>
            <p class="section-subtitle">Plantillas que generan una factura real automáticamente cada cierto tiempo (mensual, trimestral o anual)</p>
        </div>
        <button class="btn-primary" id="recurrentes-nueva-btn">
            <i class="fas fa-plus"></i> Nueva Plantilla
        </button>
    </div>

    <div class="form-panel" id="recurrentes-form-panel">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title" id="recurrentes-form-titulo">Nueva Plantilla</h3>
            </div>
            <form id="recurrentes-form" class="form-grid" novalidate>
                <div class="form-group">
                    <label class="form-label">Nombre interno <span class="form-required">*</span></label>
                    <input type="text" id="rec-nombre" class="form-input" placeholder="Ej: Mantenimiento mensual - Cliente X" maxlength="150">
                    <span class="form-error" id="err-rec-nombre"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Contacto <span class="form-required">*</span></label>
                    <select id="rec-contacto" class="form-input"></select>
                    <span class="form-error" id="err-rec-contacto"></span>
                </div>

                <div class="form-group">
                    <label class="form-label">Periodicidad</label>
                    <select id="rec-periodicidad" class="form-input">
                        <option value="mensual">Mensual</option>
                        <option value="trimestral">Trimestral</option>
                        <option value="anual">Anual</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Día de generación (a partir del 2º ciclo)</label>
                    <input type="number" id="rec-dia-generacion" class="form-input" min="1" max="28" value="1">
                </div>
                <div class="form-group">
                    <label class="form-label">Días para vencimiento</label>
                    <input type="number" id="rec-dias-vencimiento" class="form-input" min="0" max="365" value="30">
                </div>

                <div class="form-group">
                    <label class="form-label">Fecha de inicio <span class="form-required">*</span></label>
                    <input type="date" id="rec-fecha-inicio" class="form-input">
                    <span class="form-error" id="err-rec-fecha-inicio"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha de fin (opcional)</label>
                    <input type="date" id="rec-fecha-fin" class="form-input">
                    <span class="form-error" id="err-rec-fecha-fin"></span>
                </div>
                <div class="form-group dom-ssl-group">
                    <label class="dom-ssl-toggle">
                        <input type="checkbox" id="rec-activa" checked>
                        <span class="dom-ssl-label">Plantilla activa</span>
                    </label>
                </div>
                <div class="form-group dom-ssl-group">
                    <label class="dom-ssl-toggle">
                        <input type="checkbox" id="rec-enviar-email">
                        <span class="dom-ssl-label">Enviar por email automáticamente al generarse</span>
                    </label>
                </div>

                <div class="form-group full-width">
                    <div class="facturas-lineas-header">
                        <label class="form-label">Líneas <span class="form-required">*</span></label>
                        <button type="button" class="btn-secondary btn-sm" id="rec-linea-add">
                            <i class="fas fa-plus"></i> Añadir línea
                        </button>
                    </div>
                    <div class="facturas-lineas" id="rec-lineas"></div>
                    <span class="form-error" id="err-rec-lineas"></span>
                </div>

                <div class="form-group full-width">
                    <label class="form-label">Notas</label>
                    <textarea id="rec-notas" class="form-textarea" rows="2" maxlength="1000"></textarea>
                </div>

                <div class="facturas-totales full-width">
                    <div><span>Base imponible</span><strong id="rec-base">0,00 EUR</strong></div>
                    <div><span>IVA</span><strong id="rec-iva">0,00 EUR</strong></div>
                    <div class="facturas-total-final"><span>Total por ciclo</span><strong id="rec-total">0,00 EUR</strong></div>
                </div>

                <div class="form-actions">
                    <button type="submit" id="recurrentes-guardar-btn" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button type="button" id="recurrentes-cancelar-btn" class="btn-secondary">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="detalle-overlay" id="recurrente-detalle-overlay" style="display:none">
        <div class="detalle-panel factura-detalle-panel">
            <div class="detalle-header">
                <div class="detalle-avatar factura-avatar" id="rec-det-avatar"><i class="fas fa-rotate"></i></div>
                <div class="detalle-header-info">
                    <h3 id="rec-det-nombre"></h3>
                    <span id="rec-det-contacto" class="detalle-empresa"></span>
                </div>
                <div class="detalle-header-actions">
                    <button class="btn-icon" id="rec-det-editar" title="Editar"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon danger" id="rec-det-eliminar" title="Eliminar"><i class="fas fa-trash"></i></button>
                    <button class="btn-icon" id="rec-det-cerrar" title="Cerrar"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="detalle-body">
                <button class="btn-primary presupuesto-convertir-btn" id="rec-det-generar">
                    <i class="fas fa-bolt"></i> Generar factura ahora
                </button>
                <div class="detalle-campos">
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-repeat"></i> Periodicidad</span>
                        <span class="detalle-valor" id="rec-det-periodicidad"></span>
                    </div>
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-calendar-check"></i> Próxima generación</span>
                        <span class="detalle-valor" id="rec-det-proxima"></span>
                    </div>
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-euro-sign"></i> Total por ciclo</span>
                        <span class="detalle-valor" id="rec-det-total"></span>
                    </div>
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-toggle-on"></i> Estado</span>
                        <span class="detalle-valor" id="rec-det-estado"></span>
                    </div>
                </div>
                <div class="factura-det-lineas">
                    <div class="detalle-seccion-titulo"><span><i class="fas fa-list"></i> Líneas</span></div>
                    <div id="rec-det-lineas"></div>
                </div>
                <div class="factura-det-lineas">
                    <div class="detalle-seccion-titulo"><span><i class="fas fa-history"></i> Facturas generadas</span></div>
                    <div id="rec-det-historial"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="crm-toolbar">
        <div class="crm-search">
            <i class="fas fa-search"></i>
            <input type="text" id="recurrentes-buscar" class="crm-search-input" placeholder="Buscar por nombre o contacto...">
        </div>
        <div class="crm-filtros">
            <select id="rec-filtro-activa" class="crm-select">
                <option value="">Todas</option>
                <option value="1">Activas</option>
                <option value="0">Pausadas</option>
            </select>
        </div>
    </div>

    <div class="content-card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Plantilla</th>
                        <th>Contacto</th>
                        <th>Periodicidad</th>
                        <th>Próxima generación</th>
                        <th>Importe estimado</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="recurrentes-tbody">
                    <tr><td colspan="7" class="crm-loading"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="crm-paginacion" id="rec-paginacion"></div>
</section>
