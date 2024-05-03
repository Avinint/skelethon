
## Présentation de Skelethon : ##

    Quoi :
Skelethon est un générateur de code en ligne de commandes php.

    Où :
 Il faut se placer à la racine d'un répertoire de projet de la VM afin d'avoir accès à la base de données
    
    Comment :
   - On execute le script dans le répertoire projet php /chemin/vers/script e2d module|modele nom_module nom_modele
   - L'option module créera un nouveau module et sera ignorée si le module existe déja
   - L'option modele ajoutera un "modèle" à un module existant et sera ignorée si le module n'existe pas
#### ici la notion de modèle est large et ne correspond pas à la notion de domaine,
      en fait, on ajoute la classe modèle
      mais également les classes controleur correspondant au modèle
      ainsi que les classes Js et les vues html
  - la deuxième différence principale entre ces deux options est que la commande modèle met à jour les fichiers conf et les fichiers JS existants
      pour ajouter la prise en charge du nouveau "modèle".
  - On peut créer des alias bash pour appeler le script - et y inclure le type de projet - pour raccourcir la commande, par exemple :
     alias e2d="php /data/apache/base/dev/skelethon/index.php e2d" pour un projet easy2do standard.


    Détails :
  L'application skelethon se divise en deux parties :
  - Une partie configuration pour rassembler les données nécessaires à la génération de code
  - Une partie génération ou l'application va créer le contenu du module en ajoutant les fichiers
  en suivant la structure de module déclarée dans un fichier module.yml
 
La partie configuration se divise en trois parties :
  - le questionnaire 1, avec des informations essentielles pour le code  tels que le nom de la table correspond au model
  - la récupération des données depuis la BDD afin de renseigner les champs du modèle
  - le questionnaire 2, permet de compléter le premier questionnaire avec des questionnaires dépendant du schéma de données, par exzemple,
   quel champs seront affichés dans les vues recherche, edition, liste, consultation etc.
La phase configuration permet de remplir un fichier config.yml au fur et à mesures des différentes réponses aux questionnaires
une fois cette phase terminée la phase génération commence
si on ferme le script avant la génération, on peut relancer le script,
  - le questionnaire sera complété si nécessaire et la génération sera lancée
  - les questions renseignées sont sauvegardées dans le yaml et ne seront pas posées à nouveau
  - il est possible de modifier le yaml manuellement pour modifier les réponses à postériori
  - on peut également choisir de ne pas générer immédiatement le code, relancer le script permettra de générer le code plus tard
Pour les modes module et modèle, le script suit la structure renseignée dans le fichier module.yml correspond à celle d'un répertoire de module
  - ce fichier peut être modifié pour ne pas générer certains fichiers
  - il est possible d'avoir un fichier module.yml différent en fonction du template du projet.
      par exemple le template restful n'ajoute pas les mêmes fichiers que le template oracle

Templates et type : 
  - en plus de la configuration, chaque projet peut être personnalisé avec une ou plusieurs templates
  - le template standard correspond à un projet easy2do standard. il y a des templates pour gregor-amont, pour etotem,
pour esm(cnsmd) etc;
  - les templates permettent de personnaliser les fichiers générés.
  Il est également possible d'étendre les classes E2D de l'application pour personnaliser la logique
  - Pour que ces héritages soient pris en compte il faut personnaliser le fichier ModuleMakerFactory correspondant au
  projet
  - il faut créer un nouveau type de projet, celui-ci est spécifié dans la ligne de commande (e2d esm etc.)
  - Par exemple ESMModuleMakerFactory
  - mais pas toujours nécessaire, les templates restful et oracle par exemple n'ont pas besoin de code spécifique
