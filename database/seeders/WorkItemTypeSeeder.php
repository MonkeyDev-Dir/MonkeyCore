<?php

namespace Database\Seeders;

use App\Models\WorkItemType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WorkItemTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Support' => ['Incident', 'Request', 'Question'],
            'Integration' => ['Planning', 'Implementation', 'Maintenance'],
            'Planning' => ['Discovery', 'Definition', 'Estimation'],
            'Development' => ['Feature', 'Bug', 'Refactor'],
            'Improvement' => ['Process', 'Performance', 'Experience'],
            'Investigation' => ['Research', 'Analysis', 'Proof of concept'],
        ];

        foreach ($types as $typeName => $categoryNames) {
            $type = WorkItemType::query()->updateOrCreate(['slug' => Str::slug($typeName)], ['name' => $typeName, 'is_active' => true]);

            foreach ($categoryNames as $categoryName) {
                $type->categories()->updateOrCreate(['slug' => Str::slug($categoryName)], ['name' => $categoryName, 'is_active' => true]);
            }
        }
    }
}
