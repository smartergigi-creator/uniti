<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;


// class User extends Authenticatable
class User extends Authenticatable implements JWTSubject

{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
protected $fillable = [

    'serp_id',
    'name',
    'email',
    'password',
    'serp_token',
    'role',

    'can_upload',
    'can_share',

    'upload_limit',
    'upload_reset_at',
    'share_limit',
    'last_login_at',

    'status',
    'created_from',
    'created_by'
];




    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'can_upload' => 'boolean',
            'can_share' => 'boolean',
            'upload_limit' => 'integer',
            'upload_reset_at' => 'datetime',
            'share_limit' => 'integer',
            'last_login_at' => 'datetime',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
        ];
    }
    public function ebooks()
    {
        return $this->hasMany(\App\Models\Ebook::class, 'user_id');
    }
  

    public function hasRole($role)
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isSpecialProject(): bool
    {
        return $this->hasAnyRole([
            'specialproject',
            'special_project_dtp',
        ]);
    }

    public function hasUnlimitedPdfAccess(): bool
    {
        return $this->isAdmin() || $this->isSpecialProject();
    }

}
