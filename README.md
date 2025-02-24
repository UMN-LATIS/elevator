<img src="assets/images/elevatorSolo.png" alt="Elevator Logo" />

# Elevator

> Multi-instance digital asset repository

Elevator can store content in any format, such as images, audio, video, 3D objects, Sharable Content Object Reference Model (SCORM) bundles, and Portable Document Files (PDF) files. Elevator also provides a suite of standard tools for previewing and playing even exotic assets, like 3D walkthroughs and rotatable 3D objects.

## Features

- **Flexible metadata schema.** Create the schemas that are appropriate for your content, mix and match schemas, or make changes at any time.
- **Any digital asset.** Traditional media files, a Microsoft Office document, a SCORM bundle, or a proprietary filetype - Elevator can catalog it, archive it, and in most cases offer rich display.
- **Cloud storage.** Designed to run on the Amazon Web Services.

## Resources

- [Documentation](https://umn-latis.github.io/elevator/)
- [University of Minnesota Digital Content Library](https://dcl.elevator.umn.edu/)

## Contact

- Email: <elevator@umn.edu>

## Running locally

You'll need docker and some patience.

```sh
# copy .env.example to .env
cp .env.example .env

# generate some local certs
npm run cert

# build docker containers
docker compose up

# install composer dependencies
docker compose exec php-fpm composer install

# doctrine
docker compose exec php-fpm php ./doctrine.php orm:schema-tool:update --force

# initialize PostgreSQL by running the queries in postgresQueries
# username/password in .env, assuming default "elevator"
psql -h localhost -p 5432 -U elevator -d elevator -f ./docker/postgresQueries

# install npm dependencies
npm install

# build
npm run gulp

# pull in submodules
git submodule update --init --recursive

# build elevator ui
(cd assets/elevator-ui && yarn install && yarn build:prod)

# create instace assets
mkdir assets/instanceAssets && chmod 777 assets/instanceAssets

# set www-data as owner
docker compose exec php-fpm chown -R www-data:www-data /var/www/html

# restart containers
# ?? not sure if this is needed. looking at deploy.php
docker compose restart

```

2. run `bash elastic_commands.sh`
3. connect to http://localhost/defaultinstance in a browser, using admin username. Password is in bitwarden. In order to use that password, you'll need to use the same encryption key as dev
4. Optionally add some S3 bucket credentials in the instance settings. Bonus points if you mock s3 and make that all work

To try processing a file, first upload it using the normal UI. Then grab the fileObjectId (press command-control-h when viewing the asset)

Run `./runJob.sh <fileObjectId>`

By default, you won't have job queue processing running for other tasks. That's mostly fine, but you may want search indexing running. Run `./docker-php index.php beltdrive updateIndexes`
