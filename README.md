# Invotra - D9

Test migrate of the webforms
Where used a default https://github.com/wodby/docker4drupal here.

# Steps to raise a build

1. Go to the project directory via terminal
2. Copy DB backup into the docker/mariadb-init dir - `cp {path to the DB} docker/mariadb-init/` 
3. Go to docker dir via terminal - `cd docker/`
4. Raise a new build  - `docker-compose up -d`
5. Configurate settings.php file according to the settings in the docker/.env
