<?php
/*
 * File name: UserRepository.php
 * Last modified: 2024.04.18 at 17:22:51
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Repositories;

use App\Models\User;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class UserRepository
 * @package App\Repositories
 * @version July 10, 2018, 11:44 am UTC
 *
 * @method User findWithoutFail($id, $columns = ['*'])
 * @method User find($id, $columns = ['*'])
 * @method User first($columns = ['*'])
 */
class UserRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'id',
        'name',
        'email',
        'password',
        'api_token',
        'role_id',
        'remember_token'
    ];

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return User::class;
    }
    /**
 * Vérifie si l'utilisateur (le follower) existe dans la table 'users'
 * avant de lui permettre de faire une action.
 */
public function checkFollowerExists($followerId)
{
    // On cherche l'utilisateur par son ID.
    // find() renvoie l'utilisateur s'il existe, ou 'null' s'il n'existe pas.
    $user = $this->model->find($followerId);

    if (!$user) {
        // Si l'utilisateur n'existe pas, on arrête tout avec une erreur claire.
        throw new \Exception("Erreur : L'utilisateur qui tente d'effectuer l'abonnement n'existe pas dans la table users.");
    }

    return $user;
}
/**
 * Vérifie si l'utilisateur cible (celui qu'on veut suivre) existe
 */
public function checkTargetUserExists($followedId)
{
    // On cherche la cible dans la table 'users'
    $target = $this->model->find($followedId);

    if (!$target) {
        // Si la cible n'existe pas, on lance une erreur spécifique
        throw new \Exception("Action impossible : l'utilisateur que vous tentez de suivre n'existe pas.");
    }

    return $target;
}

/**
 * Récupère la liste des utilisateurs suivis par un utilisateur spécifique
 */
public function getFollowingList($userId)
{
    // On récupère l'utilisateur
    $user = $this->model->findOrFail($userId);

    // On retourne la liste des utilisateurs contenus dans la relation 'following'
    // On peut ajouter un get() pour exécuter la requête
    return $user->following()->get();
}


}
