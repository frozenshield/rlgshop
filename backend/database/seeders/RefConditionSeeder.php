<?php

namespace Database\Seeders;

use App\Models\RefCondition;
use Illuminate\Database\Seeder;

class RefConditionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conditions = [
            'Near Mint',
            'Damaged',
            'Lightly Played',
            'Moderately Played',
            'Heavily Played',
            'MISB',
            'BIB',
            'Loose',
            'Brandnew',
        ];

        foreach ($conditions as $condition) {
            RefCondition::firstOrCreate(['desc' => $condition]);
        }
    }
}
