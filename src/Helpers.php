<?php

namespace Decadence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Arr;

trait Helpers
{

    /**
     * Коллекция к массиву для select
     * @param Collection $collection
     * @param $null
     * @param $keyText
     * @param $keyValue
     * @return string[]
     */
    public static function formSelect(Collection $collection, bool $null = false, string $keyText = "name", string $keyValue = "id")
    {
        $result = $collection->pluck($keyText, $keyValue)->toArray();

        if ($null) {
            $result = ["" => "Не задано"] + $result;
        }

        return $result;
    }

    /**
     * То же самое, но для Vue Select
     * @param Collection $collection
     * @param $null
     * @param $keyText
     * @param $keyValue
     * @return array
     */
    public static function vueSelect(Collection $collection, bool $null = false, string $keyText = "name", string $keyValue = "id")
    {
        $options = [];

        if ($null) {
            $options[] = [
                "id" => null,
                "label" => "Не задано"
            ];
        }

        // собираем массив по нужным ключам
        foreach ($collection as $value) {
            $options[] = [
                "id" => data_get($value, $keyValue),
                "label" => data_get($value, $keyText)
            ];
        }

        return $options;
    }

    /**
     * Ключ кеширования для модели с учётом времени её
     * обновления
     * @param string $postfix
     * @return string
     */
    public function cacheKey(string $postfix)
    {
        $modelKey = $this->getKey();

        if ($this->usesTimestamps()) {
            $modelKey .= "." . $this->getAttribute("updated_at");
        }

        return static::class . ".{$modelKey}.{$postfix}";
    }

    /**
     * Синхронизация отношения hasMany
     * @param string $name Имя отношения модели
     * @param array $data Массив данных
     * @param bool $delete Удалять ли записи, данные для которых не переданы
     * @param array $uniqueKeys Массив ключей, по которым ищется и уникально идентифицируется связанная запись
     * @param array $fillable Массив ключей, которые допустимы для массового заполнения
     * @return bool Был ли изменен состав отношения
     *
     */
    public function syncHasMany(
        string $name,
        array  $data,
        bool   $delete = true,
        array  $uniqueKeys = ["id"],
        array  $fillable = [])
    {

        // ID записей, которые были обработаны в процессе синхронизации
        $foundIds = [];

        // изменился ли состав записей
        $changed = false;

        // проходим по всем переданным строкам
        foreach ($data as $relationRowKey => $relationRow) {

            // проверяем наличие ключей, они могут быть и null, но должны быть переданы
            foreach ($uniqueKeys as $uniqueKey) {
                if (!array_key_exists($uniqueKey, $relationRow)) {
                    $exceptionMessage = "Не найден ключ {$uniqueKey} для syncHasMany, индекс строки: {$relationRowKey}";
                    throw new InvalidArgumentException($exceptionMessage);
                }
            }

            // получаем массив с ключами для поиска записи
            $searchKeys = Arr::only($relationRow, $uniqueKeys);

            // находим запись из отношения по этим ключам или создаём новую
            /** @var Model $model */
            $model = $this->$name()
                ->where($searchKeys)
                ->firstOrNew();

            // если передан отдельный массив fillable, применяем его к модели
            // уникальные ключи должны быть в нём, иначе сохранение не будет работать правильно
            // по умолчанию будут взяты fillable из класса модели
            if ($fillable) {
                $model->fillable($fillable);
            }

            // заполняем модель атрибутами текущей строки, недопустимые отбросятся
            $model->fill($relationRow);

            // если модель изменена (новая будет изменена в любом случае),
            // то состав записей изменился
            if ($model->isDirty()) {
                $changed = true;
            }

            // сохраняем модель в текущем отношении
            $this->$name()->save($model);

            // запоминаем ID найденной или созданной модели
            $foundIds[] = $model->getKey();
        }

        // если включено удаление непереданных отношений
        if ($delete) {

            // находим все записи отношения, где ID
            // не находится в списке обработанных, а это значит,
            // что его не передали и нужно его удалить

            /** @var \Illuminate\Database\Eloquent\Collection $modelsToDelete */
            $modelsToDelete = $this->$name()
                ->whereKeyNot($foundIds)
                ->get();

            // и удаляем их через Eloquent, чтобы события отработали
            /** @var Model $modelToDelete */
            foreach ($modelsToDelete as $modelToDelete) {

                $deleteResult = $modelToDelete->delete();

                // проверяем результат удаления, потому что в deleting-событии
                // удаление модели может быть отменено
                if ($deleteResult) {
                    // если модель успешно удалена, то
                    // в этом случае происходит изменение состава записей
                    $changed = true;
                }

            }
        }

        return $changed;
    }

}
