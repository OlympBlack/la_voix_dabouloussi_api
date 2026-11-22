# Note d'audit backend — étape 1

L'ancien site (`voixdabouloussi_ancien`, non modifié) reposait sur un CMS PHP maison :
- table `groupe_page` (id, titre, texte, emplacement, file, image2, image3, date, heure, metatitre, metamotcle…) ;
- table `identification` (login, mpasse MD5, email, keympasseoublie) ;
- sous-bases DMS (`lavoixda_dmsblog` : articles, users) et SuperMarché (`lavoixda_supermarche` : rayon, produit, livebet).

Conséquences pour la nouvelle API Laravel :
1. Conserver **toutes** les rubriques via un modèle `contenus` générique (`rubrique`, `categorie_id`, `meta`, `document`, `image`, `statut`) plutôt qu'un champ `emplacement` opaque.
2. Conserver les galeries photo JDA (`albums`, `album_photos`), le blog DMS (rubrique `dms` + likes) et le jeu LIVBET (`rayons`, `produits`, `livebets`).
3. Remplacer l'authentification faible (MD5, mots de passe en dur, sessions sans contrôle) par des tokens hachés, des rôles/permissions en base et des FormRequests de validation.
4. Récupérer les fichiers (PDF journaux/rapports, images JDA/parlement/accueil) dans `storage/app/public/media` ; aucune lecture du répertoire ancien à l'exécution.
5. Exposer les endpoints publics et admin détaillés dans `AUDIT_ET_MATRICE.md` à la racine du dépôt interface.
