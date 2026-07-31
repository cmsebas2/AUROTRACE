<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MacroProcess;
use App\Models\Process;
use App\Models\SubProcess;
use App\Models\Activity;

class ProcessHierarchySeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar tablas para evitar duplicados en re-ejecución
        Activity::query()->delete();
        SubProcess::query()->delete();
        Process::query()->delete();
        MacroProcess::query()->delete();

        // 1. MACROPROCESO: FABRICACIÓN FARMACÉUTICA
        $macro = MacroProcess::create(['name' => 'Fab. Farmacéutica']);

        // --- PROCESO: FASE DOCUMENTAL ---
        $procDoc = Process::create(['macro_process_id' => $macro->id, 'name' => 'Fase Documental']);
        
        $subGen = SubProcess::create(['process_id' => $procDoc->id, 'name' => 'Generación OP']);
        Activity::create(['sub_process_id' => $subGen->id, 'name' => 'Creación de Orden', 'status_key' => 'OP_CREADA']);
        Activity::create(['sub_process_id' => $subGen->id, 'name' => 'Verificación de OP', 'status_key' => 'OP_VERIFICADA']);

        $subAj = SubProcess::create(['process_id' => $procDoc->id, 'name' => 'Ajuste API']);
        Activity::create(['sub_process_id' => $subAj->id, 'name' => 'Cálculo de Ajuste', 'status_key' => 'AJ_CREADO']);
        Activity::create(['sub_process_id' => $subAj->id, 'name' => 'Verificación de Ajuste', 'status_key' => 'AJ_VERIFICADO']);

        $subCod = SubProcess::create(['process_id' => $procDoc->id, 'name' => 'Orden de Codificado']);
        Activity::create(['sub_process_id' => $subCod->id, 'name' => 'Elaboración de Textos', 'status_key' => 'COD_CREADO']);
        Activity::create(['sub_process_id' => $subCod->id, 'name' => 'Aprobación de Textos', 'status_key' => 'COD_VERIFICADO']);

        $subCoa = SubProcess::create(['process_id' => $procDoc->id, 'name' => 'Certificados COA']);
        Activity::create(['sub_process_id' => $subCoa->id, 'name' => 'Carga de COAs', 'status_key' => 'COA_CREADO']);
        Activity::create(['sub_process_id' => $subCoa->id, 'name' => 'Aprobación de COAs', 'status_key' => 'COA_VERIFICADO']);

        // --- PROCESO: MANUFACTURA ---
        $procMan = Process::create(['macro_process_id' => $macro->id, 'name' => 'Manufactura']);

        $subVerDoc = SubProcess::create(['process_id' => $procMan->id, 'name' => 'Verificación Documental']);
        Activity::create(['sub_process_id' => $subVerDoc->id, 'name' => 'Confirmación de EBR Teórico', 'status_key' => null]);

        $subDisp = SubProcess::create(['process_id' => $procMan->id, 'name' => 'Dispensación']);
        Activity::create(['sub_process_id' => $subDisp->id, 'name' => 'Despeje de Línea', 'status_key' => null]);
        Activity::create(['sub_process_id' => $subDisp->id, 'name' => 'Realizar Dispensación', 'status_key' => null]);
        Activity::create(['sub_process_id' => $subDisp->id, 'name' => 'Verificar Dispensación', 'status_key' => null]);

        $subFab = SubProcess::create(['process_id' => $procMan->id, 'name' => 'Fabricación']);
        Activity::create(['sub_process_id' => $subFab->id, 'name' => 'Despeje de Línea', 'status_key' => null]);
        Activity::create(['sub_process_id' => $subFab->id, 'name' => 'Realizar Fabricación', 'status_key' => null]);
        Activity::create(['sub_process_id' => $subFab->id, 'name' => 'Verificar Fabricación', 'status_key' => null]);

        $subEnv = SubProcess::create(['process_id' => $procMan->id, 'name' => 'Envase']);
        Activity::create(['sub_process_id' => $subEnv->id, 'name' => 'Despeje de Línea', 'status_key' => null]);
        Activity::create(['sub_process_id' => $subEnv->id, 'name' => 'Realizar Envase', 'status_key' => null]);
        Activity::create(['sub_process_id' => $subEnv->id, 'name' => 'Verificar Envase', 'status_key' => null]);

        $subCodFis = SubProcess::create(['process_id' => $procMan->id, 'name' => 'Codificado Físico']);
        Activity::create(['sub_process_id' => $subCodFis->id, 'name' => 'Despeje de Línea', 'status_key' => null]);
        Activity::create(['sub_process_id' => $subCodFis->id, 'name' => 'Realizar Codificado', 'status_key' => null]);
        Activity::create(['sub_process_id' => $subCodFis->id, 'name' => 'Verificar Codificado', 'status_key' => null]);

        $subAcon = SubProcess::create(['process_id' => $procMan->id, 'name' => 'Acondicionado']);
        Activity::create(['sub_process_id' => $subAcon->id, 'name' => 'Despeje de Línea', 'status_key' => null]);
        Activity::create(['sub_process_id' => $subAcon->id, 'name' => 'Realizar Acondicionado', 'status_key' => null]);
        Activity::create(['sub_process_id' => $subAcon->id, 'name' => 'Verificar Acondicionado', 'status_key' => null]);

        $subConc = SubProcess::create(['process_id' => $procMan->id, 'name' => 'Conciliación Materiales']);
        Activity::create(['sub_process_id' => $subConc->id, 'name' => 'Cuadre de Mermas/Rendimientos', 'status_key' => null]);
        Activity::create(['sub_process_id' => $subConc->id, 'name' => 'Cierre de Lote (Fin Manufactura)', 'status_key' => 'COMPLETADO']);

        // --- PROCESO: DICTAMEN FINAL ---
        $procDict = Process::create(['macro_process_id' => $macro->id, 'name' => 'Dictamen Final']);
        $subDispCal = SubProcess::create(['process_id' => $procDict->id, 'name' => 'Disposición de Calidad']);
        Activity::create(['sub_process_id' => $subDispCal->id, 'name' => 'Lote Liberado', 'status_key' => 'LIBERADO']);
        Activity::create(['sub_process_id' => $subDispCal->id, 'name' => 'Lote Rechazado', 'status_key' => 'RECHAZADO']);
    }
}
