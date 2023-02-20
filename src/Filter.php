<?php

namespace Decadence;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Базовый класс для фильтров
 */
class Filter
{
    /**
     * Допустимые по умолчанию фильтры
     * @var string[]
     */
    protected $operators = [
        "=", "!=",
    ];

    /**
     * Ключ, по которому берётся значение из данных фильтра
     * @var string
     */
    protected $requestName;

    /**
     * Все фильтры для проверки
     * @var Collection
     */
    protected $filters;

    public function __construct()
    {
        $this->filters = collect();
    }

    public function getRequestName()
    {
        return $this->requestName;
    }

    /**
     * Передан допустимый оператор
     * @param $operator
     * @return bool
     */
    protected function isOperatorValid($operator)
    {
        return in_array($operator, $this->operators);
    }

    /**
     * Применение фильтра на Builder
     * @param Builder $query
     * @param $value
     * @param string $operator
     * @return Builder
     */
    public function apply(Builder $query, $value, string $operator = "=")
    {
        // по умолчанию никаких действий не предпринимаем
        return $query;
    }

    /**
     * Добавление фильтра в список на проверку
     * @param Filter $filter
     * @return void
     */
    public function addFilter(Filter $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * Применение фильтров на нужном Builder по переданным данным
     * @param Builder $builder
     * @param array $filterData
     * @return Builder
     */
    public function applyFilters(Builder $builder, array $filterData)
    {
        /** @var Filter $filter */
        foreach ($this->filters as $filter) {

            // получаем для текущего фильтра его имя, с которым
            // он в запросе передаётся
            $requestName = $filter->getRequestName();

            if ($requestName === null) {
                throw new InvalidArgumentException("Не задано имя для фильтра");
            }

            // пытаемся получить имя и оператор из отдельных ключей
            $value = data_get($filterData, "{$requestName}.value");
            $operator = data_get($filterData, "{$requestName}.operator", "=");

            // если данных в таком формате нет, пытаемся получить значение напрямую
            if ($value === null) {
                $value = data_get($filterData, $requestName);
            }

            // если значения всё равно нет, ничего не фильтруем
            if ($value === null) {
                continue;
            }

            if (!$this->isOperatorValid($operator)) {
                throw new InvalidArgumentException("Неверный оператор для фильтра");
            }

            // иначе применяем фильтр, присвоение для наглядности
            $builder = $filter->apply($builder, $value, $operator);
        }

        return $builder;

    }
}
