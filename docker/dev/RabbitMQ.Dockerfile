FROM rabbitmq:3.8

#ADD docker/dev/config/rabbitmq.conf /etc/rabbitmq/rabbitmq.config
ADD docker/dev/config/rabbit_enabled_plugins /etc/rabbitmq/enabled_plugins
