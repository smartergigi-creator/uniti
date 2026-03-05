<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Seed category and subcategory defaults.
     */
    public function run(): void
    {
        $companyEbooks = Category::firstOrCreate(
            ['slug' => 'company-e-books'],
            [
                'name' => 'Company e-Books',
                'image' => null,
                'parent_id' => null,
            ]
        );

        $companyChildren = ['Company Profile', 'Department e-Books', 'SOP', 'Stationary', 'Flyers', 'Calendar'];
        $companyChildMap = [];

        foreach ($companyChildren as $name) {
            $category = Category::firstOrCreate(
                [
                    'name' => $name,
                    'parent_id' => $companyEbooks->id,
                ],
                [
                    'slug' => Str::slug($name),
                    'image' => null,
                ]
            );

            $companyChildMap[$name] = $category;
        }

        $departmentEbooks = $companyChildMap['Department e-Books'];
        $companyProfile = $companyChildMap['Company Profile'];

        $companyProfileSubPages = [
            'ASI',
            'USTDI Company Profile',
        ];

        foreach ($companyProfileSubPages as $name) {
            Category::firstOrCreate(
                [
                    'name' => $name,
                    'parent_id' => $companyProfile->id,
                ],
                [
                    'slug' => Str::slug('company-profile-' . $name),
                    'image' => null,
                ]
            );
        }

        $departmentSubPages = [
            'Accounting',
            'General Support',
            'HRM',
            'Logistics',
            'Marketing',
            'MIS - Managed Information System',
            'Purchasing and Distribution',
            'Sales',
            'Special Project',
            'Technical',
        ];

        foreach ($departmentSubPages as $name) {
            Category::firstOrCreate(
                [
                    'name' => $name,
                    'parent_id' => $departmentEbooks->id,
                ],
                [
                    'slug' => Str::slug('department-e-books-' . $name),
                    'image' => null,
                ]
            );
        }

    }
}
