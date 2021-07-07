FROM mariadb:10.5.11

ARG UID=1000
ARG GID=1000
ENV UID=${UID}
ENV GID=${GID}
RUN getent passwd $UID || (groupadd --gid $GID user && useradd --uid $UID --gid user --shell /bin/bash --create-home user)
USER $UID

CMD ["mysqld"]
EXPOSE $MYSQL_PORT