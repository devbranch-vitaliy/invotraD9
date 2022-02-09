# Invotra - D9

Test migrate of the webforms
Where used a default https://github.com/wodby/docker4drupal here.

# Steps to raise a build

1. Go to the project directory via terminal
2. Copy DB backup to the docker/mariadb-init dir - `cp {path to the DB} docker/mariadb-init/` 
3. Go to docker dir via terminal - `cd docker/`
4. Raise a new build  - `docker-compose up -d`
5. Copy setting file to the project - `cp docker/settings.php ../drupal/web/sites/default/settings.php` 

# Users

The site has a user *admin* with the same password as the current integration sites.