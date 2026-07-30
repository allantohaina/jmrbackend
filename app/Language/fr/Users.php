<?php

return [
    'register' => [
        'success' => 'Utilisateur créé avec succès',
    ],
    'login' => [
        'success' => 'Connexion réussie',
        'locked' => 'Compte temporairement verrouillé',
        'invalid' => 'Email ou mot de passe incorrect',
        'required' => 'Email et mot de passe requis',
    ],
    'profile' => [
        'updated' => 'Profil mis à jour avec succès',
        'deleted' => 'Compte supprimé avec succès',
    ],
    'admin' => [
        'updated' => 'Utilisateur mis à jour avec succès',
        'deleted' => 'Utilisateur supprimé avec succès',
    ],
    'errors' => [
        'not_found' => 'Utilisateur non trouvé',
        'delete_account' => 'Erreur lors de la suppression du compte',
        'delete_user' => 'Erreur lors de la suppression de l\'utilisateur',
        'required_fields' => 'Champs requis manquants',
    ],
    'refresh' => [
        'required' => 'Refresh token requis',
        'invalid' => 'Refresh token invalide',
        'expired' => 'Refresh token expiré',
    ],
    'logout' => [
        'success' => 'Déconnexion réussie',
    ],
    'validation' => [
        'email' => [
            'required' => 'L\'email est requis',
            'valid_email' => 'L\'email doit être valide',
            'is_unique' => 'Cet email est déjà utilisé',
        ],
        'password' => [
            'required' => 'Le mot de passe est requis',
            'min_length' => 'Le mot de passe doit contenir au moins 8 caractères',
        ],
        'first_name' => [
            'required' => 'Le prénom est requis',
            'min_length' => 'Le prénom doit contenir au moins 2 caractères',
        ],
        'last_name' => [
            'required' => 'Le nom est requis',
            'min_length' => 'Le nom doit contenir au moins 2 caractères',
        ],
        'phone' => [
            'is_unique' => 'Veuillez choisir un autre numéro de téléphone',
        ],
    ],
];
