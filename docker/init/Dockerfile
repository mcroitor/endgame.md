# this image container is used for copying files to the volume
# no operations on the start of the container
FROM debian:10-slim

VOLUME [ "/database", "/site" ]

COPY --chown=www-data:www-data ./database /database
COPY --chown=www-data:www-data ./site /site

CMD [ "echo", "Volumes preparation is done." ]
