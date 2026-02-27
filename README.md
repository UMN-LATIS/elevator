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

You'll need [Docker Desktop](https://www.docker.com/products/docker-desktop/) and [Composer](https://getcomposer.org/).

```bash
bash scripts/bootstrap
```

The script handles everything: copying `.env`, generating SSL certs, starting Docker, installing PHP/Node dependencies, running the Doctrine schema, and seeding the database. It's safe to re-run — steps that are already complete are skipped.

After the script finishes:

- **App:** http://localhost/defaultinstance
- **Admin:** username `admin`, password is the `ADMIN_PASSWORD` value in your `.env`

### Processing files

Upload a file through the UI, then grab the `fileObjectId` (press Cmd+Ctrl+H when viewing the asset):

```bash
./docker/runJob.sh <fileObjectId>
```

To run search indexing:

```bash
docker compose exec php-fpm php index.php beltdrive updateIndexes
```

### Running API tests

```bash
npm run test:api
```

Requires `ADMIN_PASSWORD` to be set in `.env`. Set `CI_ENV=testing` in `.env` to enable the database-reset endpoint the tests use (the default `CI_ENV=local` also works).

## Documentation

Docs use [vuepress](https://vuepress.vuejs.org/) and are found in ./docs.

```
# develop docs locally
npm run docs:dev

# deploy docs
npm run docs:deploy
```
