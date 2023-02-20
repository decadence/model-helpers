# Laravel Model Helpers

## syncMany
Синхронизация отношения HasMany по массиву данных. Формат входного массива:

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

В форме делаем так, что у каждого фильтра в массиве есть ключ и в массиве по этому ключу содержатся ключи `value`, `operator`, либо сразу значение.

`value` может быть массивом, если фильтр это обрабатывает.

```php
[
    // значение и оператор
    "name" => [
        "value" => "David",
        "operator" => "!="
    ],
    
    // или сразу значение, оператор будет =
    "age" => 30
]
```

В конструктор каждого фильтра можно передавать параметры, если требуется.

Пример класса фильтра

```php
class Company extends Filter
{
    protected $requestName = "companies";

    public function apply(Builder $query, $value, $operator = "=")
    {
        // должны быть договора с такими компаниями
        $query->whereHas("contracts.company", function ($company) use ($value) {
            $company->whereIn("id", $value);
        });

        return $query;
    }
}
```


