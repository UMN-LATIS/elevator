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

1. copy .env.example to .env (no changes needed)
2. run `docker compose up` and let it build
3. run `./docker-php.sh composer.phar install`
4. run `./docker-php.sh doctrine.php orm:schema-tool:update --force`
5. connect to postgres (localhost 5432, u/p from your .env file) and run the queries in `postgresQueries`
6. run `bash elastic_commands.sh`
7. connect to http://localhost/defaultinstance in a browser, using admin username. Password is in bitwarden. In order to use that password, you'll need to use the same encryption key as dev
8. Optionally add some S3 bucket credentials in the instance settings. Bonus points if you mock s3 and make that all work

To try processing a file, first upload it using the normal UI. Then grab the fileObjectId (press command-control-h when viewing the asset)

Run `./runJob.sh <fileObjectId>`

By default, you won't have job queue processing running for other tasks. That's mostly fine, but you may want search indexing running. Run `./docker-php index.php beltdrive updateIndexes`

## Documentation

Docs use [vuepress](https://vuepress.vuejs.org/) and are found in ./docs.

```
# develop docs locally
npm run docs:dev

# deploy docs
npm run docs:deploy
```
