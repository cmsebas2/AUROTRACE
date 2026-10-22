<?php

/**
 * ============================================================================
 * MAPA DE RUTAS WEB Y API INTERNA - AUROTRACE ERP
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Este archivo conecta las URLs que el usuario escribe o pulsa en el navegador
 * con el Controlador (el "cerebro") que procesa la acción.
 *
 * Estructura del archivo:
 * 1. AUTENTICACIÓN: Login y cierre de sesión.
 * 2. ENDPOINTS PÚBLICOS DE APOYO: Mapa de ocupación 3D, migrador remoto y autocompletado de ítems.
 * 3. RUTAS PROTEGIDAS (Middleware 'auth' - Requieren haber iniciado sesión):
 *    3.1 Dashboard Principal
 *    3.2 Gestión de Usuarios y Roles (IAM - protegido por middleware 'admin')
 *    3.3 Catálogo Maestro de Productos e Instructivos EBR
 *    3.4 Módulo de Maquilas Externas (Tercerización)
 *    3.5 Firma Electrónica Universal (21 CFR Part 11)
 *    3.6 Órdenes de Producción (OP): Emisión, Ajuste de Activos, Codificado y COAs
 *    3.7 Ejecución del Lote (EBR): Conciliación, Despeje, Dispensación, Fabricación y Envase
 *    3.8 Genealogía 360°, Calidad (QA) y Archivo Físico 3D (Consultas BR)
 * ============================================================================
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\BatchRecordController;
use App\Http\Controllers\ConsultasBrController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GenealogyController;
use App\Http\Controllers\GlobalSignatureController;
use App\Http\Controllers\MaquilaProductionOrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductionOrderController;
use App\Http\Controllers\QualityController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| 1. AUTENTICACIÓN (Acceso al Sistema)
|--------------------------------------------------------------------------
*/
Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| 2. ENDPOINTS DE APOYO Y DESPLIEGUE
|--------------------------------------------------------------------------
*/
// API para consultar las ubicaciones ocupadas en el mapa 3D de archivo físico
Route::get('/archive-locations/occupied', [ConsultasBrController::class, 'getOccupiedSlots'])->name('archive_locations.occupied');
Route::get('/api/archive-locations/occupied', [ConsultasBrController::class, 'getOccupiedSlots'])->name('api.archive_locations.occupied');

// Utilidad de mantenimiento para ejecutar migraciones y seeders iniciales bajo llave secreta
Route::get('/run-migrations', function () {
    if (request()->query('secret') !== 'auromigrate2026') {
        abort(403, 'Acceso denegado');
    }
    try {
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo()->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
        } catch (\Throwable $e) {}

        $output = '';
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            $output .= \Illuminate\Support\Facades\Artisan::output();
        } catch (\Throwable $e) {
            $output .= "Migrate notice: " . $e->getMessage() . "\n";
        }

        // 1. AurofarmaCatalogSeeder omitido para mantener 0 productos y permitir creación manual en /productos
        // (new \Database\Seeders\AurofarmaCatalogSeeder())->run();

        // 2. Ejecutar MaquiladorSeeder
        (new \Database\Seeders\MaquiladorSeeder())->run();
        $output .= "MaquiladorSeeder ejecutado con éxito.\n";

        // 3. Ejecutar UserItemsSeeder
        (new \Database\Seeders\UserItemsSeeder())->run();
        $output .= "UserItemsSeeder ejecutado con éxito.\n";

        // 4. Ejecutar RolePermissionSeeder (Permisos y Roles Oficiales)
        (new \Database\Seeders\RolePermissionSeeder())->run();
        $output .= "RolePermissionSeeder ejecutado con éxito.\n";

        // 4.1 Ejecutar AurofarmaSeeder (Crear/actualizar usuarios de sistema incluyendo 'calidad')
        (new \Database\Seeders\AurofarmaSeeder())->run();
        $output .= "AurofarmaSeeder ejecutado con éxito (Usuario 'calidad' creado/actualizado).\n";

        // 5. Ejecutar SeedHistoricBatchRecords (520 lotes históricos)
        try {
            $historicMigration = require database_path('migrations/2026_09_08_210000_seed_historic_batch_records.php');
            $historicMigration->up();
            $output .= "SeedHistoricBatchRecords (520 lotes) ejecutado con éxito.\n";
        } catch (\Throwable $e) {
            $output .= "Historic Batch Records notice: " . $e->getMessage() . "\n";
        }

        // 6. Reconstruir ubicaciones físicas de archivo 3D sin huecos (Archivadores #1 al #131)
        try {
            (new ConsultasBrController())->rebuildArchiveLocations();
            $output .= "Reconstrucción secuencial de ubicaciones 3D ejecutada con éxito (Archivadores 1 a 131 rellenados al 100%).\n";
        } catch (\Throwable $e) {
            $output .= "Rebuild archive notice: " . $e->getMessage() . "\n";
        }

        return 'Migrations and Seeders run successfully! <br><pre>' . $output . '</pre>';
    } catch (\Throwable $e) {
        return 'Error running migrations/seeders: ' . $e->getMessage();
    }
});

// Lookup de ítems para autocompletado rápido en formularios de Maquila
Route::get('/maquilas/item-lookup/{codigo}', [MaquilaProductionOrderController::class, 'apiGetItem'])->name('maquila.item_lookup');
Route::get('/api/maquilas/item-lookup/{codigo}', [MaquilaProductionOrderController::class, 'apiGetItem'])->name('api.maquila.item_lookup');

/*
|--------------------------------------------------------------------------
| 3. RUTAS PROTEGIDAS DEL ERP (Requieren Sesión Activa)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // 3.1 Dashboard Principal (KPIs de Planta y Radar de Lotes)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 3.2 Usuarios y Roles (IAM - 21 CFR Part 11 - Solo Administradores)
    Route::middleware('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/toggle', [UserController::class, 'toggleStatus'])->name('users.toggle');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/delete', [UserController::class, 'destroy'])->name('users.destroy_post');
        Route::post('/users/{user}/permissions', [UserController::class, 'updateUserPermissions'])->name('users.permissions.update');
        Route::post('/users/{user}/unlock', [UserController::class, 'unlockAndReset'])->name('users.unlock');
        Route::post('/users/roles', [UserController::class, 'storeRole'])->name('users.roles.store');
        Route::delete('/users/roles/{role}', [UserController::class, 'destroyRole'])->name('users.roles.destroy');
        Route::post('/users/roles/sync', [UserController::class, 'syncRolePermissions'])->name('users.roles.sync');
        Route::post('/users/roles/{role}/sync-single', [UserController::class, 'syncSingleRolePermissions'])->name('users.roles.sync_single');
    });

    // 3.3 Catálogo de Productos y Fórmulas Maestras
    Route::get('/productos', [ProductController::class, 'index'])->name('productos.index');
    Route::get('/productos/crear', [ProductController::class, 'create'])->name('productos.create');
    Route::post('/productos/crear', [ProductController::class, 'store'])->name('productos.store');
    Route::get('/productos/{id}', [ProductController::class, 'show'])->name('productos.show');
    Route::get('/productos/{id}/imprimir', [ProductController::class, 'imprimirFicha'])->name('productos.imprimir');
    Route::get('/productos/{id}/editar', [ProductController::class, 'edit'])->name('productos.edit');
    Route::put('/productos/{id}', [ProductController::class, 'update'])->name('productos.update');
    Route::post('/productos/{id}/formula-maestra', [ProductController::class, 'saveFormulaMaestra'])->name('productos.formula.save');
    Route::delete('/productos/{id}', [ProductController::class, 'destroy'])->name('productos.destroy');

    // Configurador de Instructivos de Manufactura (EBR Master Builder)
    Route::get('/productos/{id}/instructivo', [ProductController::class, 'editInstructivo'])->name('productos.instructivo.edit');
    Route::post('/productos/{id}/instructivo', [ProductController::class, 'updateInstructivo'])->name('productos.instructivo.update');
    Route::delete('/instructivo/{id}', [ProductController::class, 'deleteInstructivo'])->name('productos.instructivo.destroy');

    // API de autocompletado de ítems por código
    Route::get('/api/items/{codigo}', [ProductController::class, 'apiGetItem']);

    // 3.4 Módulo de Control de Producción en Maquilas Externas (Res. ICA 062542 / 21 CFR Part 11)
    Route::prefix('maquilas')->name('maquila.')->group(function () {
        Route::get('/', [MaquilaProductionOrderController::class, 'dashboard'])->name('index');
        Route::get('/crear', [MaquilaProductionOrderController::class, 'create'])->name('create');
        Route::post('/crear', [MaquilaProductionOrderController::class, 'store'])->name('store');
        Route::get('/{id}', [MaquilaProductionOrderController::class, 'show'])->name('show');
        Route::get('/{id}/editar', [MaquilaProductionOrderController::class, 'edit'])->name('edit');
        Route::put('/{id}', [MaquilaProductionOrderController::class, 'update'])->name('update');
        Route::post('/{id}/update', [MaquilaProductionOrderController::class, 'update'])->name('update_post');
        Route::post('/{id}/update-location', [MaquilaProductionOrderController::class, 'updateLocation'])->name('update_location');
        Route::post('/{id}/enviar-maquilador', [MaquilaProductionOrderController::class, 'enviarMaquilador'])->name('enviar');
        Route::get('/{id}/recepcion', [MaquilaProductionOrderController::class, 'recepcionForm'])->name('recepcion');
        Route::post('/{id}/recepcion', [MaquilaProductionOrderController::class, 'storeRecepcion'])->name('recepcion.store');
        Route::get('/{id}/llegada-br', [MaquilaProductionOrderController::class, 'llegadaBrForm'])->name('llegada_br_form');
        Route::post('/{id}/llegada-br', [MaquilaProductionOrderController::class, 'registrarLlegadaBr'])->name('llegada_br');
        Route::post('/{id}/revision-dt', [MaquilaProductionOrderController::class, 'revisionDt'])->name('revision_dt');
        Route::post('/{id}/revision-calidad', [MaquilaProductionOrderController::class, 'revisionCalidad'])->name('revision_calidad');
        Route::post('/item/{itemId}/delivery', [MaquilaProductionOrderController::class, 'registerDelivery'])->name('delivery');
        Route::post('/{id}/close', [MaquilaProductionOrderController::class, 'closeOrder'])->name('close');
        Route::delete('/{id}', [MaquilaProductionOrderController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/eliminar', [MaquilaProductionOrderController::class, 'destroy'])->name('destroy_post');
    });

    // 3.5 API de Firma Electrónica Universal CFR 21 (Desacoplada)
    Route::post('/api/system/validate-signature', [GlobalSignatureController::class, 'validateSignature'])->name('api.signature.validate');

    // 3.6 Órdenes de Producción (OP): Emisión (A3PPR0007) y Ajuste de Principios Activos (A4PPR0007)
    Route::get('/ops/crear', [ProductionOrderController::class, 'create'])->name('op.crear');
    Route::get('/batch/iniciar', [ProductionOrderController::class, 'create'])->name('batch.iniciar'); // Alias de compatibilidad para navegación EBR
    Route::post('/ops/crear', [ProductionOrderController::class, 'store'])->name('op.store');
    Route::post('/ops/validate-signature', [GlobalSignatureController::class, 'validateSignature'])->name('op.validate_signature');
    Route::get('/api/products/{id}/explosion-data', [ProductionOrderController::class, 'apiGetProductData']);
    Route::get('/refresh-csrf', function () {
        return response()->json(['token' => csrf_token()]);
    });
    Route::get('/ops/{lote}/ajuste-activos', [ProductionOrderController::class, 'ajusteActivos'])->name('op.ajuste_activos');
    Route::post('/ops/{lote}/ajuste-activos', [ProductionOrderController::class, 'storeAjusteActivos'])->name('op.ajuste_activos.store');
    Route::post('/ops/{lote}/firmar-ajuste', [ProductionOrderController::class, 'firmarAjuste'])->name('op.ajuste_activos.firmar');
    Route::get('/ops/{lote}/verificar-ajuste', [ProductionOrderController::class, 'verificarAjuste'])->name('op.verificar_ajuste');
    Route::post('/ops/{lote}/verificar-ajuste', [ProductionOrderController::class, 'storeVerificarAjuste'])->name('op.verificar_ajuste.store');
    Route::post('/ops/{lote}/firmar-verificacion-ajuste', [ProductionOrderController::class, 'firmarVerificarAjuste'])->name('op.verificar_ajuste.firmar');
    Route::get('/ops/{lote}/verificar-final', [ProductionOrderController::class, 'verificarFinal'])->name('op.verificar_final');

    // 3.7 Ejecución del Lote en Planta (Flujo EBR Paso a Paso)
    // Paso A: Conciliación de Materiales
    Route::get('/batch/{batch}/conciliacion', [BatchController::class, 'createReconciliation'])->name('batch.conciliacion');
    Route::post('/batch/{batch}/conciliacion', [BatchController::class, 'storeReconciliation'])->name('batch.conciliacion.store');
    Route::post('/batch/{batch}/conciliacion/sign', [BatchController::class, 'signReconciliation'])->name('batch.conciliacion.sign');

    // Paso B: Despeje de Línea y Verificación QA
    Route::get('/batch/{batch}/despeje-linea', [BatchController::class, 'createLineClearance'])->name('batch.despeje');
    Route::post('/batch/{batch}/despeje-linea', [BatchController::class, 'storeLineClearance'])->name('batch.despeje.store');
    Route::post('/batch/{batch}/qa-credentials', [BatchController::class, 'validateQaCredentials'])->name('batch.qa.credentials');
    Route::post('/batch/{batch}/qa-verification', [BatchController::class, 'storeQaVerification'])->name('batch.qa.verification');

    // Paso C: Dispensación y Pesaje
    Route::get('/batch/{batch}/dispensacion', [BatchController::class, 'createDispensing'])->name('batch.dispensacion');
    Route::post('/batch/{batch}/dispensacion/detalle', [BatchController::class, 'storeDispensingDetail'])->name('batch.dispensacion.detalle');
    Route::post('/batch/{batch}/dispensacion/cerrar', [BatchController::class, 'closeDispensing'])->name('batch.dispensacion.cerrar');

    // Paso D: Fabricación / Manufactura
    Route::get('/batch/{batch}/fabricacion', [BatchController::class, 'createManufacturing'])->name('batch.fabricacion');
    Route::post('/batch/{batch}/fabricacion/step', [BatchController::class, 'storeManufacturingStep'])->name('batch.fabricacion.store');
    Route::post('/batch/{batch}/fabricacion/cerrar', [BatchController::class, 'finishManufacturing'])->name('batch.fabricacion.cerrar');
    Route::post('/batch/{batch}/fabricacion/dynamic-step', [BatchController::class, 'storeManufacturingStepDynamic'])->name('batch.fabricacion.store.dynamic');
    Route::post('/batch/{batch}/fabricacion/verify-step', [BatchController::class, 'verifyManufacturingStepDynamic'])->name('batch.fabricacion.verify.dynamic');

    // Paso E: Envasado y Control de Pesos (Formato A3PPR0010)
    Route::get('/batch/{batch}/envase', [BatchController::class, 'createPackaging'])->name('batch.envase');
    Route::post('/batch/{batch}/envase/store', [BatchController::class, 'storePackaging'])->name('batch.envase.store');
    Route::post('/batch/{batch}/envase/weight', [BatchController::class, 'storePackagingWeight'])->name('batch.envase.weight.store');
    Route::post('/batch/{batch}/envase/verify', [BatchController::class, 'verifyPackaging'])->name('batch.envase.verify');

    // Paneles de Ejecución, Solicitud de Codificado (A6PPR0007), Monitoreo y COAs
    Route::get('/ops/ejecucion', [ProductionOrderController::class, 'indexExecution'])->name('op.ejecucion');
    Route::get('/ops/{lote}/solicitud-codificado', [ProductionOrderController::class, 'solicitudCodificado'])->name('op.solicitud_codificado');
    Route::post('/ops/{lote}/solicitud-codificado', [ProductionOrderController::class, 'storeSolicitudCodificado'])->name('op.solicitud_codificado.store');
    Route::get('/ops/{lote}/aprobar-codificado', [ProductionOrderController::class, 'aprobarCodificado'])->name('op.aprobar_codificado');
    Route::post('/ops/{lote}/aprobar-codificado', [ProductionOrderController::class, 'storeAprobarCodificado'])->name('op.aprobar_codificado.store');
    Route::post('/ops/{lote}/firmar-solicitud-codificado', [ProductionOrderController::class, 'firmarSolicitudCodificado'])->name('op.solicitud_codificado.firmar');

    // Supervisión / Monitoreo Activo (Dirección Técnica)
    Route::get('/ops/activas', [ProductionOrderController::class, 'indexActive'])->name('op.activas');
    Route::delete('/ops/{batch}', [ProductionOrderController::class, 'destroy'])->name('op.destroy');
    Route::post('/ops/{batch}/delete', [ProductionOrderController::class, 'destroy'])->name('ops.destroy'); // Alias de compatibilidad

    // Aseguramiento de Calidad: Certificados de Análisis (COAs)
    Route::get('/ops/calidad', [ProductionOrderController::class, 'indexQuality'])->name('op.calidad');
    Route::get('/ops/{lote}/coas', [ProductionOrderController::class, 'coasForm'])->name('op.coas');
    Route::post('/ops/{lote}/coas', [ProductionOrderController::class, 'storeCoas'])->name('op.coas.store');
    Route::get('/ops/{lote}/aprobar-coas', [ProductionOrderController::class, 'aprobarCoasForm'])->name('op.aprobar_coas');
    Route::post('/ops/{lote}/aprobar-coas', [ProductionOrderController::class, 'storeAprobarCoas'])->name('op.aprobar_coas.store');
    Route::get('/ops/{lote}/coas/unificar', [ProductionOrderController::class, 'mergeCoasPdf'])->name('op.coas.merge');
    Route::post('/ops/{lote}/coas/firmar', [ProductionOrderController::class, 'firmarCoas'])->name('op.coas.firmar');

    // 3.8 Genealogía de Lote (Vista 360°), Liberación QA y Archivo Físico 3D
    Route::get('/genealogia', [GenealogyController::class, 'index'])->name('genealogia.index');
    Route::get('/genealogia/{op}', [GenealogyController::class, 'showByBatch'])->name('genealogia.show');
    Route::get('/genealogia/{op}/liberar', [GenealogyController::class, 'release'])->name('genealogia.release.get');
    Route::post('/genealogia/{op}/liberar', [GenealogyController::class, 'release'])->name('genealogia.release');
    Route::get('/genealogia/{op}/pdf', [GenealogyController::class, 'downloadPdf'])->name('genealogia.pdf');

    // Portal de Aseguramiento de Calidad (QA)
    Route::get('/calidad', [QualityController::class, 'index'])->name('calidad.index');

    // Expedientes Acumulativos (Batch Records) & Consultas BR (Archivo Físico 3D)
    Route::get('/batch-records', [BatchRecordController::class, 'index'])->name('batch-records.index');
    Route::get('/batch-records/{lote}/pdf', [BatchRecordController::class, 'downloadMasterPdf'])->name('batch-records.pdf');
    Route::get('/consultas-br', [ConsultasBrController::class, 'index'])->name('consultas.br');
    Route::get('/consultas-br/index', [ConsultasBrController::class, 'index'])->name('consultas-br.index'); // Alias de compatibilidad
    Route::get('/consultas-br/archivador/{numero}', [ConsultasBrController::class, 'apiGetArchivador'])->name('consultas.archivador');
    Route::post('/consultas-br/assign-slot', [ConsultasBrController::class, 'apiAssignSlot'])->name('consultas.assign');
    Route::get('/consultas-br/search', [ConsultasBrController::class, 'apiSearch'])->name('consultas.search');

    // Endpoints de compatibilidad API para el visor 3D de archivo físico
    Route::get('/api/consultas-br/archivador/{numero}', [ConsultasBrController::class, 'apiGetArchivador'])->name('api.consultas.archivador');
    Route::post('/api/consultas-br/assign-slot', [ConsultasBrController::class, 'apiAssignSlot'])->name('api.consultas.assign');
    Route::get('/api/consultas-br/search', [ConsultasBrController::class, 'apiSearch'])->name('api.consultas.search');
});
