# Simple Tracking System

Like Google Analytics(GA),this is a simple tracking system.Google only show sampled data,if you want to retrieve detail data.you must pay for it. so why not setup a system for yourself

Only use Lumen,Elasticsearch,Kibana make it simple and fast.if you already familiar with this,then it's very easy for you.

# Quick Start

- Install Elasticsearch & Kibana. in the root directory,there is a file docker-compose.yml.you can use that to setup a docker Elasticsearch qucikly. Or you can install that follow by [official docs](https://www.elastic.co/guide/en/elasticsearch/reference/current/deb.html)
- Deploy Code. `git clone git@github.com:nay-kang/jiaozi.git`
    - composer install
    - for performance reason.this lumen project use phpredis.you can install it by `sudo apt-get install php-redis`
    - copy .env.example to .env
    - setup nginx for it
- Edit app\Extensions\ProfileConfig.php. Or Just use it.the array key 'jn321grq8rwvp5q6' is the project id.
- then request a url like this.
    - `curl "jiaozi.example.com/collect_img.gif?type=pageview&_jiaozi_uid=123&pid=jn321grq8rwvp5q6"`
    - type: can be either pageview or event
    - pid: is your project id.
    - _jiaozi_uid: every session need a random id to track user behavior
    - there are some other params like: referer,if you type is event.than params are
        - category,action,label,value (like google)
- and then you can see it in kibana which index name is collector-*
