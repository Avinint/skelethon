# Projet Skelethon : 
### Générateur de squelette de module

## 1) Installer le générateur
    * Après avoir récup&rer ce générateur sur le répo git, je conseille de créer un alias en bash.
    En fonction de votre bash aller dans le fichier idoine : normalement .bash_aliases mais peut également être par exemple .bashrc, .bash_profile, .zshrc
    situé dans le répertoire /home/adminweb de votre VM (vous aurez besoin de l'accès à la base de données) Ne pas oublier de recharger le fichier.
    Exemples d'aliases :
      alias e2d="php /data/apache/base/dev/skelethon/index.php e2d
      alias thon="php /data/apache/base/dev/skelethon/index.php
      alias eto="php /data/apache/base/dev/skelethon/index.php eto
      alias esm="php /data/apache/base/dev/skelethon/index.php esm
    
    * le générateur utilise la config par défaut de votre projet pour l'accès à la base de données mais au cas où cela ne fonctionne pas,
    il faudra écraser la méthode getDatabaseParams de la classe DatabaseAccess ou plutôt réécrire la méthode dans une classe qui hérite de DatabaseAccess.
    Il faudrait pouvoir gérer ça dans la config.. mais pour l'instant ce n'est pas le cas.
    
    public static function getDatabaseParams()
    {
        return new static(
        'localhost',
        'adminsql',
        'doing42',
        'etotem-dev'
        );
    }

## 2) Utilisation basique du générateur

    * Il faut se placer dans le répertoire du projet qu'on veut générer
    * en passant du principe que vous utilisez un alias comme ci-dessus, vous aurez une commande du style e2d module nomdumodule nom_du_modele.
    Par exemple esm module cours salle_reservation (le nom du modèle est en underscore case). Ne pas utiliser d'underscores dans le nom du module.
  
## 3) Principes de base du générateur

###    a) ligne de commande: 
    La structure de la commande est de type : executable type_projet mode options.

    1) executable : sans alias, on aura  pour executable cheminversrepertoiregenerateur/index.php
    2) type_projet: A l'heure actuelle, e2d, eto pour etotem et esm pour esm et cnsmd
    3) alias: perso je combine l'exe et le type de projet dans les alias, comme vu ci- dessus
    4) modes : A l'heure actuelle, il y en a 2, module et modele. Chacun a  comme options le nom du module et le nom de modele (en underscore)
   
###    b)  Modes et options: 
    Les deux modes actuels (module et modele) sont finalement très proches. 
    - Le mode module génère un module autour du modèle. 
        * Concrètement cela veux dire, générer:
            - les confs du module, 
            - le modèle 
            - un controlleur lié au modèle
            - Deux fichiers JS
            - vues liées au modèle (ex edition_nomdumodele)
            - une arborescence avec un fichier css
        * Choisir un module implique : 
            - création d'un module s'il n'existe pas, et si un fichier existe déja, il est ignoré.
            - le controlleur et les deux fichier JS générés sont nommés après le module.
            
    - Le mode modèle sert à ajouter un modèle à un module existant:
        le mode modele est donc très proche du mode module sauf que :
        * sauf que si le module spécifié est inexistant, il n'est pas généré et le programme s'arrête
        * Les fichiers conf déja existants sont modifiés pour ajouter les nouvelles actions et routes et paramètres de conf nécessaires
        * le fichier JS de base est modifié pour ajouter le lien vers le nouveau fichier JS ajouté.
        * Le Controller et les fichiers JS sont nommés après le modèle
        Sinon pour le reste le mode est en tous points semblable au mode module:
        * les autres fichiers sont seulement générés s'ils n'existent pas, ils ne sont pas écrasés ou modifiés
    
    - limitations: 
        - il n'y a pas d'option pour éviter de générer les fichiers css à l'heure actuelle, maisi on peut rapidement le supprimer.
            Attention à ne pas aller trop vite et supprimer le répertoire template sans avoir vérifié au préalable
            qu'un fichier n'existait pas déja avant la génération. (mode modèle)
        - On a 1 controlleur pour chaque modèle. Si quelqu'un trouvait un intérêt particulier à regrouper 
            tous les controlleurs en une seule classe, il faut faire la modif à la main mais dans ce cas je conseille également de consulter
        - Ce serait bien d'avoir des modes permettant de choisir de générer un fichier seulement, 
            ce n'est pas possible actuellement mais si un seul fichier est manquant un seul fichier sera généré
    
    -   Remarques : Le fait de ne pas écraser des fichiers existants est un bon garde-fou pour ne pas faire des bétises dans un module existant.
        Et la modification des confs et des JS a été plutot bien testée et devrait bien se comporter. 
        (Notamment si on modifie plusieurs fois le fichier la modification n'est pas appliquée plusieurs fois). Toutefois j'aime bien perso générer un module de test
        plutot que modifier un module bien rempli parce que si je m'aperçois avoir mal configuré ma génération, 
        je peux supprimer le module test d'un coup sans avoir à me soucier des fichiers d'un module existant qu'on ne veut pas voir disparaitre.

### c) Les étapes de la génération

    1)  Questionnaire :
        Il faut répondre à des questions posées par l'application qui permet de définir une configuration qui sera sauvegardée.
        Si par la suite vous voulez regénérer ce module, les questions ne seront plus posées à nouveau mais directement lues depuis le fichier config.

    2) Génération/Modification du module :
        La génération va s'opérer sur plusieurs critères:
        * les paramètres de configuration
            Par exemple : Est ce qu'on veut générer des champs select2 plutot que selectmenu?
        * les templates:
            Ces fichiers déterminent comment les fichiers composant le module seront générés.
            Par exemple, les boutons d'actions sont ils à gauche ou à droite dans la liste de la page d'accueil
            - ils héritent tous du template "standard" (que j'aurais dû appeler "e2d") mais on peut les faire hériter d'un autre template
            - Du coup on a un template par type de projet et on peeut également créer ses propres templates 
                qui seront une variation sur le template d'un type de projet
            - L'arborescence d'un module (la liste des fichiers qui seront générés) est définie également dans les templates.
    3) Modification des configs.
        Vous vous êtes trompé dans vos choix lors du questionnaire, vous pouvez:
        - modifier le paramètre à la main
        - supprimer le paramètre et relancer la génération pour répondre à nouveau 
            (si tous les fichiers existent, cela n'aurait pour seul effet que de modifier la configuration)
        - attention, vous allez constater que certaines questions sont fastidieuses, 
        comme "Dans quel type de vue apparait tel champ" si la table a beaucoup de colonnes
        => la modification manuelle est donc conseillée

### d) Les paramètres de configuration
    * qui opèrent sur 4 niveaux : application (partout), projet, module, ou modèle)
    * Les paramètres générés par questionnaire sont configurés au niveau du modèle.
    * Plus le niveau est spécifique, plus le critère aura une précédence. Un même critère au niveau modèle écrasera le critère au niveau module
    * Ils sont stockés dans le sous-répertoire config du générrateur
    * l'arborescence est la suivante :
        - config/config.yml : config de l'application
        - config/nom_repertoire_du_projet/config.yml config du projet
        - config/nom_repertoire_du_projet/nom_module_config.yml config du module
        - A l'intérieur du fichier nom_module_config.yml, les paramètres situés dans l'arborescence models: nom_du_modele: sont au niveau du modèle
    * le questionnaire évite de mémoriser de nombreux paramètres, une fois ces paramètres sauvegardés, on peut facilement les modifier à la main. 
    * Si on supprime le paramètre correspondant à une question du questionnaire, celle-ci sera reposée

### e) Les templates:
    1) C'est quoi? c'est où?
         - ils sont situés dans le répertoire templates. Il y a forcément le template "standard" (easy2do standard) 
            et éventuellement d'autres templates correspondant à des variations du template "standard" ou des variants de variations.
         - un template est contenu dans un répertoire correspondant à son nom
         - Il contient un sous répertoire "module" qui contient les templates des fichiers du module qu'on veut générer
         - Il contient éventuellement module.yml qui définit les fichiers du module qui vont être générés
         - Il peut contenir également menu.yml qui permet de définir la structure des éléments du menu
         - dans la config, il y a un paramètre template qui peut être défini à n'importe lequel des 4 niveaux mais ça a plus de sens au niveau du projet.
         - Ce paramètre permet de définir un seul template mais permet également de chainer des templates.
         - quand on définit un template on n'a pas besoin de réécrire chaque fichier:
            si un fichier n'existe pas le générateur récupérera le fichier dans le template parent (standard), 
            on peut par exemple ne modifier que la façon de générer les controlleurs on utilisera le template standard pour tous les autres fichiers
    
    2) Mots-clés dans les templates
         - Un template comprend des mots clés destinés à être remplacés par des sous templates, 
         - on les identifie par le fait qu'ils sont en majuscules ex: MODEL MODULE, TABLE METHODS
         - certains commencent par une minuscule, ex: mODEL ou mODULE 
            indiquant qu'on remplacera ce mot clé par une version camelcase plutot que Pascal case (pour les classes)
         - certains commencent par les caractères de commentaires // permettant d'éviter des messages d'erreur de l'ide
        car les templates sont définis dans des fichiers du même types que les fichiers qu'ils sont destinés à générer (ex: php, js, html) simple coquetterie
         
    3) Créer des nouvelles templates
        Il est assez facile de faire des variations de templates:
            - en créant un nouveau template dans le répertoire templates
            - dans lequel on ajoute le fichier qu'on veut moddifier
            - attention les templates ont la même hierarchie que les fichiers qu'ils remplacent (un template de controlleur sera dans le répertoire controllers)
        
    4) les sous templates
        Souvent un mot clé est remplacé par un autre mot comme Model remplacé par le nom de classe du modèle
        mais souvent un mot sera remplacé par une partie entière du fichier qu'on veut générer 
        et qui va varier en fonction de nos choix. Par exemple, les méthodes du controller vont varier 
        en fonction des actions qu'on a sélectinner. on n'aura pas vDynamisationEdition si on n'a pas choisi l'option edition en config

    5) Créer les sous templates
        Ajouter des nouveaux sous-template est plus complexe que les autres modifications de templates
        - cela requiert de modifier le code source PHP du générateur
        - la bonne nouvelle c'est qu'on peut créer une classe qui hérite de la classe qu'on veut modifierr
        
### f) Modifier le code de l'application
    Cette usine à gaz bourssouflée qui a commeencé comme un truc léger et sympa est encore plein de bugs
    Mais si c'est pour modifier le comportement de l'application dans le cadre de votre projet mieux vaut étendre une classe existante
    et juste remplacer la méthode qui correspond au code qui nous intéresse.
    - le pb de l'héritage c'est que si vous modifiez du code parent buggé, le jour ou le bug est corrigé il faut propager le correctif aux classes enfants
    - il faudrapeut être modifier la classe parente parce que la méthode est private. 
    - ou parce que la méthode est trrop grosse et on veut modifier une petite partie
    - l'extraction en petites méthodes peut rendre le truc difficile à s'y retrouver
    - pour éviter ça j'ai créé des file generators répartis par thème ControllerFileGenerator qui permet de regrouper le code
    - et également des classes de type FieldType qui permet de regrouper le code lié à un type de champ. 
        Par exemple pour générer un select2 pour un champ de type enum
    - ce n'est pas forcément une réussite
    - le code se trouve dans le répertoire src. 
    - le code générique se trouve dans le sous-répeertoire Core.
    - Le code relatif à la génération de fichiers pour un projet Easy2do se trouve dans E2D
    - les autres sous-répertoires sont consacrés au code héritant des classes E2D.

### g) Créer des sous types de projet
    Ce système d'héritage a été créé pour gérer les types de projet car un projet etotem ne se gère pas de la même façon qu'un easy2do standard
    Si vous voulez modifier le comportement de l'application pour s'adapter aux besoins de votre projet
    et que celui ci n'existe pas, il faut créer un nouveau type de projet.
    - si vous modifiez directement le type E2D ce comportement sera le comportement par défaut du générateur sur tous les projets
    Pour créer un  nouveau type de projet il faut:
        -   un sous répertoire de src qui équivaut au préfixe de votre type (ex Eto pour Etotem)
        - la classe qui héritera d'une classe de E2D, la convention est de remplacer E2D dans les noms des classes et leurs fichiers
        par le préfixe de votre type de projet (regarder Eto par exemple)
        - En plus de l'héritage, la composition est égalemeent utilisée pour définir certaines rêgles.
        ( Créer une classe de type EtoModuleMakerFactory avec son propre préfixe, cette factory permet d'initialiser les classes qu'ont va utiliseredans le nouveau type de projet)
        - dans la classe Coree/ProjectType, il y a une constante tableau peremettant de savoir quel eest le template par défaut pour chaque type de projet. 
            Ajouter ce qui concerne le nouveau type dans cette table qui, on est d'accord, n'a rien à faire la et devrais être dans config.yml
        - le type de projet appelé sera déterminé par le premier argument de votre ligne de commande derrière l'executable. Par exemple eto lancera le code situé dans Eto.
        - A noter que vous êtes pas obligé de créer une nouvelle classe pour chaque classe présente dans initializeComponents() 
            on peut utiliser une classe de E2D directement

### h) Les composants du code
        A faire