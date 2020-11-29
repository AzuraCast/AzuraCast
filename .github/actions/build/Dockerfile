FROM azuracast/azuracast_testing:latest

USER root

ENV APPLICATION_ENV="testing"

COPY ./entrypoint.sh /entrypoint.sh

RUN chmod a+x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/bin/true"]
