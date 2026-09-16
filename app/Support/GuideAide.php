<?php

namespace App\Support;

/**
 * Contenu du guide « Besoin d'aide » : une source unique utilisée par la
 * page web et par l'export PDF, pour qu'ils ne divergent jamais.
 */
class GuideAide
{
    /**
     * Sections du guide.
     * Chaque section : titre, icône, intro, étapes, astuces, et exemples
     * d'e-mails que l'utilisateur peut recevoir (objet + corps résumé).
     */
    public static function sections(): array
    {
        return [
            'demarrage' => [
                'icone' => '🚀',
                'titre' => 'Premiers pas',
                'intro' => "MGDS regroupe toutes les applications de votre mairie derrière un identifiant unique. Après connexion, vous arrivez sur le Gestionnaire des applications : chaque tuile est une application à laquelle vous avez accès.",
                'etapes' => [
                    "Connectez-vous avec votre identifiant (prenom.nom) et votre mot de passe.",
                    "À la première connexion, un mot de passe provisoire vous est remis : il est valable 48 heures et doit être changé.",
                    "Depuis le hub, cliquez sur une tuile pour ouvrir l'application correspondante.",
                    "Une pastille bleue sur une tuile indique un élément qui vous attend (tâche, message, rappel…).",
                ],
                'astuces' => [
                    "La roue dentée à côté de votre identifiant ouvre « Mon compte ».",
                    "Les applications affichées dépendent des droits accordés par votre mairie. Chaque application se coche séparément : « Gestion des utilisateurs » ouvre tout, et le droit de modification d'une application donne sa lecture — mais rien au-delà.",
                    "Mot de passe oublié ? Le bouton « Recevoir un mot de passe provisoire » vous en envoie un par e-mail. Tant que vous ne l'utilisez pas, votre mot de passe habituel reste valable.",
                ],
                'emails' => [
                    [
                        'quand'  => "Un compte vient d'être créé pour vous",
                        'objet'  => "MGDS — Vos identifiants de connexion",
                        'corps'  => "Bonjour Prénom Nom,\n\nUn compte vous a été créé sur la plateforme MGDS pour la Mairie de …\n\nIdentifiant : prenom.nom\nMot de passe provisoire : ••••••••\n\n⚠️ Ce mot de passe est valable 48 heures. À votre première connexion, vous devrez choisir un nouveau mot de passe.",
                    ],
                ],
            ],

            'taches' => [
                'icone' => '📊',
                'titre' => 'Tableau des suivis (tâches)',
                'intro' => "Le Tableau des suivis liste les tâches de travail de la mairie. Tout le monde peut consulter ses tâches ; seules les personnes ayant le droit « Tableau des suivis — gestion » peuvent en créer.",
                'etapes' => [
                    "Créer une tâche : bouton « + Ajouter une tâche », en désignant obligatoirement un responsable.",
                    "Le responsable reçoit un e-mail et voit sa ligne surlignée en jaune tant qu'il n'a pas répondu.",
                    "Il ouvre la tâche puis choisit « Je prends en charge cette tâche » ou « Je substitue à un employé ».",
                    "En cas de substitution, l'employé désigné reçoit l'e-mail ; le responsable garde l'accès.",
                    "Pour clôturer : un commentaire est obligatoire, ainsi que la photo « une fois finie » si une photo « à faire » existait.",
                ],
                'astuces' => [
                    "Seuls le créateur, un administrateur et la direction (Maire, Directeur de Cabinet, DGS) peuvent modifier ou supprimer une tâche.",
                    "Le responsable peut changer la personne substituée grâce au crayon ✏️ à côté de son nom.",
                    "Joignez des documents à une tâche (PDF, Word, Excel, images) en les glissant dans la zone 📎, à la création comme au moment de la clôture pour répondre avec un compte rendu ou une facture. Cinq fichiers au plus, 10 Mo chacun.",
                    "Les documents d'une tâche confidentielle restent réservés aux personnes désignées.",
                ],
                'emails' => [
                    [
                        'quand'  => "Une tâche vous est affectée",
                        'objet'  => "MGDS — Tâche affectée (12-1)",
                        'corps'  => "Bonjour,\n\nVous avez une nouvelle tâche sur laquelle vous avez été affecté.\n\n🔖 Référence : 12-1\n👥 Service : Service Technique / CTM\n👤 Responsable : prenom.nom\n📅 Clôture prévue : 31/07/2026",
                    ],
                    [
                        'quand'  => "Le jour de l'échéance, si la tâche n'est pas terminée",
                        'objet'  => "⏰ MGDS — Tâche à réaliser aujourd'hui (12-1)",
                        'corps'  => "Bonjour Prénom,\n\n⏰ Ne l'oubliez pas : vous avez une tâche de travail à réaliser au plus tard aujourd'hui, le 31/07/2026.\n\n🔖 Référence : 12-1\n👥 Service : Service Technique / CTM",
                    ],
                    [
                        'quand'  => "Une tâche est clôturée",
                        'objet'  => "MGDS — Tâche clôturée (12-1)",
                        'corps'  => "Bonjour,\n\nLa tâche 12-1 vient d'être clôturée.\n\nCommentaire de clôture : « Travail terminé, voirie remise en état. »",
                    ],
                ],
            ],

            'marche' => [
                'icone' => '🛍️',
                'titre' => 'Marché',
                'intro' => "L'application Marché permet de préparer vos marchés : vue aérienne de la ville, zones de marché, disposition des exposants en 2D et en 3D, et registre des commerçants.",
                'etapes' => [
                    "Onglet Ville : téléversez la vue aérienne, puis ajoutez vos zones (place, rue, trottoir, parking).",
                    "Déplacez, redimensionnez (poignée) et faites pivoter les zones (double-clic) en mode « Déplacer / redimensionner ».",
                    "Cliquez sur une zone pour préparer son marché : type de marché, dimensions, disposition, écart entre exposants.",
                    "Ajoutez les obstacles (arbre, fontaine, poteau, obstacle temporaire) : les stands trop proches sont retirés automatiquement.",
                    "La vue 3D se tourne au glisser ; les boutons ➕ ➖ ↺ ↻ 🎯 fonctionnent sur tous les appareils.",
                ],
                'astuces' => [
                    "Le compteur indique en direct le nombre d'exposants possibles et les mètres linéaires.",
                    "L'onglet Registre récapitule les venues et les montants par commerçant.",
                    "Une seconde présentation est proposée à la comparaison : des marchés datés, chacun listant ses endroits (rue, place…), avec une recherche pour retrouver l'un d'eux rapidement. Le bandeau en haut de page bascule d'une présentation à l'autre.",
                    "L'onglet Estimations chiffre une prestation pour un commerçant (emplacement, branchement…) et l'exporte en PDF.",
                    "Les candidatures arrivées par « Contacter votre Mairie » se traitent dans la boîte « Demande adhésion marché » du Centre de Messagerie : vous pouvez discuter avec le candidat, puis l'accepter — il rejoint alors le registre — ou le refuser avec un motif.",
                ],
                'emails' => [],
            ],

            'contact' => [
                'icone' => '📇',
                'titre' => 'Fiche Contact',
                'intro' => "L'annuaire interne de la mairie : une ligne « Standard » par service, complétée automatiquement par les agents enregistrés.",
                'etapes' => [
                    "Ajoutez un numéro de standard avec le bouton ➕ de la ligne du service.",
                    "Modifiez un standard avec le crayon ✏️ (téléphone, indicatif, adresse mail).",
                    "Téléchargez l'annuaire complet en PDF avec le bouton « Télécharger en PDF ».",
                ],
                'astuces' => [
                    "Le document est privé et confidentiel : ne le diffusez pas hors de la mairie.",
                    "Pour un employé, c'est la « fonction » saisie dans sa fiche qui s'affiche (ex : Agent d'accueil).",
                ],
                'emails' => [],
            ],

            'messagerie' => [
                'icone' => '📬',
                'titre' => 'Centre de Messagerie',
                'intro' => "Les habitants écrivent à la mairie depuis la page publique « Contacter votre Mairie ». Chaque demande devient un ticket, classé dans un dossier selon son avancement.",
                'etapes' => [
                    "Réception : un nouveau message de l'habitant attend une réponse.",
                    "Réponse : la mairie a répondu au dernier message.",
                    "Clôturé : la conversation est fermée ; elle reste consultable 6 mois.",
                    "Réouverture demandée : l'habitant demande la réouverture ; vous acceptez ou refusez. Si la demande avait été transférée, elle revient au centre de tri — la personne qui l'avait transférée — et non à celle qui l'a clôturée, avec le rappel de qui l'avait eue.",
                    "Transféré : les demandes que vous avez orientées vers un service ou une personne. Elles quittent votre Réception — sauf si vous vous êtes mis parmi les destinataires — et sortent de ce dossier une fois clôturées.",
                    "Demande adhésion marché : les candidatures des commerçants, réservées aux personnes ayant le droit sur l'application Marché.",
                    "Vous ne recevez ces demandes que si la case « Réceptionner les messages extérieurs » est cochée sur votre compte (réglée par votre mairie).",
                ],
                'astuces' => [
                    "Les messages envoyés ne peuvent être ni modifiés ni supprimés.",
                    "Vous pouvez joindre jusqu'à 3 photos ou documents à chaque message.",
                    "Une mairie n'apparaît sur « Contacter votre Mairie » que si quelqu'un y est autorisé à réceptionner et que son abonnement est valide.",
                ],
                'emails' => [
                    [
                        'quand'  => "Un habitant écrit ou répond",
                        'objet'  => "MGDS — Nouveau message concernant votre demande (Voirie abîmée)",
                        'corps'  => "Bonjour,\n\nVous avez un nouveau message concernant la demande « Voirie abîmée ». Merci de consulter votre ticket 1 pour lire le message en attente de réponse.\n\n🎫 Ticket : 1 · Service Technique / CTM\n👤 De : Dupont Marie · marie@example.fr",
                    ],
                    [
                        'quand'  => "La demande de l'habitant est clôturée",
                        'objet'  => "MGDS — Votre demande a été clôturée (Voirie abîmée)",
                        'corps'  => "Bonjour,\n\nVotre demande « Voirie abîmée » (ticket 1) a été clôturée. Vous pouvez encore demander sa réouverture pendant 15 jours. Passé ce délai, la conversation restera consultable mais ne pourra plus être rouverte.",
                    ],
                ],
            ],

            'pensebete' => [
                'icone' => '🗓️',
                'titre' => 'Pense-bête (Calendrier & Notes)',
                'intro' => "Votre bloc-notes personnel : un calendrier de rappels et des notes classées en dossiers. Personne d'autre ne voit vos rappels ni vos notes.",
                'etapes' => [
                    "Calendrier : cliquez sur un jour (ou « ➕ Ajouter ») pour créer un rappel avec un texte et une pièce jointe.",
                    "Un e-mail vous est envoyé le jour J pour ne rien oublier.",
                    "Recherchez vos rappels par mot-clé ou entre deux dates.",
                    "Notes : titre obligatoire, texte, photo, et classement par dossier.",
                    "Cochez « Être notifié par e-mail de cette note » et choisissez la date : une cloche 🔔 apparaît sur la note.",
                ],
                'astuces' => [
                    "Le tri ⇅ bascule entre ordre alphabétique et du plus récent au plus ancien.",
                    "Sur téléphone, la note s'ouvre en bas de l'écran ; la croix la referme.",
                ],
                'emails' => [
                    [
                        'quand'  => "Le jour d'un rappel du calendrier",
                        'objet'  => "🔔 MGDS — Rappel de votre calendrier (14/07/2026)",
                        'corps'  => "Bonjour Prénom,\n\nNe pas oublier : vous avez noté quelque chose dans votre calendrier m-gds pour aujourd'hui, le 14/07/2026.\n\n« Préparer la cérémonie du 14 juillet »",
                    ],
                    [
                        'quand'  => "Le jour d'une note à rappeler",
                        'objet'  => "🔔 MGDS — Rappel de note : Idées budget",
                        'corps'  => "Bonjour Prénom,\n\nNe pas oublier : vous aviez demandé à être rappelé aujourd'hui au sujet de votre note « Idées budget ».",
                    ],
                ],
            ],

            'dialogue' => [
                'icone' => '💬',
                'titre' => 'Boîte de dialogue',
                'intro' => "L'espace d'entraide entre mairies : posez une question sur une application, les autres communes y répondent.",
                'etapes' => [
                    "Choisissez la rubrique correspondant à l'application concernée.",
                    "Cliquez sur « + Ajouter une Question » : elle est publiée à votre nom et celui de votre mairie.",
                    "Répondez aux questions des autres mairies directement sous la question.",
                    "Quand votre problème est résolu, clôturez votre question avec ✅.",
                ],
                'astuces' => [
                    "Une question clôturée ne peut plus recevoir de réponse et ne peut pas être rouverte.",
                    "La pastille bleue compte les questions encore sans réponse.",
                ],
                'emails' => [],
            ],

            'planning' => [
                'icone' => '🕒',
                'titre' => 'Planning (heures & signature)',
                'intro' => "Le planning établit les heures de la semaine, agent par agent. Chacun consulte le sien et le signe ; la personne qui a le droit « Planning — gestion » remplit la grille de toute la mairie.",
                'etapes' => [
                    "Créez une semaine avec son numéro et son année : une ligne est ouverte pour chaque agent.",
                    "Renseignez la durée due en heures (35, 35.5…), puis les créneaux de chaque journée.",
                    "Cochez « Repos » pour un jour non travaillé : ses horaires ne comptent plus.",
                    "Indiquez un retard en minutes le cas échéant : il se déduit des heures faites et se cumule en fin de ligne.",
                    "Chaque agent vérifie sa semaine et la signe. Toute modification ultérieure annule la signature.",
                ],
                'astuces' => [
                    "Les absences déclarées apparaissent d'elles-mêmes dans le planning : une absence de dernière minute neutralise la journée sans qu'on retouche la grille, et les créneaux saisis sont conservés.",
                    "Le filtre par service prépare une équipe à la fois ; l'export PDF reprend le filtre affiché.",
                    "La colonne VAR. compare le total au contrat : +2h00 si vous avez fait 37 h pour 35 h dues.",
                ],
                'emails' => [],
            ],

            'absences' => [
                'icone' => '🗓️',
                'titre' => 'Absences & binôme',
                'intro' => "Les absences se déclarent depuis la Gestion des utilisateurs. Pendant une absence, le binôme désigné sur le compte reprend les tâches de la personne.",
                'etapes' => [
                    "Ouvrez « Absences » depuis la Gestion des utilisateurs, puis « Ajouter une absence ».",
                    "Choisissez la personne, le motif (arrêt de travail, vacances, formation…) et les dates.",
                    "Joignez le justificatif s'il est déjà disponible — sinon vous pourrez l'ajouter plus tard.",
                    "Onglet « Historique & Justificatifs » : les absences terminées, toujours modifiables.",
                ],
                'astuces' => [
                    "Une absence reste corrigeable après coup : c'est là qu'on ajoute un arrêt reçu en retard ou qu'on prolonge les dates.",
                    "Le binôme se règle sur la fiche de la personne, pas sur l'absence : il vaut pour toutes ses absences.",
                    "Pendant l'absence, le binôme reprend tous les droits de la personne absente — gestion des utilisateurs, attribution des tâches… — et les perd dès la fin de l'absence. Un bandeau le lui rappelle sur le hub.",
                    "Les justificatifs ne sont consultables que depuis cette page et disparaissent avec les données de la mairie.",
                ],
                'emails' => [],
            ],

            'compte' => [
                'icone' => '👤',
                'titre' => 'Mon compte & sécurité',
                'intro' => "Vous gérez votre adresse e-mail et votre mot de passe. Votre identifiant, votre statut et vos droits sont définis par votre mairie.",
                'etapes' => [
                    "Modifier votre e-mail : saisissez votre mot de passe actuel pour confirmer.",
                    "Changer de mot de passe : 8 caractères minimum.",
                    "Mot de passe oublié : le bouton « Recevoir un mot de passe provisoire » vous en envoie un, utilisable une seule fois et valable 2 heures.",
                    "Votre mairie peut aussi vous regénérer un mot de passe provisoire, valable 48 heures.",
                ],
                'astuces' => [
                    "Ne partagez jamais votre mot de passe : les actions sont enregistrées dans les journaux d'activité.",
                    "Les journaux sont conservés 6 mois conformément aux règles CNIL.",
                    "MGDS ne vous demandera jamais vos coordonnées bancaires, une pièce d'identité ou vos codes d'accès — par e-mail comme ailleurs. Au moindre doute, prévenez votre mairie ou le support.",
                    "Ignorer un mot de passe provisoire ne change rien : l'ancien continue de fonctionner et le provisoire expire seul.",
                ],
                'emails' => [],
            ],
        ];
    }
}
