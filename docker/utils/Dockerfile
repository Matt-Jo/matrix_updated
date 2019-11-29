FROM alpine:3.9

ENV \
    BUILD_DEPS="gettext"  \
    RUNTIME_DEPS="libintl openssl make findutils"

RUN \
    apk add --update $RUNTIME_DEPS && \
    apk add --virtual build_deps $BUILD_DEPS &&  \
    cp /usr/bin/envsubst /usr/local/bin/envsubst && \
    apk del build_deps

