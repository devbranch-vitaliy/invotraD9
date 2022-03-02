# Invotra - D9

Test migrate of the webforms.

Where used a default https://github.com/wodby/docker4drupal here.

# Databases

Download and extract DBs from the [google drive folder](https://drive.google.com/drive/u/3/folders/1YpVoqmws_FX-86Q6SEiP1kcN0WjYpQ7l)

Databases:
* invotraD7.sql - Drupal 7 site
* invotraD9-empty.sql - DB snap of the empty Drupal 9 site (without migrated data). Uses to check migration process.
* invotraD9-MigratedData.sql - DB snap of the Drupal 9 site with migrated webforms. Uses to check the result.

# Steps to raise a build

## To check the [migration process](#migrate-data)
1. Go to the project directory via terminal
2. Copy D9 DB backup to the docker/mariadb-init/Drupal9 dir - 
  `cp {path to the DB}/invotraD9-empty.sql docker/mariadb-init/Drupal9/`
3. Copy D7 DB backup to the docker/mariadb-init/Drupal7 dir -
  `cp {path to the DB}/invotraD7.sql docker/mariadb-init/Drupal7/`
4. Go to docker dir via terminal - `cd docker/`
5. Raise a new build  - `docker-compose up -d`
6. If this is the first run, wait a couple of minutes for the databases (especially D7 DB) to load.
7. Connect to the [php container](#drushcomposer) and install the Drupal site - `composer install`
8. Clear the cache - `drush cr`
9. Check the site is working and databases have been loaded
   * Site - [http://invotra_d9.docker.localhost:99](http://invotra_d9.docker.localhost:99)
   * D7 database - [http://adminer_d7.invotra_d9.docker.localhost:99](http://adminer_d7.invotra_d9.docker.localhost:99)
   * D9 database - [http://adminer.invotra_d9.docker.localhost:99](http://adminer.invotra_d9.docker.localhost:99)

## To check the migrated webforms
1. Go to the project directory via terminal
2. Copy D9 DB backup to the docker/mariadb-init/Drupal9 dir -
   `cp {path to the DB}/invotraD9-MigratedData.sql docker/mariadb-init/Drupal9/`
3. Go to docker dir via terminal - `cd docker/`
4. Raise a new build  - `docker-compose up -d`
5. Connect to the [php container](#drushcomposer) and install the Drupal site - `composer install`
6. Clear the cache - `drush cr`
7. Enjoy - [http://invotra_d9.docker.localhost:99](http://invotra_d9.docker.localhost:99)

# Users

The site has a user _admin_ with the same password as the current integration sites.

# Migration
## Modules

There are several required contrib modules to migrate webforms:
* Migrate Plus (migrate_plus) - to provide additional migrate plugins/processes.
* Migrate tools (migrate_tools) - to provide additional drush commands.
* Migrate upgrade (migrate_upgrade) (optional) - the module that helps to create standard but separate configurations
  for migration.
* Webform (webform) - to provide webforms on the site
* Webform migrate (webform_migrate) - to provide webform sources for migration.

## Migrate data

### Summary
There ais a folder with custom configs to migrate webforms with all relationships: webforms, submissions, webform nodes,
ideas, queries, the `field_submission` field that makes the relationship between the node and the submissions, etc.
These configs located in the `docker/templates/migrate_configs`.

### Drush/Composer

Recommended to use the drush/composer from the php container - `docker-compose exec php bash`

### Migrate Data

Steps:
1. Import configs - `drush cim --partial --source=/etc/project_templates/migrate_configs/`
2. Check the status of the migration - `drush migrate-status`. Make sure we have all imported configs in the status list.
3. Run the migration process and wait for it to finish - `drush mim --group=migrate_webforms`
4. Check the status of the migration to be sure that all items have been migrated - `drush migrate-status`
