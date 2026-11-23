<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class ProductionDataSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * @var array<string, list<array<string, mixed>>>
     */
    private array $data = [];

    /**
     * @var array<int, int>
     */
    private array $roleIds = [];

    /**
     * @var array<int, int>
     */
    private array $permissionIds = [];

    /**
     * @var array<int, int>
     */
    private array $categoryIds = [];

    /**
     * @var array<int, int>
     */
    private array $albumIds = [];

    public function run(): void
    {
        $fixture = database_path('seeders/data/current_content.json');

        if (! File::isFile($fixture)) {
            throw new RuntimeException("Fixture introuvable : {$fixture}");
        }

        $this->data = json_decode(File::get($fixture), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function (): void {
            $this->seedRoles();
            $this->seedPermissions();
            $this->seedRolePermissions();
            $adminId = $this->seedAdmin();
            $this->seedCategories();
            $this->seedContenus($adminId);
            $this->seedMedias($adminId);
            $this->seedAlbums();
            $this->seedAlbumPhotos();
            $this->seedSiteSettings();
            $this->seedRayons();
            $this->seedSimpleTable('produits');
            $this->seedSimpleTable('livebets');
            $this->seedSimpleTable('contact_messages');
            $this->attachAdminPermissions($adminId);
        });

        if (! app()->runningUnitTests()) {
            $this->extractMedia();
            $this->createStorageLink();
        }
    }

    private function seedRoles(): void
    {
        foreach ($this->data['roles'] as $row) {
            $existing = DB::table('roles')->where('name', $row['name'])->first();

            if ($existing) {
                DB::table('roles')->where('id', $existing->id)->update([
                    'label' => $row['label'],
                    'updated_at' => $row['updated_at'] ?? now(),
                ]);
                $this->roleIds[(int) $row['id']] = (int) $existing->id;

                continue;
            }

            $id = DB::table('roles')->insertGetId([
                'id' => $row['id'],
                'name' => $row['name'],
                'label' => $row['label'],
                'created_at' => $row['created_at'] ?? now(),
                'updated_at' => $row['updated_at'] ?? now(),
            ]);
            $this->roleIds[(int) $row['id']] = (int) $id;
        }
    }

    private function seedPermissions(): void
    {
        foreach ($this->data['permissions'] as $row) {
            $existing = DB::table('permissions')->where('name', $row['name'])->first();

            if ($existing) {
                DB::table('permissions')->where('id', $existing->id)->update([
                    'label' => $row['label'],
                    'updated_at' => $row['updated_at'] ?? now(),
                ]);
                $this->permissionIds[(int) $row['id']] = (int) $existing->id;

                continue;
            }

            $id = DB::table('permissions')->insertGetId([
                'id' => $row['id'],
                'name' => $row['name'],
                'label' => $row['label'],
                'created_at' => $row['created_at'] ?? now(),
                'updated_at' => $row['updated_at'] ?? now(),
            ]);
            $this->permissionIds[(int) $row['id']] = (int) $id;
        }
    }

    private function seedRolePermissions(): void
    {
        $rows = [];

        foreach ($this->data['permission_role'] as $row) {
            $roleId = $this->roleIds[(int) $row['role_id']] ?? null;
            $permissionId = $this->permissionIds[(int) $row['permission_id']] ?? null;

            if ($roleId && $permissionId) {
                $rows[] = ['role_id' => $roleId, 'permission_id' => $permissionId];
            }
        }

        if ($rows !== []) {
            DB::table('permission_role')->insertOrIgnore($rows);
        }
    }

    private function seedAdmin(): int
    {
        $email = (string) env('ADMIN_EMAIL', 'admin@lavoixdabouloussi.org');
        $password = env('ADMIN_PASSWORD');
        $existing = DB::table('users')->where('email', $email)->first();

        if ($existing) {
            DB::table('users')->where('id', $existing->id)->update([
                'name' => 'Administrateur principal',
                'role' => 'admin',
                'actif' => true,
                'updated_at' => now(),
            ]);

            return (int) $existing->id;
        }

        if (! $password) {
            $password = Str::password(18);
            $this->command?->warn("ADMIN_PASSWORD absent : mot de passe généré pour {$email} : {$password}");
        }

        return (int) DB::table('users')->insertGetId([
            'name' => 'Administrateur principal',
            'email' => $email,
            'password' => Hash::make((string) $password),
            'role' => 'admin',
            'actif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedCategories(): void
    {
        foreach ($this->data['categories'] as $row) {
            $existing = DB::table('categories')->where('slug', $row['slug'])->first();

            if ($existing) {
                DB::table('categories')->where('id', $existing->id)->update([
                    'rubrique' => $row['rubrique'],
                    'nom' => $row['nom'],
                    'description' => $row['description'],
                    'ordre' => $row['ordre'],
                    'updated_at' => $row['updated_at'] ?? now(),
                ]);
                $this->categoryIds[(int) $row['id']] = (int) $existing->id;

                continue;
            }

            $id = DB::table('categories')->insertGetId([
                'id' => $row['id'],
                'rubrique' => $row['rubrique'],
                'nom' => $row['nom'],
                'slug' => $row['slug'],
                'description' => $row['description'],
                'ordre' => $row['ordre'],
                'created_at' => $row['created_at'] ?? now(),
                'updated_at' => $row['updated_at'] ?? now(),
            ]);
            $this->categoryIds[(int) $row['id']] = (int) $id;
        }
    }

    private function seedContenus(int $adminId): void
    {
        $rows = [];

        foreach ($this->data['contenus'] as $row) {
            $row['user_id'] = $adminId;
            $row['categorie_id'] = $row['categorie_id'] === null
                ? null
                : ($this->categoryIds[(int) $row['categorie_id']] ?? null);
            $row['date_publication'] = $this->nullableDate($row['date_publication'] ?? null);
            $rows[] = $row;
        }

        $this->upsertRows('contenus', $rows, ['slug'], ['id', 'slug']);
    }

    private function seedMedias(int $adminId): void
    {
        $rows = [];

        foreach ($this->data['medias'] as $row) {
            $row['user_id'] = $adminId;
            $rows[] = $row;
        }

        $this->upsertRows('medias', $rows, ['id'], ['id']);
    }

    private function seedAlbums(): void
    {
        foreach ($this->data['albums'] as $row) {
            $row['date_album'] = $this->nullableDate($row['date_album'] ?? null);
            $existing = DB::table('albums')->where('slug', $row['slug'])->first();

            if ($existing) {
                DB::table('albums')->where('id', $existing->id)->update([
                    'titre' => $row['titre'],
                    'description' => $row['description'],
                    'rubrique' => $row['rubrique'],
                    'couverture' => $row['couverture'],
                    'date_album' => $row['date_album'],
                    'statut' => $row['statut'],
                    'updated_at' => $row['updated_at'] ?? now(),
                ]);
                $this->albumIds[(int) $row['id']] = (int) $existing->id;

                continue;
            }

            $id = DB::table('albums')->insertGetId([
                'id' => $row['id'],
                'titre' => $row['titre'],
                'slug' => $row['slug'],
                'description' => $row['description'],
                'rubrique' => $row['rubrique'],
                'couverture' => $row['couverture'],
                'date_album' => $row['date_album'],
                'statut' => $row['statut'],
                'created_at' => $row['created_at'] ?? now(),
                'updated_at' => $row['updated_at'] ?? now(),
            ]);
            $this->albumIds[(int) $row['id']] = (int) $id;
        }
    }

    private function seedAlbumPhotos(): void
    {
        $rows = [];

        foreach ($this->data['album_photos'] as $row) {
            $albumId = $this->albumIds[(int) $row['album_id']] ?? null;

            if ($albumId === null) {
                continue;
            }

            $row['album_id'] = $albumId;
            $rows[] = $row;
        }

        $this->upsertRows('album_photos', $rows, ['id'], ['id']);
    }

    private function seedSiteSettings(): void
    {
        $this->upsertRows('site_settings', $this->data['site_settings'], ['cle'], ['id', 'cle']);
    }

    private function seedRayons(): void
    {
        $this->upsertRows('rayons', $this->data['rayons'], ['nom'], ['id', 'nom']);
    }

    private function seedSimpleTable(string $table): void
    {
        $rows = $this->data[$table] ?? [];

        if ($rows !== []) {
            $this->upsertRows($table, $rows, ['id'], ['id']);
        }
    }

    private function attachAdminPermissions(int $adminId): void
    {
        $rows = DB::table('permissions')->pluck('id')
            ->map(fn (int $permissionId): array => [
                'user_id' => $adminId,
                'permission_id' => $permissionId,
            ])
            ->all();

        if ($rows !== []) {
            DB::table('permission_user')->insertOrIgnore($rows);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $uniqueBy
     * @param  list<string>  $excludedFromUpdate
     */
    private function upsertRows(string $table, array $rows, array $uniqueBy, array $excludedFromUpdate = []): void
    {
        if ($rows === []) {
            return;
        }

        $columns = array_keys($rows[0]);
        $updateColumns = array_values(array_diff($columns, array_merge($uniqueBy, $excludedFromUpdate)));

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table($table)->upsert($chunk, $uniqueBy, $updateColumns);
        }
    }

    private function nullableDate(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }

    private function extractMedia(): void
    {
        $archive = database_path('seeders/data/media.zip');
        $manifestFile = database_path('seeders/data/media_manifest.json');

        if (! File::isFile($archive) || ! File::isFile($manifestFile)) {
            return;
        }

        $manifest = json_decode(File::get($manifestFile), true, 512, JSON_THROW_ON_ERROR);
        $destination = storage_path('app/public/media');
        $zip = new ZipArchive;

        if ($zip->open($archive) !== true) {
            throw new RuntimeException("Archive média illisible : {$archive}");
        }

        foreach ($manifest as $relativePath => $hash) {
            $target = $destination.'/'.$relativePath;

            if (File::exists($target)) {
                continue;
            }

            $contents = $zip->getFromName($hash);

            if ($contents === false) {
                $zip->close();

                throw new RuntimeException("Média introuvable dans l’archive : {$relativePath}");
            }

            File::ensureDirectoryExists(dirname($target));
            File::put($target, $contents);
        }

        $zip->close();
    }

    private function createStorageLink(): void
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        if (! File::exists($link) && ! is_link($link)) {
            File::link($target, $link);
        }
    }
}
