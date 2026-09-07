<section id="presupuestos" class="content-section">
    <div class="section-header">
        <div>
            <h2 class="section-title">Presupuestos</h2>
            <p class="section-subtitle">Envía presupuestos a tus contactos y conviértelos en factura al aceptarse</p>
        </div>
        <button class="btn-primary" id="presupuestos-nueva-btn">
            <i class="fas fa-plus"></i> Nuevo Presupuesto
        </button>
    </div>

    <div class="form-panel" id="presupuestos-form-panel">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title" id="presupuestos-form-titulo">Nuevo Presupuesto</h3>
            </div>
            <form id="presupuestos-form" class="form-grid" novalidate>
                <div class="form-group">
                    <label class="form-label">Contacto <span class="form-required">*</span></label>
                    <select id="pre-contacto" class="form-input"></select>
                    <span class="form-error" id="err-pre-contacto"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select id="pre-estado" class="form-input">
                        <option value="borrador">Borrador</option>
                        <option value="enviado">Enviado</option>
                        <option value="aceptado">Aceptado</option>
                        <option value="rechazado">Rechazado</option>
                        <option value="expirado">Expirado</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha emision <span class="form-required">*</span></label>
                    <input type="date" id="pre-fecha-emision" class="form-input">
                    <span class="form-error" id="err-pre-fecha-emision"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Válido hasta</label>
                    <input type="date" id="pre-fecha-validez" class="form-input">
                    <span class="form-error" id="err-pre-fecha-validez"></span>
                </div>

                <div class="form-group full-width">
                    <div class="facturas-lineas-header">
                        <label class="form-label">Lineas del presupuesto <span class="form-required">*</span></label>
                        <button type="button" class="btn-secondary btn-sm" id="pre-linea-add">
                            <i class="fas fa-plus"></i> Anadir linea
                        </button>
                    </div>
                    <div class="facturas-lineas" id="pre-lineas"></div>
                    <span class="form-error" id="err-pre-lineas"></span>
                </div>

                <div class="form-group full-width">
                    <label class="form-label">Notas</label>
                    <textarea id="pre-notas" class="form-textarea" rows="3" placeholder="Notas visibles internamente..." maxlength="1000"></textarea>
                </div>

                <div class="facturas-totales full-width">
                    <div><span>Base imponible</span><strong id="pre-base">0,00 EUR</strong></div>
                    <div><span>IVA</span><strong id="pre-iva">0,00 EUR</strong></div>
                    <div class="facturas-total-final"><span>Total</span><strong id="pre-total">0,00 EUR</strong></div>
                </div>

                <div class="form-actions">
                    <button type="submit" id="presupuestos-guardar-btn" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button type="button" id="presupuestos-cancelar-btn" class="btn-secondary">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="detalle-overlay" id="presupuesto-detalle-overlay" style="display:none">
        <div class="detalle-panel factura-detalle-panel">
            <div class="detalle-header">
                <div class="detalle-avatar factura-avatar" id="pre-det-avatar"><i class="fas fa-file-signature"></i></div>
                <div class="detalle-header-info">
                    <h3 id="pre-det-numero"></h3>
                    <span id="pre-det-contacto" class="detalle-empresa"></span>
                </div>
                <div class="detalle-header-actions">
                    <button class="btn-icon" id="pre-det-pdf" title="Descargar PDF">
                        <i class="fas fa-file-pdf"></i>
                    </button>
                    <button class="btn-icon" id="pre-det-email" title="Enviar por email">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                    <button class="btn-icon" id="pre-det-editar" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon danger" id="pre-det-eliminar" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button class="btn-icon" id="pre-det-cerrar" title="Cerrar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="detalle-body">
                <button class="btn-primary presupuesto-convertir-btn" id="pre-det-convertir" style="display:none">
                    <i class="fas fa-file-invoice-dollar"></i> Convertir en factura
                </button>
                <div class="presupuesto-convertido-aviso" id="pre-det-convertido-aviso" style="display:none">
                    <i class="fas fa-check-circle"></i> Convertido en factura <strong id="pre-det-factura-numero"></strong>
                </div>
                <div class="presupuesto-link-confirmacion" id="pre-det-link-bloque" style="display:none">
                    <span class="detalle-label"><i class="fas fa-link"></i> Enlace de confirmación para el cliente</span>
                    <div class="presupuesto-link-row">
                        <input type="text" id="pre-det-link-input" class="form-input" readonly>
                        <button type="button" class="btn-secondary btn-sm" id="pre-det-link-copiar">
                            <i class="fas fa-copy"></i> Copiar
                        </button>
                    </div>
                </div>
                <div class="detalle-campos">
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-tag"></i> Estado</span>
                        <span class="detalle-valor" id="pre-det-estado"></span>
                    </div>
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-calendar-alt"></i> Fechas</span>
                        <span class="detalle-valor" id="pre-det-fechas"></span>
                    </div>
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-euro-sign"></i> Total</span>
                        <span class="detalle-valor" id="pre-det-total"></span>
                    </div>
                </div>
                <div class="factura-det-lineas">
                    <div class="detalle-seccion-titulo">
                        <span><i class="fas fa-list"></i> Lineas</span>
                    </div>
                    <div id="pre-det-lineas"></div>
                </div>
                <div class="detalle-notas" id="pre-det-notas-bloque">
                    <span class="detalle-label"><i class="fas fa-sticky-note"></i> Notas</span>
                    <p id="pre-det-notas"></p>
                </div>
            </div>
        </div>
    </div>

    <div class="crm-toolbar">
        <div class="crm-search">
            <i class="fas fa-search"></i>
            <input type="text" id="presupuestos-buscar" class="crm-search-input"
                   placeholder="Buscar por numero, contacto, empresa o email...">
        </div>
        <div class="crm-filtros">
            <button class="btn-secondary btn-sm" id="presupuestos-exportar-btn" title="Exportar a CSV">
                <i class="fas fa-file-export"></i> Exportar
            </button>
            <button class="btn-secondary btn-sm" id="presupuestos-importar-btn" title="Importar desde CSV">
                <i class="fas fa-file-import"></i> Importar
            </button>
            <input type="file" id="presupuestos-importar-input" accept=".csv" hidden>
            <button class="btn-filtros-toggle" id="pre-filtros-toggle" title="Filtros avanzados">
                <i class="fas fa-sliders-h"></i> Filtros
                <span class="filtros-badge" id="pre-filtros-badge" style="display:none">0</span>
            </button>
        </div>
    </div>
    <div class="crm-filtros-avanzados" id="pre-filtros-avanzados">
        <div class="crm-filtro-grupo">
            <label class="crm-filtro-label">Estado</label>
            <select id="pre-filtro-estado" class="crm-select">
                <option value="">Todos</option>
                <option value="borrador">Borrador</option>
                <option value="enviado">Enviado</option>
                <option value="aceptado">Aceptado</option>
                <option value="rechazado">Rechazado</option>
                <option value="expirado">Expirado</option>
                <option value="convertido">Convertido</option>
            </select>
        </div>
        <div class="crm-filtro-grupo">
            <label class="crm-filtro-label">Emitido desde</label>
            <input type="date" id="pre-filtro-desde" class="form-input form-input-sm">
        </div>
        <div class="crm-filtro-grupo">
            <label class="crm-filtro-label">Hasta</label>
            <input type="date" id="pre-filtro-hasta" class="form-input form-input-sm">
        </div>
        <div class="crm-filtro-grupo">
            <label class="crm-filtro-label">Ordenar por</label>
            <div class="crm-orden-wrap">
                <select id="pre-filtro-orden" class="crm-select">
                    <option value="fecha_emision">Fecha emision</option>
                    <option value="numero">Numero</option>
                    <option value="fecha_validez">Validez</option>
                    <option value="total">Total</option>
                    <option value="estado">Estado</option>
                </select>
                <button class="btn-dir" id="pre-filtro-dir" data-dir="desc" title="Direccion">
                    <i class="fas fa-sort-amount-down"></i>
                </button>
            </div>
        </div>
        <button class="btn-filtros-clear" id="pre-filtros-clear"><i class="fas fa-times"></i> Limpiar</button>
    </div>

    <div class="content-card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Numero</th>
                        <th>Contacto</th>
                        <th>Estado</th>
                        <th>Emision</th>
                        <th>Validez</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="presupuestos-tbody">
                    <tr>
                        <td colspan="7" class="crm-loading">
                            <i class="fas fa-spinner fa-spin"></i> Cargando...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="crm-paginacion" id="pre-paginacion"></div>
</section>
