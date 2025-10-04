<?php

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Str;

class CategoryObserver
{
    /**
     * Before creating a category
     */
    public function creating(Category $category): void
    {
        // 1. Generate slug automatically
        if (empty($category->slug)) {
            $category->slug = $this->generateUniqueSlug($category->name);
        }

        // 2. Set order automatically if not defined
        if (is_null($category->order)) {
            $category->order = $this->getNextOrder($category->parent_id);
        }

        // 3. Calculate paths
        $this->updatePaths($category);
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
     */
    public function updated(Category $category): void
    {
        // If paths changed, update all descendants
        if (isset($category->oldPath)) {
            $this->updateDescendants(
                $category,
                $category->oldPath,
                $category->oldPathSlugs,
                $category->oldPathNames
            );
        }
    }

    /**
     * Before deleting a category
     */
    public function deleting(Category $category): void
    {
        // Children will be deleted automatically thanks to "onDelete('cascade')"
        // But we can reorder remaining categories at the same level
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

            if ($parent) {
                $category->path = $parent->path . '/' . $category->id;
                $category->path_slugs = $parent->path_slugs . '/' . $category->slug;
                $category->path_names = $parent->path_names . '/' . $category->name;
            } else {
                // Parent not found → treat as root
                $category->path = (string) $category->id;
                $category->path_slugs = $category->slug;
                $category->path_names = $category->name;
            }
        }
    }

    /**
     * Update paths of all descendants
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

        // Find all descendants (those starting with old path)
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