<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'actif'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'actif' => 'boolean',
        ];
    }

    public function tokens()
    {
        return $this->hasMany(PersonalAccessToken::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role', 'name');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_user');
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        if ($this->permissions()->where('name', $permission)->exists()) {
            return true;
        }

        return $this->role()
            ->whereHas('permissions', fn ($query) => $query->where('name', $permission))
            ->exists();
    }

    public function createToken(string $name): string
    {
        $plain = bin2hex(random_bytes(32));

        $this->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plain),
            'abilities' => ['*'],
        ]);

        return $plain;
    }
}
