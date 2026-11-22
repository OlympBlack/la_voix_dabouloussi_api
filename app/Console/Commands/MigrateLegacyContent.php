<?php

namespace App\Console\Commands;

use App\Models\Album;
use App\Models\AlbumPhoto;
use App\Models\Categorie;
use App\Models\Contenu;
use App\Models\Media;
use App\Models\Permission;
use App\Models\Rayon;
use App\Models\Role;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MigrateLegacyContent extends Command
{
    protected $signature = 'voix:migrate-legacy {--source= : Chemin du projet historique} {--fresh-media : Vider le dossier media avant copie}';

    protected $description = 'Copie les médias et importe le contenu historique de La Voix d’Abouloussi';

    private string $source;

    private string $destination;

    private ?User $admin = null;

    private int $fichiersCopies = 0;

    private int $contenusCrees = 0;

    private int $albumsCrees = 0;

    public function handle(): int
    {
        $this->source = rtrim((string) ($this->option('source') ?: env('LEGACY_PROJECT_PATH', '/home/firstone/JC/voixdabouloussi_ancien')), '/');
        $this->destination = storage_path('app/public/media');

        if (! File::isDirectory($this->source)) {
            $this->error("Projet historique introuvable : {$this->source}");

            return self::FAILURE;
        }

        if ($this->option('fresh-media') && File::isDirectory($this->destination)) {
            File::deleteDirectory($this->destination);
        }
        File::ensureDirectoryExists($this->destination);

        $this->seedRolesEtAdmin();
        $this->seedParametres();
        $this->seedCategories();

        $rapports = $this->copierDossier('assets/rapport', 'jda/documents', 'jda', ['pdf']);
        $jdaPhotos = $this->copierDossier('assets/img/JDA', 'jda/album', 'jda', ['jpg', 'jpeg', 'png', 'webp', 'jfif']);
        $rapport1 = $this->copierDossier('assets/img/JDA/rapport1', 'jda/albums/rapport-1', 'jda', ['jpg', 'jpeg', 'png']);
        $rapport2 = $this->copierDossier('assets/img/JDA/rapport2', 'jda/albums/rapport-2', 'jda', ['jpg', 'jpeg', 'png']);
        $rapport3 = $this->copierDossier('assets/img/JDA/rapport3', 'jda/albums/rapport-3', 'jda', ['jpg', 'jpeg', 'png']);
        $parlement = $this->copierDossier('assets/img/parlement', 'parlement/images', 'parlement', ['jpg', 'jpeg', 'png', 'webp', 'jfif']);
        $parlementRacine = $this->copierDossier('assets/img', 'parlement/images', 'parlement', ['jpg', 'jpeg', 'png', 'webp', 'jfif']);
        $accueil = $this->copierDossier('assets/img', 'accueil/images', 'page', ['jpg', 'jpeg', 'png', 'webp', 'jfif']);
        $equipe = $this->copierDossier('assets/img/team', 'contact/images', 'page', ['jpg', 'jpeg', 'png']);
        $uploads = $this->copierDossier('images/upload', 'archives/uploads', 'page', ['jpg', 'jpeg', 'png', 'webp', 'jfif', 'pdf', 'zip']);
        $carousel = $this->copierFichiersNommés([
            'assets/img/7191918-incliner-un-arbre-vers-le-haut-photo.jpg',
            'assets/img/baobab-troef2.jpg',
            'assets/img/agougou.jfif',
        ], 'accueil/carousel', 'page');
        $galerie = $this->copierFichiersNommés([
            'images/upload/33_316163004907.jpeg',
            'images/upload/32_34632050218.jpg',
            'images/upload/33_067294648809.jpeg',
        ], 'accueil/galerie', 'page');
        $imagesMonJournal = $this->copierFichiersNommés([
            'images/upload/51_0685497717.jpeg',
            'images/upload/52_14396523178.jpg',
        ], 'journaux/images', 'journal');
        $pdfsMonJournal = $this->copierFichiersNommés([
            'images/upload/2024_03_0202_28_490719_ABOULOUSSi_2022.pdf',
            'images/upload/2024_03_0202_27_3932629_ABOULOUSSi_2021.pdf',
            'images/upload/2024_03_0202_26_55253132_ABOULOUSSi_2020.docx.pdf',
            'images/upload/2024_03_0202_25_4821828_ABOULOUSSi_2015.docx.pdf',
            'images/upload/2024_03_0202_25_071312_ABOULOUSSi_2013.pdf',
            'images/upload/2024_03_0202_24_2121032_ABOULOUSSi_2011.pdf',
            'images/upload/2024_03_0202_22_232726_ABOULOUSSi_2010.pdf',
            'images/upload/2024_03_0202_21_1561116_ABOULOUSSi_2009.pdf',
            'images/upload/2024_03_0202_07_0471327_ABOULOUSSi_2008.pdf',
        ], 'journaux/pdf', 'journal');
        $imageDgp = $this->copierFichiersNommés([
            'images/upload/04_599409211058.jpeg',
        ], 'histoire/images', 'page');

        $this->seedJournaux($pdfsMonJournal);
        $this->seedJda($rapports, $rapport1, $rapport2, $rapport3);
        $this->seedParlement($parlement, $parlementRacine);
        $this->supprimerContenusDms();
        $this->seedContesHistoire($imageDgp);
        $this->seedPoemes();
        $this->seedPagesInstitutionnelles($galerie);
        $this->seedAlbums($jdaPhotos, $rapport1, $rapport2, $rapport3, $parlement, $parlementRacine);
        $this->seedRayons();
        $this->lierImagesParametres($carousel, $accueil, $equipe, $uploads, $imagesMonJournal, $imageDgp, $jdaPhotos);

        $this->creerLienStorage();

        $this->info("Migration terminée : {$this->fichiersCopies} fichiers copiés, {$this->contenusCrees} contenus créés, {$this->albumsCrees} albums créés.");

        return self::SUCCESS;
    }

    private function seedRolesEtAdmin(): void
    {
        $roles = [
            'admin' => ['Administrateur', ['manage_content', 'manage_users']],
            'redacteur' => ['Rédacteur', ['manage_content']],
            'lecteur' => ['Lecteur', []],
        ];

        foreach ($roles as $name => [$label, $permissions]) {
            $role = Role::firstOrCreate(['name' => $name], ['label' => $label]);
            $ids = [];
            foreach ($permissions as $permission) {
                $modele = Permission::firstOrCreate(['name' => $permission], ['label' => $permission === 'manage_content' ? 'Gérer les contenus' : 'Gérer les utilisateurs']);
                $ids[] = $modele->id;
            }
            $role->permissions()->sync($ids);
        }

        $email = env('ADMIN_EMAIL', 'admin@lavoixdabouloussi.org');
        $motDePasse = env('ADMIN_PASSWORD');
        $genere = false;
        if (! $motDePasse) {
            $motDePasse = Str::password(18);
            $genere = true;
        }

        $this->admin = User::firstOrCreate(
            ['email' => $email],
            ['name' => 'Administrateur principal', 'password' => Hash::make($motDePasse), 'role' => 'admin', 'actif' => true]
        );

        if ($genere && $this->admin->wasRecentlyCreated) {
            $this->warn("Mot de passe administrateur généré : {$motDePasse}");
            $this->warn('Définissez ADMIN_PASSWORD dans .env puis relancez si vous souhaitez le changer.');
        }
    }

    private function seedParametres(): void
    {
        $heroSousTitre = "La voix d'Abouloussi est un magazine de jeunesse créé en 2006. L'objectif est d'amener les élèves à s'approprier de leur propre culture à travers les histoires qu'on leurs raconte et au même titre que leurs parents à s'informer mais surtout les faire lire;";

        $objectif = <<<'TEXTE'
La voix d'Abouloussi est un magazine de jeunesse créé en 2006. L'objectif est d'amener les élèves à s'approprier de leur propre culture à travers les histoires qu'on leurs raconte et au même titre que leurs parents à s'informer mais surtout les faire lire. Aujourd'hui nous avons trois leviers d'interventions : le Parlement des Jeunes de Koko, Dis-moi grand-père et Mon journal. La voix d'abouloussi évolue sous la couverture de la Fondation Aide à l'Autonomie Tobé/Bénin représentée par Mme Karin OSTERTAG. Notre mission est d'organiser les informations à l'échelle locale pour les rendre accessibles et utiles à tous mais aussi favoriser l'émergence de jeunes localement ancrés aguerris pour le développement et la promotion de la culture itcha.
TEXTE;

        $journalUn = <<<'TEXTE'
Nous devons la création de ce journal à Mme Kristina, stagiaire de l’école Ayéké en 2006. Faisant allusion à Abouloussi, divinité phare de Koko qui par une voix d’oiseau alerte la population d’un éventuel événement heureux ou malheureux afin qu’elle se prépare. Ce journal est créé pour alerter, informer et former les lecteurs à affronter et surmonter les aléas de la société actuelle. Notre mission est d'organiser les informations à l'échelle locale pour les rendre accessibles et utiles à tous mais aussi favoriser l'émergence de jeunes localement ancrés aguerris pour le développement et la promotion de la culture itcha. Le journal est au numéro 9 grâce à la participation des gens de tout horizon du Bénin.
TEXTE;

        $journalDeux = <<<'TEXTE'
Présentation de KOKO sur le plan Cultuel

Situé au cœur du Département des Collines, Koko est l'un des neuf arrondissements de la commune de Bantè, un village à 6 Km de la paroisse de Bantè. Il compte trois (3) villages que sont: Akpaka, Issalè et Itchocobo ; seule la langue *Itcha* est parlée dans ces trois villages que compte Koko. C'est un Arrondissement qui a pour habitants 7259 selon le recensement conduit par l'Institut National de la Statistique et de l' Analyse Économique (INSAE) en 2013. KOKO dispose alors plusieurs divinités parmi lesquelles on peut citer: Abouloussi ; l'une des divinités premières car ladite divinité est un porteur des messages bien que mal aux sages de koko à travers un cri. A cela s'ajoute les divinités comme : Boukou-adja, Idi-ara (Dieu du fer), Tchankponon (Dieu de la terre), Djafou, Aboloudjè et Okouta-meta... La liste est vraiment longue mais cela ne nous empêche pas de penser à divinité Combattante, celle qui lutte pour la défense de ses populations. Adja-kiroko est alors une divinité qui est installée dans la brousse et est présente pendant les offrandes par un grand vent. Ces différentes divinités sont célébrées chaque 14 Août par sa Majesté Adé-yèmi Oyé-dayo, Roi de koko.
TEXTE;

        $dgpIntro = <<<'TEXTE'
Dans la quête permanente des fondements de la société itcha, cette rubrique est dédiée au partage des contes et histoires recueillies et des recherches menées auprès des personnes ressources, garants de la culture itcha, qui la protègent, la conservent et la transmettent chaque jour.
TEXTE;

        $jdaIntro = <<<'TEXTE'
L'ONG Jeunes Dynamiques et Actions (JDA) est une organisation à but non lucratif règle par la loi de 1901. Elle est politiquement neutre et d'énvergure nationale.
TEXTE;

        $jdaObjectifs = <<<'TEXTE'
L'ONG Jeunes Dynamiques et Actions (JDA) a pour objectif de:
- oeuvrer pour l'excellence en milieu scolaire
- Promouvoir l'entrepreneuriat des jeunes
- Pormouvoir le droit des jeunes, des femmes et des enfants ainsi que des personnes handicapées
- Oeuvre pour la restauration du couvert végétal.
TEXTE;

        $pjkIntro = <<<'TEXTE'
Il s’agit d’un parlement local créé en 2017 et parrainé par la Fondation Tobé. Ce parlement est composé des responsables de classe du CEG-Koko. Il est le fruit de l’expérience de l’ancien Jeune parlementaire du Bénin Duconois ADJAKOSSA avec l’accompagnement de Hyacinthe Adémonla ATTOUNON, ressortissants de l’école Ayékè.
TEXTE;

        $pjkMission = <<<'TEXTE'
Briser le joug de la honte de parler en publique (ce qui s’observe chez nombre d’apprenants de la localité) et amener les responsable à débattre des problèmes que rencontrent les élèves durant leur cursus en vue de trouver des voies de sortie.
TEXTE;

        $pjkOrganisation = <<<'TEXTE'
Le Parlement des Jeunes de Koko est composé de cinq commissions à savoir : la commission Environnement, commission Education Scolaire, Commission Education Sexuelle, Commission Loisirs et Culture et la Commission Education Civique et Sociale.
TEXTE;

        $pjkCommissions = [
            ['1- La Commission Environnement', "Cette commission se charge des questions de gestion de l'environnement. Elle organise des journées de salubrité et de reboisement dans les villages en vue de lutter contre la DAL et la désertification. Sensibilise pour l'atteinte des ODD."],
            ['2- La commission Education Sexuelle', "Elle travaille en collaboration avec les agents de santé de l'arrondissement de Koko, ceux de l'hôpital de Bantè et l'hôpital de zone de Savalou. Les points capitaux de ses interventions sont la lutte contre les grossesses en milieu scolaire, l'éducation à la santé sexuelle et l'encouragement à l'utilisation des méthodes de contraception. Cette commission dispose d'une cellule de consultation en conseils et accompagnement en cas de difficulté."],
            ['3- La Commission Education Scolaire', "C'est la commission la plus proche de l'administration et de l'Association des Parents d'Elève. Elle veille au bon déroulement des activités pédagogiques et exhorte surtout les élèves à donner le meilleur d'eux-mêmes. Elle initie depuis 2017 l'organisation des séances de renforcement des capacités en collaboration avec la Fondation Tobé."],
            ['4- La Commission Loisirs et Culture', 'Elle se charge des animations culturelles par la recherche et la valorisation des pratiques des savoir-faire, savoir-vivre et savoir-être itcha. La valorisation des rythmes itcha en vogue et ceux délaissés. Cette commission excite les apprenants à prendre goût de la chose culturelle.'],
            ['5- La Commission Education Civique et Sociale', 'Cette commission travaille avec le Surveillant Général pour instaurer la discipline au CEG-Koko et sensibilise la population au respect des codes de la route.'],
        ];

        SiteSetting::whereIn('cle', ['dms_whatsapp', 'dms_intro_titre', 'dms_intro_texte', 'dms_intro_image', 'favicon'])->delete();

        $parametres = [
            ['adresse', 'Adresse', '08 BP 0266 Cotonou, Bénin', 'contact'],
            ['email', 'E-mail principal', 'lavoixdabouloussi@gmail.com', 'contact'],
            ['email2', 'E-mail secondaire', 'contact@lavoixdabouloussi.org', 'contact'],
            ['telephone', 'Téléphone principal', '+229 01 97 52 74 24', 'contact'],
            ['telephone2', 'Téléphone secondaire', '+229 96 11 47 52', 'contact'],
            ['administrateur1', 'Administrateur historique 1', 'Jules-christ GBASSI', 'contact'],
            ['administrateur2', 'Administrateur historique 2', 'Oswald GNASSOUNOU', 'contact'],
            ['administrateur3', 'Administrateur historique 3', 'Ulrich KOUDOGBO', 'contact'],
            ['administrateur4', 'Administrateur historique 4', 'Delco ADOGBO', 'contact'],
            ['contact_admin_1', 'Administratrice historique', 'Karin OSTERTAG', 'contact'],
            ['contact_admin_2', 'Administrateur historique', 'Ademonla Hyacinth ATTOUNON', 'contact'],
            ['jda_email', 'E-mail JDA', 'ongjda@mail.com', 'jda'],
            ['jda_telephone', 'Téléphones JDA', '66391089 / 97959660', 'jda'],
            ['jda_siege', 'Siège JDA', 'Abomey-Calavi', 'jda'],
            ['jda_intro', 'Introduction JDA', $jdaIntro, 'jda'],
            ['jda_objectifs_titre', 'Titre Objectifs JDA', 'Objetifs', 'jda'],
            ['jda_objectifs', 'Objectifs JDA', $jdaObjectifs, 'jda'],
            ['pjk_intro', 'Introduction PJK', $pjkIntro, 'parlement'],
            ['pjk_mission', 'Mission PJK', $pjkMission, 'parlement'],
            ['pjk_organisation', 'Organisation PJK', $pjkOrganisation, 'parlement'],
            ['nombre_journaux', 'Nombre de numéros de journal', '9', 'journal'],
            ['nom_site', 'Nom du site', "La Voix d'Abouloussi", 'general'],
            ['description_site', 'Description du site', 'Mon journal, actualités JDA, Parlement des Jeunes de Koko, contes, poèmes et mémoire associative.', 'general'],
            ['hero_titre', 'Titre du hero', "LA VOIX D'ABOULOUSSI", 'accueil'],
            ['hero_sous_titre', 'Sous-titre du hero', $heroSousTitre, 'accueil'],
            ['hero_image', 'Image du hero', '', 'accueil'],
            ['accueil_objectif_titre', 'Titre Objectif', 'Objectif', 'accueil'],
            ['accueil_objectif_texte', 'Texte Objectif', $objectif, 'accueil'],
            ['accueil_identification_titre', 'Titre IDENTIFICATION', 'IDENTIFICATION', 'accueil'],
            ['accueil_identification_texte', 'Texte IDENTIFICATION', "Koko est l'un des neufs arrondissements de la commune de Bantè dans le département des Collines au Bénin", 'accueil'],
            ['plan_demographie_titre', 'Titre Démographie', 'DEMOGRAPHIE', 'accueil'],
            ['plan_demographie_texte', 'Texte Démographie', "Selon le recensement de la population de 2013 conduit par l'Institut national de la statistique et de l'analyse économique (INSAE), Koko compte 7 259 habitants", 'accueil'],
            ['plan_histoire_titre', 'Titre Histoire', 'HISTOIRE', 'accueil'],
            ['plan_histoire_texte', 'Texte Histoire', 'La création du village de Koko, est une histoire vieille de 700 ans. Ce village est né après la création de nombreux quartiers (sous villages). Seule la langue itcha est parlée.', 'accueil'],
            ['plan_geographie_titre', 'Titre Géographie', 'GEOGRAPHIE', 'accueil'],
            ['plan_geographie_texte', 'Texte Géographie', 'Situé au cœur du département des Collines, Koko est l’un des 34 villages qui constituent la commune de Bantè un village situé à 6 km de la paroisse de Bantè et compte 3 villages que sont Akpaka, Itchocobo et Issalè.', 'accueil'],
            ['journal_titre_1', 'Titre Mon journal 1', 'La Voix d\'Abouloussi', 'journal'],
            ['journal_texte_1', 'Texte Mon journal 1', $journalUn, 'journal'],
            ['journal_image_1', 'Image Mon journal 1', '', 'journal'],
            ['journal_titre_2', 'Titre Mon journal 2', 'les divinités à koko', 'journal'],
            ['journal_texte_2', 'Texte Mon journal 2', $journalDeux, 'journal'],
            ['journal_image_2', 'Image Mon journal 2', '', 'journal'],
            ['journal_telechargement_titre', 'Titre Télécharger les journaux', 'Télécharger les journaux', 'journal'],
            ['journal_telechargement_texte', 'Texte Télécharger les journaux', 'Le journal est au numéro 9 grâce à la participation des gens de tout horizon du Bénin. Alors vous pouvez les télécharger en cliquant sur le bouton ouvrir de chaque numéro', 'journal'],
            ['dgp_intro_titre', 'Titre Dis-moi Grand-Père', 'Contes, Legendes & Proverbes', 'histoire'],
            ['dgp_intro_texte', 'Texte Dis-moi Grand-Père', $dgpIntro, 'histoire'],
            ['dgp_intro_image', 'Image Dis-moi Grand-Père', '', 'histoire'],
        ];

        foreach ($pjkCommissions as $index => [$titre, $texte]) {
            $parametres[] = ['pjk_commission_'.($index + 1).'_titre', 'Titre commission PJK '.($index + 1), $titre, 'parlement'];
            $parametres[] = ['pjk_commission_'.($index + 1).'_texte', 'Texte commission PJK '.($index + 1), $texte, 'parlement'];
        }

        foreach ($parametres as [$cle, $libelle, $valeur, $groupe]) {
            SiteSetting::updateOrCreate(['cle' => $cle], ['libelle' => $libelle, 'valeur' => $valeur, 'groupe' => $groupe]);
        }
    }

    private function seedCategories(): void
    {
        Categorie::where('rubrique', 'dms')->delete();

        $categories = [
            ['journal', 'Journaux', 'Tous les numéros du journal'],
            ['jda', 'Rapports d’activités', 'Comptes rendus et rapports du JDA'],
            ['jda', 'Actualités JDA', 'Actualités du mouvement'],
            ['parlement', 'Commissions', 'Les cinq commissions du Parlement des Jeunes'],
            ['parlement', 'Sessions', 'Sessions plénières et rencontres'],
            ['conte', 'Contes et légendes', 'Récits et légendes d’Abouloussi'],
            ['poeme', 'Poésie', 'Poèmes du patrimoine'],
        ];

        foreach ($categories as [$rubrique, $nom, $description]) {
            $slug = Str::slug($nom);
            Categorie::firstOrCreate(['slug' => $slug], [
                'rubrique' => $rubrique,
                'nom' => $nom,
                'description' => $description,
                'ordre' => 0,
            ]);
        }
    }

    private function supprimerContenusDms(): void
    {
        Contenu::where('rubrique', 'dms')->delete();

        $dossier = $this->destination.'/dms';
        if (File::isDirectory($dossier)) {
            File::deleteDirectory($dossier);
        }
    }

    private function seedContesHistoire(array $imageDgp): void
    {
        $intro = SiteSetting::where('cle', 'dgp_intro_texte')->value('valeur');

        $this->creerContenu([
            'slug' => 'contes-legendes-proverbes',
            'rubrique' => 'conte',
            'titre' => 'Contes, Legendes & Proverbes',
            'resume' => $intro,
            'corps' => $intro,
            'auteur' => "La Voix d'Abouloussi",
            'image' => array_values($imageDgp)[0] ?? null,
            'categorie_id' => Categorie::where('slug', 'contes-et-legendes')->value('id'),
            'a_la_une' => true,
        ]);
    }

    private function copierDossier(string $sourceRelatif, string $destinationRelatif, string $rubrique, array $extensions): array
    {
        $source = $this->source.'/'.$sourceRelatif;
        $destination = $this->destination.'/'.$destinationRelatif;
        $resultat = [];

        if (! File::isDirectory($source)) {
            return $resultat;
        }
        File::ensureDirectoryExists($destination);

        foreach (File::files($source) as $fichier) {
            $extension = strtolower($fichier->getExtension());
            if (! in_array($extension, $extensions, true)) {
                continue;
            }
            $resultat[$fichier->getRealPath()] = $this->copierFichier($fichier->getRealPath(), $destination, $destinationRelatif, $rubrique);
        }

        return $resultat;
    }

    private function copierFichiersNommés(array $sources, string $destinationRelatif, string $rubrique): array
    {
        $resultat = [];
        $destination = $this->destination.'/'.$destinationRelatif;
        File::ensureDirectoryExists($destination);

        foreach ($sources as $sourceRelatif) {
            $source = $this->source.'/'.$sourceRelatif;
            if (! File::exists($source)) {
                continue;
            }
            $resultat[$source] = $this->copierFichier($source, $destination, $destinationRelatif, $rubrique);
        }

        return $resultat;
    }

    private function copierFichier(string $source, string $destinationDir, string $destinationRelatif, string $rubrique): string
    {
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $base = Str::slug(pathinfo($source, PATHINFO_FILENAME)) ?: 'fichier';
        $nom = $base.'.'.$extension;
        $suffixe = 2;

        while (File::exists($destinationDir.'/'.$nom)) {
            $cible = $destinationDir.'/'.$nom;
            $identique = filesize($source) === filesize($cible) && hash_file('sha256', $source) === hash_file('sha256', $cible);
            if ($identique) {
                break;
            }
            $nom = $base.'-'.$suffixe.'.'.$extension;
            $suffixe++;
        }

        if (! File::exists($destinationDir.'/'.$nom)) {
            File::copy($source, $destinationDir.'/'.$nom);
            $this->fichiersCopies++;
        }

        $chemin = 'media/'.$destinationRelatif.'/'.$nom;
        $type = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif'], true) ? 'image' : 'document';

        Media::firstOrCreate(['chemin' => $chemin], [
            'user_id' => $this->admin?->id,
            'nom' => pathinfo($source, PATHINFO_BASENAME),
            'type' => $type,
            'mime' => mime_content_type($source) ?: null,
            'taille' => filesize($source) ?: 0,
            'alt' => pathinfo($source, PATHINFO_FILENAME),
            'rubrique' => $rubrique,
        ]);

        return $chemin;
    }

    private function creerContenu(array $donnees): Contenu
    {
        $slug = $donnees['slug'] ?? (Str::slug($donnees['titre']) ?: 'contenu');
        unset($donnees['slug']);

        $contenu = Contenu::updateOrCreate(['slug' => $slug], $donnees + [
            'slug' => $slug,
            'statut' => 'publie',
            'user_id' => $this->admin?->id,
            'meta' => [],
            'a_la_une' => false,
            'vues' => 0,
        ]);

        if ($contenu->wasRecentlyCreated) {
            $this->contenusCrees++;
        }

        return $contenu;
    }

    private function seedJournaux(array $journaux): void
    {
        $libelles = [
            2022 => 'N°9 (En 2022)',
            2021 => 'N°8 (En 2021)',
            2020 => 'N°7 (En 2020)',
            2015 => 'N°6  (En 2015)',
            2013 => 'N°5  (En 2013)',
            2011 => 'N°4   (En 2011)',
            2010 => 'N°3  (En 2010)',
            2009 => 'N°2 (En 2009)',
            2008 => 'N°1   (En 2008)',
        ];

        $slugs = [];
        foreach (array_keys($journaux) as $source) {
            if (preg_match('/((?:19|20)\d{2})(?:\.docx)?\.pdf$/i', basename($source), $matches)) {
                $slugs[] = 'journal-'.$matches[1];
            }
        }
        if ($slugs !== []) {
            Contenu::where('rubrique', 'journal')->whereNotIn('slug', $slugs)->delete();
        }

        foreach ($journaux as $source => $chemin) {
            if (! preg_match('/((?:19|20)\d{2})(?:\.docx)?\.pdf$/i', basename($source), $matches)) {
                continue;
            }
            $annee = (int) $matches[1];
            $this->creerContenu([
                'slug' => 'journal-'.$annee,
                'rubrique' => 'journal',
                'titre' => $libelles[$annee] ?? "N° (En {$annee})",
                'resume' => 'Télécharger le numéro du journal de La Voix d\'Abouloussi.',
                'corps' => 'Le journal est au numéro 9 grâce à la participation des gens de tout horizon du Bénin. Alors vous pouvez les télécharger en cliquant sur le bouton ouvrir de chaque numéro.',
                'auteur' => "La Voix d'Abouloussi",
                'date_publication' => "{$annee}-12-31",
                'document' => $chemin,
                'document_nom' => basename($source),
                'categorie_id' => Categorie::where('slug', 'journaux')->value('id'),
                'a_la_une' => $annee === 2022,
            ]);
        }
    }

    private function trouverImage(array $images, string $nomFichier): ?string
    {
        return collect($images)->first(fn ($chemin) => str_ends_with(strtolower($chemin), strtolower($nomFichier)));
    }

    private function ordonnerImages(array $images, array $nomsFichiers): array
    {
        $ordonnees = [];

        foreach ($nomsFichiers as $nomFichier) {
            $chemin = $this->trouverImage($images, $nomFichier);
            if ($chemin && ! in_array($chemin, $ordonnees, true)) {
                $ordonnees[] = $chemin;
            }
        }

        foreach ($images as $chemin) {
            if (! in_array($chemin, $ordonnees, true)) {
                $ordonnees[] = $chemin;
            }
        }

        return $ordonnees;
    }

    private function seedJda(array $rapports, array $rapport1, array $rapport2, array $rapport3): void
    {
        $pdf = fn (string $nom): ?string => collect($rapports)->first(fn ($chemin) => str_contains($chemin, Str::slug($nom))) ??
            collect($rapports)->first(fn ($chemin) => str_contains(strtolower(basename($chemin)), Str::slug($nom, '')));

        $seanceLecture = <<<'TEXTE'
Bonjour chères madames.
Bonjour chers collègue.
Commme prévu dans notre plan de travail annuel, les séances de lecture vont de bon train.
Je pense que c'est une activité importante pour relever le niveau des ces apprenants. On essaie de travailler pour corriger aussi l'accent qui sort quand ils parle le français.
C'est un gros travail qui est là vu leur niveau, je pense.
TEXTE;

        $activites = [
            [
                'titre' => 'Rencontre à la bibliothèque du centre AKPON MANKORO',
                'resume' => "Ce 28 décembre 2024, s'est tenue dans la bibliothèque du centre AKPON MANKORO la première plénière du Parlement des Jeunes de KOKO. Elle a connu la présence effective des représentants de L'ONG JDA aux noms de Mr Jude TCHALLA, Mr Ferdinand TATA, Mr Bruno ACAKPO, Mr Dassi BAH et des élèves membres du PJK...",
                'pdf' => 'Rapport d activite du 28-12-24-JDA',
                'image' => $this->trouverImage($rapport1, 'img-20250109-wa0017.jpg'),
                'date' => '2024-12-28',
            ],
            [
                'titre' => 'Rapport de la journée de formation en savonnerie à Parakou',
                'resume' => "L'objectif principal de cette formation était  de promouvoir L'entrepreneuriat des jeunes afin de remédier le chômage au sein des étudiants de Bantè résidant à Parakou...",
                'pdf' => 'Formation en savonnerie',
                'image' => $this->trouverImage($rapport2, 'img-20250110-wa0018.jpg'),
                'date' => '2024-11-30',
            ],
            [
                'titre' => 'Rencontre à Ayékè le 15 novembre 2024',
                'resume' => "ce document tient lieu de compte rendu de la rencontre qui s'est tenue à Ayékè le 15 novembre 2024 à 16h entre l'ONG JDA et les membres du bureau APE de la dite école. Étaient présents ...",
                'pdf' => 'Rencontre a Ayeke',
                'image' => $this->trouverImage($rapport3, 'img-20250110-wa0032.jpg'),
                'date' => '2024-11-15',
            ],
            [
                'titre' => 'Séance de lecture',
                'resume' => 'Commme prévu dans notre plan de travail annuel, les séances de lecture vont de bon train.',
                'corps' => $seanceLecture,
                'pdf' => null,
                'image' => $this->trouverImage($rapport3, 'img-20250110-wa0029.jpg'),
                'date' => '2024-11-15',
            ],
            [
                'titre' => 'Rencontre tenue au laboratoire du CEG KOKO',
                'resume' => "Ce vendredi 15 novembre 2024 s'est tenue au laboratoire du CEG KOKO, la rencontre de l'ONG JDA avec le bureau APE...",
                'pdf' => 'laboratoire du CEG koko',
                'image' => $this->trouverImage($rapport3, 'img-20250110-wa0030.jpg'),
                'date' => '2024-11-15',
            ],
        ];

        foreach ($activites as $index => $activite) {
            $cheminPdf = $activite['pdf'] ? $pdf($activite['pdf']) : null;
            $this->creerContenu([
                'rubrique' => 'jda',
                'titre' => $activite['titre'],
                'resume' => $activite['resume'],
                'corps' => $activite['corps'] ?? $activite['resume'],
                'auteur' => 'JDA',
                'date_publication' => $activite['date'],
                'image' => $activite['image'],
                'document' => $cheminPdf,
                'document_nom' => $cheminPdf ? basename($cheminPdf) : null,
                'meta' => ['ordre' => $index + 1],
                'categorie_id' => Categorie::where('slug', 'rapports-dactivites')->value('id'),
                'a_la_une' => true,
            ]);
        }
    }

    private function seedParlement(array $parlement, array $parlementRacine): void
    {
        $images = array_values($parlement);
        $image = $images[0] ?? array_values($parlementRacine)[0] ?? null;

        $this->creerContenu([
            'rubrique' => 'parlement',
            'titre' => 'Parlement des Jeunes de Koko — Mission',
            'resume' => 'Briser le joug de la honte de parler en publique (ce qui s’observe chez nombre d’apprenants de la localité) et amener les responsable à débattre des problèmes que rencontrent les élèves durant leur cursus en vue de trouver des voies de sortie.',
            'corps' => 'Le Parlement des Jeunes de Koko est composé de cinq commissions à savoir : la commission Environnement, commission Education Scolaire, Commission Education Sexuelle, Commission Loisirs et Culture et la Commission Education Civique et Sociale.',
            'auteur' => 'Parlement des Jeunes de Koko',
            'image' => $image,
            'date_publication' => '2017-11-01',
            'a_la_une' => true,
            'categorie_id' => Categorie::where('slug', 'sessions')->value('id'),
        ]);

        $commissions = [
            ['1- La Commission Environnement', "Cette commission se charge des questions de gestion de l'environnement. Elle organise des journées de salubrité et de reboisement dans les villages en vue de lutter contre la DAL et la désertification. Sensibilise pour l'atteinte des ODD."],
            ['2- La commission Education Sexuelle', "Elle travaille en collaboration avec les agents de santé de l'arrondissement de Koko, ceux de l'hôpital de Bantè et l'hôpital de zone de Savalou. Les points capitaux de ses interventions sont la lutte contre les grossesses en milieu scolaire, l'éducation à la santé sexuelle et l'encouragement à l'utilisation des méthodes de contraception. Cette commission dispose d'une cellule de consultation en conseils et accompagnement en cas de difficulté."],
            ['3- La Commission Education Scolaire', "C'est la commission la plus proche de l'administration et de l'Association des Parents d'Elève. Elle veille au bon déroulement des activités pédagogiques et exhorte surtout les élèves à donner le meilleur d'eux-mêmes. Elle initie depuis 2017 l'organisation des séances de renforcement des capacités en collaboration avec la Fondation Tobé."],
            ['4- La Commission Loisirs et Culture', 'Elle se charge des animations culturelles par la recherche et la valorisation des pratiques des savoir-faire, savoir-vivre et savoir-être itcha. La valorisation des rythmes itcha en vogue et ceux délaissés. Cette commission excite les apprenants à prendre goût de la chose culturelle.'],
            ['5- La Commission Education Civique et Sociale', 'Cette commission travaille avec le Surveillant Général pour instaurer la discipline au CEG-Koko et sensibilise la population au respect des codes de la route.'],
        ];

        foreach ($commissions as $index => [$titre, $corps]) {
            $this->creerContenu([
                'rubrique' => 'parlement',
                'titre' => $titre,
                'resume' => Str::limit($corps, 180),
                'corps' => $corps,
                'auteur' => 'Parlement des Jeunes de Koko',
                'date_publication' => '2017-11-01',
                'image' => $images[$index + 1] ?? $image,
                'categorie_id' => Categorie::where('slug', 'commissions')->value('id'),
            ]);
        }
    }

    private function seedPoemes(): void
    {
        $poemeAncetres = <<<'TEXTE'
Contre le temps j’ai lutté en vain
Pour qu’un jour, mes yeux croisent vos visages
Ou mes pas rencontrent vos pas au passage
Mais vous n’êtes plus à l’horizon lointain.

Comme un fugitif oiseau
Sous le calme de la ramure,
Vous aviez doublé l’allure
En direction de l’île du repos.

De vos innombrables biens, un m’est précieux :
Vos noms plus que souverains
Que bégaient les gamins ;
Et pour ce digne trésor, je bénis les cieux

Car en ces noms se cache une abondance ;
Comme la nuit du jour est la mère,
Du lien entre ces noms et moi, sincère,
Provient ma force, que dis-je ?  Mon essence.

Toute ma vie est en vous : le tronc et les racines
Ne sont mieux soudés que mon âme et les vôtres
Tant de moi, vous avez fait un digne apôtre
Soumis et obéissant à vos voix divines.

Fortuné, l’enfant à la mémoire limpide
Qui au lieu de bâtir sur du sable
Vient construire un autel stable
Sur le rocher de ses pères. Mon seul guide,

C’est le doux murmure du vent
Qui me souffle à l’oreille
La voie qui mène au sérail
Du savoir pur et pénétrant.

Dans la funeste et opaque ombre
Ma lumière, ce sont ces lanternes,
Vos gracieux regards paternes
Peuplés d’étincelles sans nombre.

Quel puissant fleuve coulant ses eaux
Oublie sa fidèle source ?
Pour l’homme, la ressource,
C’est l’aïeul des temps immémoriaux ;

Pardonnez-moi, mémoire de mes pères ;
Si mon cœur se prend d’un orgueil indocile,
Je promets comme dans un rêve fragile
Qu’à l’éternité vous seriez mon repère.

Je voudrais reprendre le culte des temples
Et donner à vos dieux domestiques
La fraîche libation mystique
Pour qu’à leurs yeux je sois un exemple.

C’est par là que je peux, par une foi solide
Vous marquer ma tendresse
Et l’ardeur qui m’empresse
A vous adorer d’un cœur intrépide

Je voudrais que les progénitures
Apprennent de votre vie austère
L’admirable et le doux mystère
De la vie dans la vérité et la droiture ;

Ainsi, égarées, elles apercevront
Dans le tumultueux fourmillement
Des humains l’éclat de vos cheveux blancs
Pour guider les âmes qui naîtront.

C’est pourquoi, malgré les tempêtes,
Je suis et veux demeurer POËTE

Honneur et gloire à vous, ancêtres vénérés !
Comme le chaton ne peut ressembler au chiot
Que sur mon front se reflète votre bravoure,
Que mon sang bouillonne de votre amour.
Je voudrais vivre comme les héros de TCHIKO.

Apprenez-moi l’art de triompher.
Apprenez-moi, savants griots,
Sous le rythme du GAMATO,
La dance hardie des guerriers

Hyacinthe Ademonla D. ATTOUNON
TEXTE;

        $poemeKoko = <<<'TEXTE'
Le village de ma vie.
Village de mon amour.
Village de mon espoir.

Toi qui m’as donné le jour.
Toi qui me nourris par les richesses de ton sol,
par la sueur de tes braves fils. Ecoute ma voix!
Ecoute mon témoignage!
Koko! Koko! Koko!

Si j’étais né ailleurs, je l’aurais
regretté car tu es un arbre de la paix,
un eldorado. Loin de toi je maigrirai
car ta chaleur me manquerait. Tu l’as
compris et pour me garder encore pendant
quatre ans dans tes seins, tu as créé un
collège pour mon épanouissement intellectuel
et pour ton rayonnement culturel.

Koko! Tu dois rimer avec le sens de ton
nom qui signifie l’harmonie, l’unité
Loin de toi tout esprit d’inclusion,
soit unis et indivisible. Itchokobo
n’est qu’un petit de tes morceaux.
Issalè n’est qu’un petit de tes morceaux.
Tu ne peux construire l’un de tes multiples
morceaux. Un morceau de moins tu n’es plus
Koko, mais tu es Ko. Veux-tu t’opposer à
l’unité de tes fils ? Bras valides ? Que
chacun joue alors sa partition pour
l’harmonie et pour le bonheur de tous.
Toi, élève du CEG de Koko saches que tu
as une mission, celle d’unir nos parents
tel que nous sommes unis en salle.
Pour suivre le cours d’un seul professeur
tous à la fois, tu dois aussi relever
le défi de l’honneur, le défi du succès,
le défi de l’éclaireur. Mets-toi résolument
au travail!

Elève fille oublie ton sexe et ait de cœur
qu’aux études.
Elève garçon aide ta sœur à s’épanouir.
Directeur, professeurs, montrez-nous
seulement le bon chemin et nous vous
suivrons. Nous vous promettons d’être
une pépinière de cadres talentueux et
vertueux pour l’utilité nationale.
Nous vous promettons le BECP mérité
un juin 2010.
Que Dieu écoute nos vœux. Chef de
l’arrondissement de Koko soutient nous.
Parents, supportez nous! Amis et invités,
encouragez nous!

Merci!
TEXTE;

        $this->creerContenu([
            'rubrique' => 'poeme',
            'titre' => 'AUX MÂNES DES ANCÊTRES DE KOKO',
            'resume' => 'Poème de Hyacinthe Ademonla D. ATTOUNON en hommage aux ancêtres de Koko.',
            'corps' => $poemeAncetres,
            'auteur' => 'Hyacinthe Ademonla D. ATTOUNON',
            'date_publication' => '2010-01-01',
            'a_la_une' => true,
            'categorie_id' => Categorie::where('slug', 'poesie')->value('id'),
        ]);

        $this->creerContenu([
            'rubrique' => 'poeme',
            'titre' => 'POESIE : Koko',
            'resume' => 'Poème dédié au village de Koko, à son unité et à son collège.',
            'corps' => $poemeKoko,
            'auteur' => 'Auteur inconnu',
            'date_publication' => '2010-06-01',
            'categorie_id' => Categorie::where('slug', 'poesie')->value('id'),
        ]);
    }

    private function seedPagesInstitutionnelles(array $galerie): void
    {
        $valeur = fn (string $cle): ?string => SiteSetting::where('cle', $cle)->value('valeur');

        Contenu::whereIn('rubrique', ['objectif', 'identification'])
            ->whereNotIn('slug', ['objectif-accueil', 'identification-accueil'])
            ->delete();

        $this->creerContenu([
            'slug' => 'objectif-accueil',
            'rubrique' => 'objectif',
            'titre' => $valeur('accueil_objectif_titre') ?? 'Objectif',
            'resume' => 'La voix d\'Abouloussi est un magazine de jeunesse créé en 2006.',
            'corps' => $valeur('accueil_objectif_texte'),
            'auteur' => "La Voix d'Abouloussi",
        ]);

        $this->creerContenu([
            'slug' => 'identification-accueil',
            'rubrique' => 'identification',
            'titre' => $valeur('accueil_identification_titre') ?? 'IDENTIFICATION',
            'resume' => $valeur('accueil_identification_texte'),
            'corps' => $valeur('accueil_identification_texte'),
            'auteur' => "La Voix d'Abouloussi",
        ]);

        foreach (['demographie', 'histoire', 'geographie'] as $plan) {
            $this->creerContenu([
                'slug' => 'plan-'.$plan,
                'rubrique' => 'plan',
                'titre' => $valeur('plan_'.$plan.'_titre') ?? strtoupper($plan),
                'resume' => $valeur('plan_'.$plan.'_texte'),
                'corps' => $valeur('plan_'.$plan.'_texte'),
                'auteur' => "La Voix d'Abouloussi",
            ]);
        }

        foreach (array_values($galerie) as $index => $chemin) {
            $this->creerContenu([
                'slug' => 'galerie-koko-'.($index + 1),
                'rubrique' => 'image',
                'titre' => 'Koko',
                'resume' => 'Images de Koko, publiées sur la page d\'accueil historique.',
                'image' => $chemin,
                'auteur' => "La Voix d'Abouloussi",
            ]);
        }
    }

    private function seedAlbums(array $jda, array $rapport1, array $rapport2, array $rapport3, array $parlement, array $parlementRacine): void
    {
        $jda = $this->ordonnerImages($jda, [
            'whatsapp-image-2024-03-05-at-110807-am.jpeg',
            'whatsapp-image-2024-03-05-at-110809-am.jpeg',
            'whatsapp-image-2024-03-05-at-111019-am-2.jpeg',
            'whatsapp-image-2024-03-05-at-111019-am.jpeg',
            'whatsapp-image-2024-03-05-at-111020-am-1.jpeg',
            'whatsapp-image-2024-03-05-at-111021-am-1.jpeg',
            'whatsapp-image-2024-03-05-at-111022-am.jpeg',
            'whatsapp-image-2024-03-05-at-111022-am-3.jpeg',
        ]);
        $rapport1 = $this->ordonnerImages($rapport1, [
            'img-20250109-wa0035.jpg',
            'img-20250109-wa0037.jpg',
            'img-20250109-wa0039.jpg',
            'img-20250109-wa0049.jpg',
            'img-20250109-wa0051.jpg',
            'img-20250109-wa0075.jpg',
            'img-20250109-wa0059.jpg',
            'img-20250109-wa0065.jpg',
            'img-20250109-wa00251.jpg',
            'img-20250109-wa0029.jpg',
            'img-20250109-wa0021.jpg',
            'img-20250109-wa0017.jpg',
        ]);
        $rapport2 = $this->ordonnerImages($rapport2, [
            'img-20250110-wa0024.jpg',
            'img-20250110-wa0022.jpg',
            'img-20250110-wa0026.jpg',
            'img-20250110-wa0028.jpg',
            'img-20250110-wa0020.jpg',
            'img-20250110-wa0018.jpg',
            'img-20250110-wa0016.jpg',
        ]);
        $rapport3 = $this->ordonnerImages($rapport3, [
            'img-20250110-wa0032.jpg',
            'img-20250110-wa0035.jpg',
            'img-20250110-wa0031.jpg',
            'img-20250110-wa0033.jpg',
        ]);

        $albums = [
            ['Album photos du JDA', 'jda', 'Photos des activités des Jeunes Dynamiques et Actions.', '2024-03-05', $jda],
            ['Rapport d’activité 1 — Plénière du PJK', 'jda', 'Photos de la première plénière du Parlement des Jeunes de Koko.', '2025-01-09', $rapport1],
            ['Rapport d’activité 2 — Formation en savonnerie', 'jda', 'Photos de la formation en savonnerie à Parakou.', '2025-01-10', $rapport2],
            ['Rapport d’activité 3 — Laboratoire du CEG Koko', 'jda', 'Photos de la rencontre au laboratoire du CEG Koko et à Ayékè.', '2025-01-10', $rapport3],
            ['Parlement des Jeunes de Koko', 'parlement', 'Sessions et élections du Parlement des Jeunes de Koko.', '2018-05-01', array_merge($parlement, $parlementRacine)],
        ];

        foreach ($albums as [$titre, $rubrique, $description, $date, $photos]) {
            $slug = Str::slug($titre);
            $album = Album::updateOrCreate(['slug' => $slug], [
                'titre' => $titre,
                'description' => $description,
                'rubrique' => $rubrique,
                'couverture' => array_values($photos)[0] ?? null,
                'date_album' => $date,
                'statut' => 'publie',
            ]);

            if ($album->wasRecentlyCreated) {
                $this->albumsCrees++;
            }

            foreach (array_values($photos) as $ordre => $chemin) {
                AlbumPhoto::updateOrCreate([
                    'album_id' => $album->id,
                    'image' => $chemin,
                ], [
                    'legende' => pathinfo($chemin, PATHINFO_FILENAME),
                    'ordre' => $ordre,
                ]);
            }
        }
    }

    private function seedRayons(): void
    {
        $sql = $this->source.'/market.lavoixdabouloussi.org/supermarche.sql';
        if (! File::exists($sql)) {
            return;
        }

        $contenu = File::get($sql);
        if (! preg_match_all("/\((\d+),\s*'([^']+)',\s*'([\d.]+)'\)/", $contenu, $matches, PREG_SET_ORDER)) {
            return;
        }

        foreach ($matches as $match) {
            Rayon::firstOrCreate(['nom' => $match[2]], ['taux' => (float) $match[3]]);
        }
    }

    private function lierImagesParametres(array $carousel, array $accueil, array $equipe, array $uploads, array $imagesMonJournal, array $imageDgp, array $jdaPhotos): void
    {
        $definir = function (string $cle, string $libelle, ?string $valeur, string $groupe): void {
            if ($valeur) {
                SiteSetting::updateOrCreate(['cle' => $cle], ['libelle' => $libelle, 'valeur' => $valeur, 'groupe' => $groupe]);
            }
        };

        $imagesCarousel = array_values($carousel);
        foreach ([1, 2, 3] as $index) {
            $definir("carousel_{$index}", "Image du carousel {$index}", $imagesCarousel[$index - 1] ?? null, 'accueil');
        }
        $definir('hero_image', 'Image du hero', $imagesCarousel[0] ?? null, 'accueil');

        $slider = array_values($accueil);
        foreach ([1, 2, 3] as $index) {
            $definir("slider_{$index}", "Image du slider {$index}", $slider[$index - 1] ?? null, 'accueil');
        }

        $imagesJournal = array_values($imagesMonJournal);
        $definir('journal_image_1', 'Image Mon journal 1', $imagesJournal[0] ?? null, 'journal');
        $definir('journal_image_2', 'Image Mon journal 2', $imagesJournal[1] ?? null, 'journal');
        $definir('dgp_intro_image', 'Image Dis-moi Grand-Père', array_values($imageDgp)[0] ?? null, 'histoire');
        $definir('jda_image', 'Image JDA', $this->trouverImage($jdaPhotos, 'whatsapp-image-2024-03-05-at-110803-am-1.jpeg'), 'jda');

        $equipeImages = array_values($equipe);
        $definir('equipe_1', 'Photo administratrice historique', collect($equipe)->first(fn ($chemin) => str_contains(basename($chemin), 'adm1')) ?? $equipeImages[0] ?? null, 'contact');
        $definir('equipe_2', 'Photo administrateur historique', collect($equipe)->first(fn ($chemin) => str_contains(basename($chemin), 'adm2')) ?? $equipeImages[1] ?? null, 'contact');

        $imageUpload = collect($uploads)->first(fn ($chemin) => str_ends_with($chemin, '.jpg') || str_ends_with($chemin, '.png'));
        if ($imageUpload) {
            SiteSetting::updateOrCreate(['cle' => 'og_image'], ['libelle' => 'Image de partage', 'valeur' => $imageUpload, 'groupe' => 'general']);
        }
    }

    private function creerLienStorage(): void
    {
        $lien = public_path('storage');
        if (! File::exists($lien)) {
            File::link(storage_path('app/public'), $lien);
        }
    }
}
