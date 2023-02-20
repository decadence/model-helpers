# Laravel Model Helpers

## syncMany
Формат входного массива

```php
[
    "любой ключ" => [
        // ключ хоть и может быть пустым, но должен быть передан
        "id" => "id существующей записи отношения или пустой / случайный несуществующий ID для создания новой записи",
    
        // далее любые атрибуты, которые подойдут для
        // метода fill
        "attribute" => "value",
        "attribute" => "value",    
    ],
];
```

Если id не передан, запись удаляется из отношения.
