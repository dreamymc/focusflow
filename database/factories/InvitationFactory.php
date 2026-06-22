<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\Workspace;
use App\Enums\InviteStatus;
use App\Enums\WorkspaceRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'email' => fake()->safeEmail(),
            'role' => WorkspaceRole::Member->value,
            'token' => Str::random(40),
            'status' => InviteStatus::Pending->value,
        ];
    }
}
