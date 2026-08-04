<?php

use App\Models\User;

describe('user management', function () {
    test('owner can view users page', function () {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->get(route('settings.users.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('settings/Users'));
    });

    test('admin can view users page', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('settings.users.index'))
            ->assertOk();
    });

    test('member cannot view users page', function () {
        $member = User::factory()->member()->create();

        $this->actingAs($member)
            ->get(route('settings.users.index'))
            ->assertForbidden();
    });

    test('owner can create a member', function () {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->post(route('settings.users.store'), [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'member',
            ])
            ->assertRedirect(route('settings.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'role' => 'member',
        ]);
    });

    test('owner can create an admin', function () {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->post(route('settings.users.store'), [
                'name' => 'New Admin',
                'email' => 'newadmin@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'admin',
            ])
            ->assertRedirect(route('settings.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'newadmin@example.com',
            'role' => 'admin',
        ]);
    });

    test('admin can create a member', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('settings.users.store'), [
                'name' => 'New Member',
                'email' => 'newmember@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'member',
            ])
            ->assertRedirect(route('settings.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'newmember@example.com',
            'role' => 'member',
        ]);
    });

    test('admin attempting to create an admin gets downgraded to member', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('settings.users.store'), [
                'name' => 'Sneaky Admin',
                'email' => 'sneaky@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'admin', // Admins can't create admins
            ])
            ->assertRedirect(route('settings.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'sneaky@example.com',
            'role' => 'member', // Should be downgraded
        ]);
    });

    test('member cannot create a user', function () {
        $member = User::factory()->member()->create();

        $this->actingAs($member)
            ->post(route('settings.users.store'), [
                'name' => 'Hacker',
                'email' => 'hacker@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'member',
            ])
            ->assertForbidden();
    });

    test('owner can delete a user', function () {
        $owner = User::factory()->owner()->create();
        $target = User::factory()->member()->create();

        $this->actingAs($owner)
            ->delete(route('settings.users.destroy', $target))
            ->assertRedirect(route('settings.users.index'));

        $this->assertDatabaseMissing('users', [
            'id' => $target->id,
        ]);
    });

    test('owner cannot delete themselves', function () {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->delete(route('settings.users.destroy', $owner))
            ->assertForbidden();
    });

    test('owner cannot delete another owner', function () {
        $owner1 = User::factory()->owner()->create();
        $owner2 = User::factory()->owner()->create();

        $this->actingAs($owner1)
            ->delete(route('settings.users.destroy', $owner2))
            ->assertForbidden();
    });

    test('admin cannot delete a user', function () {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->member()->create();

        $this->actingAs($admin)
            ->delete(route('settings.users.destroy', $target))
            ->assertForbidden();
    });

    test('owner can transfer ownership', function () {
        $owner = User::factory()->owner()->create();
        $target = User::factory()->admin()->create();

        $this->actingAs($owner)
            ->post(route('settings.users.transfer-ownership'), [
                'user_id' => $target->id,
            ])
            ->assertRedirect(route('settings.users.index'));

        expect($owner->fresh()->role)->toBe('admin');
        expect($target->fresh()->role)->toBe('owner');
    });

    test('admin cannot transfer ownership', function () {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->member()->create();

        $this->actingAs($admin)
            ->post(route('settings.users.transfer-ownership'), [
                'user_id' => $target->id,
            ])
            ->assertForbidden();
    });

    test('owner cannot transfer ownership to themselves', function () {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->post(route('settings.users.transfer-ownership'), [
                'user_id' => $owner->id,
            ])
            ->assertSessionHasErrors(['user_id']);
    });
});
