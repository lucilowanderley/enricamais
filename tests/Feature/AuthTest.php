<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_registration_creates_personal_workspace_with_defaults(): void
    {
        $this->post('/cadastro', [
            'name' => 'Maria',
            'email' => 'maria@example.com',
            'password' => 'segredo123',
            'password_confirmation' => 'segredo123',
        ])->assertRedirect(route('dashboard'));

        $user = User::where('email', 'maria@example.com')->firstOrFail();
        $workspace = $user->workspaces()->first();

        $this->assertNotNull($workspace);
        $this->assertSame('owner', $workspace->pivot->role);
        $this->assertSame($workspace->id, $user->current_workspace_id);
        $this->assertGreaterThan(10, $workspace->categories()->count());
        $this->assertSame(1, $workspace->accounts()->count());

        $this->get('/')->assertOk()->assertSee('Painel');
    }

    public function test_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'segredo123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'errada'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $user->email, 'password' => 'segredo123'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
