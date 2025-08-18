<?php
namespace Modules\Informasi\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Modules\Auth\Models\AuthUser;
// use Illuminate\Database\Eloquent\SoftDeletes;

class Informasi extends Model
{
    use HasFactory, HasUuids;
    // use SoftDeletes;

    protected $table = 'informasis';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'judul',
        'konten',
        'slug',
        'gambar',
        'kategori',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function createdBy()
    {
        return $this->belongsTo(AuthUser::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(AuthUser::class, 'updated_by');
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    protected static function booted()
    {
        static::creating(function ($informasi) {
            // Set slug kalau belum ada
            if (empty($informasi->slug)) {
                $informasi->slug = static::generateUniqueSlug($informasi->judul);
            }
        });
    }

    protected static function generateUniqueSlug(string $judul, ?string $ignoreId = null): string
    {
        $base = 'informasi-' . Str::slug($judul);
        $slug = $base;
        $i = 2;

        while (static::query()
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
