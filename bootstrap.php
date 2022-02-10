<?php

declare (strict_types = 1);

use Phalcon\Di\FactoryDefault as DI;
use Phalcon\Loader;
use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Mvc\Micro;
use Phalcon\Db\Enum;

$config = new Config(require_once __DIR__ . "/config.php");
$loader = new Loader;
$loader->registerDirs([__DIR__]);

$di = new DI;
$di["config"] = $config;
$di["loader"] = $loader;
$di->setShared("db",  function () use ($di) {
  $db = new Mysql($this->getConfig()->db->toArray());

  if (!$db->tableExists("contacts")) {
    $dbCreate = "CREATE TABLE `contacts` (
      `id` INT NOT NULL AUTO_INCREMENT,
      `source_id` INT NULL DEFAULT NULL,
      `name` VARCHAR(1000) NULL DEFAULT NULL,
      `phone` CHAR(10) NULL DEFAULT NULL,
      `email` VARCHAR(500) NULL DEFAULT NULL,
      `dt_create` VARCHAR(500) NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      INDEX `phone` (`phone`)
    )
    ENGINE=InnoDB
    ;";
    $db->query($dbCreate);
  }

  return $db;
});

$app = new Micro($di);

$app->get("/contacts", function () use ($app) {
  $phone = $app->request->getQuery("phone", "int", null);
  if ($phone) {
    $phone = (string) $phone;
    if (mb_strlen((string)$phone) > 10) {
      $phone = mb_substr($phone, (mb_strlen($phone)-10));
    }

    $result = $app->db->query("SELECT * FROM `contacts` WHERE `phone` = :phone", [
      "phone" => $phone,
    ]);
    $result->setFetchMode(Enum::FETCH_OBJ);

    if ($result->numRows()) {
      $answer = $result->fetchAll();

      return $app->response->setJsonContent($answer)->send();
    }

    echo "Данных не обнаружено";
  }
});

$app->post("/contacts", function () use ($app) {
  $data = json_decode(file_get_contents("php://input"));

  $source_id = $data->source_id ?? null;
  $items = $data->items ?? null;
  if ($source_id && is_array($items)) {
    $date = date("Y-m-d");
    $insertCount = 0;
    foreach($items as $item) {
      $phone = $item->phone;
      $phone = preg_replace('/[^0-9]/', '', $phone);
      if (mb_strlen($phone) > 10) {
        $phone = mb_substr($phone, (mb_strlen($phone)-10));
      }
      // проверяем наличие сегодня такой записи
      $result = $app->db->query("SELECT * FROM `contacts` WHERE `source_id` = :source_id AND `phone` = :phone ANd `dt_create` > :date", [
        "source_id" => $source_id,
        "phone" => $phone,
        "date" => date("Y-m-d"),
      ]);
      $result->setFetchMode(Enum::FETCH_NUM);
      if($result->numRows()) {
        // сегодня уже есть требуемая запись
        // идем дальше
        continue;
      }
      $name  = $item->name ?? null;
      $email = $item->email ?? null;

      $result = $app->db->insert("contacts",
        [$source_id,  $name,  $phone,  $email],
        ["source_id", "name", "phone", "email"]
      );

      if ($result) {
        ++$insertCount;
      }


    }

    $answer = [
      "add" => $insertCount,
    ];

    return $app->response->setJsonContent($answer)->send();
  }
  echo "неверный формат данных";
});

$app->notFound(function () use ($app) {
  echo $app->config->application->name;
});

$app->handle($di["request"]->getURI());
