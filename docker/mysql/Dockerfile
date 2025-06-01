ARG MYSQL_VERSION
FROM mysql:${MYSQL_VERSION}-debian

# 日本語環境の設定
RUN apt-get update \
    && apt-get install -y locales \
    && echo "ja_JP.UTF-8 UTF-8" > /etc/locale.gen \
    && locale-gen ja_JP.UTF-8

ENV LANG ja_JP.UTF-8
ENV LANGUAGE ja_JP:ja
ENV LC_ALL ja_JP.UTF-8

CMD ["mysqld"]