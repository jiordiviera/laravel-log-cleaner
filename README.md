# Laravel Log Cleaner

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jiordiviera/laravel-log-cleaner.svg?style=flat-square)](https://packagist.org/packages/jiordiviera/laravel-log-cleaner)
[![Total Downloads](https://img.shields.io/packagist/dt/jiordiviera/laravel-log-cleaner.svg?style=flat-square)](https://packagist.org/packages/jiordiviera/laravel-log-cleaner)

**Laravel Log Cleaner** est un package simple permettant de nettoyer le contenu du fichier de logs `laravel.log` via une commande Artisan. Ce package est compatible avec Laravel 9, 10, et 11.

## Installation

Tu peux installer ce package via **Composer** en utilisant la commande suivante :

```bash
composer require jiordiviera/laravel-log-cleaner
```

## Compatibilité

- Laravel 9.x
- Laravel 10.x
- Laravel 11.x

## Utilisation

Ce package ajoute une commande Artisan permettant de supprimer le contenu du fichier `laravel.log`. Pour utiliser cette commande, exécute :

```bash
php artisan log:clear
```

Lorsque tu exécutes cette commande, tout le contenu du fichier `laravel.log` est supprimé, et un message de confirmation est affiché.

### Exemple

```bash
$ php artisan log:clear
Laravel log file cleared successfully.
```

## Configuration

Aucune configuration supplémentaire n'est nécessaire. Dès que le package est installé, la commande `log:clear` est disponible.

## Tests

Pour tester ce package, nous utilisons **Pest**. Pour lancer les tests, exécute la commande suivante :

```bash
./vendor/bin/pest
```

Assure-toi d'avoir les tests correctement définis dans le dossier `tests/`.

## Contribution

Les contributions sont les bienvenues ! N'hésite pas à soumettre des **Issues** ou des **Pull Requests** via [GitHub](https://github.com/jiordiviera/laravel-log-cleaner).

### Développement

Pour démarrer avec le développement :

1. Clone le repository :

   ```bash
   git clone https://github.com/jiordiviera/laravel-log-cleaner.git
   ```

2. Installe les dépendances :

   ```bash
   composer install
   ```

3. Lance les tests :

   ```bash
   ./vendor/bin/pest
   ```

## À propos

Ce package a été développé pour simplifier la gestion des fichiers de logs dans un projet Laravel. Plutôt que de supprimer manuellement les logs, tu peux maintenant le faire en une seule commande.

## Licence

Le package Laravel Log Cleaner est open-source sous la licence [MIT](https://opensource.org/licenses/MIT).

---

> **Note :** Ce package a été développé avec Laravel 11, mais il est également compatible avec les versions antérieures de Laravel (9 et 10).

Pour plus d'informations, consulte [le repository sur GitHub](https://github.com/jiordiviera/laravel-log-cleaner).