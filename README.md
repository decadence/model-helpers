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

## Filter
```php
$filterData = request("filter");

$filter = new Filter();
$filter->addFilter(new \App\Filters\Contractors\Company());

$contractors = $filter->applyFilters($contractors, $filterData);

$contractors = $contractors->get();
```

В форме делаем так, что у каждого поля есть имя и в массиве содержатся ключи value, operator. value может быть массивом, если фильтр это обрабатывает. В конструктор каждого фильтра можно передавать параметры, если требуется.


