# this image container is used for copying files to the volume
# no operations on the start of the container
FROM debian:10-slim

VOLUME [ "/database", "/site" ]

COPY ./database /src/database
COPY ./site /src/site
COPY ./docker/init/init.sh /init.sh
RUN sed -i 's/\r$//' /init.sh && chmod +x /init.sh

CMD [ "bash", "/init.sh" ]
