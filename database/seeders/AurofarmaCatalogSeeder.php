<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AurofarmaCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rawItems = [
            ['A11000', 'AUROMECK INYECTABLE', 'Frasco x 50 mL', 'SOLUCIÓN INYECTABLE', 'UND', 36, '5304-DB'],
            ['A11001', 'AUROMECK INYECTABLE', 'Frasco x 500 mL', 'SOLUCIÓN INYECTABLE', 'UND', 36, '5304-DB'],
            ['A11002', 'AUROMECK INYECTABLE', 'Frasco x 200 mL', 'SOLUCIÓN INYECTABLE', 'UND', 36, '5304-DB'],
            ['A11003', 'ANAPIRAN', 'Frasco x 50 mL', 'SOLUCIÓN INYECTABLE', 'UND', 24, '5906-DB'],
            ['A11004', 'BOLDEBIG 50', 'Frasco x 50 mL', 'SOLUCIÓN INYECTABLE', 'UND', 36, '6009-MV'],
            ['A11005', 'BOLDEBIG 50', 'Frasco x 250 mL', 'SOLUCIÓN INYECTABLE', 'UND', 36, '6009-MV'],
            ['A11006', 'BOLDEBIG 50', 'Frasco x 500 mL', 'SOLUCIÓN INYECTABLE', 'UND', 36, '6009-MV'],
            ['A11007', 'CABATEL', 'Frasco x 20 mL', 'SUSPENSIÓN ORAL', 'UND', 36, '2332-DB'],
            ['A11008', 'CABATEL', 'Frasco x 500 mL', 'SUSPENSIÓN ORAL', 'UND', 36, '2332-DB'],
            ['A11009', 'AURO DIARREGAN', 'Caja x 10 Sobres', 'POLVO ORAL', 'UND', 36, '3494-MV'],
            ['A11010', 'AURO DIARREGAN', 'Caja x 50 Sobres', 'POLVO ORAL', 'UND', 36, '3494-MV'],
            ['A11011', 'ERIPANTO INYECTABLE', 'Frasco x 50 mL', 'SOLUCIÓN INYECTABLE', 'UND', 36, '4797-DB'],
            ['A11012', 'ERIPANTO MASTITIS', 'Caja 4 x 12 mL', 'SUSPENSIÓN INTRAMAMARIA', 'UND', 36, '2347-DB'],
            ['A11013', 'ERIPANTO', 'Sobre x 24 G', 'POLVO ORAL', 'UND', 36, '2347-DB'],
            ['A11014', 'Q FOS 25', 'Sobre x 250 GR', 'POLVO ORAL', 'UND', 24, '8135-MV'],
            ['A11015', 'Q FOS 25', 'Sobre x 1 KG', 'POLVO ORAL', 'UND', 24, '8135-MV'],
            ['A11016', 'Q NORFLOXAN', 'Frasco x 20 mL', 'SOLUCIÓN ORAL', 'UND', 30, '7933-MV'],
            ['A11017', 'Q NORFLOXAN', 'Frasco x 100 mL', 'SOLUCIÓN ORAL', 'UND', 30, '7933-MV'],
            ['A11018', 'Q NORFLOXAN', 'Frasco x 1000 mL', 'SOLUCIÓN ORAL', 'UND', 30, '7933-MV'],
            ['A11019', 'Q OXY 200 LA', 'Frasco x 50 mL', 'SOLUCIÓN INYECTABLE', 'UND', 24, '8107-MV'],
            ['A11020', 'Q OXY 200 LA', 'Frasco x 500 mL', 'SOLUCIÓN INYECTABLE', 'UND', 24, '8107-MV'],
            ['A11021', 'Q TARTILO 100', 'Sobre x 250 G', 'POLVO ORAL', 'UND', 18, '7934-MV'],
            ['A11022', 'Q TARTILO 100', 'Sobre x 1 KG', 'POLVO ORAL', 'UND', 18, '7934-MV'],
            ['A11023', 'QTYCON', 'Tubo x 30 G', 'GEL ORAL', 'UND', 24, '5554-MV'],
            ['A11024', 'SULFATROPHIN', 'Frasco x 10 mL', 'SUSPENSIÓN INYECTABLE', 'UND', 36, '5320-DB'],
            ['A11025', 'SULFATROPHIN', 'Frasco x 50 mL', 'SUSPENSIÓN INYECTABLE', 'UND', 36, '5320-DB'],
            ['A11026', 'SULFATROPHIN', 'Frasco x 100 mL', 'SUSPENSIÓN INYECTABLE', 'UND', 36, '5320-DB'],
            ['A11027', 'SULFATROPHIN', 'Frasco x 50 mL', 'SUSPENSIÓN ORAL', 'UND', 24, '5630-DB'],
            ['A11028', 'SULFATROPHIN', 'Frasco x 10 mL', 'SUSPENSIÓN ORAL', 'UND', 24, '5630-DB'],
            ['A11029', 'SULFATROPHIN', 'Frasco x 100 mL', 'SUSPENSIÓN ORAL', 'UND', 24, '5630-DB'],
            ['A11030', 'SULFATROPHIN', 'Frasco x 1000 mL', 'SUSPENSIÓN ORAL', 'UND', 24, '5630-DB'],
            ['A11031', 'AURO UNGUENTO', 'Tarro x 100 G', 'UNGÜENTO', 'UND', 48, '3439-BD'],
            ['A11032', 'AURO UNGUENTO', 'Tarro x 500 G', 'UNGÜENTO', 'UND', 48, '3439-BD'],
            ['A11033', 'AUROTEL', 'Jeringa x 2.5 ML', 'SUSPENSIÓN ORAL', 'UND', 24, '4474-DB'],
            ['A11034', 'AUROTEL', 'Jeringa x 5.0 ML', 'SUSPENSIÓN ORAL', 'UND', 24, '4474-DB'],
            ['A11035', 'AUROZOLE 25 CO', 'Frasco x 120 ML', 'SUSPENSIÓN ORAL', 'UND', 24, '6631-MV'],
            ['A11036', 'AUROZOLE 25 CO', 'Frasco x 500 ML', 'SUSPENSIÓN ORAL', 'UND', 24, '6631-MV'],
            ['A11037', 'AUROZOLE 25 CO', 'Frasco x 1000 ML', 'SUSPENSIÓN ORAL', 'UND', 24, '6631-MV'],
            ['A11038', 'AUROZOLE 25 CO', 'Frasco x 2000 ML', 'SUSPENSIÓN ORAL', 'UND', 24, '6631-MV'],
            ['A11039', 'AVICUR POLVO', 'Sobre x 20 G', 'POLVO ORAL', 'UND', 36, '4638-DB'],
            ['A11040', 'AVICUR POLVO', 'Sobre x 1 KG', 'POLVO ORAL', 'UND', 36, '4638-DB'],
            ['A11041', 'AVICUR POLVO', 'Bolsa x 12.5 KG', 'POLVO ORAL', 'UND', 36, '4638-DB'],
            ['A11042', 'PORCIX', 'Sobre x 20 GR', 'POLVO ORAL', 'UND', 48, '2539-DB'],
            ['A11043', 'Q IVERMEC 3.5', 'Frasco x 50 ML', 'SOLUCIÓN INYECTABLE', 'UND', 36, '8008-MV'],
            ['A11044', 'Q IVERMEC 3.5', 'Frasco x 250 ML', 'SOLUCIÓN INYECTABLE', 'UND', 36, '8008-MV'],
            ['A11045', 'Q IVERMEC 3.5', 'Frasco x 500 ML', 'SOLUCIÓN INYECTABLE', 'UND', 36, '8008-MV'],
            ['A11046', 'RAFOXANIDE', 'Jeringa x 30 ML', 'SUSPENSIÓN ORAL', 'UND', 36, '3678-DB'],
            ['A11047', 'RAFOXANIDE', 'Frasco x 120 ML', 'SUSPENSIÓN ORAL', 'UND', 36, '3678-DB'],
            ['A11048', 'RAFOXANIDE', 'Frasco x 1000 ML', 'SUSPENSIÓN ORAL', 'UND', 36, '3678-DB'],
            ['A11049', 'Q B COMPLEX', 'Frasco x 10 ML', 'SOLUCIÓN INYECTABLE', 'UND', 36, null],
            ['A11050', 'Q TARTILO 100', 'Sobre x 50 GR', 'POLVO ORAL', 'UND', 18, '7934-MV'],
            ['A11051', 'COCCIDIOL', 'Sobre x 25 GR', 'POLVO ORAL', 'UND', 48, '2645-DB'],
            ['A11052', 'COCCIDIOL', 'Sobre x 1 KG', 'POLVO ORAL', 'UND', 48, '2645-DB'],
            ['A11053', 'SULFACOCCIDIOL', 'Sobre x 25 G', 'POLVO ORAL', 'UND', 48, '3106-DB'],
            ['A11054', 'AUROCHAMPU P', 'Frasco x 120 ML', 'SHAMPOO TÓPICO', 'UND', 36, '6343-MV'],
            ['A11055', 'CIPERMETRINA 15 EC', 'Frasco x 20 ML', 'CONCENTRADO EMULSIONABLE', 'UND', 36, '6671-MV'],
            ['A11056', 'CIPERMETRINA 15 EC', 'Frasco x 100 ML', 'CONCENTRADO EMULSIONABLE', 'UND', 36, '6671-MV'],
            ['A11057', 'CIPERMETRINA 15 EC', 'Frasco x 500 ML', 'CONCENTRADO EMULSIONABLE', 'UND', 36, '6671-MV'],
            ['A11058', 'CIPERMETRINA 15 EC', 'Frasco x 1000 ML', 'CONCENTRADO EMULSIONABLE', 'UND', 36, '6671-MV'],
            ['A11059', 'PULPHOX', 'Tarro x 100 G', 'POLVO TÓPICO', 'UND', 36, '6199-MV'],
            ['A11060', 'AUROFARVIT INYECTABLE', 'Frasco x 10 ML', 'SOLUCIÓN INYECTABLE', 'UND', 24, '4946-DB'],
            ['A11061', 'AUROFARVIT INYECTABLE', 'Frasco x 50 ML', 'SOLUCIÓN INYECTABLE', 'UND', 24, '4946-DB'],
            ['A11062', 'AUROFARVIT INYECTABLE', 'Frasco x 250 ML', 'SOLUCIÓN INYECTABLE', 'UND', 24, '4946-DB'],
            ['A11063', 'AUROFARVIT INYECTABLE', 'Frasco x 500 ML', 'SOLUCIÓN INYECTABLE', 'UND', 24, '4946-DB'],
            ['A11064', 'AUROFARVIT ORAL', 'Frasco x 120 ML', 'SOLUCIÓN ORAL', 'UND', 24, '4919-DB'],
            ['A11065', 'AUROFARVIT ORAL', 'Frasco x 1000 ML', 'SOLUCIÓN ORAL', 'UND', 24, '4919-DB'],
            ['A11066', 'AUROFARVIT ORAL', 'Garrafa x 4000 ML', 'SOLUCIÓN ORAL', 'UND', 24, '4919-DB'],
            ['A11067', 'AUROVITEL', 'Sobre x 20 G', 'POLVO ORAL', 'UND', 24, '4551-DB'],
            ['A11068', 'AUROVITEL', 'Sobre x 1 KG', 'POLVO ORAL', 'UND', 24, '4551-DB'],
            ['A11069', 'BRILLA PEL', 'Sobre x 150 G', 'POLVO ORAL', 'UND', 24, '5701-SL'],
            ['A11070', 'BRILLA PEL', 'Sobre x 1 KG', 'POLVO ORAL', 'UND', 24, '5701-SL'],
            ['A11071', 'ERIPANTO', 'Sobre x 1 KG', 'POLVO ORAL', 'UND', 36, '2347-DB'],
            ['A11072', 'Q B COMPLEX', 'Frasco x 500 ML', 'SOLUCIÓN INYECTABLE', 'UND', 36, null],
            ['A11073', 'Q OXY 200 LA', 'Frasco x 100 ML', 'SOLUCIÓN INYECTABLE', 'UND', 24, '8107-MV'],
            ['A11074', 'AUROFARVIT INYECTABLE', 'Frasco x 100 ML', 'SOLUCIÓN INYECTABLE', 'UND', 24, '4946-DB'],
            ['A11075', 'AUROTEL', 'Frasco x 10 ML', 'SUSPENSIÓN ORAL', 'UND', 24, '4474-DB'],
            ['A11076', 'Q OXY 200 LA', 'Frasco x 250 ML', 'SOLUCIÓN INYECTABLE', 'UND', 24, '8107-MV'],
            ['A11077', 'SULFACOCCIDIOL', 'Sobre x 1 KG', 'POLVO ORAL', 'UND', 48, '3106-DB'],
            ['A11078', 'MELOXIDOL', 'Frasco x 10 ML', 'SOLUCIÓN INYECTABLE', 'UND', 24, '7872-MV'],
            ['A11079', 'MELOXIDOL', 'Frasco x 50 ML', 'SOLUCIÓN INYECTABLE', 'UND', 24, '7872-MV'],
            ['A11080', 'MELOXIDOL', 'Frasco x 100 ML', 'SOLUCIÓN INYECTABLE', 'UND', 24, '7872-MV'],
            ['A11081', 'AUROCEF', 'Frasco x 1 G', 'POLVO ESTÉRIL INYECTABLE', 'UND', 24, '7849-MV'],
            ['A11082', 'AUROCEF', 'Frasco x 4 G', 'POLVO ESTÉRIL INYECTABLE', 'UND', 24, '7849-MV'],
            ['A11083', 'ANAPIRAN INYECTABLE', 'Frasco x 250 ML', 'SOLUCIÓN INYECTABLE', 'UND', 24, '5906-DB'],
            ['A11084', 'AUROLITOS SOBRE', 'Sobre x 1 KG', 'POLVO ORAL', 'KG', 24, '16582-SL'],
            ['A11085', 'PENIDEXINA', 'Frasco x 4 MILLONES', 'POLVO ESTÉRIL INYECTABLE', 'UND', 24, '6292-MV'],
            ['A11086', 'PENIDEXINA', 'Frasco x 8 MILLONES', 'POLVO ESTÉRIL INYECTABLE', 'UND', 24, '6292-MV'],
            ['A11087', 'AUROTEL', 'Frasco x 250 ML', 'SUSPENSIÓN ORAL', 'UND', 24, '4474-DB'],
            ['A11088', 'AUROPUPPY', 'Jeringa x 5 ML', 'SUSPENSIÓN ORAL', 'UND', 36, '2538-DB'],
            ['A11089', 'AUROPUPPY', 'Jeringa x 2.5 ML', 'SUSPENSIÓN ORAL', 'UND', 36, '2538-DB'],
            ['A11090', 'ERIPANTO INYECTABLE', 'Frasco x 100 ML', 'SOLUCIÓN INYECTABLE', 'UND', 36, '4797-DB'],
            ['A11091', 'DILUYENTE AUROCEF', 'Frasco x 20 ML', 'SOLUCIÓN DILUYENTE', 'UND', 24, '7849-MV'],
            ['A11092', 'DILUYENTE AUROCEF', 'Frasco x 80 ML', 'SOLUCIÓN DILUYENTE', 'UND', 24, '7849-MV'],
            ['A11093', 'BRIO PERFORMANCE', 'Tarro x 2 KG', 'SUPLEMENTO ORAL', 'UND', 24, null],
            ['A11094', 'BRIO INCREASE', 'Tarro x 2 KG', 'SUPLEMENTO ORAL', 'UND', 24, null],
            ['A11095', 'BRIO JOINTS', 'Tarro x 2 KG', 'SUPLEMENTO ORAL', 'UND', 24, null],
            ['A11096', 'EQUINOLISINA', 'Tarro x 1 KG', 'SUPLEMENTO ORAL', 'UND', 24, null],
            ['A11097', 'BRILLA PEL', 'Tarro x 2 KG', 'POLVO ORAL', 'UND', 24, '5701-SL'],
            ['A11098', 'BRIO PERFORMANCE', 'Sobre x 300 G', 'SUPLEMENTO ORAL', 'UND', 24, null],
            ['A11099', 'AUROPETS INCREASE', 'Tarro x 200 G', 'SUPLEMENTO ORAL', 'UND', 24, '15874-SL'],
            ['A11100', 'AUROPETS PERFORMANCE', 'Tarro x 200 G', 'SUPLEMENTO ORAL', 'UND', 24, '25873-SL'],
            ['A11101', 'AUROPETS SENIOR', 'Tarro x 200 G', 'SUPLEMENTO ORAL', 'UND', 24, '15977-SL'],
            ['A11102', 'BRILLAPEL EMULSION', 'Frasco x 130 ML', 'EMULSIÓN ORAL', 'UND', 24, '16010-SL'],
            ['A11103', 'BRILLAPEL EMULSION', 'Frasco x 270 ML', 'EMULSIÓN ORAL', 'UND', 24, '16010-SL'],
            ['A11104', 'BRILLAPEL EMULSION', 'Frasco x 550 ML', 'EMULSIÓN ORAL', 'UND', 24, '16010-SL'],
            ['A11105', 'BRILLA PEL', 'Tarro x 250 G', 'POLVO ORAL', 'UND', 24, '5701-SL'],
            ['A11106', 'AUROTILMICOSIN', 'Frasco x 240 ML', 'SOLUCIÓN ORAL', 'UND', 36, '10424-MV'],
            ['A11107', 'AUROTILMICOSIN', 'Frasco x 1000 ML', 'SOLUCIÓN ORAL', 'UND', 36, '10424-MV'],
            ['A11108', 'BRIO EQBALANCE', 'Sobre x 40 G', 'SUPLEMENTO ORAL', 'UND', 24, null],
            ['A11109', 'BRIO EQBALANCE', 'Tarro x 1 KG', 'SUPLEMENTO ORAL', 'UND', 24, null],
            ['A11110', 'BRIO EQBALANCE', 'Balde x 5 KG', 'SUPLEMENTO ORAL', 'UND', 24, null],
            ['A11111', 'BRIO ENERGY', 'Jeringa x 30 ML', 'GEL ORAL', 'UND', 24, null],
            ['A11112', 'BRIO ENERGY', 'Frasco x 1000 ML', 'SOLUCIÓN ORAL', 'UND', 24, null],
            ['A11113', 'EQUINOLISINA', 'Sobre x 50 G', 'SUPLEMENTO ORAL', 'UND', 24, null],
            ['A11114', 'FLORMIX', 'Frasco x 1000 ML', 'SOLUCIÓN ORAL', 'UND', 24, '10453-MV'],
            ['A11115', 'FLORMIX', 'Garrafa x 4000 ML', 'SOLUCIÓN ORAL', 'UND', 24, '10453-MV'],
            ['A11116', 'FLORMIX', 'Garrafa x 2000 ML', 'SOLUCIÓN ORAL', 'UND', 24, '10453-MV'],
            ['A11117', 'AURODIARREGAN NF', 'Caja 10 Sobres x 10 G', 'POLVO ORAL', 'UND', 36, '3494-MV'],
            ['A11118', 'AURODIARREGAN NF', 'Caja 50 Sobres x 10 G', 'POLVO ORAL', 'UND', 36, '3494-MV'],
            ['A11119', 'CABATEL NF', 'Jeringa x 20 ML', 'SUSPENSIÓN ORAL', 'UND', 24, '10488-MV'],
            ['A11120', 'CABATEL NF', 'Frasco x 100 ML', 'SUSPENSIÓN ORAL', 'UND', 24, '10488-MV'],
            ['A11121', 'CABATEL NF', 'Frasco x 500 ML', 'SUSPENSIÓN ORAL', 'UND', 24, '10488-MV'],
            ['A11122', 'AUROFRESH CHAMPU', 'Frasco x 120 ML', 'SHAMPOO TÓPICO', 'UND', 24, '10506-MV'],
            ['A11123', 'AUROFRESH CHAMPU', 'Frasco x 250 ML', 'SHAMPOO TÓPICO', 'UND', 24, '10506-MV'],
            ['A11124', 'AUROFRESH CHAMPU', 'Frasco x 1000 ML', 'SHAMPOO TÓPICO', 'UND', 24, '10506-MV'],
            ['A11125', 'AUROFRESH CHAMPU', 'Garrafa x 2000 ML', 'SHAMPOO TÓPICO', 'UND', 24, '10506-MV'],
            ['A11126', 'AUROPETS JABON', 'Barra x 100 GR', 'JABÓN SÓLIDO', 'UND', 36, '10524-MV'],
            ['A11127', 'AUROPETS JABON', 'Barra x 30 G', 'JABÓN SÓLIDO', 'UND', 36, '10524-MV'],
            ['A11128', 'CIPROFARM 20%', 'Sobre x 1 KG', 'POLVO ORAL', 'UND', 24, '10661-MV'],
            ['A11130', 'ERIPANTO INYECTABLE', 'Frasco x 250 ML', 'SOLUCIÓN INYECTABLE', 'UND', 36, '4797-DB'],
            ['A11131', 'AUROTILMICOSIN', 'Gotero x 10 ML', 'SOLUCIÓN ORAL', 'UND', 36, '10424-MV'],
            ['A11132', 'AURODIARREGAN NF', 'Caja 10 Sobres x 20 G', 'POLVO ORAL', 'UND', 36, '3494-MV'],
            ['A11133', 'AURODIARREGAN NF', 'Caja 50 Sobres x 20 G', 'POLVO ORAL', 'UND', 36, '3494-MV'],
            ['A11134', 'DOXYCOL', 'Sobre x 1 KG', 'POLVO ORAL', 'KG', 24, '10756-MV'],
            ['A11135', 'FERRYDECK', 'Frasco x 50 ML', 'SOLUCIÓN INYECTABLE', 'UND', 24, '11013-MV'],
            ['A11136', 'FERRYDECK', 'Frasco x 100 ML', 'SOLUCIÓN INYECTABLE', 'UND', 24, '11013-MV'],
            ['A11137', 'CREO TAY', 'Frasco x 120 ML', 'SOLUCIÓN DESINFECTANTE', 'UND', 24, '2262-DB'],
            ['A11138', 'CREO TAY', 'Frasco x 250 ML', 'SOLUCIÓN DESINFECTANTE', 'UND', 24, '2262-DB'],
            ['A11139', 'CREO TAY', 'Frasco x 500 ML', 'SOLUCIÓN DESINFECTANTE', 'UND', 24, '2262-DB'],
            ['A11140', 'CREO TAY', 'Frasco x 1000 ML', 'SOLUCIÓN DESINFECTANTE', 'UND', 24, '2262-DB'],
            ['A11141', 'CREO TAY', 'Garrafa x 3800 ML', 'SOLUCIÓN DESINFECTANTE', 'UND', 24, '2262-DB'],
            ['A11142', 'CREO TAY', 'Garrafa x 20 L', 'SOLUCIÓN DESINFECTANTE', 'UND', 24, '2262-DB'],
            ['A11143', 'VANOVET', 'Frasco x 120 ML', 'SOLUCIÓN DESINFECTANTE', 'UND', 36, '2497-DB'],
            ['A11144', 'VANOVET', 'Frasco x 1000 ML', 'SOLUCIÓN DESINFECTANTE', 'UND', 36, '2497-DB'],
            ['A11145', 'VANOVET', 'Garrafa x 18.75 L', 'SOLUCIÓN DESINFECTANTE', 'UND', 36, '2497-DB'],
            ['A11146', 'VANOVET', 'Garrafa x 3750 ML', 'SOLUCIÓN DESINFECTANTE', 'UND', 36, '2497-DB'],
            ['A11147', 'GLH 20', 'Garrafa x 20 L', 'SOLUCIÓN DESINFECTANTE', 'UND', 12, '10423-MV'],
            ['A11148', 'GLH 20', 'Garrafa x 3800 ML', 'SOLUCIÓN DESINFECTANTE', 'UND', 12, '10423-MV'],
            ['A11149', 'SULFACOCCIDIOL', 'Bolsa x 3 KG', 'POLVO ORAL', 'UND', 48, '3106-DB'],
            ['A11150', 'DOXYCOL', 'Bolsa x 5 KG', 'POLVO ORAL', 'UND', 24, '10756-MV'],
            ['A11151', 'Q FOS 25', 'Bolsa x 25 KG', 'POLVO ORAL', 'UND', 24, '8135-MV'],
            ['A11152', 'GLH 20', 'Frasco x 1000 ML', 'SOLUCIÓN DESINFECTANTE', 'UND', 12, '10423-MV'],
            ['A11153', 'BRIO JOINTS', 'Tarro x 600 GR', 'SUPLEMENTO ORAL', 'UND', 24, null],
            ['A11156', 'ANAPIRAN INYECTABLE', 'Frasco x 500 ML', 'SOLUCIÓN INYECTABLE', 'UND', 24, '5906-DB'],
            ['A11157', 'FORTICAT', 'Frasco x 100 ML', 'SUPLEMENTO ORAL', 'UND', 24, null],
            ['A11158', 'FORTICAT RENAL', 'Frasco x 100 ML', 'SUPLEMENTO ORAL', 'UND', 24, null],
            ['A11159', 'AUROVITEL', 'Sobre x 10 G', 'POLVO ORAL', 'UND', 24, '4551-DB'],
            ['A11160', 'PHYTO FISH', 'Bolsa x 1 KG', 'PREMEZCLA NUTRICIONAL', 'UND', 24, null],
            ['A11162', 'NEOMIXIN', 'Sobre x 20 G', 'POLVO ORAL', 'UND', 24, null],
            ['A11163', 'NEOMIXIN', 'Bolsa x 1 KG', 'POLVO ORAL', 'UND', 24, null],
            ['A11165', 'VITA MIRABILIS', 'Bolsa x 1 KG', 'PREMEZCLA NUTRICIONAL', 'UND', 24, null],
            ['A11166', 'VITA MIRABILIS', 'Bolsa x 5 KG', 'PREMEZCLA NUTRICIONAL', 'UND', 24, null],
            ['A11167', 'VITA MIRABILIS', 'Saco x 10 KG', 'PREMEZCLA NUTRICIONAL', 'UND', 24, null],
            ['A11168', 'VITA MIRABILIS', 'Saco x 25 KG', 'PREMEZCLA NUTRICIONAL', 'UND', 24, null],
            ['A11169', 'HEPAXYN', 'Bolsa x 1 KG', 'PREMEZCLA NUTRICIONAL', 'UND', 24, null],
            ['A11170', 'POWERQUIN BARRA', 'Barra x 100 GR', 'SUPLEMENTO EN BARRA', 'UND', 24, null],
            ['A11171', 'FLY NO MORE', 'Frasco x 1500 ML', 'CEBO LÍQUIDO', 'UND', 24, null],
            ['A11172', 'FERRYDECK', 'Frasco x 10 ML', 'SOLUCIÓN INYECTABLE', 'UND', 24, '11013-MV'],
            ['A11173', 'ACTIV GEL', 'Tubo x 35 G', 'GEL TÓPICO', 'UND', 24, '22806-SL'],
            ['A11174', 'MELOXIDOL AFRICA', 'Frasco x 100 ML', 'SOLUCIÓN INYECTABLE', 'UND', 24, '7872-MV'],
            ['A31000', 'Q CLORMUTIN', 'Granel x KG', 'POLVO ORAL', 'KG', 24, '8048-MV'],
            ['A31003', 'Q SULFATYL', 'Granel x KG', 'POLVO ORAL', 'KG', 24, '8188-MV'],
            ['A31004', 'Q TILMICOX', 'Granel x KG', 'POLVO ORAL', 'KG', 24, '8131-MV'],
            ['A31009', 'HEPAXYN', 'Bolsa x 5 KG', 'PREMEZCLA NUTRICIONAL', 'UND', 24, null],
            ['A31010', 'HEPAXYN', 'Bolsa x 10 KG', 'PREMEZCLA NUTRICIONAL', 'UND', 24, null],
            ['A31012', 'Q FLORFEN', 'Bolsa x 1 KG', 'PREMEZCLA', 'KG', 33, '8132-MV'],
            ['A31013', 'Q FLORFEN', 'Bolsa x 5 KG', 'PREMEZCLA', 'KG', 33, '8132-MV'],
            ['A31019', 'AUROLISTINA 10%', 'Granel x KG', 'POLVO ORAL', 'KG', 24, null],
            ['A31021', 'Q MICOSPECTIN L', 'Granel x KG', 'PREMEZCLA', 'KG', 36, '8134-MV'],
            ['A31025', 'ACTIV GEL', 'Pote x KG', 'GEL TÓPICO', 'UND', 24, '22806-SL'],
            ['A31026', 'GRANEL AUROESENCIAL', 'Granel x KG', 'PREMEZCLA', 'KG', 24, '21403AL'],
        ];

        // 1. Agrupar por producto y poblar tabla products y product_presentations
        $byProduct = [];
        foreach ($rawItems as $row) {
            $code = $row[0];
            $prodName = trim($row[1]);
            $presentation = trim($row[2]);
            $pharmForm = trim($row[3]);
            $unit = trim($row[4]);
            $months = (int) $row[5];
            $ica = $row[6] ? trim($row[6]) : null;

            $prodKey = $prodName . '|' . $pharmForm;
            if (!isset($byProduct[$prodKey])) {
                $byProduct[$prodKey] = [
                    'name' => $prodName,
                    'pharmaceutical_form' => $pharmForm,
                    'ica_license' => $ica,
                    'vigencia_meses' => $months,
                    'base_unit' => $unit,
                    'presentations' => [],
                ];
            }
            $byProduct[$prodKey]['presentations'][] = [
                'code' => $code,
                'name' => $presentation,
                'unit' => $unit,
                'months' => $months,
                'ica' => $ica,
            ];
        }

        foreach ($byProduct as $pData) {
            $presSummary = collect($pData['presentations'])->pluck('name')->unique()->implode(', ');
            $product = DB::table('products')->where('name', $pData['name'])->first();

            if ($product) {
                DB::table('products')->where('id', $product->id)->update([
                    'pharmaceutical_form' => $pData['pharmaceutical_form'],
                    'presentation' => $presSummary,
                    'ica_license' => $pData['ica_license'] ?: $product->ica_license,
                    'vigencia_meses' => $pData['vigencia_meses'],
                    'base_unit' => $pData['base_unit'],
                    'updated_at' => now(),
                ]);
                $productId = $product->id;
            } else {
                $productId = DB::table('products')->insertGetId([
                    'name' => $pData['name'],
                    'presentation' => $presSummary,
                    'pharmaceutical_form' => $pData['pharmaceutical_form'],
                    'ica_license' => $pData['ica_license'],
                    'vigencia_meses' => $pData['vigencia_meses'],
                    'base_batch_size' => 100.0,
                    'base_unit' => $pData['base_unit'],
                    'status' => 'ACTIVO',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($pData['presentations'] as $pres) {
                if (Schema::hasTable('product_presentations')) {
                    DB::table('product_presentations')->updateOrInsert(
                        ['presentation_code' => $pres['code']],
                        [
                            'product_id' => $productId,
                            'name' => $pres['name'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }

        // 2. Poblar tabla maquila_catalog_items y tabla items
        foreach ($rawItems as $row) {
            $code = $row[0];
            $prodName = trim($row[1]);
            $presentation = trim($row[2]);
            $pharmForm = trim($row[3]);
            $unit = trim($row[4]);
            $months = (int) $row[5];
            $ica = $row[6] ? trim($row[6]) : null;

            if (Schema::hasTable('maquila_catalog_items')) {
                DB::table('maquila_catalog_items')->updateOrInsert(
                    ['codigo_item' => $code],
                    [
                        'producto_nombre' => $prodName,
                        'presentacion' => $presentation,
                        'forma_farmaceutica' => $pharmForm,
                        'unidad_medida' => $unit,
                        'vigencia_meses' => $months,
                        'registro_ica' => $ica,
                        'activo' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            if (Schema::hasTable('items')) {
                DB::table('items')->updateOrInsert(
                    ['item_code' => $code],
                    [
                        'description' => $prodName,
                        'reference' => $presentation,
                        'ext_1_detail' => $presentation,
                        'inventory_uom' => $unit,
                        'is_manufactured' => true,
                        'is_sold' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
