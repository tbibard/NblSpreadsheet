**NblSpreadsheet** est une application en ligne de commande permettant d'importer des données depuis des fichiers de tableur (xls, ...) vers une base de données.

L'application a été développé pour un besoin spécifique, l'import des estimations de la population française par région et département fournis par l'INSEE.
Ces données n'étaient pas intégrables en base de données sans un traitement spécifiques.
[Source INSEE][1].

D'autres type d'imports pourraient être ajoutés.

L'application utilise les composants suivant:
- [symfony/console][2]
- [symfony/dotenv][3]
- [PHPOffice/PhpSpreadsheet][4]

Installation
------------

```
$ mkdir [VotreDossier]
$ cd [VotreDossier]
$ git clone https://github.com/tbibard/NblSpreadsheet.git .
$ composer install
$ cp .env.dist .env
```

Usage
-----

```
$ bin/console
```

Usage pour import Population
----------------------------
Cette application a été testé avec deux types de base de données (Mysql/MariaDB et Postgresql).
Afin d'importer les estimations de populations:
- créer une bdd, par exemple nommée 'population'.
- compléter le fichier .env précédemment créé.

Créer le schéma dans votre bdd:
```
$ bin/console population:prepare-database all
```

Des options permettent de re-créé le schéma ou de vider les tables.

Récupérer les fichiers :
```
$ bin/console population:get-files
```

Importer les données :
```
$ bin/console population:import all
$ bin/console population:import regionale --table=classe
```

Vérifier les données
--------------------
Vérifie si l'import s'est correctement effectué en comparant la somme totale des populations.
```
$ bin/console population:check
$ bin/console population:check --table=regionale_classe
```

Des options permettent d'importer uniquement certains fichiers.


[1]: https://www.insee.fr/fr/statistiques/1893198
[2]: https://symfony.com/doc/current/components/console.html
[3]: https://symfony.com/doc/current/components/dotenv.html
[4]: https://github.com/PHPOffice/PhpSpreadsheet