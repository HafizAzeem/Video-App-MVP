<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Create default questions for LAS MVP
        $questions = [
            [
                'text' => 'What is your biggest dream or aspiration?',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'text' => 'What challenge have you overcome that made you stronger?',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'text' => 'What makes you unique or special?',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'text' => 'What message would you share with the world?',
                'order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($questions as $question) {
            Question::create($question);
        }
    }
}
