<img src="assets/images/elevatorSolo.png" alt="Elevator Logo" />

# Elevator

> Multi-instance digital asset repository

Elevator can store content in any format, such as images, audio, video, 3D objects, Sharable Content Object Reference Model (SCORM) bundles, and Portable Document Files (PDF) files. Elevator also provides a suite of standard tools for previewing and playing even exotic assets, like 3D walkthroughs and rotatable 3D objects.

## Features

- **Flexible metadata schema.** Create the schemas that are appropriate for your content, mix and match schemas, or make changes at any time.
- **Any digital asset.** Traditional media files, a Microsoft Office document, a SCORM bundle, or a proprietary filetype — Elevator can catalog it, archive it, and in most cases offer rich display.
- **Cloud storage.** Designed to run on Amazon Web Services.

## Resources
- [Documentation](https://umn-latis.github.io/elevator/)
- [University of Minnesota Digital Content Library](https://dcl.elevator.umn.edu/)

## Contact

- Email: <elevator@umn.edu>

---

## Running locally

### Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- [`gh`](https://cli.github.com/) — GitHub CLI, authenticated with `gh auth login`. Required to fetch the Elasticsearch setup script from the private ansible repo.
- [`mkcert`](https://github.com/FiloSottile/mkcert) *(optional but recommended)* — generates locally-trusted SSL certs. Without it, bootstrap falls back to a self-signed cert that will show browser warnings.

### First-time setup

```bash
bash scripts/bootstrap
```

The script handles everything: copying `.env`, generating SSL certs, starting Docker, installing PHP and Node dependencies, running the Doctrine schema, seeding the database, syncing the admin password, and setting up the Elasticsearch index with the correct mapping. It's safe to re-run — steps that are already complete are skipped.

The Elasticsearch step fetches the canonical index setup script from [ansible-elevator-v2](https://github.com/umn-cla/ansible-elevator-v2) via `gh`, so you'll need the `gh` CLI installed and authenticated (`gh auth login`) with access to that repo.

After the script finishes:

- **App:** `https://localhost/defaultinstance`
- **Admin:** username `admin`, password is `DEFAULT_ADMIN_PASSWORD` from your `.env` (default: `admin`). To change it, update `DEFAULT_ADMIN_PASSWORD` in `.env` and re-run `./scripts/bootstrap`.

### Multiple worktrees / port conflicts

Each worktree needs its own `.env` with unique port values so their Docker stacks don't collide:

```dotenv
HTTP_PORT=8080
HTTPS_PORT=8443
POSTGRES_PORT=5433
REDIS_PORT=6380
ELASTICSEARCH_PORT=9201
ELASTICSEARCH_TRANSPORT_PORT=9301
BEANSTALKD_PORT=11301
```

See `.env.example` for the full list and defaults.

---

## Day-to-day development

### Processing files

Upload a file through the UI, then grab the `fileObjectId` (press Cmd+Ctrl+H when viewing the asset):

```bash
./docker/runJob.sh <fileObjectId>
```

### Override admin password

Update `DEFAULT_ADMIN_PASSWORD` in `.env` and re-run `./scripts/bootstrap`.

---

## Running API tests

```bash
npm test
```

Requires `DEFAULT_ADMIN_PASSWORD` to be set in `.env`. `CI_ENV=local` (the default) is sufficient to enable the database-reset endpoint the tests use.

---

## Database migrations (Doctrine ORM)

Elevator uses Doctrine for schema management. The entity PHP files are generated from XML descriptors in `application/doctrine/`.

### Making a schema change

1. Edit the XML descriptor (e.g. `application/doctrine/Entity.Foo.dcm.xml`)
2. Regenerate the PHP entity:
   ```bash
   docker compose exec php-fpm php doctrine.php orm:generate-entities application/models
   ```
3. Preview the SQL:
   ```bash
   docker compose exec php-fpm php doctrine.php orm:schema-tool:update --dump-sql
   ```
4. Apply it:
   ```bash
   docker compose exec php-fpm php doctrine.php orm:schema-tool:update --force
   ```
5. Clear the Doctrine cache (models are cached in Redis):
   ```bash
   docker compose exec redis redis-cli flushdb
   ```

---

## S3 storage

Elevator requires S3 for file storage — there is no local storage fallback. File uploads and processing will fail until an S3 bucket is configured.

S3 credentials are set **per instance** in the admin UI (Instance settings → Amazon S3 Key / Secret / Bucket), not in `.env`.

**Preferred:** ask the team for dev server S3 credentials and use those. This skips bucket setup and lets you process real files immediately.

**Optional — set up your own bucket:**

1. Create an S3 bucket (e.g. `elevator-local-yourname`)
2. In IAM, create a new user with a policy granting `s3:*` on that bucket
3. Generate an access key for the user — save the key and secret (shown once)
4. Apply the following bucket policy (replace the bucket name):

```json
{
  "Version": "2008-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Principal": { "AWS": "*" },
      "Action": "s3:GetObject",
      "Resource": [
        "arn:aws:s3:::YOUR-BUCKET/derivative/*streaming/*",
        "arn:aws:s3:::YOUR-BUCKET/thumbnail/*",
        "arn:aws:s3:::YOUR-BUCKET/vtt/*"
      ]
    },
    {
      "Effect": "Deny",
      "Principal": { "AWS": "*" },
      "Action": ["s3:DeleteObject", "s3:DeleteObjectVersion"],
      "Resource": "arn:aws:s3:::YOUR-BUCKET/original/*",
      "Condition": { "Null": { "aws:MultiFactorAuthAge": true } }
    },
    {
      "Effect": "Deny",
      "Principal": { "AWS": "*" },
      "Action": "s3:PutLifecycleConfiguration",
      "Resource": "arn:aws:s3:::YOUR-BUCKET",
      "Condition": { "Null": { "aws:MultiFactorAuthAge": true } }
    }
  ]
}
```

5. Enter the key, secret, bucket name, and region in the admin UI under Instance settings

The `AWS_QUEUEING_*` vars in `.env` are separate — they're for the AWS Batch job queue used for background file processing, and are only needed if you're not using the local Beanstalkd queue.

---

## Troubleshooting

### Docker "no space left on device"

```bash
docker system prune -a --volumes -f
docker builder prune -a -f
```

This typically frees 20–40 GB and clears the error.

### Elasticsearch not returning results

Make sure your `.env` has `ELASTIC_HOST=elasticsearch:9200` (the `:9200` port suffix is required).

If the index exists but search returns nothing, the index mapping is likely missing or stale. Do a full reset:

```bash
# Re-fetch the setup script and rebuild the index from scratch
bash scripts/bootstrap
```

Or manually, if you want to avoid re-running the full bootstrap:

```bash
_idx="elevator"  # match ELASTIC_INDEX in .env
curl -XDELETE "http://localhost:9200/${_idx}_1"
curl -XDELETE "http://localhost:9200/${_idx}"
bash scripts/elastic_setup.sh localhost "${_idx}_1" "${_idx}"
docker compose exec php-fpm php index.php admin reindex
```

Assets are automatically re-indexed on save, so a full reindex is only needed after a mapping change or bulk data import.

---

## Documentation

Docs use [VuePress](https://vuepress.vuejs.org/) and live in `./docs`.

```bash
# develop docs locally
npm run docs:dev

# deploy docs
npm run docs:deploy
```
