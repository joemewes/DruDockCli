FROM openjdk:8

LABEL maintainer="4 All Digital  <joe@4alldigital.com>"

RUN apt-get -y update \
    && apt-get install -y --no-install-recommends curl git tmux htop maven \
    && apt-get autoremove -y \
    && apt-get autoclean \
    && apt-get clean

RUN apt-get -y update \
    && apt-get -q install -y --no-install-recommends software-properties-common \
    && curl -sL https://deb.nodesource.com/setup_8.x | bash

RUN apt-get -y update && \
    apt-get -q install -y --no-install-recommends \
    nodejs \
    && rm -fr /tmp/* /var/lib/apt/lists/* /var/tmp/* \
    && apt-get autoremove -y \
    && apt-get autoclean \
    && apt-get clean

RUN npm install -g yarn nodemon jest

WORKDIR /root

RUN curl --insecure -o ./sonarscanner.zip -L https://sonarsource.bintray.com/Distribution/sonar-scanner-cli/sonar-scanner-cli-3.0.3.778-linux.zip
RUN unzip sonarscanner.zip
RUN rm sonarscanner.zip

ENV SONAR_RUNNER_HOME=/root/sonar-scanner-3.0.3.778-linux
ENV PATH $PATH:/root/sonar-scanner-3.0.3.778-linux/bin

COPY sonar-runner.properties ./sonar-scanner-3.0.3.778-linux/conf/sonar-scanner.properties

RUN usermod -u 1000 www-data && \
    usermod -a -G users www-data

# Use bash if you want to run the environment from inside the shell, otherwise use the command that actually runs the underlying stuff
#CMD /bin/bash
CMD sonar-scanner -Dsonar.projectBaseDir=./src