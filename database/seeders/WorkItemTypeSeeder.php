<?php

namespace Database\Seeders;

use App\Models\WorkItemType;
use Illuminate\Database\Seeder;

class WorkItemTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'support' => ['name' => 'Soporte', 'categories' => ['incident' => 'Incidente', 'request' => 'Solicitud', 'question' => 'Pregunta']],
            'integration' => ['name' => 'Integración', 'categories' => ['planning' => 'Planificación', 'implementation' => 'Implementación', 'maintenance' => 'Mantenimiento']],
            'planning' => ['name' => 'Planificación', 'categories' => ['discovery' => 'Descubrimiento', 'definition' => 'Definición', 'estimation' => 'Estimación']],
            'development' => ['name' => 'Desarrollo', 'categories' => ['feature' => 'Funcionalidad', 'bug' => 'Error', 'refactor' => 'Refactorización']],
            'improvement' => ['name' => 'Mejora', 'categories' => ['process' => 'Proceso', 'performance' => 'Rendimiento', 'experience' => 'Experiencia']],
            'investigation' => ['name' => 'Investigación', 'categories' => ['research' => 'Investigación', 'analysis' => 'Análisis', 'proof-of-concept' => 'Prueba de concepto']],
        ];

        foreach ($types as $typeSlug => $typeData) {
            $type = WorkItemType::query()->updateOrCreate(
                ['slug' => $typeSlug],
                ['name' => $typeData['name'], 'is_active' => true],
            );

            foreach ($typeData['categories'] as $categorySlug => $categoryName) {
                $type->categories()->updateOrCreate(
                    ['slug' => $categorySlug],
                    ['name' => $categoryName, 'is_active' => true],
                );
            }
        }
    }
}
