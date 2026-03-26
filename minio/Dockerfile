FROM pgsty/minio:latest

VOLUME ["/data"]

EXPOSE 9000 9001

ENTRYPOINT ["/usr/bin/minio"]
CMD ["server", "/data", "--console-address", ":9001"]