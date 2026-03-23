<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Seed demo projects for local development.
     */
    public function run(): void
    {
        $companies = User::query()->where('role', 'company')->get();

        if ($companies->isEmpty()) {
            return;
        }

        $projectTemplates = [
            [
                'title' => 'Campus Event Mobile App',
                'description' => 'Build a mobile-friendly app for campus events with schedule, speaker profiles and ticket QR check-ins.',
                'requirements' => 'Basic Vue or React experience, REST API basics and clean UI implementation.',
                'tech_stack' => ['Vue', 'TypeScript', 'Laravel', 'MySQL'],
                'status' => 'open',
                'max_students' => 3,
                'deadline' => now()->addDays(25)->toDateString(),
            ],
            [
                'title' => 'Inventory Dashboard for SME',
                'description' => 'Create a dashboard for stock monitoring, low-stock alerts and CSV import for product data.',
                'requirements' => 'Experience with data tables and charting libraries is preferred.',
                'tech_stack' => ['Vue', 'Tailwind', 'Chart.js', 'Laravel'],
                'status' => 'open',
                'max_students' => 2,
                'deadline' => now()->addDays(18)->toDateString(),
            ],
            [
                'title' => 'AI Resume Parser Prototype',
                'description' => 'Prototype a backend service that extracts key CV fields and produces a structured profile summary.',
                'requirements' => 'Good backend fundamentals and validation/security mindset.',
                'tech_stack' => ['Laravel', 'Python', 'OpenAI API'],
                'status' => 'draft',
                'max_students' => 2,
                'deadline' => now()->addDays(35)->toDateString(),
            ],
            [
                'title' => 'Internal Support Chat Module',
                'description' => 'Implement real-time team chat with basic moderation tools and unread counters for support agents.',
                'requirements' => 'Understanding of websockets/events and clean state management.',
                'tech_stack' => ['Laravel', 'Reverb', 'Vue', 'Redis'],
                'status' => 'open',
                'max_students' => 4,
                'deadline' => now()->addDays(30)->toDateString(),
            ],
            [
                'title' => 'Company Portfolio Website Revamp',
                'description' => 'Redesign legacy company pages into a modern responsive site with CMS-managed content blocks.',
                'requirements' => 'Strong frontend design sense and accessibility best practices.',
                'tech_stack' => ['Vue', 'Tailwind', 'Headless CMS'],
                'status' => 'open',
                'max_students' => 2,
                'deadline' => now()->addDays(20)->toDateString(),
            ],
            [
                'title' => 'Analytics ETL for Hiring Funnel',
                'description' => 'Build a nightly ETL job and dashboard that tracks applicant funnel conversion metrics.',
                'requirements' => 'SQL proficiency and data modeling basics are required.',
                'tech_stack' => ['Laravel', 'MySQL', 'Metabase'],
                'status' => 'draft',
                'max_students' => 2,
                'deadline' => now()->addDays(40)->toDateString(),
            ],
        ];

        foreach ($projectTemplates as $index => $template) {
            $company = $companies[$index % $companies->count()];

            Project::query()->updateOrCreate(
                [
                    'company_user_id' => $company->id,
                    'title' => $template['title'],
                ],
                [
                    'description' => $template['description'],
                    'requirements' => $template['requirements'],
                    'tech_stack' => $template['tech_stack'],
                    'status' => $template['status'],
                    'max_students' => $template['max_students'],
                    'deadline' => $template['deadline'],
                ]
            );
        }
    }
}
