# Invotra - D9

Test migrate of the webforms
Where used a default https://github.com/wodby/docker4drupal here.

# Steps to raise a build

1. Go to the project directory via terminal
2. Copy DB backup to the docker/mariadb-init/Drupal9 dir - `cp {path to the DB} docker/mariadb-init/Drupal9/`
3. Copy private files to the docker/templates/files/private - `cp -r {path to the files} docker/templates/files/private/`
4. Go to docker dir via terminal - `cd docker/`
5. Raise a new build  - `docker-compose up -d`

# Users

The site has a user *admin* with the same password as the current integration sites.