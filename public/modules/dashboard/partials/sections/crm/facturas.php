<section id="facturas" class="content-section">
    <div class="section-header">
        <div>
            <h2 class="section-title">Facturas</h2>
            <p class="section-subtitle">Gestiona facturas vinculadas a tus contactos</p>
        </div>
        <div class="header-actions">
            <button class="btn-secondary" id="facturas-plantillas-btn">
                <i class="fas fa-palette"></i> Modelos
            </button>
            <button class="btn-primary" id="facturas-nueva-btn">
                <i class="fas fa-plus"></i> Nueva Factura
            </button>
        </div>
    </div>

    <div class="form-panel" id="facturas-form-panel">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title" id="facturas-form-titulo">Nueva Factura</h3>
            </div>
            <form id="facturas-form" class="form-grid" novalidate>
                <div class="form-group">
                    <label class="form-label">Contacto <span class="form-required">*</span></label>
                    <select id="fac-contacto" class="form-input"></select>
                    <span class="form-error" id="err-fac-contacto"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select id="fac-estado" class="form-input">
                        <option value="borrador">Borrador</option>
                        <option value="emitida">Emitida</option>
                        <option value="pagada">Pagada</option>
                        <option value="vencida">Vencida</option>
                        <option value="cancelada">Cancelada</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Modelo visual</label>
                    <select id="fac-plantilla" class="form-input">
                        <option value="">Diseño estándar</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha emision <span class="form-required">*</span></label>
                    <input type="date" id="fac-fecha-emision" class="form-input">
                    <span class="form-error" id="err-fac-fecha-emision"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha vencimiento</label>
                    <input type="date" id="fac-fecha-vencimiento" class="form-input">
                    <span class="form-error" id="err-fac-fecha-vencimiento"></span>
                </div>

                <div class="form-group full-width">
                    <div class="facturas-lineas-header">
                        <label class="form-label">Lineas de factura <span class="form-required">*</span></label>
                        <button type="button" class="btn-secondary btn-sm" id="fac-linea-add">
                            <i class="fas fa-plus"></i> Anadir linea
                        </button>
                    </div>
                    <div class="facturas-lineas" id="fac-lineas"></div>
                    <span class="form-error" id="err-fac-lineas"></span>
                </div>

                <div class="form-group full-width">
                    <label class="form-label">Notas</label>
                    <textarea id="fac-notas" class="form-textarea" rows="3" placeholder="Notas visibles internamente..." maxlength="1000"></textarea>
                </div>

                <div class="facturas-totales full-width">
                    <div><span>Base imponible</span><strong id="fac-base">0,00 EUR</strong></div>
                    <div><span>IVA</span><strong id="fac-iva">0,00 EUR</strong></div>
                    <div class="facturas-total-final"><span>Total</span><strong id="fac-total">0,00 EUR</strong></div>
                </div>

                <div class="form-actions">
                    <button type="submit" id="facturas-guardar-btn" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button type="button" id="facturas-cancelar-btn" class="btn-secondary">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="form-panel" id="plantillas-form-panel">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title" id="plantillas-form-titulo">Modelos de factura</h3>
                <button type="button" class="btn-secondary btn-sm" id="plantillas-cerrar-btn"><i class="fas fa-times"></i> Cerrar</button>
            </div>
            <p class="section-subtitle">Personaliza la imagen de tus facturas. Los modelos son privados para tu empresa.</p>
            <form id="plantillas-form" class="invoice-designer" novalidate>
                <input type="hidden" id="plantilla-id">
                <input type="hidden" id="plantilla-logo">
                <div class="invoice-designer-controls">
                <div class="form-group">
                    <label class="form-label">Nombre del modelo <span class="form-required">*</span></label>
                    <input id="plantilla-nombre" class="form-input" maxlength="100" placeholder="Ej. Factura corporativa">
                </div>
                <div class="form-group">
                    <label class="form-label">Logo</label>
                    <label class="logo-dropzone" id="plantilla-logo-drop" for="plantilla-logo-file">
                        <i class="fas fa-cloud-arrow-up"></i><span>Arrastra tu logo o pulsa para subirlo</span><small>PNG, JPG o WebP · máximo 2 MB</small>
                    </label>
                    <input id="plantilla-logo-file" type="file" accept="image/png,image/jpeg,image/webp" hidden>
                </div>
                <div class="form-group">
                    <label class="form-label">Color principal</label>
                    <input id="plantilla-primario" type="color" class="form-input" value="#1d4ed8">
                </div>
                <div class="form-group">
                    <label class="form-label">Color de fondo</label>
                    <input id="plantilla-secundario" type="color" class="form-input" value="#eff6ff">
                </div>
                <div class="form-group">
                    <label class="form-label">Tipografía</label>
                    <select id="plantilla-fuente" class="form-input">
                        <option value="Helvetica">Helvetica — moderna</option>
                        <option value="Times-Roman">Times — clásica</option>
                        <option value="Courier">Courier — técnica</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label class="form-label">Texto de pie</label>
                    <textarea id="plantilla-pie" class="form-textarea" rows="2" maxlength="500" placeholder="Gracias por confiar en nosotros."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Marca de agua</label>
                    <input id="plantilla-marca-agua" class="form-input" maxlength="120" placeholder="EJEMPLO · CONFIDENCIAL">
                </div>
                <div class="form-group">
                    <label class="form-label">Opacidad <span id="plantilla-opacidad-valor">10%</span></label>
                    <input id="plantilla-opacidad" type="range" min="0" max="35" value="10" class="form-input">
                </div>
                <div class="form-group full-width">
                    <label class="form-label">Estructura de la factura</label>
                    <p class="designer-help">Arrastra los bloques para decidir el orden del documento.</p>
                    <div class="designer-blocks" id="plantilla-bloques">
                        <button type="button" class="designer-block" draggable="true" data-block="cabecera"><i class="fas fa-grip-vertical"></i> Cabecera</button>
                        <button type="button" class="designer-block" draggable="true" data-block="cliente"><i class="fas fa-grip-vertical"></i> Cliente</button>
                        <button type="button" class="designer-block" draggable="true" data-block="lineas"><i class="fas fa-grip-vertical"></i> Líneas</button>
                        <button type="button" class="designer-block" draggable="true" data-block="totales"><i class="fas fa-grip-vertical"></i> Totales</button>
                        <button type="button" class="designer-block" draggable="true" data-block="pie"><i class="fas fa-grip-vertical"></i> Pie</button>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Guardar modelo</button>
                    <button type="button" class="btn-secondary" id="plantillas-limpiar-btn">Nuevo modelo</button>
                </div>
                </div>
                <aside class="invoice-preview-wrap">
                    <div class="invoice-preview-toolbar"><i class="fas fa-eye"></i> Vista previa en tiempo real</div>
                    <article class="invoice-preview" id="plantilla-preview">
                        <div class="preview-watermark" id="preview-watermark"></div>
                        <div class="preview-content" id="preview-content"></div>
                    </article>
                </aside>
            </form>
            <div class="table-container">
                <table class="data-table">
                    <thead><tr><th>Modelo</th><th>Estilo</th><th>Acciones</th></tr></thead>
                    <tbody id="plantillas-tbody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="detalle-overlay" id="factura-detalle-overlay" style="display:none">
        <div class="detalle-panel factura-detalle-panel">
            <div class="detalle-header">
                <div class="detalle-avatar factura-avatar" id="fac-det-avatar"><i class="fas fa-file-invoice"></i></div>
                <div class="detalle-header-info">
                    <h3 id="fac-det-numero"></h3>
                    <span id="fac-det-contacto" class="detalle-empresa"></span>
                </div>
                <div class="detalle-header-actions">
                    <button class="btn-icon" id="fac-det-pdf" title="Descargar PDF">
                        <i class="fas fa-file-pdf"></i>
                    </button>
                    <button class="btn-icon" id="fac-det-email" title="Enviar por email">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                    <button class="btn-icon" id="fac-det-editar" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon danger" id="fac-det-eliminar" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button class="btn-icon" id="fac-det-cerrar" title="Cerrar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="detalle-body">
                <div class="detalle-campos">
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-tag"></i> Estado</span>
                        <span class="detalle-valor" id="fac-det-estado"></span>
                    </div>
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-calendar-alt"></i> Fechas</span>
                        <span class="detalle-valor" id="fac-det-fechas"></span>
                    </div>
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-euro-sign"></i> Total</span>
                        <span class="detalle-valor" id="fac-det-total"></span>
                    </div>
                </div>
                <div class="factura-det-lineas">
                    <div class="detalle-seccion-titulo">
                        <span><i class="fas fa-list"></i> Lineas</span>
                    </div>
                    <div id="fac-det-lineas"></div>
                </div>
                <div class="detalle-notas" id="fac-det-notas-bloque">
                    <span class="detalle-label"><i class="fas fa-sticky-note"></i> Notas</span>
                    <p id="fac-det-notas"></p>
                </div>
            </div>
        </div>
    </div>

    <div class="crm-toolbar">
        <div class="crm-search">
            <i class="fas fa-search"></i>
            <input type="text" id="facturas-buscar" class="crm-search-input"
                   placeholder="Buscar por numero, contacto, empresa o email...">
        </div>
        <div class="crm-filtros">
            <button class="btn-secondary btn-sm" id="facturas-exportar-btn" title="Exportar a CSV">
                <i class="fas fa-file-export"></i> Exportar
            </button>
            <button class="btn-secondary btn-sm" id="facturas-importar-btn" title="Importar desde CSV">
                <i class="fas fa-file-import"></i> Importar
            </button>
            <input type="file" id="facturas-importar-input" accept=".csv" hidden>
            <button class="btn-filtros-toggle" id="fac-filtros-toggle" title="Filtros avanzados">
                <i class="fas fa-sliders-h"></i> Filtros
                <span class="filtros-badge" id="fac-filtros-badge" style="display:none">0</span>
            </button>
        </div>
    </div>
    <div class="crm-filtros-avanzados" id="fac-filtros-avanzados">
        <div class="crm-filtro-grupo">
            <label class="crm-filtro-label">Estado</label>
            <select id="fac-filtro-estado" class="crm-select">
                <option value="">Todos</option>
                <option value="borrador">Borrador</option>
                <option value="emitida">Emitida</option>
                <option value="pagada">Pagada</option>
                <option value="vencida">Vencida</option>
                <option value="cancelada">Cancelada</option>
            </select>
        </div>
        <div class="crm-filtro-grupo">
            <label class="crm-filtro-label">Emitida desde</label>
            <input type="date" id="fac-filtro-desde" class="form-input form-input-sm">
        </div>
        <div class="crm-filtro-grupo">
            <label class="crm-filtro-label">Hasta</label>
            <input type="date" id="fac-filtro-hasta" class="form-input form-input-sm">
        </div>
        <div class="crm-filtro-grupo">
            <label class="crm-filtro-label">Ordenar por</label>
            <div class="crm-orden-wrap">
                <select id="fac-filtro-orden" class="crm-select">
                    <option value="fecha_emision">Fecha emision</option>
                    <option value="numero">Numero</option>
                    <option value="fecha_vencimiento">Vencimiento</option>
                    <option value="total">Total</option>
                    <option value="estado">Estado</option>
                </select>
                <button class="btn-dir" id="fac-filtro-dir" data-dir="desc" title="Direccion">
                    <i class="fas fa-sort-amount-down"></i>
                </button>
            </div>
        </div>
        <button class="btn-filtros-clear" id="fac-filtros-clear"><i class="fas fa-times"></i> Limpiar</button>
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
                        <th>Vencimiento</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="facturas-tbody">
                    <tr>
                        <td colspan="7" class="crm-loading">
                            <i class="fas fa-spinner fa-spin"></i> Cargando...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="crm-paginacion" id="fac-paginacion"></div>
</section>
