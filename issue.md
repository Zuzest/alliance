1. Написать API для приема контактных данных клиентов, с возможностью:
  1.1 Добавлять данные в базу массивом, например:
    POST /contacts
    {
      source_id: 1,
      items: [{
        "name": "Анна",
        "phone": 9001234453,
        "email": "mail1@gmail.com"
      }, {
        "name": "Иван",
        "phone": "+79001234123",
        "email": "mail2@gmail.com"
      }]
    }

    - `source_id` - id источника контактов, для примера достаточно 1 и 2
    - `phone` в бд сохраняем в формате без +7 (10 цифр)
    - `phone` не уникально, но может добавляться максимум 1 раз в сутки для каждого `source_id`

    В ответе - количество добавленных контактов.

  1.2 Находить данные по номеру телефона, например:
    GET /contacts?phone=9001234453

    В ответе - массив с найденными данными

2. Оптимизировать скорость добавления и поиска, т.к. контактов в базе будет десятки млн

3. По желанию. Сделать простой интерфейс, например на bootstrap


Результат - исходный код на github или архив