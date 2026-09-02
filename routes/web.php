<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GenealogyController;
use App\Http\Controllers\ProductionOrderController;

Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/run-migrations', function () {
    if (request()->query('secret') !== 'auromigrate2026') {
        abort(403, 'Acceso denegado');
    }
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        return 'Migrations run successfully! <br><pre>' . \Illuminate\Support\Facades\Artisan::output() . '</pre>';
    } catch (\Throwable $e) {
        return 'Error running migrations: ' . $e->getMessage();
    }
});


Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Usuarios y Roles (IAM - CFR 21)
    Route::middleware('admin')->group(function () {
        Route::get('/users', [\App\Http\Controllers\UserController::class, 'index'])->name('users.index');
        Route::post('/users', [\App\Http\Controllers\UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [\App\Http\Controllers\UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/toggle', [\App\Http\Controllers\UserController::class, 'toggleStatus'])->name('users.toggle');
        Route::post('/users/roles/sync', [\App\Http\Controllers\UserController::class, 'syncRolePermissions'])->name('users.roles.sync');
    });

    // Catálogo de Productos (Frontend Interactivo)
    Route::get('/productos', [\App\Http\Controllers\ProductController::class, 'index'])->name('productos.index');
    Route::get('/productos/crear', [\App\Http\Controllers\ProductController::class, 'create'])->name('productos.create');
    Route::post('/productos/crear', [\App\Http\Controllers\ProductController::class, 'store'])->name('productos.store');
    Route::get('/productos/{id}', [\App\Http\Controllers\ProductController::class, 'show'])->name('productos.show');
    Route::get('/productos/{id}/imprimir', [\App\Http\Controllers\ProductController::class, 'imprimirFicha'])->name('productos.imprimir');
    Route::get('/productos/{id}/editar', [\App\Http\Controllers\ProductController::class, 'edit'])->name('productos.edit');
    Route::put('/productos/{id}', [\App\Http\Controllers\ProductController::class, 'update'])->name('productos.update');
    Route::delete('/productos/{id}', [\App\Http\Controllers\ProductController::class, 'destroy'])->name('productos.destroy');
    
    // EBR Master Builder (Configurador de Instructivos)
    Route::get('/productos/{id}/instructivo', [\App\Http\Controllers\ProductController::class, 'editInstructivo'])->name('productos.instructivo.edit');
    Route::post('/productos/{id}/instructivo', [\App\Http\Controllers\ProductController::class, 'updateInstructivo'])->name('productos.instructivo.update');
    Route::delete('/instructivo/{id}', [\App\Http\Controllers\ProductController::class, 'deleteInstructivo'])->name('productos.instructivo.destroy');

    // API autocompletado de ítems
    Route::get('/api/items/{codigo}', [\App\Http\Controllers\ProductController::class, 'apiGetItem']);

    // Módulo Control de Producción en Maquilas Externas (Res. ICA 062542 / 21 CFR Part 11)
    Route::get('/api/maquilas/item-lookup/{codigo}', [\App\Http\Controllers\MaquilaProductionOrderController::class, 'apiGetItem'])->name('api.maquila.item_lookup');
    Route::prefix('maquilas')->name('maquila.')->group(function () {
        Route::get('/', [\App\Http\Controllers\MaquilaProductionOrderController::class, 'dashboard'])->name('index');
        Route::get('/crear', [\App\Http\Controllers\MaquilaProductionOrderController::class, 'create'])->name('create');
        Route::post('/crear', [\App\Http\Controllers\MaquilaProductionOrderController::class, 'store'])->name('store');
        Route::get('/{id}', [\App\Http\Controllers\MaquilaProductionOrderController::class, 'show'])->name('show');
        Route::post('/item/{itemId}/delivery', [\App\Http\Controllers\MaquilaProductionOrderController::class, 'registerDelivery'])->name('delivery');
        Route::post('/{id}/close', [\App\Http\Controllers\MaquilaProductionOrderController::class, 'closeOrder'])->name('close');
    });


    // API de Firma Universal CFR 21 (Desacoplado)
    Route::post('/api/system/validate-signature', [\App\Http\Controllers\GlobalSignatureController::class, 'validateSignature'])->name('api.signature.validate');

    // 0. Módulo de Creación (Formulario Inteligente A3PPR0007)
    Route::get('/ops/crear', [ProductionOrderController::class, 'create'])->name('op.crear');
    Route::post('/ops/crear', [ProductionOrderController::class, 'store'])->name('op.store');
    Route::post('/ops/validate-signature', [ProductionOrderController::class, 'validateSignature'])->name('op.validate_signature');
    Route::get('/api/products/{id}/explosion-data', [ProductionOrderController::class, 'apiGetProductData']);
    Route::get('/refresh-csrf', function() {
        return response()->json(['token' => csrf_token()]);
    });
    Route::get('/ops/{lote}/ajuste-activos', [ProductionOrderController::class, 'ajusteActivos'])->name('op.ajuste_activos');
    Route::post('/ops/{lote}/ajuste-activos', [ProductionOrderController::class, 'storeAjusteActivos'])->name('op.ajuste_activos.store');
    Route::post('/ops/{lote}/firmar-ajuste', [ProductionOrderController::class, 'firmarAjuste'])->name('op.ajuste_activos.firmar');
    Route::get('/ops/{lote}/verificar-ajuste', [ProductionOrderController::class, 'verificarAjuste'])->name('op.verificar_ajuste');
    Route::post('/ops/{lote}/verificar-ajuste', [ProductionOrderController::class, 'storeVerificarAjuste'])->name('op.verificar_ajuste.store');
    Route::post('/ops/{lote}/firmar-verificacion-ajuste', [ProductionOrderController::class, 'firmarVerificarAjuste'])->name('op.verificar_ajuste.firmar');
    Route::get('/ops/{lote}/verificar-final', [ProductionOrderController::class, 'verificarFinal'])->name('op.verificar_final');
    
    // Conciliación de Materiales
    Route::get('/batch/{batch}/conciliacion', [\App\Http\Controllers\BatchController::class, 'createReconciliation'])->name('batch.conciliacion');
    Route::post('/batch/{batch}/conciliacion', [\App\Http\Controllers\BatchController::class, 'storeReconciliation'])->name('batch.conciliacion.store');
    Route::post('/batch/{batch}/conciliacion/sign', [\App\Http\Controllers\BatchController::class, 'signReconciliation'])->name('batch.conciliacion.sign');
    
    // Despeje de Línea
    Route::get('/batch/{batch}/despeje-linea', [\App\Http\Controllers\BatchController::class, 'createLineClearance'])->name('batch.despeje');
    Route::post('/batch/{batch}/despeje-linea', [\App\Http\Controllers\BatchController::class, 'storeLineClearance'])->name('batch.despeje.store');

    // Doble Verificación QA
    Route::post('/batch/{batch}/qa-credentials', [\App\Http\Controllers\BatchController::class, 'validateQaCredentials'])->name('batch.qa.credentials');
    Route::post('/batch/{batch}/qa-verification', [\App\Http\Controllers\BatchController::class, 'storeQaVerification'])->name('batch.qa.verification');

    // Módulo 3: Fabricación (Manufacturing)
    Route::get('/batch/{batch}/fabricacion', [\App\Http\Controllers\BatchController::class, 'createManufacturing'])->name('batch.fabricacion');
    Route::post('/batch/{batch}/fabricacion/step', [\App\Http\Controllers\BatchController::class, 'storeManufacturingStep'])->name('batch.fabricacion.store');
    Route::post('/batch/{batch}/fabricacion/cerrar', [\App\Http\Controllers\BatchController::class, 'finishManufacturing'])->name('batch.fabricacion.cerrar');
    Route::post('/batch/{batch}/fabricacion/dynamic-step', [\App\Http\Controllers\BatchController::class, 'storeManufacturingStepDynamic'])->name('batch.fabricacion.store.dynamic');
    Route::post('/batch/{batch}/fabricacion/verify-step', [\App\Http\Controllers\BatchController::class, 'verifyManufacturingStepDynamic'])->name('batch.fabricacion.verify.dynamic');

    // Dispensación
    Route::get('/batch/{batch}/dispensacion', [\App\Http\Controllers\BatchController::class, 'createDispensing'])->name('batch.dispensacion');
    Route::post('/batch/{batch}/dispensacion/detalle', [\App\Http\Controllers\BatchController::class, 'storeDispensingDetail'])->name('batch.dispensacion.detalle');
    Route::post('/batch/{batch}/dispensacion/cerrar', [\App\Http\Controllers\BatchController::class, 'closeDispensing'])->name('batch.dispensacion.cerrar');

    // Módulo 6: Envase (Format A3PPR0010)
    Route::get('/batch/{batch}/envase', [\App\Http\Controllers\BatchController::class, 'createPackaging'])->name('batch.envase');
    Route::post('/batch/{batch}/envase/store', [\App\Http\Controllers\BatchController::class, 'storePackaging'])->name('batch.envase.store');
    Route::post('/batch/{batch}/envase/weight', [\App\Http\Controllers\BatchController::class, 'storePackagingWeight'])->name('batch.envase.weight.store');
    Route::post('/batch/{batch}/envase/verify', [\App\Http\Controllers\BatchController::class, 'verifyPackaging'])->name('batch.envase.verify');

    // 1. Módulo de Ejecución
    Route::get('/ops/ejecucion', [\App\Http\Controllers\ProductionOrderController::class, 'indexExecution'])->name('op.ejecucion');
    Route::get('/ops/{lote}/solicitud-codificado', [ProductionOrderController::class, 'solicitudCodificado'])->name('op.solicitud_codificado');
    Route::post('/ops/{lote}/solicitud-codificado', [ProductionOrderController::class, 'storeSolicitudCodificado'])->name('op.solicitud_codificado.store');
    Route::get('/ops/{lote}/aprobar-codificado', [ProductionOrderController::class, 'aprobarCodificado'])->name('op.aprobar_codificado');
    Route::post('/ops/{lote}/aprobar-codificado', [ProductionOrderController::class, 'storeAprobarCodificado'])->name('op.aprobar_codificado.store');
    Route::post('/ops/{lote}/firmar-solicitud-codificado', [ProductionOrderController::class, 'firmarSolicitudCodificado'])->name('op.solicitud_codificado.firmar');

    // 2. Módulo de Supervisión / Monitoreo Activo (Director)
    Route::get('/ops/activas', [\App\Http\Controllers\ProductionOrderController::class, 'indexActive'])->name('op.activas');
    Route::delete('/ops/{batch}', [\App\Http\Controllers\ProductionOrderController::class, 'destroy'])->name('op.destroy');
    
    // Módulo de Aseguramiento de Calidad (COAs)
    Route::get('/ops/calidad', [\App\Http\Controllers\ProductionOrderController::class, 'indexQuality'])->name('op.calidad');
    Route::get('/ops/{lote}/coas', [\App\Http\Controllers\ProductionOrderController::class, 'coasForm'])->name('op.coas');
    Route::post('/ops/{lote}/coas', [\App\Http\Controllers\ProductionOrderController::class, 'storeCoas'])->name('op.coas.store');
    Route::get('/ops/{lote}/aprobar-coas', [\App\Http\Controllers\ProductionOrderController::class, 'aprobarCoasForm'])->name('op.aprobar_coas');
    Route::post('/ops/{lote}/aprobar-coas', [\App\Http\Controllers\ProductionOrderController::class, 'storeAprobarCoas'])->name('op.aprobar_coas.store');
    Route::get('/ops/{lote}/coas/unificar', [\App\Http\Controllers\ProductionOrderController::class, 'mergeCoasPdf'])->name('op.coas.merge');
    Route::post('/ops/{lote}/coas/firmar', [\App\Http\Controllers\ProductionOrderController::class, 'firmarCoas'])->name('op.coas.firmar');

    // Genealogía de Lote (Vista 360)
    Route::get('/genealogia', [GenealogyController::class, 'index'])->name('genealogia.index');
    Route::get('/genealogia/{op}', [GenealogyController::class, 'showByBatch'])->name('genealogia.show');
    Route::get('/genealogia/{op}/liberar', [GenealogyController::class, 'release'])->name('genealogia.release'); // Fix method if needed, usually it's post
    Route::post('/genealogia/{op}/liberar', [GenealogyController::class, 'release'])->name('genealogia.release');
    Route::get('/genealogia/{op}/pdf', [GenealogyController::class, 'downloadPdf'])->name('genealogia.pdf');
    // Módulo BATCH RECORDS (Expedientes Acumulativos)
    Route::get('/batch-records', [\App\Http\Controllers\BatchRecordController::class, 'index'])->name('batch-records.index');
    Route::get('/batch-records/{lote}/pdf', [\App\Http\Controllers\BatchRecordController::class, 'downloadMasterPdf'])->name('batch-records.pdf');
});
