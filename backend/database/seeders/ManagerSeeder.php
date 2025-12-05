<?php

namespace Database\Seeders;

use App\Models\Manager;
use Illuminate\Database\Seeder;

class ManagerSeeder extends Seeder
{
    public function run(): void
    {
        $managers = Manager::getDefaultManagers();

        foreach ($managers as $manager) {
            Manager::updateOrCreate(
                ['name' => $manager['name']],
                $manager
            );
        }
    }
}

