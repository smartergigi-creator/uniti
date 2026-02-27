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

        $departmentEbooks = Category::firstOrCreate(
            ['slug' => 'department-e-books'],
            [
                'name' => 'Department e-Books',
                'image' => null,
                'parent_id' => $companyEbooks->id,
            ]
        );

        foreach (['Manual', 'Handmade'] as $name) {
            Category::firstOrCreate(
                ['slug' => Str::slug('department-e-books-' . $name)],
                [
                    'name' => $name,
                    'image' => null,
                    'parent_id' => $departmentEbooks->id,
                ]
            );
        }

        $department = Category::firstOrCreate(
            ['slug' => 'department'],
            [
                'name' => 'Department',
                'image' => null,
                'parent_id' => null,
            ]
        );

        $ebooks = Category::firstOrCreate(
            ['slug' => 'ebooks'],
            [
                'name' => 'Ebooks',
                'image' => null,
                'parent_id' => null,
            ]
        );

        foreach (['Manual', 'Handmade'] as $name) {
            Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'image' => null,
                    'parent_id' => $ebooks->id,
                ]
            );
        }
    }
}
