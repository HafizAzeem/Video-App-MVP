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

        // Create default questions for LAS MVP - Book Report Theme
        $questions = [
            [
                'text' => 'Which book did you read?',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'text' => 'Which part of the book did you find interesting?',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'text' => 'Have you experienced the contents of the book?',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'text' => 'What did you imagine through the interesting parts of the book?',
                'order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($questions as $question) {
            Question::create($question);
        }
    }
}
