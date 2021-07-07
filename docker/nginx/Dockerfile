FROM nginx:alpine

WORKDIR /var/www

ADD https://github.com/ufoscout/docker-compose-wait/releases/download/2.7.3/wait /wait
RUN chmod +x /wait

ARG UID=1000
ARG GID=1000
ENV UID=${UID}
ENV GID=${GID}
RUN getent passwd $UID || (addgroup -g $GID user && adduser -u $UID -G user -s /bin/sh -D user)

CMD /wait
CMD ["nginx"]
EXPOSE 80