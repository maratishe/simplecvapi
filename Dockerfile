FROM sightmachine/simplecv
RUN \
	apt-get update -y && \
	apt-get install -y python-software-properties && \
	add-apt-repository -y ppa:ondrej/php5-5.6 && \
	apt-get update -y && \
	apt-get dist-upgrade -y && \
	apt-get install -y php5 php5-cli && \
	cd / && \
	rm -Rf test && \
	mkdir test && \
	cd test
COPY . /test
WORKDIR /test
EXPOSE 8001
#CMD [ "php", "php example.php server 0.0.0.0:8001"]
