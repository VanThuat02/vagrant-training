<?php
$redis = new Redis();
$redis->connect('training-redis', 6379); // tên service trong docker-compose

$redis->set("hello", "world");
echo $redis->get("hello"); // in ra "world"
