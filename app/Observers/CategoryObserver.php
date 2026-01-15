<?php

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class CategoryObserver
{
    /**
     * Before creating a category
     */
    public function creating(Category $category): void
    {
        // 1. Generate slug
        if (empty($category->slug)) {
            $category->slug = $this->generateUniqueSlug($category->name);
        }

        // 2. Set order
        if (is_null($category->order)) {
            $category->order = $this->getNextOrder($category->parent_id);
        }

        // 3. Don't calculate paths yet (no ID)
    }

    public function created(Category $category): void
    {
        // Now ID exists, calculate paths and update
        $this->updatePaths($category);

        // Update directly in DB (no events triggered)
        DB::table('categories')
            ->where('id', $category->id)
            ->update([
                'path' => $category->path,
                'path_slugs' => $category->path_slugs,
                'path_names' => $category->path_names,
            ]);

        // Refresh model to get updated values
        $category->refresh();
    }

    /**
     * Before updating a category
     */
    public function updating(Category $category): void
    {
        // Check if slug was manually modified
        if ($category->isDirty('name') && !$category->isDirty('slug')) {
            // Name changed but not slug → regenerate slug
            $category->slug = $this->generateUniqueSlug($category->name, $category->id);
        }

        // If parent_id, name or slug changed, recalculate paths
        if ($category->isDirty(['parent_id', 'name', 'slug'])) {
            // Save old paths to update descendants
            $oldPath = $category->getOriginal('path');
            $oldPathSlugs = $category->getOriginal('path_slugs');
            $oldPathNames = $category->getOriginal('path_names');

            // Recalculate new paths
            $this->updatePaths($category);

            // Store old values for "updated" hook
            $category->oldPath = $oldPath;
            $category->oldPathSlugs = $oldPathSlugs;
            $category->oldPathNames = $oldPathNames;
        }
    }

    /**
     * After updating a category
     * @throws Throwable
     */
    public function updated(Category $category): void
    {
        // If paths changed, update all descendants in a transaction
        if (isset($category->oldPath)) {
            DB::transaction(function () use ($category) {
                $this->updateDescendants(
                    $category,
                    $category->oldPath,
                    $category->oldPathSlugs,
                    $category->oldPathNames
                );
            });
        }

        unset($category->oldPath, $category->oldPathSlugs, $category->oldPathNames);
    }

    /**
     * Before deleting a category
     */
    public function deleting(Category $category): void
    {
        // Note: Children will have parent_id set to NULL (onDelete('set null'))
        // Reorder remaining categories at the same level
        $this->reorderAfterDelete($category);
    }

    // ========================================
    // PRIVATE METHODS
    // ========================================

    /**
     * Generate a unique slug
     */
    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        // Check uniqueness
        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }

    /**
     * Check if a slug already exists
     */
    private function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = Category::where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get next available order for a given level
     */
    private function getNextOrder(?int $parentId): int
    {
        $maxOrder = Category::where('parent_id', $parentId)->max('order');

        return ($maxOrder ?? 0) + 1;
    }

    /**
     * Calculate and update the 3 paths
     */
    private function updatePaths(Category $category): void
    {
        if (is_null($category->parent_id)) {
            // Root category
            $category->path = (string) $category->id;
            $category->path_slugs = $category->slug;
            $category->path_names = $category->name;
        } else {
            // Sub-category: get parent paths
            $parent = Category::find($category->parent_id);

            if (!$parent) {
                throw new InvalidArgumentException(
                    "Parent category with ID $category->parent_id not found"
                );
            }

            // Validate that parent has paths calculated
            if (empty($parent->path) || empty($parent->path_slugs) || empty($parent->path_names)) {
                // throw new InvalidArgumentException(
                //     "Parent category {$parent->id} has incomplete paths. Cannot create sub-category."
                // );
                $this->updatePaths($parent); // recursively ensure parent has paths
                $parent->save();
            }

            $category->path = $parent->path . '/' . $category->id;
            $category->path_slugs = $parent->path_slugs . '/' . $category->slug;
            $category->path_names = $parent->path_names . '/' . $category->name;
        }
    }

    /**
     * Update paths of all descendants
     * Optimized for large hierarchies using direct SQL updates
     */
    private function updateDescendants(
        Category $category,
        ?string $oldPath,
        ?string $oldPathSlugs,
        ?string $oldPathNames
    ): void {
        if (empty($oldPath)) {
            return;
        }

        // Count descendants to choose the best strategy
        $descendantsCount = Category::where('path', 'like', $oldPath . '/%')->count();

        if ($descendantsCount === 0) {
            return;
        }

        // For large hierarchies (100+), use direct SQL for better performance
        if ($descendantsCount > 100) {
            // Escape values for SQL safety
            $oldPathEscaped = addslashes($oldPath);
            $newPathEscaped = addslashes($category->path);
            $oldPathSlugsEscaped = addslashes($oldPathSlugs);
            $newPathSlugsEscaped = addslashes($category->path_slugs);
            $oldPathNamesEscaped = addslashes($oldPathNames);
            $newPathNamesEscaped = addslashes($category->path_names);

            DB::table('categories')
                ->where('path', 'like', $oldPath . '/%')
                ->update([
                    'path' => DB::raw("REPLACE(path, '{$oldPathEscaped}', '{$newPathEscaped}')"),
                    'path_slugs' => DB::raw("REPLACE(path_slugs, '{$oldPathSlugsEscaped}', '{$newPathSlugsEscaped}')"),
                    'path_names' => DB::raw("REPLACE(path_names, '{$oldPathNamesEscaped}', '{$newPathNamesEscaped}')"),
                    'updated_at' => now(),
                ]);
        } else {
            // For smaller hierarchies, use Eloquent for better event handling
            $descendants = Category::where('path', 'like', $oldPath . '/%')->get();

            foreach ($descendants as $descendant) {
                // Replace old path with new in each descendant
                $descendant->path = str_replace(
                    $oldPath,
                    $category->path,
                    $descendant->path
                );

                $descendant->path_slugs = str_replace(
                    $oldPathSlugs,
                    $category->path_slugs,
                    $descendant->path_slugs
                );

                $descendant->path_names = str_replace(
                    $oldPathNames,
                    $category->path_names,
                    $descendant->path_names
                );

                // Save without triggering events (avoid recursion)
                $descendant->saveQuietly();
            }
        }
    }

    /**
     * Reorder after deletion
     */
    private function reorderAfterDelete(Category $category): void
    {
        // Get all categories at same level with higher order
        $siblings = Category::where('parent_id', $category->parent_id)
            ->where('order', '>', $category->order)
            ->orderBy('order')
            ->get();

        // Decrement their order by 1
        foreach ($siblings as $sibling) {
            --$sibling->order;
            $sibling->saveQuietly();
        }
    }
}