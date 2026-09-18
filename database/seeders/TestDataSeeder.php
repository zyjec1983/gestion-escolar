<?php

namespace Database\Seeders;

use App\Models\Alumno;
use App\Models\Matricula;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $representantes = [];
        for ($i = 1; $i <= 25; $i++) {
            $rep = User::create([
                'name' => "REPRESENTANTE $i",
                'email' => "rep$i@fake.edu.ec",
                'password' => bcrypt('password'),
                'rol' => 'REPRESENTANTE',
                'institucion_id' => 1,
            ]);
            $representantes[] = $rep->id;
        }

        $estudiantes = [
            ['nombres' => 'LUIS ALBERTO', 'apellidos' => 'ZAMBRANO MENDOZA', 'genero' => 'masculino'],
            ['nombres' => 'MARÍA FERNANDA', 'apellidos' => 'TORRES VILLEGAS', 'genero' => 'femenino'],
            ['nombres' => 'CARLOS ANDRÉS', 'apellidos' => 'GÓMEZ PALACIOS', 'genero' => 'masculino'],
            ['nombres' => 'ANA BELÉN', 'apellidos' => 'RUIZ CÁRDENAS', 'genero' => 'femenino'],
            ['nombres' => 'PEDRO JOSÉ', 'apellidos' => 'MOLINA CEDEÑO', 'genero' => 'masculino'],
            ['nombres' => 'SOFÍA ALEJANDRA', 'apellidos' => 'VEGA LOOR', 'genero' => 'femenino'],
            ['nombres' => 'DIEGO FERNANDO', 'apellidos' => 'HERRERA PINCAY', 'genero' => 'masculino'],
            ['nombres' => 'LAURA CAROLINA', 'apellidos' => 'PAZ SANTANA', 'genero' => 'femenino'],
            ['nombres' => 'JAVIER ALEJANDRO', 'apellidos' => 'DELGADO ROMERO', 'genero' => 'masculino'],
            ['nombres' => 'VALENTINA ISABEL', 'apellidos' => 'MACÍAS GIL', 'genero' => 'femenino'],
            ['nombres' => 'RICARDO MANUEL', 'apellidos' => 'PLAZA ALCÍVAR', 'genero' => 'masculino'],
            ['nombres' => 'CAMILA ESTEFANÍA', 'apellidos' => 'SALAZAR CEDEÑO', 'genero' => 'femenino'],
            ['nombres' => 'FELIPE SANTIAGO', 'apellidos' => 'BRAVO MENDOZA', 'genero' => 'masculino'],
            ['nombres' => 'GABRIELA MARIANA', 'apellidos' => 'VILLACÍS MEJÍA', 'genero' => 'femenino'],
            ['nombres' => 'ANDRÉS FELIPE', 'apellidos' => 'RODRÍGUEZ LOOR', 'genero' => 'masculino'],
            ['nombres' => 'NICOLE ALEXANDRA', 'apellidos' => 'MACENCINI PINTO', 'genero' => 'femenino'],
            ['nombres' => 'SEBASTIÁN EMILIO', 'apellidos' => 'INTRIAGO ALCÍVAR', 'genero' => 'masculino'],
            ['nombres' => 'DANIELA ESTER', 'apellidos' => 'MERCHÁN SANTOS', 'genero' => 'femenino'],
            ['nombres' => 'MIGUEL ÁNGEL', 'apellidos' => 'ZAMORA BRIONES', 'genero' => 'masculino'],
            ['nombres' => 'PAULA ANDREA', 'apellidos' => 'BAQUE RIVADENEIRA', 'genero' => 'femenino'],
            ['nombres' => 'JOSÉ DAVID', 'apellidos' => 'CABRERA VALDIVIESO', 'genero' => 'masculino'],
            ['nombres' => 'KAREN LISETTE', 'apellidos' => 'AGUIRRE CHÁVEZ', 'genero' => 'femenino'],
            ['nombres' => 'RAÚL ESTEBAN', 'apellidos' => 'SARANGO MORA', 'genero' => 'masculino'],
            ['nombres' => 'DIANA MARCELA', 'apellidos' => 'YAGUAL BURGOS', 'genero' => 'femenino'],
            ['nombres' => 'OSCAR IVÁN', 'apellidos' => 'BARRETO REYES', 'genero' => 'masculino'],
            ['nombres' => 'EVELYN TATIANA', 'apellidos' => 'CHOEZ VILLAMAR', 'genero' => 'femenino'],
            ['nombres' => 'MAURICIO XAVIER', 'apellidos' => 'SUÁREZ CARRIÓN', 'genero' => 'masculino'],
            ['nombres' => 'LILIANA BEATRIZ', 'apellidos' => 'GUTIÉRREZ PINEDA', 'genero' => 'femenino'],
            ['nombres' => 'ALEXIS RODRIGO', 'apellidos' => 'PANCHI ULLAURI', 'genero' => 'masculino'],
            ['nombres' => 'JENNIFER KATHERINE', 'apellidos' => 'MOREIRA ARTEAGA', 'genero' => 'femenino'],
            ['nombres' => 'JOEL ANTONIO', 'apellidos' => 'RAMÍREZ TOAPANTA', 'genero' => 'masculino'],
            ['nombres' => 'ANDREA MICHELLE', 'apellidos' => 'CORDERO SALVADOR', 'genero' => 'femenino'],
            ['nombres' => 'BRYAN STALIN', 'apellidos' => 'NAVARRETE CHÁVEZ', 'genero' => 'masculino'],
            ['nombres' => 'ALISSON DAYANA', 'apellidos' => 'SÁNCHEZ DELGADO', 'genero' => 'femenino'],
            ['nombres' => 'JONATHAN ALEXIS', 'apellidos' => 'GARCÍA SANTIAGO', 'genero' => 'masculino'],
            ['nombres' => 'YADIRA MONSERRATE', 'apellidos' => 'LÓPEZ FIGUEROA', 'genero' => 'femenino'],
            ['nombres' => 'HENRY ADRIÁN', 'apellidos' => 'MORA QUINDE', 'genero' => 'masculino'],
            ['nombres' => 'MELISSA NATHALY', 'apellidos' => 'ESPINOZA AVILÉS', 'genero' => 'femenino'],
            ['nombres' => 'DARWIN ANDRÉS', 'apellidos' => 'ANCHUNDIA VALENCIA', 'genero' => 'masculino'],
            ['nombres' => 'KATHERINE ESTEFANÍA', 'apellidos' => 'MERO BETANCOURT', 'genero' => 'femenino'],
            ['nombres' => 'REINALDO JAVIER', 'apellidos' => 'LUCAS MACKLIFT', 'genero' => 'masculino'],
            ['nombres' => 'LISSETH CAROLINA', 'apellidos' => 'QUIMÍ GILER', 'genero' => 'femenino'],
            ['nombres' => 'MAXIMILIANO JOSÉ', 'apellidos' => 'VÉLIZ VILLA', 'genero' => 'masculino'],
            ['nombres' => 'CINDY MICHELLE', 'apellidos' => 'ROBLES TUMBACO', 'genero' => 'femenino'],
            ['nombres' => 'WILSON GERMÁN', 'apellidos' => 'MONTALVÁN CARRERA', 'genero' => 'masculino'],
            ['nombres' => 'LADY DIANA', 'apellidos' => 'ROMERO BAQUERIZO', 'genero' => 'femenino'],
            ['nombres' => 'KEVIN JOEL', 'apellidos' => 'CANTOS BARZOLA', 'genero' => 'masculino'],
            ['nombres' => 'JOSSELYN MARIBEL', 'apellidos' => 'CONTRERAS ORELLANA', 'genero' => 'femenino'],
            ['nombres' => 'ALEJANDRO JOSUÉ', 'apellidos' => 'PARRALES VERA', 'genero' => 'masculino'],
            ['nombres' => 'ESTEFANÍA MARÍA', 'apellidos' => 'PONCE NEIRA', 'genero' => 'femenino'],
            ['nombres' => 'VICENTE RAMÓN', 'apellidos' => 'ZAMBRANO ARROYO', 'genero' => 'masculino'],
            ['nombres' => 'FÁTIMA CARMITA', 'apellidos' => 'CASTRO HOLGUÍN', 'genero' => 'femenino'],
            ['nombres' => 'MARCO VINICIO', 'apellidos' => 'FIALLOS MEDINA', 'genero' => 'masculino'],
            ['nombres' => 'YOMIRA MICHELLE', 'apellidos' => 'CEVALLOS MUÑOZ', 'genero' => 'femenino'],
            ['nombres' => 'JOSÉ LUIS', 'apellidos' => 'REYES RODRÍGUEZ', 'genero' => 'masculino'],
            ['nombres' => 'ARIANA BELÉN', 'apellidos' => 'SUÁREZ PÁRRAGA', 'genero' => 'femenino'],
            ['nombres' => 'EDUARDO JAVIER', 'apellidos' => 'RIVERA VILLÓN', 'genero' => 'masculino'],
            ['nombres' => 'SAMANTHA ELIZABETH', 'apellidos' => 'ALTAMIRANO GUZMÁN', 'genero' => 'femenino'],
            ['nombres' => 'ROBERTO ISRAEL', 'apellidos' => 'CALERO SANTOS', 'genero' => 'masculino'],
            ['nombres' => 'NAYELI ESTHER', 'apellidos' => 'RUIZ MOREIRA', 'genero' => 'femenino'],
            ['nombres' => 'LUIS FELIPE', 'apellidos' => 'CHILÁN MENDOZA', 'genero' => 'masculino'],
            ['nombres' => 'DANIELA SOFÍA', 'apellidos' => 'CASTILLO ICHIGUEN', 'genero' => 'femenino'],
            ['nombres' => 'CHRISTIAN JAVIER', 'apellidos' => 'MERA MEZA', 'genero' => 'masculino'],
            ['nombres' => 'ALICIA DEL ROCÍO', 'apellidos' => 'INTRIAGO VIELMA', 'genero' => 'femenino'],
            ['nombres' => 'JAIRO ADRIÁN', 'apellidos' => 'PIGUAVE TUMBACO', 'genero' => 'masculino'],
            ['nombres' => 'VERÓNICA ALEJANDRA', 'apellidos' => 'ABAD CARRANZA', 'genero' => 'femenino'],
            ['nombres' => 'GUSTAVO ADOLFO', 'apellidos' => 'MUÑOZ GUILLEN', 'genero' => 'masculino'],
            ['nombres' => 'YARITZA GISELLE', 'apellidos' => 'PARRA SANTANA', 'genero' => 'femenino'],
            ['nombres' => 'FERNANDO JOSUÉ', 'apellidos' => 'ORTEGA COELLO', 'genero' => 'masculino'],
            ['nombres' => 'SONIA MARGARITA', 'apellidos' => 'SALTOS ÁLAVA', 'genero' => 'femenino'],
            ['nombres' => 'BYRON STEVEN', 'apellidos' => 'TIGRERO PINCAY', 'genero' => 'masculino'],
            ['nombres' => 'GÉNESIS NICOLE', 'apellidos' => 'LOOR VINTIMILLA', 'genero' => 'femenino'],
            ['nombres' => 'FRANKLIN ISAAC', 'apellidos' => 'BARZOLA PINTO', 'genero' => 'masculino'],
            ['nombres' => 'GINGER MARIANELA', 'apellidos' => 'CHÁVEZ BASTIDAS', 'genero' => 'femenino'],
            ['nombres' => 'SANTIAGO NICOLÁS', 'apellidos' => 'CAMPOS FARÍAS', 'genero' => 'masculino'],
            ['nombres' => 'CATHERINE MISHELL', 'apellidos' => 'MERINO RAMOS', 'genero' => 'femenino'],
            ['nombres' => 'FABRICIO ANDRÉS', 'apellidos' => 'CACERES QUIJIJE', 'genero' => 'masculino'],
            ['nombres' => 'ANGIE KATHERINE', 'apellidos' => 'HIDALGO SEGURA', 'genero' => 'femenino'],
            ['nombres' => 'DANIEL ARTURO', 'apellidos' => 'CADENA MONTENEGRO', 'genero' => 'masculino'],
            ['nombres' => 'BRITTANY DAYANNA', 'apellidos' => 'SALINAS ESPINOZA', 'genero' => 'femenino'],
            ['nombres' => 'JORGE LUIS', 'apellidos' => 'NAVAS CORDERO', 'genero' => 'masculino'],
            ['nombres' => 'SCARLETT SOFÍA', 'apellidos' => 'MOLINA GONZÁLEZ', 'genero' => 'femenino'],
            ['nombres' => 'ARMANDO JOSÉ', 'apellidos' => 'MEJILLÓN CEDEÑO', 'genero' => 'masculino'],
            ['nombres' => 'KERLY NATHALY', 'apellidos' => 'QUIROLA GONZABAY', 'genero' => 'femenino'],
            ['nombres' => 'HUGO FERNANDO', 'apellidos' => 'ZAMBRONI PARRAGA', 'genero' => 'masculino'],
            ['nombres' => 'DOME KATHERINE', 'apellidos' => 'ZUÑIGA SORIA', 'genero' => 'femenino'],
            ['nombres' => 'VÍCTOR HUGO', 'apellidos' => 'AVILÉS MENÉNDEZ', 'genero' => 'masculino'],
            ['nombres' => 'MELANY XIOMARA', 'apellidos' => 'TOALA BARRETO', 'genero' => 'femenino'],
            ['nombres' => 'EDWIN PATRICIO', 'apellidos' => 'DEL PEZO MACÍAS', 'genero' => 'masculino'],
            ['nombres' => 'ADRIANA MICHELLE', 'apellidos' => 'RODRÍGUEZ ZAMBRANO', 'genero' => 'femenino'],
            ['nombres' => 'RAMIRO GABRIEL', 'apellidos' => 'ANILEMA CHICAIZA', 'genero' => 'masculino'],
            ['nombres' => 'JULIANA ALEJANDRA', 'apellidos' => 'VÉLEZ FARÍAS', 'genero' => 'femenino'],
            ['nombres' => 'ISAAC DAVID', 'apellidos' => 'REZABALA CASTRO', 'genero' => 'masculino'],
            ['nombres' => 'MÓNICA NATALIA', 'apellidos' => 'BERMEO ROMÁN', 'genero' => 'femenino'],
            ['nombres' => 'CARLOS JULIO', 'apellidos' => 'ZAMBRANO CORNEJO', 'genero' => 'masculino'],
            ['nombres' => 'PRISCILLA ESTEFANÍA', 'apellidos' => 'SOLÓRZANO GARCÍA', 'genero' => 'femenino'],
            ['nombres' => 'BORIS WILFRIDO', 'apellidos' => 'RODRÍGUEZ FLORES', 'genero' => 'masculino'],
            ['nombres' => 'JACQUELINE MARÍA', 'apellidos' => 'CRUZ ANDRADE', 'genero' => 'femenino'],
            ['nombres' => 'AARÓN BENJAMÍN', 'apellidos' => 'ZAPATA MORENO', 'genero' => 'masculino'],
            ['nombres' => 'MARTHA IRENE', 'apellidos' => 'VILLEGAS PONCE', 'genero' => 'femenino'],
        ];

        foreach ($estudiantes as $i => $est) {
            $num = $i + 1;
            $cedula = sprintf('090000%04d', $num);

            $alumno = Alumno::create([
                'codigo' => $cedula,
                'numero_cedula' => $cedula,
                'nombres' => $est['nombres'],
                'apellidos' => $est['apellidos'],
                'genero' => $est['genero'],
                'representante_user_id' => $representantes[(int) floor(($num - 1) / 4)],
            ]);

            $paraleloId = (int) ceil($num / 25);

            Matricula::create([
                'codigo_matricula' => 'MAT-' . $cedula,
                'alumno_id' => $alumno->id,
                'curso_id' => 1,
                'paralelo_id' => $paraleloId,
                'periodo_id' => 1,
            ]);
        }
    }
}
