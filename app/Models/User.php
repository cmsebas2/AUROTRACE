<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\AuditableTrait;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ============================================================================
 * MODELO DE USUARIO E IDENTIDAD ELECTRÓNICA (IAM / CFR 21) - User
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Representa a cada persona autorizada en la planta (Operarios, Inspectores de
 * Calidad, Dirección Técnica, Administradores).
 *
 * Características clave:
 * - Usa `SoftDeletes`: Si un empleado se retira, su cuenta se desactiva (`deleted_at`),
 *   pero su historial de firmas en lotes antiguos jamás se pierde (Artículo 4 GEMINI.md).
 * - Control de Permisos (RBAC): El método `hasPermission()` consulta qué permisos
 *   tiene asignado el rol del usuario.
 * ============================================================================
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, AuditableTrait;

    /**
     * Campos asignables masivamente.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'pin_firma',
        'area',
        'signature_level',
        'custom_permissions',
        'failed_login_attempts',
        'last_login_at',
        'password_changed_at',
    ];

    /**
     * Campos ocultos al convertir el modelo a JSON o array.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'pin_firma',
        'remember_token',
    ];

    /**
     * Conversión automática de tipos de datos (Fechas y Contraseña encriptada).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'   => 'datetime',
            'last_login_at'       => 'datetime',
            'password_changed_at' => 'datetime',
            'password'            => 'hashed',
            'custom_permissions'  => 'array',
        ];
    }

    /**
     * Catálogo Maestro de Permisos de AUROTRACE agrupado por los 9 Módulos del ERP.
     * Sirve como única fuente de verdad para la interfaz IAM, el Seeder y el Simulador de Accesos.
     */
    public static function permissionCatalog(): array
    {
        return [
            'dashboard' => [
                'title'       => '1. Centro de Monitoreo & KPIs',
                'icon'        => 'fa-chart-pie',
                'color'       => 'cyan',
                'route_label' => '/dashboard',
                'description' => 'Visualización en tiempo real del estado de planta, lotes activos, codificación y liberaciones.',
                'permissions' => [
                    'ver_dashboard' => [
                        'label' => 'Ver Centro de Monitoreo (Dashboard)',
                        'desc'  => 'Permite acceder al panel principal de indicadores KPIs de planta y radar de lotes.',
                        'type'  => 'LECTURA',
                    ],
                ],
            ],
            'productos' => [
                'title'       => '2. Catálogo Maestro & Fórmulas (EBR)',
                'icon'        => 'fa-flask',
                'color'       => 'blue',
                'route_label' => '/productos · /productos/crear · /productos/{id}/editar',
                'description' => 'Alta de productos (Paso 1: Activos %, Presentaciones g/Kg/mL/L + Envase, Vida Útil en Meses, Maquiladores + Lote Mín/Máx Kg/L) y Paso 2 (Fórmula Granel, BOM e Instructivos EBR).',
                'permissions' => [
                    'ver_productos' => [
                        'label' => 'Consultar Catálogo, Buscador y Filtros de Productos',
                        'desc'  => 'Permite ver el catálogo en /productos, filtrar por forma farmacéutica, maquilador o ICA y consultar fichas técnicas.',
                        'type'  => 'LECTURA',
                    ],
                    'crear_ficha_inicial_producto' => [
                        'label' => 'Crear Producto · Paso 1: Ficha Inicial (/productos/crear)',
                        'desc'  => 'Autoriza registrar nuevos productos con Nombre, Registro ICA, Forma Farmacéutica libre, Vida Útil (Meses), Principios Activos (%) e Imagen.',
                        'type'  => 'MAKER',
                    ],
                    'configurar_presentaciones_envases' => [
                        'label' => 'Gestionar Presentaciones (g, Kg, mL, L) y Tipo de Envase',
                        'desc'  => 'Permite agregar o modificar presentaciones comerciales seleccionando su unidad (g, Kg, mL, L) y su tipo de envase primario.',
                        'type'  => 'MAKER',
                    ],
                    'configurar_maquiladores_lotes_producto' => [
                        'label' => 'Asignar Maquiladores y Tamaño de Lote Mín/Máx (Kg / L)',
                        'desc'  => 'Autoriza definir uno o varios maquiladores por producto junto con su tamaño de lote mínimo y máximo en Kg o L.',
                        'type'  => 'MAKER',
                    ],
                    'editar_ficha_producto' => [
                        'label' => 'Editar Ficha Inicial del Producto (/productos/{id}/editar)',
                        'desc'  => 'Permite editar los datos creados en el Paso 1 (datos generales, activos %, presentaciones, envases y maquiladores).',
                        'type'  => 'MAKER',
                    ],
                    'gestionar_productos_formulas' => [
                        'label' => 'Agregar y Configurar Fórmula Maestra (/productos/{id})',
                        'desc'  => 'Autoriza usar el botón «Agregar / Configurar Fórmula Maestra» en la ficha del producto para registrar materias primas a granel y materiales de empaque.',
                        'type'  => 'MAKER',
                    ],
                    'configurar_instructivos_ebr' => [
                        'label' => 'Configurar Instructivos EBR (Master Builder)',
                        'desc'  => 'Permite diseñar las etapas de manufactura, tiempos, RPM, temperaturas y controles IPC.',
                        'type'  => 'MAKER',
                    ],
                    'imprimir_ficha_tecnica' => [
                        'label' => 'Imprimir / Exportar Ficha Técnica Oficial',
                        'desc'  => 'Habilita la generación e impresión del documento PDF de fórmula maestra.',
                        'type'  => 'LECTURA',
                    ],
                    'eliminar_producto_catalogo' => [
                        'label' => 'Eliminar Productos del Catálogo Maestro',
                        'desc'  => 'Permiso crítico para eliminar definitivamente un producto y su fórmula del catálogo.',
                        'type'  => 'ADMIN',
                    ],
                ],
            ],
            'ordenes_op' => [
                'title'       => '3. Órdenes de Producción (OP)',
                'icon'        => 'fa-file-signature',
                'color'       => 'indigo',
                'route_label' => '/ops/*',
                'description' => 'Emisión de OPs (A3PPR0007), Ajuste de Principios Activos (A4PPR0007), Codificado (A6PPR0007) y Monitoreo.',
                'permissions' => [
                    'crear_op' => [
                        'label' => 'Emitir Orden de Producción (A3PPR0007)',
                        'desc'  => 'Permite iniciar y crear nuevas Órdenes de Producción con explosión de materiales.',
                        'type'  => 'MAKER',
                    ],
                    'aprobar_op' => [
                        'label' => 'Aprobar / Verificar Orden de Producción',
                        'desc'  => 'Autoriza la firma de verificación de apertura de una Orden de Producción.',
                        'type'  => 'CHECKER',
                    ],
                    'ver_monitoreo_ops' => [
                        'label' => 'Monitoreo de OPs Activas en Planta',
                        'desc'  => 'Acceso al tablero de supervisión de lotes activos, fases EBR y progreso en planta.',
                        'type'  => 'LECTURA',
                    ],
                    'registrar_ajuste_activos' => [
                        'label' => 'Registrar Ajuste de Principios Activos (A4PPR0007)',
                        'desc'  => 'Permite calcular y firmar como realizador el ajuste estequiométrico por potencia/humedad.',
                        'type'  => 'MAKER',
                    ],
                    'verificar_ajuste_activos' => [
                        'label' => 'Verificar Ajuste de Principios Activos (QA / DT)',
                        'desc'  => 'Doble verificación (Checker) sobre los cálculos de ajuste de principios activos.',
                        'type'  => 'CHECKER',
                    ],
                    'solicitar_aprobacion_codificado' => [
                        'label' => 'Solicitar Codificado de Lote y Vencimiento (A6PPR0007)',
                        'desc'  => 'Permite diligenciar y firmar la solicitud de codificado de envase/empaque.',
                        'type'  => 'MAKER',
                    ],
                    'aprobar_codificado' => [
                        'label' => 'Aprobar Codificado y Etiquetado (Checker)',
                        'desc'  => 'Autoriza verificar y liberar la impresión de lote y fecha de vencimiento.',
                        'type'  => 'CHECKER',
                    ],
                    'eliminar_op' => [
                        'label' => 'Anular / Eliminar Orden de Producción',
                        'desc'  => 'Permiso crítico para eliminar una OP activa exigiendo firma electrónica y motivo.',
                        'type'  => 'ADMIN',
                    ],
                ],
            ],
            'manufactura_ebr' => [
                'title'       => '4. Ejecución de Lotes en Planta (Flujo EBR)',
                'icon'        => 'fa-industry',
                'color'       => 'amber',
                'route_label' => '/ops/ejecucion · /batch/*',
                'description' => 'Ejecución paso a paso en planta: Conciliación, Despeje de Línea, Dispensación, Fabricación y Envase.',
                'permissions' => [
                    'ejecutar_manufactura' => [
                        'label' => 'Acceso a Terminal de Ejecución de Lotes',
                        'desc'  => 'Habilita la vista operativa de lotes en ejecución para operarios y supervisores.',
                        'type'  => 'MAKER',
                    ],
                    'registrar_conciliacion_materiales' => [
                        'label' => 'Paso A: Conciliación y Devolución de Materiales',
                        'desc'  => 'Permite registrar cantidades recibidas, usadas, mermas y firmar conciliación.',
                        'type'  => 'MAKER',
                    ],
                    'realizar_despeje_y_muestreo' => [
                        'label' => 'Paso B: Ejecutar Despeje de Línea y Muestreo',
                        'desc'  => 'Permite evaluar el checklist de limpieza y despeje de área antes de producir.',
                        'type'  => 'MAKER',
                    ],
                    'registrar_manufactura_y_pesaje' => [
                        'label' => 'Paso C y D: Dispensación, Pesaje y Fabricación',
                        'desc'  => 'Habilita al operario para registrar pesajes en balanza y etapas de manufactura (RPM, Temp).',
                        'type'  => 'MAKER',
                    ],
                    'verificacion_controles_en_proceso' => [
                        'label' => 'Verificación QA en Proceso (Despejes, Pesos e IPC)',
                        'desc'  => 'Firma de Segunda Persona (Checker QA) en despejes de línea, etapas críticas y envase.',
                        'type'  => 'CHECKER',
                    ],
                    'registrar_envasado_acondicionamiento' => [
                        'label' => 'Paso E: Envasado y Control de Pesos (A3PPR0010)',
                        'desc'  => 'Permite registrar controles de peso en envasado, unidades obtenidas y rendimiento.',
                        'type'  => 'MAKER',
                    ],
                ],
            ],
            'calidad_qa' => [
                'title'       => '5. Aseguramiento de Calidad (QA) & COAs',
                'icon'        => 'fa-shield-halved',
                'color'       => 'emerald',
                'route_label' => '/calidad · /ops/calidad',
                'description' => 'Portal de Dictamen de Calidad, gestión de Certificados de Análisis (COAs) y control de cuarentena.',
                'permissions' => [
                    'ver_aseguramiento_calidad' => [
                        'label' => 'Acceso al Portal de Dictamen de Calidad (QA)',
                        'desc'  => 'Habilita el módulo central de Aseguramiento de Calidad y bandeja de solicitudes pendientes.',
                        'type'  => 'CHECKER',
                    ],
                    'registrar_coas' => [
                        'label' => 'Cargar y Registrar Certificados de Análisis (COAs)',
                        'desc'  => 'Permite adjuntar PDFs de COAs y registrar resultados fisicoquímicos/microbiológicos.',
                        'type'  => 'MAKER',
                    ],
                    'aprobar_coas' => [
                        'label' => 'Aprobar, Firmar y Unificar Dossier de COAs',
                        'desc'  => 'Autoriza la revisión final y firma electrónica de conformidad de los COAs del lote.',
                        'type'  => 'CHECKER',
                    ],
                    'dictaminar_calidad_qa' => [
                        'label' => 'Emitir Dictamen de Calidad (Aprobado / Cuarentena)',
                        'desc'  => 'Permite cambiar el dictamen oficial de calidad sobre órdenes e inspecciones.',
                        'type'  => 'FIRMA_CFR21',
                    ],
                ],
            ],
            'batch_records_genealogia' => [
                'title'       => '6. Batch Records & Genealogía 360°',
                'icon'        => 'fa-book-medical',
                'color'       => 'purple',
                'route_label' => '/batch-records · /genealogia',
                'description' => 'Expedientes acumulativos de lote, descarga de Master PDF y Liberación Final 21 CFR Part 11.',
                'permissions' => [
                    'ver_expedientes_batch_records' => [
                        'label' => 'Ver Expedientes Batch Record y Descargar PDF',
                        'desc'  => 'Acceso al repositorio de expedientes históricos y activos con exportación de dossier PDF.',
                        'type'  => 'LECTURA',
                    ],
                    'ver_genealogia' => [
                        'label' => 'Consultar Genealogía 360° y Trazabilidad Total',
                        'desc'  => 'Permite inspeccionar el árbol completo del lote: insumos, equipos, firmas y rendimientos.',
                        'type'  => 'LECTURA',
                    ],
                    'liberacion_final_lote' => [
                        'label' => 'Ejecutar Proceso de Liberación Final de Lote',
                        'desc'  => 'Habilita la pantalla y el formulario de dictamen final para liberar producto terminado.',
                        'type'  => 'FIRMA_CFR21',
                    ],
                    'firmar_liberacion' => [
                        'label' => 'Firma Certificadora de Liberación (QA / Dirección Técnica)',
                        'desc'  => 'Autoriza estampar la firma electrónica definitiva que libera el lote para despacho.',
                        'type'  => 'FIRMA_CFR21',
                    ],
                ],
            ],
            'archivo_3d' => [
                'title'       => '7. Consultas BR · Sala de Archivo Físico 3D',
                'icon'        => 'fa-boxes-packing',
                'color'       => 'teal',
                'route_label' => '/consultas-br',
                'description' => 'Gemelo digital 3D de los Racks y 131 Archivadores físicos de custodia documental de Batch Records.',
                'permissions' => [
                    'ver_modulo_archivo_3d' => [
                        'label' => 'Consultar Mapa 3D de Archivo Físico',
                        'desc'  => 'Permite buscar lotes en los archivadores físicos y visualizar su posición en el Rack 3D.',
                        'type'  => 'LECTURA',
                    ],
                    'gestionar_archivo_fisico_3d' => [
                        'label' => 'Asignar y Reubicar Cajas/Lotes en Archivo 3D',
                        'desc'  => 'Autoriza asignar ubicaciones físicas en archivadores y actualizar fechas de custodia.',
                        'type'  => 'MAKER',
                    ],
                ],
            ],
            'maquilas' => [
                'title'       => '8. Cadena de Suministro · Maquilas Externas',
                'icon'        => 'fa-truck-ramp-box',
                'color'       => 'orange',
                'route_label' => '/maquilas',
                'description' => 'Control de producción tercerizada (Res. ICA 062542): envíos, entregas parciales, recepción BR y dictamen.',
                'permissions' => [
                    'acceso_maquilas_externas' => [
                        'label' => 'Ver Tablero y Radar de Maquilas Externas',
                        'desc'  => 'Acceso general al módulo de seguimiento de órdenes de producción en maquiladores.',
                        'type'  => 'LECTURA',
                    ],
                    'gestionar_ordenes_maquila' => [
                        'label' => 'Crear, Editar y Enviar Órdenes al Maquilador',
                        'desc'  => 'Permite emitir nuevas órdenes de maquila, cargar documentos de salida y despacharlas.',
                        'type'  => 'MAKER',
                    ],
                    'registrar_recepcion_maquila' => [
                        'label' => 'Registrar Entregas, Recepción y Llegada de BR',
                        'desc'  => 'Autoriza registrar entregas parciales de producto y la recepción del Batch Record físico.',
                        'type'  => 'MAKER',
                    ],
                    'revision_dt_maquila' => [
                        'label' => 'Aprobar Revisión Dirección Técnica (Maquila)',
                        'desc'  => 'Firma de aval técnico sobre el expediente Batch Record recibido del maquilador.',
                        'type'  => 'CHECKER',
                    ],
                    'revision_calidad_maquila' => [
                        'label' => 'Aprobar Revisión y Liberación QA (Maquila)',
                        'desc'  => 'Dictamen final de Aseguramiento de Calidad y asignación de archivo para lotes de maquila.',
                        'type'  => 'FIRMA_CFR21',
                    ],
                    'cerrar_eliminar_maquila' => [
                        'label' => 'Cerrar o Anular Órdenes de Maquila',
                        'desc'  => 'Permite dar por cerrada una orden completada o eliminar registros erróneos.',
                        'type'  => 'ADMIN',
                    ],
                    'acceder_reacondicionamiento' => [
                        'label' => 'Gestionar Reacondicionamiento en Maquilas',
                        'desc'  => 'Habilita funciones especiales de reacondicionamiento y liberaciones asociadas.',
                        'type'  => 'MAKER',
                    ],
                ],
            ],
            'iam_seguridad' => [
                'title'       => '9. Administración IAM, Inventario & Auditoría CFR 21',
                'icon'        => 'fa-user-lock',
                'color'       => 'rose',
                'route_label' => '/users',
                'description' => 'Gobierno de identidades, matriz RBAC, control de inventarios FEFO y bitácora forense inmutable.',
                'permissions' => [
                    'gestionar_usuarios_roles' => [
                        'label' => 'Administrar Usuarios, Credenciales y Estados',
                        'desc'  => 'Permite crear usuarios, editar roles, desbloquear cuentas e inactivar personal.',
                        'type'  => 'ADMIN',
                    ],
                    'eliminar_usuario_definitivo' => [
                        'label' => 'Eliminar Usuarios Definitivamente (Hard Delete)',
                        'desc'  => 'Permite eliminar de forma permanente un usuario del directorio /users conservando la trazabilidad en el Audit Trail.',
                        'type'  => 'ADMIN',
                    ],
                    'gestionar_ajustes_sistema' => [
                        'label' => 'Configurar Matriz RBAC y Roles del Sistema',
                        'desc'  => 'Autoriza modificar los permisos de los roles y asignar permisos individuales.',
                        'type'  => 'ADMIN',
                    ],
                    'ver_audit_trail' => [
                        'label' => 'Inspeccionar Audit Trail Forense (21 CFR Part 11)',
                        'desc'  => 'Acceso al registro inmutable de eventos, cambios de valores (Antes/Después) e IPs.',
                        'type'  => 'ADMIN',
                    ],
                    'ver_auditoria' => [
                        'label' => 'Monitorear Alertas de Seguridad y Desviaciones BPM',
                        'desc'  => 'Permite filtrar intentos fallidos de acceso, bloqueos de cuarentena y alertas del 5%.',
                        'type'  => 'CHECKER',
                    ],
                    'gestionar_inventario' => [
                        'label' => 'Control de Inventarios FEFO y Ajustes de Stock',
                        'desc'  => 'Autoriza movimientos y ajustes manuales de inventario bajo firma electrónica.',
                        'type'  => 'MAKER',
                    ],
                ],
            ],
        ];
    }

    /**
     * Devuelve una lista plana de todos los permisos definidos en el catálogo con sus metadatos.
     */
    public static function flatPermissionCatalog(): array
    {
        $flat = [];
        foreach (self::permissionCatalog() as $moduleKey => $module) {
            foreach ($module['permissions'] as $permKey => $meta) {
                $flat[$permKey] = array_merge($meta, [
                    'name'         => $permKey,
                    'module_key'   => $moduleKey,
                    'module_title' => $module['title'],
                ]);
            }
        }
        return $flat;
    }

    /**
     * Scope dinámico: Filtra usuarios cuyo rol posea un permiso específico.
     */
    public function scopeWithPermission($query, $permissionName)
    {
        $roles = Role::whereHas('permissions', function ($q) use ($permissionName) {
            $q->where('name', $permissionName);
        })->pluck('name');

        return $query->whereIn('role', $roles);
    }

    /**
     * Filtra usuarios habilitados para ejecutar pesaje y manufactura en planta.
     */
    public function scopeOperarios($query)
    {
        $roles = Role::whereHas('permissions', function ($q) {
            $q->where('name', 'registrar_manufactura_y_pesaje');
        })->pluck('name')->toArray();

        $roles = array_unique(array_merge($roles, ['OPERARIO', 'Operario', 'operario', 'ADMIN', 'admin']));

        return $query->whereIn('role', $roles);
    }

    /**
     * Filtra usuarios habilitados para realizar verificaciones de Aseguramiento de Calidad (QA).
     */
    public function scopeCalidad($query)
    {
        $roles = Role::whereHas('permissions', function ($q) {
            $q->where('name', 'verificacion_controles_en_proceso');
        })->pluck('name')->toArray();

        $roles = array_unique(array_merge($roles, [
            'CALIDAD', 'Calidad', 'calidad',
            'INSPECTOR DE CALIDAD', 'DIRECTOR DE ASEGURAMIENTO Y CONTROL DE CALIDAD',
            'DIRECCION TECNICA', 'DIRECCIÓN TÉCNICA', 'direccion_tecnica',
            'ADMIN', 'admin',
        ]));

        return $query->whereIn('role', $roles);
    }

    /**
     * Verifica si el usuario tiene uno o varios roles (insensible a mayúsculas/minúsculas).
     */
    public function hasRole($roles)
    {
        $userRole = mb_strtoupper(trim($this->role ?? ''));
        if (is_array($roles)) {
            $upperRoles = array_map(fn($r) => mb_strtoupper(trim($r)), $roles);
            return in_array($userRole, $upperRoles);
        }
        return $userRole === mb_strtoupper(trim($roles));
    }

    /**
     * Indica si el usuario tiene rol de Administrador del sistema.
     */
    public function isAdmin()
    {
        return $this->hasRole(['ADMIN', 'Administrador', 'admin', 'SUPERADMIN']);
    }

    /**
     * Obtiene los permisos individuales otorgados (grant) y revocados (revoke) directamente al usuario.
     */
    public function getCustomPermissionArrays(): array
    {
        $raw = $this->custom_permissions;
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (!is_array($raw)) {
            return ['grant' => [], 'revoke' => []];
        }
        return [
            'grant'  => array_values(array_unique((array) ($raw['grant'] ?? []))),
            'revoke' => array_values(array_unique((array) ($raw['revoke'] ?? []))),
        ];
    }

    /**
     * Obtiene la lista de nombres de permisos que posee el rol del usuario.
     */
    public function getRolePermissionNames(): array
    {
        static $rolePermissionsCache = [];
        $roleKey = mb_strtolower(trim($this->role ?? ''));

        if ($this->hasRole(['ADMIN', 'Administrador', 'admin', 'SUPERADMIN', 'DIRECCION TECNICA', 'DIRECCIÓN TÉCNICA', 'DIRECTOR TECNICO Y DE PRODUCCION'])) {
            return array_keys(self::flatPermissionCatalog());
        }

        if (!isset($rolePermissionsCache[$roleKey])) {
            try {
                $role = Role::with('permissions')
                    ->whereRaw('LOWER(name) = ?', [$roleKey])
                    ->first();
                $rolePermissionsCache[$roleKey] = $role ? $role->permissions->pluck('name')->all() : [];
            } catch (\Throwable $e) {
                $rolePermissionsCache[$roleKey] = [];
            }
        }

        return $rolePermissionsCache[$roleKey];
    }

    /**
     * Devuelve la lista efectiva de permisos del usuario (Permisos de Rol + Otorgados - Revocados).
     */
    public function getEffectivePermissionNames(): array
    {
        // El superadministrador conserva siempre acceso total
        if ($this->isAdmin()) {
            return array_keys(self::flatPermissionCatalog());
        }

        $rolePerms = $this->getRolePermissionNames();
        $custom = $this->getCustomPermissionArrays();

        $merged = array_unique(array_merge($rolePerms, $custom['grant']));
        $effective = array_values(array_diff($merged, $custom['revoke']));

        return $effective;
    }

    /**
     * Verifica si el usuario cuenta con un permiso específico (Rol + Overrides individuales).
     */
    public function hasPermission($permissionName)
    {
        // Los roles administradores tienen acceso maestro garantizado
        if ($this->isAdmin()) {
            return true;
        }

        $custom = $this->getCustomPermissionArrays();

        // 1. Si fue revocado explícitamente para este usuario
        if (in_array($permissionName, $custom['revoke'], true)) {
            return false;
        }

        // 2. Si fue otorgado explícitamente de forma individual a este usuario
        if (in_array($permissionName, $custom['grant'], true)) {
            return true;
        }

        // 3. Dirección Técnica tiene acceso maestro por defecto salvo revocación explícita
        if ($this->hasRole(['DIRECCION TECNICA', 'DIRECCIÓN TÉCNICA', 'direccion_tecnica', 'DIRECTOR TECNICO Y DE PRODUCCION'])) {
            return true;
        }

        // 4. Consultar permisos del Rol en la matriz RBAC
        return in_array($permissionName, $this->getRolePermissionNames(), true);
    }

    /**
     * Devuelve metadatos visuales del perfil (Área, Nivel CFR 21, Color) según el rol.
     */
    public function getProfileBadgeMeta(): array
    {
        $r = mb_strtoupper(trim($this->role ?? ''));

        if (str_contains($r, 'ADMIN')) {
            return [
                'area'       => $this->area ?: 'SISTEMAS & GOBIERNO IAM',
                'level'      => $this->signature_level ?: 'SUPERUSUARIO / CUSTODIO CFR 21',
                'badge_bg'   => 'bg-cyan-50 text-cyan-800 border-cyan-200',
                'avatar_bg'  => 'from-[#0A2540] to-[#06B6D4]',
                'icon'       => 'fa-user-shield',
            ];
        }
        if (str_contains($r, 'TECNIC') || str_contains($r, 'TÉCNIC')) {
            return [
                'area'       => $this->area ?: 'DIRECCIÓN TÉCNICA & PLANTA',
                'level'      => $this->signature_level ?: 'LIBERADOR / APROBADOR DT',
                'badge_bg'   => 'bg-indigo-50 text-indigo-800 border-indigo-200',
                'avatar_bg'  => 'from-indigo-700 to-blue-500',
                'icon'       => 'fa-user-tie',
            ];
        }
        if (str_contains($r, 'CALIDAD') || str_contains($r, 'QA')) {
            return [
                'area'       => $this->area ?: 'ASEGURAMIENTO DE CALIDAD (QA)',
                'level'      => $this->signature_level ?: 'CHECKER / VERIFICADOR QA',
                'badge_bg'   => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                'avatar_bg'  => 'from-[#028838] to-emerald-500',
                'icon'       => 'fa-clipboard-check',
            ];
        }
        if (str_contains($r, 'ANALISTA')) {
            return [
                'area'       => $this->area ?: 'PLANEACIÓN & PRODUCCIÓN',
                'level'      => $this->signature_level ?: 'MAKER / GESTOR OPERATIVO',
                'badge_bg'   => 'bg-amber-50 text-amber-800 border-amber-200',
                'avatar_bg'  => 'from-[#F28E13] to-amber-500',
                'icon'       => 'fa-user-gear',
            ];
        }

        return [
            'area'       => $this->area ?: 'PLANTA DE MANUFACTURA EBR',
            'level'      => $this->signature_level ?: 'MAKER / EJECUTOR EN PLANTA',
            'badge_bg'   => 'bg-blue-50 text-blue-800 border-blue-200',
            'avatar_bg'  => 'from-[#005889] to-[#048ABF]',
            'icon'       => 'fa-hard-hat',
        ];
    }

    /**
     * Identifica si el usuario pertenece al perfil exclusivo de Calidad (QA).
     */
    public function isQualityUser()
    {
        if ($this->hasRole(['ADMIN', 'admin', 'Administrador', 'SUPERADMIN', 'DIRECCION TECNICA', 'DIRECCIÓN TÉCNICA', 'direccion_tecnica', 'DIRECTOR TECNICO Y DE PRODUCCION'])) {
            return false;
        }

        return $this->hasRole([
            'CALIDAD', 'calidad',
            'INSPECTOR DE CALIDAD', 'AUXILIAR DE CALIDAD',
            'COORDINADOR DE ASEGURAMIENTO DE CALIDAD',
            'DIRECTOR DE ASEGURAMIENTO Y CONTROL DE CALIDAD',
        ]);
    }
}
