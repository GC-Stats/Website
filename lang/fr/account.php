<?php

return [
    'errors' => [
        'last_auth_method' => "Impossible de retirer ce moyen de connexion : c'est le dernier restant sur ce compte.",
        'social_already_linked' => 'Ce compte est déjà lié à un autre profil.',
        'password_requires_email' => "Vous devez d'abord ajouter un email à votre compte avant de définir un mot de passe.",
        'email_required_for_password' => "Vous ne pouvez pas retirer votre adresse email tant qu'un mot de passe est défini sur ce compte — retirez d'abord votre mot de passe, ou ajoutez une autre adresse.",
        'email_blocked' => 'Cette adresse email ne peut pas être utilisée pour créer un compte.',
        'email_disposable' => "Les adresses email temporaires/jetables ne sont pas acceptées. Merci d'utiliser une adresse permanente.",
        'email_undeliverable' => "Cette adresse email ne semble pas valide ou joignable.",
        'social_blocked' => 'Ce compte ne peut pas être utilisé pour créer un profil.',
        'sanctioned_global' => "Votre compte fait l'objet d'une sanction active : :reason",
        'sanctioned_team' => "Vous faites l'objet d'une sanction active sur cette équipe : :reason",
        'cannot_report_user' => "Vous ne pouvez pas vous signaler vous-même.",
    ],
];
