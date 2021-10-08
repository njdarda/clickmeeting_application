# clickmeeting_application

Simple application that lets you create a 150px x 150px thumbnail of an image. You can then save it on disk or upload to Dropbox, or an Amazon S3 Bucket.
Built on top of symfony/skeleton.

[These commits contain implementation](https://github.com/njdarda/clickmeeting_application/compare/b57cea781333a3759cdba8d1f07229e3a18d42bf...17a11482c209208156a033960752adeb8caad98f). The rest is mostly setup and styling.

## Getting started

### Prerequisites
- docker
- npm

### Setting up environment
Run the following command in project's root directory:
```
(cd docker && docker-compose up)
```
This will download a docker image with required environment and start the server on `localhost:8000`.

If you need to use different port you can do that by changing the exposed port in `docker/docker-compose.yml`
```
    ports:
      - "[PORT]:80"
```
and adding `APP_URL` to `.env.local`
```
APP_URL=http://localhost:[PORT]
```

### Installing dependencies
Now you can install composer and npm dependencies:
```
docker exec -it docker_clickmeeting_1 composer install
npm install
npm run build
```
#### Setting environment variables
In order to upload files to Amazon S3 and dropbox you will have to provide the following credentials
```bash
# .env.local

AWS_BUCKET_NAME=clickmeeting-application
AWS_ACCESS_KEY_ID=ABC
AWS_SECRET_ACCESS_KEY=DEF

DROPBOX_CLIENT_ID=abc
DROPBOX_CLIENT_SECRET=def
```
