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

1. run `docker compose up` and let it build
2. run `./docker-php.sh composer.phar install`
3. run `./docker-php.sh doctrine.php orm:schema-tool:update --force`
4. connect to postgres (localhost 5432, u/p from your .env file) and run the queries in `postgresQueries`
5. run `bash elastic_commands.sh`
6. connect to http://localhost/defaultinstance in a browser, using admin username. Password is in bitwarden
7. Optionally add some S3 bucket credentials in the instance settings. Bonus points if you mock s3 and make that all work

To try processing a file, first upload it using the normal UI. Then grab the fileObjectId (press command-control-h when viewing the asset)

Run `./runJob.sh <fileObjectId>`

