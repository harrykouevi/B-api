<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;

class CommentApiController extends Controller
{
    
    // 2. MASQUER (is_hidden)
    public function maskComment($id)
    {
        $comment = Comment::findOrFail($id);
        $comment->update([
            'is_hidden' => true,
            'is_reported' => false // On considère le problème traité
        ]);

        return response()->json(['message' => 'Commentaire masqué avec succès (Shadowban).']);
    }

    // 3. SUPPRIMER (Soft Delete)
    public function destroyComment($id)
    {
        $comment = Comment::findOrFail($id);
        $comment->delete(); // Laravel remplit automatiquement deleted_at

        return response()->json(['message' => 'Commentaire supprimé définitivement de la vue publique.']);
    }

    // 4. APPROUVER / RÉTABLIR
    public function approveComment($id)
    {
        // On utilise withTrashed() au cas où le commentaire était supprimé
        $comment = Comment::withTrashed()->findOrFail($id);
        
        $comment->update([
            'is_reported' => false,
            'report_count' => 0,
            'is_hidden' => false
        ]);

        if ($comment->trashed()) {
            $comment->restore(); // On le sort de la corbeille s'il y était
        }

        return response()->json(['message' => 'Commentaire rétabli et signalé comme sain.']);
    }
}
