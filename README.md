**NblSpreadsheet** est une application en ligne de commande permettant d'importer des données depuis des fichiers de tableur (xls, ...) vers une base de données.

L'application a été développé pour un besoin spécifique, l'import des estimations de la population française par région et département fournis par l'INSEE.
Ces données n'étaient pas intégrables en base de données sans un traitement spécifiques.
[Source INSEE][1].

D'autres type d'imports pourraient être ajoutés.

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
$ ./console
```

Usage pour import Population
----------------------------
Cette application a été testé avec deux types de base de données (Mysql/MariaDB et Postgresql).
Afin d'importer les estimations de populations:
- créer une bdd, par exemple nommée 'population'.
- compléter le fichier .env précédemment créé.

Créer le schéma dans votre bdd:
```
$ ./console population:prepare-database all
```

Des options permettent de re-créé le schéma ou de vider les tables.

Récupérer les fichiers :
```
$ ./console population:get-files
```

Importer les données :
```
$ ./console population:import all
```

Des options permettent d'importer uniquement certains fichiers.


[1]: https://www.insee.fr/fr/statistiques/1893198